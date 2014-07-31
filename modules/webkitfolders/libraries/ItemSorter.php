<?php defined('SYSPATH') OR die('No direct access allowed.');

// ------------------------------------------------------------------------

/**
 * Tools Library
 *
 * Useful functions used across the system
 *
 */
 
// ------------------------------------------------------------------------ 

class ItemSorter
{
	public function __construct($item_array)
	{
		$this->items = $item_array;
	}
	
	public function get_sorted_items($sort_on, $sort_direction = 1)
	{
		if(empty($sort_on)) { return $this->items; }
		
		if(strtolower($sort_direction)=='desc') { $sort_direction = -1; }
		if(strtolower($sort_direction)=='asc') { $sort_direction = 1; }
		
		if(!isset($sort_direction))
		{
			$sort_direction = 1;
		}
		
		$this->sort_on = $sort_on;
		$this->sort_direction = $sort_direction;

		$sort_array = array();
		
		foreach($this->items as $item)
		{
			$sort_array[] = $item;
		}
		
		usort($sort_array, array($this , "item_sorter" ));

		return $sort_array;
	}
	
	function item_sorter($a, $b)
	{
		$sort_on = $this->sort_on;
		$direction = $this->sort_direction;
		
		if($sort_on == 'random')
		{
			$random_check = rand(0, 100);
			
			if($random_check>50)
			{
				return 1;
			}
			else
			{
				return -1;
			}
		}
		else
		{
			$a_value = 0;
			$b_value = 0;
			
			$a_value = $this->item_sorter_extract_value($a, $sort_on);
			$b_value = $this->item_sorter_extract_value($b, $sort_on);
			
			if(empty($a_value) && empty($b_value))
			{
				return 0;	
			}
			else if(empty($a_value))
			{
				return 1;	
			}
			else if(empty($b_value))
			{
				return -1;
			}
		
			if( $a_value == $b_value ) return 0;
    		return ($a_value < $b_value ) ? -1 * $direction : 1 * $direction;
    	}
	}		
	
// checks two possible values used in ordering and chooses the
	// most appropriate one (i.e. lowest if order=asc and highest if order=desc)
	function item_sorter_compare_value($current_value, $new_value)
	{
		if(preg_match('/(\d+)\/(\d+)\/(\d+) (\d+:\d+:\d+)/', $new_value, $match))
		{
			$new_value = $match[3].'/'.$match[2].'/'.$match[1].' '.$match[4];	
		}
		else if(preg_match('/(\d+)\/(\d+)\/(\d+)/', $new_value, $match))
		{
			$new_value = $match[3].'/'.$match[2].'/'.$match[1];	
		}
				
		if(empty($current_value))
		{
			return $new_value;	
		}
		
		if(empty($new_value))
		{
			return $current_value;	
		}
		
		// this is desc so we want a higher value
		if($this->sort_direction==-1)
		{
			if($new_value > $current_value)
			{
				$current_value = $new_value;	
			}
		}
		// this is asc so we want a lower value
		else
		{
			if($new_value < $current_value)
			{
				$current_value = $new_value;	
			}
		}
		
		return $current_value;
	}
	
	function item_sorter_extract_value($item, $field)
	{
		$split_values = explode(' or ', $field);
		
		$current_value = null;
		
		foreach($split_values as $use_field)
		{
			if(preg_match('/^children\.(\w+)$/', $use_field, $match))
			{
				$use_field = $match[1];
				
				foreach($item->children as $child)
				{
					$current_value = $this->item_sorter_compare_value($current_value, $child->$use_field);	
				}
			}
			else
			{
				$current_value = $this->item_sorter_compare_value($current_value, $item->$use_field);
			}
		}
		
		return $current_value;
	}
}
?>