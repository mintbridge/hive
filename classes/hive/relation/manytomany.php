<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Hive is an intelligent modeling system for Kohana.
 *
 * @package    Hive
 * @category   Relation
 * @author     Woody Gilk <woody@wingsc.com>
 * @copyright  (c) 2008-2009 Woody Gilk
 * @license    MIT
 */
class Hive_Relation_ManyToMany extends Hive_Relation {

	const SINGULAR = FALSE;

	/**
	 * @var  string  table name
	 */
	public $table = '';

	public function as_array(Hive $parent)
	{
		$result = array();

		if ($parent->prepared())
		{
			$child = Hive::factory($this->model);

			list($id, $fk) = $this->field;

			$query = DB::select(array($fk, $id))
				->from($this->table)
				->as_object(FALSE)
				;

			foreach ($this->using as $local => $remote)
			{
				$query->where("{$this->table}.{$remote}", '=', $parent->$local);
			}

			$result = $query
				->execute(Hive::meta($parent)->db)
				->as_array($id, $id);

			$field = Hive::meta($child)->fields[$id];

			foreach ($result as $value)
			{
				// Type cast the values
				$result[$value] = $field->value($value);
			}
		}

		return $result;
	}

	public function read(Hive $parent)
	{
		$container = Hive_Container::factory($parent, $this);

		if ($parent->prepared())
		{
			// Create child model
			$child = Hive::factory($this->model);

			// Import meta data
			$parent_meta = Hive::meta($parent);
			$child_meta  = Hive::meta($child);

			// Create a new query
			$query = $child->query_select();

			// Apply JOINs
			$query->join($this->table);

			foreach ($this->using['child'] as $local => $remote)
			{
				$query->on($child_meta->column($local), '=', "{$this->table}.{$remote}");
			}

			foreach ($this->using['parent'] as $local => $remote)
			{
				$query->where("{$this->table}.{$remote}", '=', $parent->$local);
			}

			// Return the result as an array of child objects
			$results = $query
				->as_object($child)
				->execute($parent_meta->db)
				->as_array($this->key)
				;

			$container->values($results);
		}

		return $container;
	}

	public function value(array $value)
	{
		if ($value)
		{
			$value = array_combine($value, $value);

			ksort($value);
		}

		return $value;
	}

} // End Hive_Relation_ManyToMany
