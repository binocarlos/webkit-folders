<?php defined('SYSPATH') OR die('No direct access allowed.');

// ------------------------------------------------------------------------

/**
 * Installation Class
 *
 * Object representing an installation
 * 
 */
 
// ------------------------------------------------------------------------

class foldersystem
{
	public static $foldersystems = array();
	
	// this will build a new system based on the system_layouts config
	// you can integrate this config into existing systems also
	// i.e. it will create folders that don't exist but will leave ones that do
	// the comparison made on an existing system is to check the top level and the item_type
	// so - if an existing system dosn't have a user_database at the top level - 
	// and the layout does - one will be created - note this wont go any further than the top level
	//
	// the system name is what it will be refered to
	// the layout name is what config it will use to build itself
	
	public static function build($system_name = NULL, $layout_name = NULL)
	{	
		if(empty($system_name))
		{
			$system_name = 'default';
		}
		
		$tree_model = new Tree_Model();
		
		$existing = foldersystem::instance($system_name);
		
		$system_id = NULL;
		$system_item = NULL;
		
		if(isset($existing))
		{
			//throw new Kohana_User_Exception("System Already Exists!", "There is already a system with that name");
			
			// we already have this system so we want to add to it
			$system_id = $existing->id;
			
			$system_item = $tree_model->load_system_item($system_id);
		}
		else
		{
			// we need to create the system - it dosn't exist
		
			$db = statictools::database_instance();

    		$query = $db->insert('item_system', array(
	    		'installation_id' => installation::id(),
    			'name' => $system_name ));
    	
    		$system_id = $query->insert_id();
    		
    		$existing = foldersystem::instance($system_name);
    	}
    	
    	if(!isset($layout_name))
    	{
    		$layout_name = $system_name;
    	}
    	
    	$layout = foldersystem::get_system_layout($layout_name);
    	
    	$top_children_map = array();
    	
    	if(isset($system_item))
    	{
    		$top_children = $system_item->load_children_with_keywords();
    		
    		//$top_children = $tree_model->load_item_children($system_item);
    		
    		foreach($top_children as $child)
    		{
    			$top_children_map[$child->item_type] = TRUE;
    		}
    	}
    	else
    	{
    		// lets create the root node from the config
    		$system_item = Item_Model::create_fresh($system_name, $layout['type']);
    		
    		// the root level hook - every other item will get the system id from the parent
    		// this should be the only place outside of the tree_model the system_id gets set
    		$system_item->create();
    		
    		$tree_model->add_new_child_to_parent($system_item, $system_id);
    	}
    	
    	foreach($layout['items'] as $top_layout_node)
    	{
    		if(!isset($top_children_map[$top_layout_node['type']]))
    		{
				foldersystem::build_system_layout_node($top_layout_node, $system_item);
    		}
    	}
    	
    	return $existing;
	}
	
	public static function build_system_layout_node($layout_node, $parent_item)
	{
		$layout_item = Item_Model::create_fresh($layout_node['name'], $layout_node['type']);
		
		$layout_item->create();
		
		$layout_item->create_field('path', $layout_node['path']);
		
		$tree_model = new Tree_Model();
		
		$tree_model->add_new_child_to_parent($layout_item, $parent_item);
		
		foreach($layout_node['items'] as $child_layout_item)
    	{
    		foldersystem::build_system_layout_node($child_layout_item, $layout_item);
    	}
	}
	
	public static function get_system_layout($layout_name = 'default', $existing_system = NULL)
	{
		$system_layouts = Kohana::config('webkitfoldersinstall.system_layouts');
    	
    	$system_layout = $system_layouts[$layout_name];
    	
    	if(!isset($system_layout))
    	{
    		$system_layout = $system_layouts['default'];
    		
    		if(!isset($system_layout))
    		{
	    		throw new Kohana_User_Exception("No Default Layout Found", "Please check the webkitfoldersinstall file - it needs a system_layouts config");
	    	}
    	}
    	
    	return $system_layout;
	}
	
	public static function get_system_items($system_id = NULL)
	{
		$system_object = installation::ensure_system($system_id);
		
		$system_id = $system_object->id;
		
		return Itemfactory_Model::factory("item_link.parent_item_id IS NULL and item_link.system_id = $system_id");
	}
	
	public static function load_top_level_item($system_id = NULL, $item_type)
	{
		$results = foldersystem::load_top_level_items($system_id, $item_type);
		
		if($results->count()<=0)
		{
			return null;
		}
		else
		{
			return $results->asarray[0];	
		}
	}
	
	// loads you a item of the given type that lives at the top of one system
	public static function load_top_level_items($system_id = NULL, $item_type)
	{
		$system_object = installation::ensure_system($system_id);
		
		$system_id = $system_object->id;
		
		$factory = new Itemfactory_Model();
		
		return $factory->system_id($system_id)->item_types($item_type)->load();
	}
	
	public static function instance($system_id = NULL)
	{
		if(!isset($system_id))
		{
			$system_id = 'default';
		}

		if(!isset(foldersystem::$foldersystems[$system_id]))
		{
			$db = statictools::database_instance();
			
			$query_prop = 'id';
			
			if(!preg_match("/^\d+$/", $system_id))
			{
				$query_prop = 'name';
			}
			
			$model = new Generic_Model();
			
			$db->
				from('item_system')->
				where(array(
					$query_prop => $system_id,
					'installation_id' => installation::id() ));
					
								
			
			$system_object = $model->load_object($db->get());

			if(isset($system_object))
			{
				foldersystem::$foldersystems[$system_id] = $system_object;
			}
			else
			{
				return NULL;
			}
		}
		
		return foldersystem::$foldersystems[$system_id];
	}
	
	public static function id($system_id = NULL)
	{
		if(preg_match("/^\d+$/", $system_id))
		{
			return $system_id;
		}
		
		$system_instance = foldersystem::instance($system_id);
		
		return $system_instance->id;	
	}
}
?>