<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Hive is an intelligent modeling system for Kohana.
 *
 * @package    Hive
 * @category   Field
 * @author     Woody Gilk <woody@wingsc.com>
 * @copyright  (c) 2008-2009 Woody Gilk
 * @license    MIT
 */
class Hive_Field_Float extends Hive_Field {

	/**
	 * @var  integer  number of decimals to show
	 */
	public $decimals = NULL;

	public function value($value)
	{
		if ( ! $value)
		{
			if ($this->null)
			{
				return NULL;
			}

			$value = $this->default;
		}

		return (float) $value;
	}

	public function verbose($value)
	{
		$value = $this->value($value);

		if ($this->decimals)
		{
			$value = Num::format($value, $this->decimals);
		}

		return (string) $value;
	}

} // End Hive_Field_Float
