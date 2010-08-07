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
class Hive_Field_Auto extends Hive_Field_Integer {

	public $primary = TRUE;

	public $unique = TRUE;

	public $null = TRUE;

} // End Hive_Field_Auto
