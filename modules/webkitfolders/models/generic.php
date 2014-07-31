<?php defined('SYSPATH') OR die('No direct access allowed.');

// ------------------------------------------------------------------------

/**
 * Genericmodel Class
 *
 * Provides a hander wrapper for you to load plain objects or the full ORM classes for a given DB result set
 * 
 * the idea is that your model class constructs and executes a query e.g. 
 *
 *		load the keywords for item 4
 *		select * from keyword where item_id = 4
 *
 *	you then pass the result of $db->get into load objects
 *	it will process the results into a map ready for inspection after
 *	if you provide an ORM classname - it will create ORM objects and populate them
 *
 */
 
// ------------------------------------------------------------------------ 

class Generic_Model extends Model
{
    function __construct()
    {
		if ( ! is_object($this->db))
		{
			$this->db = statictools::database_instance();
		}
    }
    
   	protected function installation_id()
	{
		if(!isset($this->installation))
		{
			$this->installation = new Installation_Model();
		}
		
		return $this->installation->id;
	}
    
	// --------------------------------------------------------------------

	/**
	* 	Add 3 handy fields to the result set object
	*
	*	asarray = an array of the results
	*
	*	asmap = a map of results indexed by $index_property
	*
	*	asarraymap = a map of arrays of results indexed by index_property
	*	useful if you have items with duplicate ids
	*/
    function load_objects($resultset, $classname = NULL, $index_property = 'id')
    {
    	$this->last_index_property = $index_property;
    	
    	$resultset->asarray = array();
    	$resultset->asmap = array();
    	$resultset->asmaparray = array();
    	
    	foreach ($resultset->result_array(TRUE, $classname) as $row)
		{
			$index = $row->$index_property;
			$resultset->asmap[$index] = $row;
			$resultset->asarray[] = $row;
			
			$existing = $resultset->asarraymap[$index];
			
			if(!isset($existing))
			{
				$existing = array();
			}
			
			$existing[] = $row;
			
			$resultset->asmaparray[$index] = $existing;
		}
		
		return $resultset;
    }

	function get_single_object_result($results, $obj)
	{
		if(isset($obj))
		{
			$results->asarray = array($obj);
    		$results->astree = array($obj);
    		$results->asmap = array(
	    		$obj->id => $obj
    		);
    		$results->asmaparray = array(
	    		$obj->id => $obj
    		);
    	}
		else
		{
			$results->asarray = array();
    		$results->astree = array();
    		$results->asmap = array();
    		$results->asmaparray = array();
		}
    	return $results;
	}
	
    function load_object($resultset, $classname = NULL)
    {
    	$resultset = $this->load_objects($resultset, $classname);
    	
    	if($resultset->count()>0)
    	{
    		return $resultset->asarray[0];	
    	}
    	else
    	{
    		return NULL;
    	}
    }
    
    function db()
    {
    	return $this->db;	
    }
    
}
?>