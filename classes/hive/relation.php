<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Hive is an intelligent modeling system for Kohana.
 *
 * @package    Hive
 * @category   Relation
 * @author     Woody Gilk <woody@wingsc.com>
 * @copyright  (c) 2010 Woody Gilk
 * @license    MIT
 */
abstract class Hive_Relation {

	/**
	 * @var  string  relation model name
	 */
	public $model = '';

	/**
	 * @var  array  fields to join: local => remote, ...
	 */
	public $using = array();

	/**
	 * @var  string  parent model relation name
	 */
	public $parent;

	/**
	 * Set relation options.
	 *
	 * @param   array  relation options
	 * @return  void
	 */
	public function __construct(array $options = NULL)
	{
		if ($options)
		{
			foreach ($options as $key => $val)
			{
				$this->$key = $val;
			}
		}

		if ( ! $this->model)
		{
			throw new Hive_Exception('All relations must specify :option', array(
				':option' => 'model',
			));
		}

		if ( ! $this->using)
		{
			throw new Hive_Exception('All relations must specify :option', array(
				':option' => 'using',
			));
		}
	}

	/**
	 * Read the relations from a parent model.
	 *
	 *     $relations = $relation->read($model);
	 *
	 * @param   Hive  parent model
	 * @return  array
	 */
	abstract public function read(Hive $parent);

} // End Hive_Relation
