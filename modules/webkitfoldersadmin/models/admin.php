<?php defined('SYSPATH') OR die('No direct access allowed.');

// ------------------------------------------------------------------------

/**
 * Admin Model Class
 *
 *	Stuff for dealing with admin style situations (save_item, get_item_tree etc)
 *
 * Model for dealing with a tree hierarchy for items
 * Deals with listing children of a node and providing info about the hierarchy
 * Also does the CRUD for items
 */
 
// ------------------------------------------------------------------------

class Admin_Model extends Generic_Model
{
    function __construct()
    {
        parent::__construct();
        
        $this->schema = Schema_Model::instance();
    }


    
    
    /**
	* 	returns the processed schema tree
	*
	* 	@access	public
	*	@params
	*
	* 	@return	boolean
	*/
    
    function get_schemas()
	{
		return $this->schema->get_schemas();
	}
	
	 /**
	* 	returns a list of keyword items that are additional icons used in the system
	*	this is so a list of icon css can be given back to the browser
	*
	* 	@access	public
	*	@params
	*
	* 	@return	array of icon names
	*/	
	
 	function get_icon_keywords()
    {
    	$this->db->
    		select('item_keyword.*')->
    		from('item_keyword')->
    		where("item_keyword.installation_id = ".installation::id()." and item_keyword.name = 'foldericon'")->
    		orderby('item_keyword.value');
    	
    	$keywords = $this->load_objects($this->db->get());
    	
    	$ret = array();
    	
    	foreach($keywords->asarray as $keyword)
    	{
    		$ret[] = $keyword->value;
    	}
    	
    	return $ret;
    }
    
   

   
}
?>