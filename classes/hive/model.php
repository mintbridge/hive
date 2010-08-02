<?php defined('SYSPATH') or die('No direct script access.');

abstract class Hive_Model {

	/**
	 * @var  Hive_Meta  meta instance
	 */
	public static $meta;

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

	public static function init()
	{
		return new Hive_Meta;
	}

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
	 * @var  boolean  has the model been initialized?
	 */
	protected $__init = FALSE;

	/**
	 * Initializes model fields and loads meta data.
	 *
	 *     $model = new Model_Foo;
	 *
	 * @return  void
	 */
	public function __construct()
	{
		if ($this->__init === 0x3adb4)
		{
			// PHP *_fetch_object functions call __set before __construct.
			// To work around the problem, __construct is called twice.
			// The second time it is called, all "changed" data is loaded.
			$this->loaded(TRUE);

			// Initialization is now complete
			$this->__init = TRUE;
		}
		else
		{
			// Restore meta object
			$this->__wakeup();

			// Reset the object
			$this->reset();
		}
	}

	/**
	 * Magic method, called when the model is unserialized. Also called by
	 * __construct to load the meta object.
	 *
	 * @return  void
	 * @uses    Hive::init
	 */
	public function __wakeup()
	{
		if ( ! static::$meta)
		{
			// Meta has not yet been loaded for this model
			static::$meta = static::init();
		}

		// Initialize has been done
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
		if ( ! isset(static::$meta->fields[$name]))
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
				$this->load();
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

		if ( ! isset(static::$meta->fields[$name]))
		{
			throw new Hive_Exception('Field :name is not defined in :model', array(
				':name'  => $name,
				':model' => get_class($this),
			));
		}

		$field = static::$meta->fields[$name];

		$value = $field->value($value);

		if ($this->__data[$name] === $value)
		{
			// Value is the same as original, remove changes
			unset($this->__changed[$name]);
		}
		else
		{
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
		if ( ! isset(static::$meta->fields[$name]))
		{
			throw new Hive_Exception('Field :name is not defined in :model', array(
				':name'  => $name,
				':model' => get_class($this),
			));
		}

		// Remove changed value
		unset($this->__changed[$name]);

		$field = static::$meta->fields[$name];

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
		return isset(static::$meta->fields[$name]);
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
	 * Get and set the model's "prepared" state. If a model is prepared, it can
	 * be loaded.
	 *
	 *     // Set the prepared state
	 *     $model->prepared(TRUE);
	 *
	 *     // Get the prepared state
	 *     if ($model->prepared()) $model->load();
	 *
	 * @param   boolean   new state
	 * @return  boolean
	 */
	public function prepared($state = NULL)
	{
		if ($state !== NULL)
		{
			$this->__prepared = (bool) $state;
		}

		return $this->__prepared;
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
	 * @param   boolean   new state
	 * @return  boolean
	 */
	public function loaded($state = NULL)
	{
		if ($state !== NULL)
		{
			$this->__loaded = (bool) $state;

			if ($this->__loaded)
			{
				// Move changes into data
				$this->__data = array_merge($this->__data, $this->__changed);

				// Clear all changes
				$this->__changed = array();
			}
		}

		return $this->__loaded;
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
		$fields = array_keys(static::$meta->fields);

		foreach ($fields as $name)
		{
			// Reset the field to the default value
			unset($this->$name);
		}

		// Reset the model state
		$this->prepared(FALSE);
		$this->loaded(FALSE);

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
		$values = array_intersect_key((array) $values, static::$meta->fields);

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

	/**
	 * Get the current model data as an array. Changed values are combined with
	 * loaded values.
	 *
	 *     $data = $model->as_array();
	 *
	 * @return  array
	 */
	public function as_array()
	{
		$fields = array_keys(static::$meta->fields);

		$array = array();

		foreach ($fields as $name)
		{
			$array[$name] = $this->$name;
		}

		return $array;
	}

	/**
	 * Load model data from the database.
	 *
	 *     // Load model from database
	 *     $model->load();
	 *
	 *     // Load all records as models
	 *     $models = $model->load(NULL, FALSE);
	 *
	 * @param   object  SELECT query
	 * @param   mixed   number of records to fetch, FALSE for all
	 * @return  $this            when loading a single object
	 * @return  Database_Result  when loading multiple objects
	 * @uses    Hive::query_select
	 */
	public function load(Database_Query_Builder_Select $query = NULL, $limit = 1)
	{
		$query = $this->query_select($query);

		$meta = static::$meta;

		if ( ! $limit OR $limit > 1)
		{
			return $query
				->as_object(get_class($this))
				->execute($meta->db);
		}

		$result = $query
			->as_object(FALSE)
			->execute($meta->db)
			->current();

		if ($result)
		{
			$this
				->values($result)
				->loaded(TRUE);
		}
		else
		{
			$this->prepared(FALSE);
		}

		return $this;
	}

	/**
	 * Validate the current model data. Applies the field label, filters,
	 * rules, and callbacks. [Validate::check] must be manually called.
	 *
	 *     $array = $model->validate();
	 *
	 * [!!] If no fields are specified, all fields will be validated.
	 *
	 * @param   array   list of fields to validate
	 * @return  Validate
	 */
	public function validate(array $fields = NULL)
	{
		$meta = static::$meta;

		$data = Validate::factory($this->as_array());

		if ( ! $fields)
		{
			// Validate all fields
			$fields = array_keys($meta->fields);
		}

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
	 * Returns a SELECT query for the current model data. If no query is given,
	 * a new query will be created.
	 *
	 *     $query = $model->query_select();
	 *
	 * @param   object  SELECT query
	 * @return  Database_Query_Builder_Select
	 */
	public function query_select(Database_Query_Builder_Select $query = NULL)
	{
		if ( ! $query)
		{
			$query = DB::select();
		}

		$meta = static::$meta;

		foreach ($meta->fields as $name => $field)
		{
			$query->select($meta->alias($name));

			if (array_key_exists($name, $this->__changed))
			{
				$query->where($meta->column($name), '=', $this->__changed[$name]);
			}
			elseif ($field->unique AND $this->__data[$name])
			{
				$query->where($meta->column($name), '=', $this->__data[$name]);
			}
		}

		$query->from($meta->table);

		foreach ($meta->sorting as $name => $direction)
		{
			$query->order_by($meta->column($name), $direction);
		}

		return $query;
	}

} // End Hive_Model
