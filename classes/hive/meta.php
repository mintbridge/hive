<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Hive is an intelligent modeling system for Kohana.
 *
 * @package    Hive
 * @category   Base
 * @author     Woody Gilk <woody@wingsc.com>
 * @copyright  (c) 2010 Woody Gilk
 * @license    MIT
 */
class Hive_Meta {

	/**
	 * @var  mixed  database instance or instance name
	 */
	public $db = NULL;

	/**
	 * @var  string  database table
	 */
	public $table = '';

	/**
	 * @var  array  model fields: name => field object, ...
	 */
	public $fields = array();

	/**
	 * @var  array  model aliases: name => function, ...
	 */
	public $aliases = array();

	/**
	 * @var  array  row sorting fields: name => direction, ...
	 */
	public $sorting = array();

	/**
	 * @var  array  model relations: name => relation object, ...
	 */
	public $relations = array();

	/**
	 * @var  array  filters by field: name => filter list, ...
	 */
	public $filters = array();

	/**
	 * @var  array  rules by field: name => filter list, ...
	 */
	public $rules = array();

	/**
	 * @var  array  callbacks by field: name => callback list, ...
	 */
	public $callbacks = array();

	/**
	 * @var  array  validation context: context => field list, ...
	 */
	public $validate = array();

	/**
	 * Finishes the initialization of meta.
	 *
	 *     $meta->finish();
	 *
	 * @return  $this
	 */
	public function finish()
	{
		foreach ($this->fields as $name => $field)
		{
			if ( ! $field->table)
			{
				$field->table = $this->table;
			}

			if ( ! $field->column)
			{
				$field->column = $name;
			}
		}

		return $this;
	}

	/**
	 * [Validate] callback, used to test if a field value is unique.
	 *
	 *     $data->callback($name, array($meta, 'is_unique'));
	 *
	 * @param   Validate  array
	 * @param   string    field name
	 * @return  void
	 */
	public function is_unique($array, $name)
	{
		// SELECT name FROM table WHERE name = $name
		$query = DB::select($this->alias($name))
			->from($this->table)
			->where($this->column($name), '=', $array[$name])
			->as_object(FALSE)
			;

		// Get the number of records found
		$result = (int) $query
			->execute($this->db)
			->count()
			;

		if ($result)
		{
			// Records found, this field is not unique!
			$array->error($name, 'not_unique');
		}
	}

	/**
	 * Get a single field object.
	 *
	 *     $id = $meta->field('id');
	 *
	 * @return  Hive_Field
	 */
	public function field($name)
	{
		return $this->fields[$name];
	}

	/**
	 * Get a single relation object.
	 *
	 *     $other = $meta->relation('thing');
	 *
	 * @return  Hive_Relation
	 */
	public function relation($name)
	{
		return $this->relations[$name];
	}

	/**
	 * Get the complete column name for a field.
	 *
	 *     $column = $meta->column('foo');
	 *
	 * @return  string
	 */
	public function column($name)
	{
		$field = $this->fields[$name];

		return "{$field->table}.{$field->column}";
	}

	/**
	 * Get the complete column alias for a field.
	 *
	 *     $alias = $meta->alias('foo');
	 *
	 * @return  mixed
	 */
	public function alias($name)
	{
		$field = $this->fields[$name];

		$alias = $this->column($name);

		if ($field->column !== $name)
		{
			// Create a "foo AS bar" alias
			$alias = array($alias, $name);
		}

		return $alias;
	}

} // End Hive_Meta
