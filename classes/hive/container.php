<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Hive is an intelligent modeling system for Kohana.
 *
 * @package    Hive
 * @category   Exceptions
 * @author     Woody Gilk <woody@wingsc.com>
 * @copyright  (c) 2010 Woody Gilk
 * @license    MIT
 */
class Hive_Container extends ArrayObject {

	/**
	 * Create a new container.
	 *
	 *     $container = Hive_Container::factory($owner, $relation);
	 *
	 * @param   Hive           owner of this container
	 * @param   Hive_Relation  relation that generated this container
	 * @return  Hive_Container
	 */
	public static function factory(Hive $owner = NULL, Hive_Relation $relation = NULL)
	{
		$container = new Hive_Container;

		if ($owner)
		{
			// Store the owner that created this container
			$container->owner($owner);
		}

		if ($relation)
		{
			// Store the relation that created this container
			$container->relation($relation);
		}

		return $container;
	}

	/**
	 * @var  Hive  owner of this container
	 */
	protected $__owner;

	/**
	 * @var  Hive_Relation  relationship of the owner to this container
	 */
	protected $__relation;

	/**
	 * @var  array   removed models
	 */
	protected $__removed = array();

	/**
	 * Preloads the container.
	 *
	 *     $containter = new Hive_Container;
	 *
	 * @param   array  associtive array of models
	 * @return  void
	 */
	public function __construct(array $data = array())
	{
		parent::__construct($data, ArrayObject::STD_PROP_LIST);
	}

	public function owner(Hive $owner = NULL)
	{
		if ( ! $owner)
		{
			return $this->__owner;
		}

		$this->__owner = $owner;

		return $this;
	}

	public function relation(Hive_Relation $relation = NULL)
	{
		if ( ! $relation)
		{
			return $this->__relation;
		}

		$this->__relation = $relation;

		return $this;
	}

	public function values(array $values = NULL)
	{
		foreach ($values as $key => $value)
		{
			$this[$key] = $value;
		}

		return $this;
	}

	/**
	 * Return the container contents as an associative array.
	 *
	 *     $array = $container->as_array();
	 *
	 * @return  array
	 */
	public function as_array()
	{
		return $this->getArrayCopy();
	}

	public function changed()
	{
		$changed = array();

		foreach ($this as $key => $model)
		{
			if ($model->changed())
			{
				// Model has been changed, return it
				$changed[$key] = $model;
			}
		}

		return $changed;
	}

	public function offsetSet($key, $model)
	{
		if (isset($this->__relation->parent) AND $this->__owner)
		{
			// Set the parent of the model this the owner of the collection
			$model->{$this->__relation->parent} = $this->__owner;
		}

		return parent::offsetSet($key, $model);
	}

	/**
	 * Store the removed element, so that it can be managed later.
	 *
	 *     unset($container['foo']);
	 *
	 * @param   string  identifier
	 * @return  void
	 */
	public function offsetUnset($key)
	{
		if (isset($this[$key]))
		{
			$this->__removed[$key] = $this[$key];
		}

		return parent::offsetUnset($key);
	}


} // End Hive_Container
