<?php defined('SYSPATH') or die('No direct script access.');

abstract class Hive_Model {

	/**
	 * @var  array  meta instances: name => meta object, ...
	 */
	public static $meta = array();

	public static function factory($name, array $values = NULL)
	{
		$model = "Model_{$name}";

		$model = new $model;

		if ($values)
		{
			$model->values($values);
		}

		return $model;
	}

	/**
	 * Get [Hive_Meta] object for a model. Creates a new singleton if the meta
	 * object has not yet been created.
	 *
	 *     // All of the following will return the same object
	 *     $meta = Hive::meta('person');
	 *     $meta = Hive::meta(new Model_Person);
	 *     $meta = Model_Person::meta();
	 *
	 * [!!] When calling this method within a model, use `static::meta($this)`
	 * or `Hive::$meta[$this->__model]` for best performance.
	 *
	 * @param   mixed   model name or object
	 * @return  Hive_Meta
	 * @uses    Hive::init
	 */
	public static function meta($model = NULL)
	{
		if (is_object($model))
		{
			// Get the class name from the object
			$model = get_class($model);
		}
		elseif ( ! $model)
		{
			// Get the class using LSB
			$model = get_called_class();
		}
		else
		{
			// Convert the model name to a class name
			$model = strtolower("model_{$model}");
		}

		if ( ! isset(Hive::$meta[$model]))
		{
			// Meta has not yet been created, create it now
			// Using static::init() here will not work properly!
			Hive::$meta[$model] = $model::init();

			// Finished initializing the meta object
			Hive::$meta[$model]->finish();
		}

		return Hive::$meta[$model];
	}

	/**
	 * Model meta initialization. Creates and returns a [Hive_Meta] object.
	 *
	 * [!!] __All models must define an `init` method!__ The `init` method must
	 * set the table name and the fields.
	 *
	 *     public static function init()
	 *     {
	 *         $meta = parent::init();
	 *
	 *         $meta->table = 'people';
	 *
	 *         $meta->fields += array(
	 *             'id' => new Hive_Field_Auto,
	 *             'name' => new Hive_Field_String,
	 *             'age' => new Hive_Field_Integer,
	 *         );
	 *
	 *         return $meta;
	 *     }
	 *
	 * Use [Hive::meta] to get the meta instance for a model.
	 *
	 * @return  Hive_Meta
	 */
	public static function init()
	{
		return new Hive_Meta;
	}

	/**
	 * @var  string  model identifier
	 */
	protected $__model = '';

	/**
	 * @var  boolean  has the model been initialized?
	 */
	protected $__init = FALSE;

	/**
	 * @var  boolean  is the model ready to be loaded?
	 */
	protected $__prepared = FALSE;

	/**
	 * @var  boolean  is the model loaded?
	 */
	protected $__loaded = FALSE;

	/**
	 * @var  array  loaded data
	 */
	protected $__data = array();

	/**
	 * @var  array  changed data
	 */
	protected $__changed = array();

	/**
	 * Initializes model fields and loads meta data.
	 *
	 *     $model = new Model_Foo;
	 *
	 * @return  void
	 */
	public function __construct()
	{
		// Set the name of this model, removing the "Model_" prefix
		$this->__model = strtolower(substr(get_class($this), 6));

		if ($this->__init === 0x3adb4)
		{
			// PHP *_fetch_object functions call __set before __construct.
			// To work around the problem, __construct is called twice.
			// The second time it is called, all "changed" data is loaded.
			$this->loaded(TRUE);
		}
		else
		{
			// Reset the object
			$this->reset();
		}

		// Initialization is now complete
		$this->__init = TRUE;
	}

	/**
	 * Magic method, called when accessing model properties externally.
	 *
	 *     $value = $model->foo;
	 *
	 * [!!] If the field does not exist, an exception will be thrown.
	 *
	 * @param   string  field name
	 * @return  mixed
	 * @uses    Hive::loaded
	 * @uses    Hive::prepared
	 * @uses    Hive::load
	 * @throws  Hive_Exception
	 */
	public function __get($name)
	{
		// Import meta data
		$meta = static::meta($this);

		if (isset($meta->aliases[$name]))
		{
			// Import alias closure
			$alias = $meta->aliases[$name];

			// Call aliases, passing the model through
			return $alias($this);
		}

		if ( ! isset($meta->fields[$name]))
		{
			throw new Hive_Exception('Field :name is not defined in :model', array(
				':name'  => $name,
				':model' => get_class($this),
			));
		}

		if (array_key_exists($name, $this->__changed))
		{
			return $this->__changed[$name];
		}
		else
		{
			if ( ! $this->loaded() AND $this->prepared())
			{
				$this->read();
			}

			return $this->__data[$name];
		}
	}

