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
abstract class Hive_Field {

	/**
	 * @var  string  table that contains this field
	 */
	public $table = NULL;

	/**
	 * @var  boolean  is this field a primary key?
	 */
	public $primary = FALSE;

	/**
	 * @var  boolean  must this field always be unique?
	 */
	public $unique = FALSE;

	/**
	 * @var  boolean  convert empty values to NULL?
	 */
	public $null = FALSE;

	/**
	 * @var  mixed  default value
	 */
	public $default = NULL;

	/**
	 * @var  string  real column name, empty for same as field name
	 */
	public $column;

	/**
	 * Set field options.
	 *
	 * @param   array  field options
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
	}

	/**
	 * Convert an incoming value to the proper type.
	 *
	 *     $value = $field->value($value);
	 *
	 * @param   mixed  value
	 * @return  mixed
	 */
	abstract public function value($value);

	/**
	 * Convert a value to a human readable format.
	 *
	 *     $verbose = $field->value($value);
	 *
	 * @param   mixed  value
	 * @return  string
	 */
	public function verbose($value)
	{
		return (string) $value;
	}

} // End Hive_Field
