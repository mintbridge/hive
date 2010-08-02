<?php defined('SYSPATH') or die('No direct script access.');

class Hive_Field_Integer extends Hive_Field {

	public function value($value)
	{
		if ( ! $value AND $this->null)
		{
			return NULL;
		}

		return (int) $value;
	}

} // End Hive_Field_Integer
