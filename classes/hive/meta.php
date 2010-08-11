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
