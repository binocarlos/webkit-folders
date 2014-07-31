<?php defined('SYSPATH') OR die('No direct access allowed.');

// ------------------------------------------------------------------------

/**
 * Itemtrigger Class
 *
 * Object representing a trigger that should happen when an item within a certain path is saved
 * 
 */
 
// ------------------------------------------------------------------------

class Itemtrigger_Model extends Simpleorm_Model
{
	protected $_table_name = 'item_trigger';
	protected $_fields = array('installation_id', 'item_path', 'action', 'arguments');
	
    function __construct($id = NULL)
    {
        parent::__construct($id);
    }
    
    function database_id()
    {
    	return $this->_id;
    }
    
    public static function load_triggers()
    {
    	$model = new Generic_Model();
    	
    	$db = $model->db();
    	
    	$db->
    		from('item_trigger')->
    		where(array('item_trigger.installation_id' => installation::id()))->
    		orderby('item_trigger.arguments');
    		
		$triggers = $model->load_objects($db->get(), 'Itemtrigger_Model');
		
		return $triggers;
    }
    
    public static function ensure_triggers_exist($arguments, $paths)
    {
    	if(empty($arguments) || empty($paths)) { return; }
    	if(!is_array($paths))
    	{
    		$paths = array($paths);
    	}
    	
    	foreach($paths as $path)
    	{
    		$existing_trigger = Itemtrigger_Model::load_trigger_for_name_and_path($arguments, $path);
    		
    		if(!$existing_trigger)
    		{
    			$trigger = new Itemtrigger_Model();
		
				$trigger->installation_id = installation::id();
				$trigger->action = 'cache';
		
				$trigger->arguments = $arguments;
				$trigger->item_path = $path;
		
				$trigger->create();
    		}
    	}	
    }
    
    public static function load_trigger_for_name_and_path($name, $path)
    {
    	if(empty($name) || empty($path)) { return null; }
    	
    	$installation_id = installation::id();
    	
    	$sql=<<<EOT
    
SELECT
	*
FROM
	item_trigger
WHERE
(
	item_trigger.item_path = '$path'
	and
	item_trigger.arguments = '$name'
)
and
	item_trigger.installation_id = $installation_id
    	
EOT;

		$model = new Generic_Model();
    	
    	$db = $model->db();
    	
    	$trigger_result = $model->load_objects($db->query($sql), 'Itemtrigger_Model');
    	
    	$triggers = $trigger_result->asarray;
    	
    	if(count($triggers)<=0)
    	{
    		return null;
    	}
    	
    	$trigger = $triggers[0];
    	
    	return $trigger;
    }
    
    public static function load_triggers_for_path($path)
    {
    	$path = preg_replace('/^\//', '', $path);
    	
    	$path_parts = explode('/', $path);
    	
    	$sql_clauses = array();
    	
    	while(count($path_parts)>0)
    	{
    		$sql_clauses[] = "item_trigger.item_path = '/".implode('/', $path_parts)."'";
    		
    		array_pop($path_parts);
    	}
    	
    	$model = new Generic_Model();
    	
    	$db = $model->db();
    	
    	$sql_clause = implode("\n or \n", $sql_clauses);
    	
    	$installation_id = installation::id();
    	
    	$sql=<<<EOT
    
SELECT
	*
FROM
	item_trigger
WHERE
(
	$sql_clause
)
and
	item_trigger.installation_id = $installation_id
    	
EOT;

    	$trigger_result = $model->load_objects($db->query($sql), 'Itemtrigger_Model');
    	
    	$triggers = $trigger_result->asarray;
    	
    	return $triggers;
    }
    
    public static function clear_cache_for_item_path($path)
    {
    	if(empty($path)) { return; }
    	
    	$triggers = Itemtrigger_Model::load_triggers_for_path($path);
    	
    	foreach($triggers as $trigger)
    	{
    		$cache_to_clear = $trigger->arguments;
    		
    		HTMLCache::remove_cache_folder($cache_to_clear);
    	}
    }
    
    
}
?>