	/**
	 * Magic method, called when setting model properties externally.
	 *
	 *     $model->foo = 'new value';
	 *
	 * [!!] If the field does not exist, an exception will be thrown.
	 *
	 * @param   string  field name
	 * @param   mixed   new value
	 * @return  void
	 * @uses    Hive::__construct
	 * @uses    Hive::prepared
	 * @throws  Hive_Exception
	 */
	public function __set($name, $value)
	{
		if ( ! $this->__init)
		{
			$this->__construct();

			// Hack for working with *_fetch_object
			// (Bunny egg! What does this say? Hint: 10/24)
			$this->__init = 0x3adb4;
		}

		// Import meta data
		$meta = static::meta($this);

		if ( ! isset($meta->fields[$name]))
		{
			throw new Hive_Exception('Field :name is not defined in :model', array(
				':name'  => $name,
				':model' => get_class($this),
			));
		}

		// Import field data
		$field = $meta->fields[$name];

		// Normalize the field value to the proper type
		$value = $field->value($value);

		if ($this->__data[$name] === $value)
		{
			// Value is the same as original, remove changes
			unset($this->__changed[$name]);
		}
		else
		{
			// Value has been changed
			$this->__changed[$name] = $value;

			if ($field->unique)
			{
				if ($value)
				{
					$this->prepared(TRUE);
				}
				else
				{
					$this->prepared(FALSE);
				}
			}
		}
	}

	/**
	 * Magic method, called when unsetting model properties externally.
	 *
	 *     unset($model->foo);
	 *
	 * [!!] If the field does not exist, an exception will be thrown.
	 *
	 * @param   string  field name
	 * @return  void
	 * @throws  Hive_Exception
	 */
	public function __unset($name)
	{
		// Import meta data
		$meta = static::meta($this);

		if ( ! isset($meta->fields[$name]))
		{
			throw new Hive_Exception('Field :name is not defined in :model', array(
				':name'  => $name,
				':model' => get_class($this),
			));
		}

		// Remove changed value
		unset($this->__changed[$name]);

		// Import field data
		$field = $meta->fields[$name];

		// Reset the field value to the default value
		$this->__data[$name] = $field->value($field->default);
	}

	/**
	 * Magic method, called when checking if an model property exists externally.
	 *
	 *     isset($model->foo);
	 *
	 * @param   string   field name
	 * @return  boolean
	 */
	public function __isset($name)
	{
		// Import meta data
		$meta = static::meta($this);

		return isset($meta->fields[$name])
			OR isset($meta->aliases[$name]);
	}

	/**
	 * Magic method, called when displaying the model as a string. By default,
	 * this method will return a JSON representation of model data.
	 *
	 *     echo $model;
	 *
	 * @return  string
	 */
	public function __toString()
	{
		return json_encode($this->as_array());
	}

	/**
	 * Get the current model data as an array. Changed values are combined with
	 * loaded values.
	 *
	 *     $array = $model->as_array();
	 *
	 * @return  array
	 */
	public function as_array()
	{
		// Import meta data
		$meta = static::meta($this);

		// Get a list of model fields
		$fields = array_keys($meta->fields);

		$array = array();

		foreach ($fields as $name)
		{
			// Add every field value to the array
			$array[$name] = $this->$name;
		}

		return $array;
	}

	/**
	 * Get the current model data as a JSON string.
	 *
	 *     $json = $model->as_json();
	 *
	 * @return  string
	 * @uses    Hive::as_array
	 */
	public function as_json()
	{
		return json_encode($this->as_array());
	}

	/**
	 * Get and set the model's "prepared" state. If a model is prepared, it can
	 * be loaded.
	 *
	 *     // Set the prepared state
	 *     $model->prepared(TRUE);
	 *
	 *     // Get the prepared state
	 *     if ($model->prepared()) $model->read();
	 *
	 * @param   boolean   new state
	 * @return  boolean  when getting
	 * @return  $this    when setting
	 */
	public function prepared($state = NULL)
	{
		if ($state === NULL)
		{
			return $this->__prepared;
		}

		// Change state
		$this->__prepared = (bool) $state;

		return $this;
	}

	/**
	 * Get and set the model's "loaded" state. If a model is loaded, it has
	 * loaded data, probably from a database.
	 *
	 *     // Force the model to be unloaded
	 *     $model->loaded(FALSE);
	 *
	 * [!!] Changing the loaded state to `TRUE` will cause all changed data
	 * to be merged into the currently loaded data.
	 *
	 * @param   boolean  new state
	 * @return  boolean  when getting
	 * @return  $this    when setting
	 */
	public function loaded($state = NULL)
	{
		if ($state === NULL)
		{
			return $this->__loaded;
		}

		// Change state
		$this->__loaded = (bool) $state;

		if ($this->__loaded)
		{
			// Move changes into data
			$this->__data = array_merge($this->__data, $this->__changed);

			// Clear all changes
			$this->__changed = array();
		}

		return $this;
	}

