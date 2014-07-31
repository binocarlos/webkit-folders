<?php defined('SYSPATH') OR die('No direct access allowed.');

// ------------------------------------------------------------------------

/**
 * Simpleorm Class
 *
 * Basic ORM without the restrictions :)
 * 
 */
 
// ------------------------------------------------------------------------


class Simpleorm_Model
{
	protected $_load_id_field = 'id';
	protected $_table_name = '';
	protected $_fields = array();
	
	protected $_loaded = false;
	protected $_saved = false;
	protected $_created = false;
	
	// internal id so you can load stuff with queries not just numeric id
	protected $_id = NULL;
	
    function __construct($id = NULL)
    {
    	$this->initialize();
    	
    	if(!empty($id))
    	{
    		$this->_id = $id;
    		$this->load();
    	}
    }
    
    function db()
    {	
    	$db = statictools::database_instance();
    	
    	return $db;
    }
    
    protected function initialize()
    {
    	$this->_loaded = false;
    	$this->_saved = false;
    	
//    	$this->installation_id = installation::id();
    }
    
    // gets this item ready for a JSON dump by clearing everything apart from 
    // the data fields - you can provide a list of only the fields to include
    // or exclude also
    function cleanup($config = NULL)
    {
    	$remove_map = $this->get_map_from_array($config['remove']);
    	
    	foreach($remove_map as $key => $remove)
    	{
    		unset($this->$key);
    	}
    }
    
    // gives you a db object ready to load a single item
    protected function get_base_load_db($db = NULL)
    {
    	if(!isset($db))
    	{
    		$db = statictools::database_instance();
    	}
    	
    	$db->
    		where(array(
    			$this->_table_name.'.installation_id' => installation::id()
    		));
    	
    	return $db;
    }
    
    protected function get_load_db()
    {
    	if(!preg_match("/^\w+$/", $this->_id))
    	{
    		throw new Kohana_User_Exception("Missing ID", "Cannot load object without a id");    		
    	}
    	
    	$db = $this->get_base_load_db();
    	
    	$db->
    		from($this->_table_name)->
    		where(array(
    		$this->_id_field => $this->_id ))->
    		limit(1);
    	
    	return $db;
    }
    
    protected function load()
    {
    	$db = NULL;
    	
    	// if we have been passed a $db in the constructor - we will use that as the base!
    	
    	if(is_object($this->_id))
    	{
    		$db = $this->_id;
    	}
    	else
    	{
    		$db = $this->get_load_db();
    	}
    	
    	if(isset($db))
    	{
   			$query = $db->get();

   			$this->set_data($query->current());
   		}
    }
    
    function get($fieldname)
    {
    	return $this->$fieldname;
    }
    
    function get_data()
    {
    	$ret = array();
    	
    	foreach ($this->_fields as $field)
    	{
    		$value = $this->get($field);
    		
    		if(isset($value))
    		{
    			$ret[$field] = $value;
    		}
    	}
    	
//    	$ret['installation_id'] = installation::id();

    	return $ret;	
    }
    
    function set_data($new_data = NULL)
    {
    	if(isset($new_data))
    	{
    		foreach ($new_data as $field => $value)
    		{
    			$this->$field = $value;
    		}
    		
    		$this->_loaded = true;
    	}
    }
    
    function save_or_create()
    {
    	if($this->exists())
    	{
    		$this->save();
    	}
    	else
    	{
    		$this->create();
    	}
    }
    
    function create()
    {
    	if($this->exists())
    	{
    		throw new Kohana_User_Exception("Object cannot be created", "This object of type ".get_class($this)." cannot be created because it has an id: ".$this->id);
    		return;
    	}
    	
    	if(!isset($this->installation_id))
    	{
    		$this->installation_id = installation::id();
    	}

    	$db = $this->db();
    	
    	$query = $db->insert($this->_table_name, $this->get_data());
    	
    	statictools::devsql();
    	
    	$this->id = $query->insert_id();
    	
    	$this->_created = true;
    	$this->_saved = true;
    }
    
    function save($from_root)
    {
    	if(!$this->exists())
    	{
    		throw new Kohana_User_Exception("Object cannot save", "The object of type ".get_class($this)." cannot be saved because it has no id");
    		return;
    	}
    	
    	$clause = array( 'id' => $this->database_id() );
    	
    	if(!$from_root)
    	{
    		$clause['installation_id'] = installation::id();
    	}
    	
    	$db = $this->db();
    	$db->update($this->_table_name, $this->get_data(), $clause);
    	
    	statictools::devsql();
    	
    	$this->_saved = true;
    }
    
    function database_id()
    {
    	return $this->id;
    }
    
    function create_carbon_copy()
    {
    	unset($this->id);
    	unset($this->installation_id);
    	
    	$this->create();
    }
    
    // tells you if this object currently exists in the database
    function exists()
    {
    	if(empty($this->id))
    	{
    		return FALSE;
    	}
    	else
    	{
    		return TRUE;
    	}
    }
    
    // tells you if this item did not exist before the start of the request
    // (i.e. it has only just been created)
    function existed()
    {
    	if($this->_created)
    	{
    		return FALSE;
    	}
    	else
    	{
    		return $this->exists();
    	}
    }
    
    function throw_load_error()
    {
    	$error = "An object that belongs to installation: ".$this->installation_id." is being loaded by installation: ".installation::id();
    	
    	throw new Kohana_User_Exception("Object cannot be loaded", $error);
    	
    	return;
    }
    
	function get_field_map()
    {
		return $this->get_map_from_array($this->_fields);
    }
    
    function get_map_from_array($arr)
    {
    	if(!isset($arr)) { return NULL; }
    	
    	$map = array();
    	
    	foreach($arr as $field)
    	{
    		$map[$field] = TRUE;
    	}
    	
    	return $map;
    }
    
    function delete()
    {
    	$db = $this->db();
		
		$sql = "delete from {$this->_table_name} where id = {$this->_id}";
		
		$db->query($sql);	
    }
}
?>