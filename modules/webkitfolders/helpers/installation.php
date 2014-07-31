<?php defined('SYSPATH') OR die('No direct access allowed.');

// ------------------------------------------------------------------------

/**
 * Installation Class
 *
 * Object representing an installation
 * 
 */
 
// ------------------------------------------------------------------------

class installation
{
	public static $installation_id = NULL;
	
	public static $installation = NULL;
	
	public static function instance($id = NULL, $force = false)
	{
		if(!isset($id))
		{			
			$id = installation::id();
		}
		
		if($force || !isset(installation::$installation))
		{
			$db = statictools::database_instance();
			
			$db->
				from('item_installation')->
				where(array(id => $id));
				
			$query = $db->get();

			installation::$installation = $query->current();
			installation::$installation_id = installation::$installation->id;
		}

		
		return installation::$installation;
	}
	
	public static function name()
	{
		installation::instance();
		
		return installation::$installation->name;	
	}
	
	public static function build_system($system_name = NULL)
	{
		$system = foldersystem::build($system_name);
		
		return $system;
	}
	
	public static function fix_installation()
	{
		$model = new Tree_Model();
		
		$model->remove_floating_links();
		
		$floating_items = installation::load_items_with_no_links();
		$system_item = installation::load_default_system_item();
		
		$model->move_children_to_parent($floating_items->asarray, $system_item);
		
		installation::remove_duplicate_system_links();
	}
	
	public static function load_links_with_no_items()
	{
		$installation_id = installation::id();
		
		$model = new Generic_Model();
		$db = statictools::database_instance();
		
		$sql = <<<EOT
select item_link.*
from item_link left join item on item_link.item_id = item.id		
where item.id IS NULL
and item_link.installation_id = $installation_id
EOT;

		return $model->load_objects($db->query($sql));
	}
	
	// gets rid of duplicate system items
	public static function remove_duplicate_system_links()
	{
		$schema = Schema_Model::instance();
		
		$system_types = $schema->get_schemas_that_have_property('access', 'system');
		
		$factory = new Itemfactory_Model();
		
		$results = $factory->item_types($system_types)->load();
		
		$found_items = array();
		
		foreach($results->asarray as $item)
		{
			if($found_items[$item->database_id()])
			{
				$item->detatch_from_tree();
			}
			
			$found_items[$item->database_id()] = $item;
		}
	}
	
	// loads items that have fallen out of the tree somehow
	public static function load_items_with_no_links()
	{
		$installation_id = installation::id();
		
		$model = new Generic_Model();
		$db = statictools::database_instance();
		
		$sql = <<<EOT
select item.*, item_link.id as link_id
from item left join item_link on item_link.item_id = item.id		
where item_link.id IS NULL
and item.installation_id = $installation_id
EOT;

		return $model->load_objects($db->query($sql), 'Item_Model');
	}
	
	public static function ensure_system($system_name = NULL)
	{
		$system = foldersystem::instance($system_name);
		
		if(!isset($system))
		{
			throw new Kohana_User_Exception("Missing System", "Cannot find the system for $system_name");    		
		}
		
		return $system;
	}
	
	public static function load_default_system_item($system_name = NULL)
	{
		$system = installation::load_default_system();
		
		$model = new Tree_Model();
		
		return $model->load_system_item($system->id);
	}
	
	public static function load_default_system($system_name = NULL)
	{
		return installation::ensure_system();	
	}
	
	public static function seperate_create($name)
	{
		$db = statictools::database_instance();
		
		$query = $db->insert('item_installation', array(
			name => $name ));
    	
    	$installation_id = $query->insert_id();
    	    	
    	$old_id = installation::id();
    	
    	installation::switch_installations($installation_id);
    	
    	installation::build_system();
    	
    	installation::switch_installations($old_id);
    	
    	return $installation_id;		
	}
	
	public static function create($name, $diskname = NULL)
	{
		$db = statictools::database_instance();
		
		$query = $db->insert('item_installation', array(
			name => $name ));
    	
    	$installation_id = $query->insert_id();
    	
    	$installation = installation::instance($installation_id);
    	
    	installation::build_system();
	}
	
	public static function is_pageloc()
	{
		$ret = Kohana::config('pageloc.is_pageloc');
    	
    	if(!empty($_SERVER['webkitfolders_is_pageloc']))
		{
			$ret = true;
		}
		
		return $ret;
	}
	
	public static function switch_installations($id)
	{
		installation::id($id);
		installation::instance($id, true);
	}
	
    public static function id($new_value)
    {
    	if(isset($new_value))
    	{
    		installation::$installation_id = $new_value;
    	}
    	
    	if(isset(installation::$installation_id))
    	{
			return installation::$installation_id;
    	}
    	
    	$config_id = Kohana::config('installation.installation_id');
    	
    	if(empty($config_id))
    	{
			$config_id = Kohana::config('webkitfolders.installation_id');
		}
    	
    	if(!empty($_SERVER['webkitfolders_installation_id']))
		{
			$config_id = $_SERVER['webkitfolders_installation_id'];
		}
		
    	if(!isset($config_id))
    	{
    		throw new Kohana_User_Exception("No Installation ID Given", "You have not configured an installation id!");
    	}
    	
    	if(!preg_match("/^\d+$/", $config_id))
    	{
    		throw new Kohana_User_Exception("Incorrect Installation ID Given", "You have configured an faulty installation id!");
    	}    	
    	
    	installation::$installation_id = $config_id;
    	
    	return installation::$installation_id;
    }
}
?>