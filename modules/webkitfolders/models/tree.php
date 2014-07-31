<?php defined('SYSPATH') OR die('No direct access allowed.');

// ------------------------------------------------------------------------

/**
 * Tree Model Class
 *
 *	Stuff for dealing with the tree hierarcy
 
 
 Any id coming into the tree model for an item needs to be:
 
 	item_id.link_id (e.g. 343.456 or 3.45)
 	
 External to the tree model - an item MUST be referenced in this way
 The tree model sorts out the mapping internally to the database
 
 *
 */
 
 
 
// ------------------------------------------------------------------------

class Tree_Model extends Generic_Model
{
    function __construct()
    {
        parent::__construct();
        
        $this->schema = Schema_Model::instance();
    }

    
	public function copy_children_to_parent($items, $parent_item)
    {
    	foreach($items as $item)
    	{
    		$this->copy_child_to_parent($item, $parent_item);
    	}
    }
    
    // removes the current set of links for item and then creates news one for parent item
    public function copy_child_to_parent($item, $parent_item)
    {
    	if(!isset($item) || !isset($parent_item))
    	{
    		throw new Kohana_User_Exception("Blank items given", "You must provide a source and destination to move an item");
    	}
    	
    	$item->copy_to_parent_item($parent_item);
    }
    
    public function ghost_children_to_parent($items, $parent_item)
    {
    	foreach($items as $item)
    	{
    		$this->ghost_child_to_parent($item, $parent_item);
    	}
    }
    
    // removes the current set of links for item and then creates news one for parent item
    public function ghost_child_to_parent($item, $parent_item)
    {
    	if(!isset($item) || !isset($parent_item))
    	{
    		throw new Kohana_User_Exception("Blank items given", "You must provide a source and destination to move an item");
    	}
    	
    	$item->ghost_to_parent_item($parent_item);
    }
    
    public function move_children_to_parent($items, $parent_item)
    {
    	foreach($items as $item)
    	{
    		$this->move_child_to_parent($item, $parent_item);
    	}
    }
    
    // removes the current set of links for item and then creates news one for parent item
    public function move_child_to_parent($item, $parent_item)
    {
    	if(!isset($item) || !isset($parent_item))
    	{
    		throw new Kohana_User_Exception("Blank items given", "You must provide a source and destination to move an item");
    	}
    	
    	$item->move_to_parent_item($parent_item);
    }
    
    // creates a new set of links for the item in the context of parent item
    public function add_child_to_parent($item, $parent_item)
    {
    	$parent_item->add_child_item($item);
    }
    
    // creates a new link in the parent for the child item
    // dosn't matter if the child item already has a link - this means we are copying the item
    
    public function add_new_child_to_parent($item, $parent_item)
    {    
    	if($item->existed())
    	{
    		throw new Kohana_User_Exception("Only for new items", "You cannot call this method with an existing item");
    	}
    		
    	// if the parent_item is an id then this is the top level system creation
    	// and the parent_item is the system id
    	if(!isset($parent_item))
    	{
			throw new Kohana_User_Exception("Cannot add to blank parent", "You have passed a blank parent (or system id)");
    	}
    	else if(!is_object($parent_item))
    	{
    		$system_id = $parent_item;
    		
    		$system = installation::ensure_system($system_id);
    		
    		$existing_system_items = foldersystem::get_system_items($system_id);
    		
    		if($existing_system_items->count()>0)
    		{
				throw new Kohana_User_Exception("System Exists", "You are trying to make a system item for an existing system");    			
    		}

    		$item->system_id = $system_id;
    		$item->create_root_link();
		}
		else
		{
			// lets add this item to its parent
			$parent_item->add_child_item($item);
		}
    }
    
    
    // removes items from a tree
    //
    // the delete sequence goes as follows:
    //
    //		1. check to see if item has copies or just ghosts (or none)
    //
    //		2. if there are multiple copies - ask if they want to delete just the copy or all copies
    //
    //		3. otherwise there is only one copy with optional ghosts so proceed to step 5
    //
    //		4. if they want to delete just one copy, leaving the rest alone - then just remove the
    //		link tree for the copy and done (there wont be any ghosts because this is a true copy)
    //
    //		5. remove all remaining copies of the item leaving one - move this one to the bin
    //
    //		the rule is anything in the bin cannot have a copy outside of the bin
    //		(although it dosn't really matter cos its only folders and links)
    //
    //		6. Emptying the bin therefore becomes a case of deleting the last remaining copy
    //		and then removing the actual items
    
