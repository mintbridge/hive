<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Hive is an intelligent modeling system for Kohana.
 *
 * @package    Hive
 * @category   Field
 * @author     Woody Gilk <woody@wingsc.com>
 * @copyright  (c) 2010 Woody Gilk
 * @license    MIT
 */
class Hive_Field_Timestamp extends Hive_Field_Integer {

	/**
	 * @var  boolean  set the value to "now" on creation?
	 */
	public $auto_now_create = FALSE;

	/**
	 * @var  boolean  set the value to "now" on update?
	 */
	public $auto_now_update = FALSE;

	/**
	 * @var  string  date format for verbose timestamps
	 */
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

	public function verbose($value)
	{
		$value = $this->value($value);

		return date($this->format, $value);
	}

} // End Hive_Field_Timestamp
