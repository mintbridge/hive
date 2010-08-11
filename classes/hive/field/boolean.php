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
class Hive_Field_Boolean extends Hive_Field {

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

		return (boolean) $value;
	}

	public function verbose($value)
	{
		$value = $this->value($value);

		return $value ? 'Yes' : 'No';
	}

} // End Hive_Field_Boolean