	/**
	 * Get the currently changed data.
	 *
	 *     // Get changed data
	 *     $changes = $model->changed();
	 *
	 *     // Save changed data
	 *     if ($model->changed()) $model->save();
	 *
	 * @return  array
	 */
	public function changed()
	{
		return $this->__changed;
	}

	/**
	 * Reset the model to a completely unloaded state. Clears all loaded and
	 * changed data and resets the "prepared" and "loaded" states.
	 *
	 *     $model->reset();
	 *
	 * @return  $this
	 */
	public function reset()
	{
		// Import meta data
		$meta = static::meta($this);

		$fields = array_keys($meta->fields);

		foreach ($fields as $name)
		{
			// Reset the field to the default value
			unset($this->$name);
		}

		// Reset the model state
		$this
			->prepared(FALSE)
			->loaded(FALSE);

		return $this;
	}

	/**
	 * Set multiple values at once. Only values with fields will be used.
	 *
	 *     $model->values($_POST);
	 *
	 * @param   array    values to change
	 * @param   boolean  are the values clean? (typically not)
	 * @return  $this
	 */
	public function values($values, $clean = FALSE)
	{
		// Import meta data
		$meta = static::meta($this);

		$values = array_intersect_key((array) $values, $meta->fields);

		if ($clean)
		{
			foreach ($values as $name => $value)
			{
				$this->__changed[$name] = $value;
			}
		}
		else
		{
			foreach ($values as $name => $value)
			{
				$this->$name = $value;
			}
		}

		return $this;
	}

	public function create(Database_Query_Builder_Insert $query = NULL)
	{
	}

	/**
	 * Read model data from the database.
	 *
	 *     // Read model from database
	 *     $model->read();
	 *
	 *     // Read all records as models
	 *     $models = $model->read(NULL, FALSE);
	 *
	 * @param   object  SELECT query
	 * @param   mixed   number of records to fetch, FALSE for all
	 * @return  $this            when loading a single object
	 * @return  Database_Result  when loading multiple objects
	 * @uses    Hive::query_select
	 */
	public function read(Database_Query_Builder_Select $query = NULL, $limit = 1)
	{
		// Apply modeling to the query
		$query = $this->query_select($query, $limit);

		// Import meta data
		$meta = static::meta($this);

		if ( ! $limit OR $limit > 1)
		{
			// Return an iterator of results using this class
			return $query->execute($meta->db);
		}

		// Load a single row as an object
		$result = $query
			->as_object(FALSE)
			->execute($meta->db)
			->current();

		if ($result)
		{
			// A result has been found, load the values
			$this
				->values($result)
				->loaded(TRUE);
		}
		else
		{
			// No result was found, this object is not properly prepared
			$this->prepared(FALSE);
		}

		return $this;
	}

	public function update(Database_Query_Builder_Update $query = NULL, $limit = 1)
	{
		// Apply modeling to the query
		$query = $this->query_update($query, $limit);

		// Import meta data
		$meta = static::meta($this);

		// Execute the query and get the number of rows updated
		$count = $query->execute($meta->db);

		if ( ! $limit OR $limit > 1)
		{
			// Return the number of rows updated
			return $count;
		}

		// Changes are now in sync with the database, effectively a clean load
		$this->loaded(TRUE);

		return $this;
	}

	public function delete(Database_Query_Builder_Delete $query = NULL, $limit = 1)
	{

	}

	/**
	 * Validate the current model data. Applies the field label, filters,
	 * rules, and callbacks to the data.
	 *
	 * A specific list of fields can be specifically validated. If no fields
	 * are specified, all fields will be validated.
	 *
	 * A different data set can be validated instead of the model data.
	 *
	 *     $array = $model->validate();
	 *
	 * [!!] If no fields are specified, all fields will be validated.
	 *
	 * @param   array  list of fields to validate
	 * @param   array  external data to validate
	 * @return  Validate
	 */
	public function validate(array $fields = NULL, array $data = NULL)
	{
		if ( ! $fields)
		{
			// Validate all fields
			$fields = array_keys($meta->fields);
		}

		if ($data === NULL)
		{
			// Validate the model data
			$data = $this->as_array();
		}

		// Convert the data into a validation object
		$data = Validate::factory($data);

		// Import meta object
		$meta = static::meta($this);

		foreach ($fields as $field)
		{
			if (isset($meta->labels[$field]))
			{
				// Apply the label for this field
				$data->label($field, $meta->labels[$field]);
			}

			if (isset($meta->filters[$field]))
			{
				// Apply the filters for this field
				$data->filters($field, $meta->filters[$field]);
			}

			if (isset($meta->rules[$field]))
			{
				// Apply the rules for this field
				$data->rules($field, $meta->rules[$field]);
			}

			if (isset($meta->callbacks[$field]))
			{
				// Apply the callbacks for this field
				$data->callbacks($field, $meta->callbacks[$field]);
			}
		}

		return $data;
	}

