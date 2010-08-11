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
class Hive_Relation_HasMany extends Hive_Relation {

	public function read(Hive $parent)
	{
		if ( ! $parent->prepared() AND ! $parent->loaded())
		{
			// Nothing can be loaded when no unique model exists
			return array();
		}

		// Create child model
		$child = Hive::factory($this->model);

		// Import meta data
		$parent_meta = Hive::meta($parent);
		$child_meta  = Hive::meta($child);

		// Create a new query
		$query = $child->query_select();

		// Apply JOINs
		$query->join($parent_meta->table);

		foreach ($this->fields as $parent_field => $child_field)
		{
			$query->on(
				$parent_meta->column($parent_field),
				'=',
				$child_meta->column($child_field)
			);
		}

		// Apply parent WHERE conditions
		$parent->query_conditions($query);

		// Return the result as an array of child objects
		$result = $query
			->as_object($child)
			->execute($parent_meta->db)
			->as_array($this->key)
			;

		return $result;
	}

} // End Hive_Relation_HasMany
