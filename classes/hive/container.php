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
class Hive_Container implements ArrayAccess {

	/**
	 * @var  Hive  owner of this container
	 */
	public $owner;

	/**
	 * @var  Hive_Relation  relationship of the owner to this container
	 */
	public $relation;

	/**
	 * @var  array  contained models
	 */
	protected $__data = array();

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
	public function __construct(Hive $owner = NULL, Hive_Relation $relation)
	{
		if ($owner)
		{
			$this->owner = $owner;

			if ($relation)
			{
				$this->relation = $relation;
			}
		}
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
		return $this->__data;
	}

	public function changed()
	{
		$changed = array();

		foreach ($this->__data as $key => $model)
		{
			if ($model->changed())
			{
				// Model has been changed, return it
				$changed[$key] = $model;
			}
		}

		return $changed;
	}

	/**
	 * Access for isset()
	 *
	 *     isset($container['foo']);
	 *
	 * @param   mixed    model identifier
	 * @return  boolean
	 */
	public function offsetExists($key)
	{
		return isset($this->__data[$key]);
	}

	/**
	 * Access for getting
	 *
	 *     $foo = $container['foo'];
	 *
	 * [!!] Returns `NULL` if the specified offset does not yet exist.
	 *
	 * @param   string  model identifier
	 * @return  Hive
	 */
	public function offsetGet($key)
	{
		if (isset($this->__data[$key]))
		{
			return $this->__data[$key];
		}

		return NULL;
	}

	/**
	 * Access for setting
	 *
	 *     $container['foo'] = $foo;
	 *
	 * @param   string  identifier
	 * @param   Hive    model
	 * @return  void
	 */
	public function offsetSet($key, $value)
	{
		if ( ! isset($this->__data[$key]))
		{
			if ($this->owner AND $this->relation)
			{
				$value->{$this->relation->self} = $this->owner;
			}

			$this->__data[$key] = $value;
		}
		else
		{
			if ( ! is_array($value))
			{
				$value = $value->as_array();
			}

			$this->__data[$key]->values($value);
		}
	}

	/**
	 * Access for unset()
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

		unset($this->__data[$key]);
	}

} // End Hive_Container
