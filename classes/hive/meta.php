<?php defined('SYSPATH') or die('No direct script access.');

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
	 * @var  array  model fields
	 */
	public $fields = array();

	/**
	 * @var  array  row sorting fields
	 */
	public $sorting = array();

	/**
	 * @var  array  model relations
	 */
	public $relations = array();

	/**
	 * @var  array  filters by field
	 */
	public $filters = array();

	/**
	 * @var  array  rules by field
	 */
	public $rules = array();

	/**
	 * @var  array  callbacks by field
	 */
	public $callbacks = array();

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

		if ($field->column)
		{
			return "{$this->table}.{$field->column}";
		}
		else
		{
			return "{$this->table}.{$name}";
		}
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

		if ($field->column)
		{
			return array("{$this->table}.{$field->column}", $name);
		}
		else
		{
			return "{$this->table}.{$name}";
		}
	}

	// public function finish()
	// {
	// 	foreach ($this->fields as $name => $field)
	// 	{
	// 		if ( ! $field->empty)
	// 		{
	// 			$this->rules[$name]['not_empty'] = NULL;
	// 		}
	//
	// 		if ($field instanceof Hive_Field_Email)
	// 		{
	// 			$this->rules[$name]['email'] = NULL;
	// 		}
	//
	// 		if ($field->unique)
	// 		{
	// 			$this->callbacks[$name]['email'] = TRUE;
	// 		}
	// 	}
	//
	// 	return $this;
	// }

} // End Hive_Meta
