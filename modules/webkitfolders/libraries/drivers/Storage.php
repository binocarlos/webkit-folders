<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Storage API driver.
 *
 */
abstract class Storage_Driver
{
	// a reference to the item for this driver
	protected $item = null;
	
	public function __construct($item)
	{
		$this->item = $item;
	}
	
	// is called with a reference to the current item when the item is created
	abstract public function do_create();
	
	// is called with a reference to the current item when the item is saved
	abstract public function do_save();
}