    public function delete_items_from_parent($items, $parent_item, $all = NULL)
    {
    	$bin = null;
    	
    	foreach($items->asarray as $item)
    	{
    		$bin = $this->delete_item_from_parent($item, $parent_item, $all);
    	}
    	
    	return $bin;
    }
    
    
    public function delete_item_from_parent($item, $parent_item)
	{
    	// are we deleting from a bin?
    	// if so - then is a terminal delete
    	// we should remove the links and delete the items
    	if($parent_item->is_of_type('bin'))
    	{
    		$item->destroy();
    		
    		return null;
    	}
    	// they are deleting from elsewhere
    	// so we need to move the contents into the bin
    	// or remove the single copy leaving the rest behind
    	else
    	{
    		$bins = foldersystem::load_top_level_items($parent_item->system_id, 'bin');
    		
    		// if we don't have a bin then just get rid of it
    		if($bins->count()<=0)
    		{
    			$item->destroy();
    			
    			return null;
    		}
    		else
    		{
    			// lets choose which bin we want to delete our things to
    			// by default the first 'bin'
    			$use_bin = null;
    			
    			foreach($bins->asarray as $bin)
    			{
    				if(!isset($use_bin))
    				{
						if($bin->item_type == 'bin')
						{
							$use_bin = $bin;
						}
					}
    			}
    			
    			if(!isset($use_bin))
    			{
					$use_bin = $bins->asarray[0];
    			}
    			
    			if($item->is_ghost())
    			{
    				$item->move_to_parent_item($use_bin, false, true);
    				
    				return null;
    			}
    			else
    			{
    				$item->move_to_parent_item($use_bin, true);
    				
    				return $use_bin;
    			}
    		}
    	}
    }

    
   
    
    
     // --------------------------------------------------------------------

	/**
	* 	saves a given item using the data supplied
	*
	*	the id is for the item (left NULL if a new item)
	*
	*	the item_data needs the following format:
	*
	*		item_id
	*		item_type
	*		name
	*		fields (hash) (the map holding the items field values)
	*			key => value (value is proper value i.e. could be hash or string or anything passed by controller)
	*		keywords (2 dimensional array)
	*			[$name, $value] (value is normal keyword string)
	*			
	*		
	*
	* 	@access	public
	* 	@return	array
	*/
    
    function save_item($item, $item_data, $parent_id)
    {
    	if(!isset($item))
    	{
			throw new Kohana_User_Exception("Cannot save item", "You must pass an item to save");
    	}
    	
    	$item->save_form_data($item_data);

    	if(!$item->existed())
    	{
    		$tree_model = new Tree_Model();
    		
    		$parent_item = new Item_Model($parent_id);
    		
    		$tree_model->add_new_child_to_parent($item, $parent_item);
    	}
    	
    	$this->create_paths($item->system_id);
    }
    
    
     /**
	* 	renames an item
	*
	* 	@access	public
	*	@params
	*		id
	*		name
	* 	@return	boolean
	*/
	
    function rename_item($item, $name)
    {
    	if(!isset($item))
    	{
			throw new Kohana_User_Exception("Cannot save item", "You must pass an item to save");
    	}
    	
    	$item->name = $name;
    	
    	$item->save();
    	
    	$this->create_paths($item->system_id);
    }
    
    // this is called for one item once it has been saved
    // we will load all the ghosts + parents for this item and readjust the paths
    function create_item_paths($item)
    {
    	$all_item_copies = $item->load_copies_of_item();
    	
    	foreach($all_item_copies->asarray as $item_copy)
    	{
    		$item_copy_parent = $item_copy->load_parent(true);
    		
    		$item_copy->load_children(true);
    		
    		$this->create_item_urls($item_copy, $item_copy_parent);
    	}
    }
    
