<?php defined('SYSPATH') or die('No direct script access.');

class Hive_Field_Timestamp extends Hive_Field_Integer {

	/**
	 * @var  boolean  set the value to "now" on creation?
	 */
	public $auto_now_create = FALSE;

	/**
	 * @var  boolean  set the value to "now" on update?
	 */
	public $auto_now_update = FALSE;

	public $format = 'm/d/Y';

	public $null = TRUE;

	public function value($value)
	{
		if ($value AND is_string($value) AND ! ctype_digit($value))
		{
			$value = strtotime($value);
		}

		return parent::value($value);
	}

} // End Hive_Field_Timestamp
