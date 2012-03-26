<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Hive is an intelligent modeling system for Kohana.
 *
 * @package    Hive
 * @category   Relation
 * @author     Woody Gilk <woody@wingsc.com>
 * @copyright  (c) 2008-2010 Woody Gilk
 * @license    MIT
 */
class Hive_Relation_BelongsTo extends Hive_Relation {

	public function read(Hive $parent)
	{
		$child = Hive::factory($this->model, array(
			$this->parent => $parent,
		));

		if ($parent->prepared())
		{
			foreach ($this->using as $local => $remote)
			{
				$child->$remote = $parent->$local;
			}
		}

		if ($this->conditions)
		{
			foreach ($this->conditions as $remote => $value)
			{
				$child->$remote = $value;
			}
		}

		return $child;
	}

} // End Hive_Relation_BelongsTo