    function create_paths($system_id)
    {
    	$factory = new Itemfactory_Model();
    	
    	// lets load the tree
    	$items = $factory->
    				system_id($system_id)->
    				tree()->
    				load();
    				
    	$installation_id = installation::id();
    				
    	$db = $this->db;

		//$db->query("delete from item_keyword where installation_id = $installation_id and ( name = 'path' or name = 'url' )");
    	
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
    
    
     // loads the top level item for a given system
    //
    // this is always one item that represents the 'root' of the tree
    //
    // it can be any type of item however - check the system_layout config
    //
    // the rule for it being a system item is that an item_link exists with no parent for the system_id
    //
    // if multiple system objects are found - an exception is thrown as our tree algorithm dosn't like multiple roots
    //
    // (thats what systems are for : )
    
    public function load_system_item($system_id)
    {
    	if(!preg_match("/^(all|\d+)$/", $system_id))
    	{
			throw new Kohana_User_Exception("Missing System ID", "Cannot load system object without a numeric system id - $system_id");    		
    	}
    	
    	$factory = new Itemfactory_Model(array(
			'item_link.parent_item_id' => NULL
		));
				
		if($system_id != 'all')
		{
			$factory->system_id($system_id);
		}

		$rootitems = $factory->load();

		// uh oh - we found more than one root item for this system - we are in trouble
		if($rootitems->count()>1)
		{
			$found_system_ids = array();
			
			foreach($rootitems->asarray as $rootitem)
			{
				if(isset($found_system_ids[$rootitem->system_id]))
				{
					throw new Kohana_User_Exception("Multiple Root Items Found", "There seems to be several root items in the database for system id: ".$system_id);
				}
				
				$found_system_ids[$rootitem->system_id] = TRUE;
			}
			
			return $rootitems->asarray;
		}
		else if($rootitems->count()<=0)
		{
			return NULL;
		}
		
		$root = $rootitems->asarray[0];
		
		return $root;
    }
    
    
    
    function load_installation_tree()
    {
    	
    }
    
    function load_system_tree($system_id)
    {
    	
    }
    
    // loads the whole tree ready for a mobile device i.e. it loads everything
    function load_mobile_tree($item = null, $system_id = NULL)
    {
    	$root_items = array();

   		$factory = new Itemfactory_Model();
    		
    	if(isset($item))
    	{
    		// $item is actually an id so lets turn it into a real item
    		if(!is_object($item))
    		{
    			$item = new Item_Model($item);
    		}    		
    		
    		$item->add_load_children_to_factory($factory, true);
    	}
    	
   		if(isset($system_id))
		{
			$factory->system_id($system_id);
		}

		//$factory->dev = true;	
		$root_items = $factory->keywords()->tree()->load();

		return $root_items;
	}
	
	function load_folder_tree($item = null, $system_id = null)
	{
		return $this->load_item_tree($item, $system_id, array(
			item_types => 'folder',
			keyword_words => array(
				'foldericon'
			)
		));
	}
	
    // loads you a tree for an item - leave item blank to load the whole tree for a system
    
    function load_item_tree($item = null, $system_id = null, $config = null)
    {
    	$root_items = array();

   		$factory = new Itemfactory_Model();
    	
    	if(isset($item))
    	{
    		// $item is actually an id so lets turn it into a real item
    		if(!is_object($item))
    		{
    			$item = new Item_Model($item);
    		}
	    		
    		$item->add_load_children_to_factory($factory, true);
    	}
    	
   		if(isset($system_id))
		{
			$factory->system_id($system_id);
		}

		if(isset($config['keyword_words']))
		{
			$factory->keyword_words($config['keyword_words']);

		}
		
		if(isset($config['item_types']))
		{
			$factory->item_types($config['item_types']);
		}
		
		//$factory->dev = true;	
		$root_items = $factory->tree()->load();

		return $root_items;
	}

    
    
    function remove_floating_links()
    {
    	$floating_links = installation::load_links_with_no_items();
		
		foreach($floating_links as $floating_link)
		{
			$status = $this->db->delete('item_link', array('id' => $floating_link->id));	
		}
    }
    
	function save_item_to_parent($item, $parent)
    {
		if(!$item->existed())
    	{
    		$this->add_new_child_to_parent($item, $parent);
    		
			$item->storage_driver_create();
    	}
    	else
    	{
    		$item->storage_driver_save();
    	}
    	
    	$item->update_linked_keywords();
    	
    	$this->create_item_paths($item);
    }

   
}

?>