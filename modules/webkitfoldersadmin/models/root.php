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

class Root_Model extends Generic_Model
{
    function __construct()
    {
        parent::__construct();
        
        $this->schema = & Schema_Model::instance();
    }
    
    function load_installations()
    {
		$sql = <<< EOT
		
select
	item_installation.name as installation_name,
	item_installation.id as installation_id,
	item_system.name as system_name,
	item_system.id as system_id
	
	from item_installation
	left join item_system
	on item_system.installation_id = item_installation.id
	
	group by item_installation.id, item_system.id
	
	order by item_installation.name

EOT;
		
		$results = $this->load_objects($this->db->query($sql));
		
		return $results;
    }
    
    function load_all_fields_of_type()
    {
    	$field_type = func_get_args();
    	
    	$fieldsql = "field_type is not null";
    	
    	if(is_array($field_type))
    	{
    		$sqlarr = array();
    		
    		foreach($field_type as $type)
    		{
    			$sqlarr[] = "field_type = '$type'";
    		}
    		
    		$fieldsql = implode(' or ', $sqlarr);
    	}
    	
    	$sql=<<<EOT
select
	item_keyword.*
	
	from item_keyword
	
	where ($fieldsql);

EOT;
		
		$results = $this->load_objects($this->db->query($sql), 'Keyword_Model');  

		return $results;
    }

    
    function fix_files()
    {
		$results = $this->load_all_fields_of_type('video', 'image');
		
		$file_array = $results->asarray;
		
		foreach($file_array as $file)
		{
			if(empty($file->long_value)) { continue; }
			
			$data = json_decode($file->long_value);
			
			$full_path = statictools::get_full_file_path($data->folder, $data->file);
			
			$dimensions = Thumbnailcache::get_file_dimensions($full_path, $data->type);
			
			$data->width = $dimensions['width'];
			$data->height = $dimensions['height'];
			
			$file->long_value = json_encode($data);
			
			$file->save(true);
			
			echo "doing {$file->long_value}<p>";
		}
    }
    
    function create_installation($name)
    {
    	return installation::create($name);	
    }
    
    function fix_installation($installation_id)
    {
    	$installation = installation::instance($installation_id);
    	
    	installation::fix_installation();    	
    }
    
    function rebuild_installation($installation_id, $system_id)
    {
    	$installation = installation::instance($installation_id);
    	
    	installation::build_system($system_id);
    }
    
    function delete_installation($id)
    {
    	$installation = installation::instance($id);
		
		if(!isset($installation))
		{
			throw new Kohana_User_Exception("No installation found", "cannot delete installation - cannot find it!");
		}
		
		$this->db->delete('item_keyword', array('installation_id' => $id));
		$this->db->delete('item_link', array('installation_id' => $id));
		$this->db->delete('item', array('installation_id' => $id));
		$this->db->delete('item_system', array('installation_id' => $id));
		$this->db->delete('item_installation', array('id' => $id));
    }
    
    function delete_system($id)
    {
    	$system = foldersystem::instance($id);
		$installation = installation::instance($system->installation_id);
		
		if(!isset($system))
		{
			throw new Kohana_User_Exception("No installation found", "cannot delete installation - cannot find it!");
		}
		
		$remaining_system_count = $this->db->count_records('item_system', array('installation_id' => installation::id()));
		
		$this->db->delete('item_link', array('system_id' => $id));
		$this->db->delete('item_system', array('id' => $id));	
		
		if($remaining_system_count<=1)
		{
			$this->db->delete('item_keyword', array('installation_id' => installation::id()));
			$this->db->delete('item', array('installation_id' => installation::id()));
			$this->db->delete('installation', array('installation_id' => installation::id()));	
		}
    }
    
    function fix_top_folder()
    {
    	return;
    	
    	$disk = new Item_Model();
		
		$disk->item_type = 'folder';
		$disk->name = 'X:\\';
		$disk->create();
		
		$this->db->
			from('item')->
			where('installation_id = '.installation::id().' and item_id IS NULL');
		
		$items = $this->load_objects($this->db->get(), 'Item_Model');

		foreach($items->asarray as $item)
		{
			$item->item_id = $disk->id;
			
			$item->save();
		}
		
		
		echo 'done';
    }
    
    function create_urls($installation_id, $system_id)
    {
    	installation::id($installation_id);
    	
    	$tree = new Tree_Model();
    	
    	$factory = new Itemfactory_Model();
    	
    	// lets load the tree
    	$items = $factory->
    				system_id($system_id)->
    				tree()->
    				load();
    				
    	$installation_id = installation::id();
    				
    	$db = $this->db;

		$db->query("delete from item_keyword where installation_id = $installation_id and ( name = 'path' or name = 'url' )");
    	
    	// get a handle on the system item
    	$root_item = $items->astree[0];
    	
    	$this->create_item_urls($root_item);
    }
    
    function create_item_urls($item, $parent = null)
    {
   	 	$item->assign_url();
   	 	
   	 	$item->create_path($parent);
   	 	
   	 	foreach($item->children as $child)
   	 	{
   	 		$this->create_item_urls($child, $item);
   	 	}
	}
	
	function fix_old_tree($system_id)
    {
    	$system = foldersystem::instance($system_id);
    	$installation_id = $system->installation_id;
    	
    	$model = new Generic_Model();
		$db = statictools::database_instance();
		
		$sql = <<<EOT
select *
from item
where installation_id = $installation_id
EOT;

		$items = $model->load_objects($db->query($sql), 'Item_Model');
		
		$root_item = null;
		$disk_item = null;
		$top_disk_items = array();
		
		foreach($items->asarray as $item)
		{
			if($item->is_system())
			{
				$root_item = $item;	
			}
			elseif($item->is_system_object())
			{
				if($item->is_disk())
				{
					$disk_item = $item;
				}
				
				$root_item->children[] = $item;
			}
			else
			{
				if(!empty($item->item_id))
				{
					$parent_item = $items->asmap[$item->item_id];
					
					$parent_item->children[] = $item;
				}
				else
				{
					$top_disk_items[] = $item;	
				}
			}
		}
		
		$disk_item->children = $top_disk_items;
		
    	// lets remove the links for the tree
    	$this->db->delete('item_link', array(system_id => $system_id ));	
    		
    	statictools::devsql();
    	
		// now rebuild a fresh tree from the memory structure
    	$root_item->build_link_info($system_id);
    	$root_item->commit_link_info($system_id);
    }
    
    function fix_tree($system_id)
    {
    	$tree = new Tree_Model();
    	
    	$factory = new Itemfactory_Model();
    	
    	// lets load the tree
    	$items = $factory->
    				system_id($system_id)->
    				tree()->
    				load();
    	
    	// get a handle on the system item
    	$root_item = $items->astree[0];

    	// lets remove the links for the tree
    	$this->db->delete('item_link', array(system_id => $system_id ));	
    		
    	statictools::devsql();
    	
		// now rebuild a fresh tree from the memory structure
    	$root_item->build_link_info($system_id);
    	$root_item->commit_link_info($system_id);
    }
     
}
?>