	/**
	 * Get a simple key => value array.
	 *
	 *     $people = $model->select_list('name' => 'age');
	 *
	 * @param   string  field name for key
	 * @param   string  field name for value
	 * @param   object  SELECT query
	 * @return  array
	 */
	public function select_list($key, $value, Database_Query_Builder_Select $query = NULL)
	{
		// Import meta data
		$meta = static::meta($this);

		if ( ! $query)
		{
			// Create a new SELECT DISTINCT query
			$query = DB::select()
				->distinct(TRUE);
		}

		// Load only the key and value
		$query
			->select(
				$meta->alias($key),
				$meta->alias($value)
			)
			->from($meta->table);

		// Apply query conditions
		$this->query_conditions($query);

		foreach ($meta->sorting as $name => $direction)
		{
			// Apply sorting
			$query->order_by($meta->column($name), $direction);
		}

		// Load the result
		$result = $this
			->query_select($query)
			->distinct(TRUE)
			->as_object(get_class($this))
			->execute($meta->db);

		$list = array();

		foreach ($result as $row)
		{
			// Create an associative array of the keys and values
			$list[$row->$key] = $row->$value;
		}

		return $list;
	}

	/**
	 * Returns a SELECT query for the current model data. If no query is given,
	 * a new query will be created.
	 *
	 *     $query = $model->query_select();
	 *
	 * @param   object  SELECT query
	 * @param   mixed   number of records to fetch, FALSE for all
	 * @return  Database_Query_Builder_Select
	 * @uses    Hive::query_conditions
	 */
	public function query_select(Database_Query_Builder_Select $query = NULL, $limit = NULL)
	{
		if ( ! $query)
		{
			$query = DB::select();
		}

		// Import meta data
		$meta = static::meta($this);

		foreach ($meta->fields as $name => $field)
		{
			$query->select($meta->alias($name));
		}

		$query->from($meta->table);

		// Apply query conditions
		$this->query_conditions($query);

		foreach ($meta->sorting as $name => $direction)
		{
			$query->order_by($meta->column($name), $direction);
		}

		if ($limit)
		{
			// Limit the number of results
			$query->limit($limit);
		}

		return $query;
	}

	/**
	 * Returns a UPDATE query for the current model data. If no query is given,
	 * a new query will be created.
	 *
	 *     $query = $model->query_update();
	 *
	 * @param   object  UPDATE query
	 * @param   mixed   number of records to update, FALSE for all
	 * @return  Database_Query_Builder_Update
	 */
	public function query_update(Database_Query_Builder_Update $query = NULL, $limit = NULL)
	{
		if ( ! $query)
		{
			$query = DB::update();
		}

		// Import meta data
		$meta = static::meta($this);

		// Set the table to update
		$query->table($meta->table);

		foreach ($meta->fields as $name => $field)
		{
			if (array_key_exists($name, $this->__changed))
			{
				// Field has been changed, set a new value
				$query->value($meta->column($name), $this->__changed[$name]);
			}

			if ($field->primary AND $this->__data[$name])
			{
				// Field is unique, limit what gets updated
				$query->where($meta->column($name), '=', $this->__data[$name]);
			}
		}

		if ($limit)
		{
			// Limit the number of results
			$query->limit($limit);
		}

		return $query;
	}

	/**
	 * Applies WHERE conditions to a query.
	 *
	 *     // Create a new SELECT query
	 *     $query = DB::select();
	 *
	 *     // Apply model conditions
	 *     $model->query_conditions($query);
	 *
	 *
	 * @param   object  query builder
	 * @return  object
	 */
	public function query_conditions(Database_Query_Builder $query)
	{
		// Import meta data
		$meta = static::meta($this);

		foreach ($meta->fields as $name => $field)
		{
			if (array_key_exists($name, $this->__changed))
			{
				$query->where($meta->column($name), '=', $this->__changed[$name]);
			}
			elseif ($field->unique AND $this->__data[$name])
			{
				$query->where($meta->column($name), '=', $this->__data[$name]);
			}
		}

		return $query;
	}

} // End Hive_Model
