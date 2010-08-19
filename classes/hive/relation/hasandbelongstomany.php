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
class Hive_Relation_HasAndBelongsToMany extends Hive_Relation {

	/**
	 * @var  string  table name
	 */
	public $table = '';

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

} // End Hive_Relation_HasAndBelongsToMany
