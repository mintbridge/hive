<?php defined('SYSPATH') or die('No direct script access.');

abstract class Hive_Field {

	// /**
	//  * @var  boolean  can this field be an empty value?
	//  */
	// public $empty = FALSE;

	/**
	 * @var  boolean  convert empty values to NULL?
	 */
	public $null = FALSE;

	/**
	 * @var  boolean  must this field always be unique?
	 */
	public $unique = FALSE;

	/**
	 * @var  mixed  default value
	 */
	public $default = NULL;

	/**
	 * @var  string  real column name, empty for same as field name
	 */
	public $column;

	public function __construct(array $options = NULL)
	{
		if ($options)
		{
			foreach ($options as $key => $val)
			{
				$this->$key = $val;
			}
		}
	}

} // End Hive_Field
