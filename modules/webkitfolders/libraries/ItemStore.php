<?php defined('SYSPATH') OR die('No direct access allowed.');

class ItemStore
{
	private $object_store = array();
	
	function __construct()
    {
    	
	}
	
	public function get($type, $field, $value)
    {
    	$ret = null;
    	
    	if(isset($this->object_store[$type]))
    	{
    		if(isset($this->object_store[$type][$field]))
    		{
    			if(isset($this->object_store[$type][$field][$value]))
    			{
    				$ret = $this->object_store[$type][$field][$value];
    			}
    		}
    	}
    	
    	if($ret === 'null')
    	{
    		$ret = null;
    	}
    	
    	return $ret;
    }
    
    public function set($type, $field, $value, $object)
    {
    	if(empty($object))
    	{
    		$object = 'null';
    	}
    	
    	$this->object_store[$type][$field][$value] = $object;
    	
    	return $object;
    }

}