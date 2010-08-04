<?php defined('SYSPATH') or die('No direct script access.');

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

} // End Hive_Field_Float
