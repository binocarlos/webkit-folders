<?php defined('SYSPATH') OR die('No direct access allowed.');

// ------------------------------------------------------------------------

/**
 * Item Class
 *
 * Object representing one item and its data
 
 an item interacts with the Tree_Model
 
 the idea is you ask an item to do something and it co-ordinates with the tree
 * 
 */
 
// ------------------------------------------------------------------------

class Item_Model extends Simpleorm_Model
{
	protected $_table_name = 'item';
	protected $_fields = array('installation_id', 'item_type', 'name');	
	
	// array that tells you what fields (apart from the schema ones)
	// to leave behind in an object before dumping it back to the browser
	//
	// nothing will get sent to the browser unless:
	//
	//	a) it is in this list
	//  b) it is a plain keyword - it is added to keywords - controlled by list
	//  c) the field name is in the items schema
	//
	// everything else is removed
	//
	// the rule inside the clean_map tells you what mode to run in
	//
	// if there is no mode - then all fields in the cleanmap will be
	// returned - if run in a mode - then the field must have that mode in its list
	private $_clean_map = array(
		'id' => 'all',
		'name' => 'all',
		'item_type' => 'all',
		'system_id' => 'all',
		'parent_id' => 'all',
		'l' => 'all',
		'r' => 'all',
		'leaf' => 'all',
		'children' => 'all',
		'keywords' => 'all',
		'_clean_map' => 'all'
	); 

	private $_keep_keywords = array(
		'item_created',
		'created'
	);
	
	public $children = array();
	public $keywords = array();
	
	private $_id_regexp = '/^(\d+)\.(\d+)\.?/';
	
    function __construct($id = null, $with_keywords = null)
    {
        parent::__construct();
        
        if(is_object($id))
        {
        	return $id;	
        }
        
        if(isset($id))
        {
        	$result = Itemfactory_Model::instance($id, $with_keywords);
        	
        	if(isset($result))
        	{
				$this->set_data($result);
        	}
        }
        else
        {
        	$this->installation_id = installation::id();
        }
    }
    
    // enables you to load an item based on a keyword value
    // useful for overriding in subclasses
    public static function load_from_keyword($config)
    {
    	$keyword = $config['word'];
    	$value = $config['value'];
    	$beneath_path = $config['beneath'];
    	
    	$item_type = empty($config['item_type']) ? 'item' : $config['item_type'];
    	$item_class = empty($config['class']) ? 'Item_Model' : $config['class'];
    	
		if(empty($keyword) || empty($value))
		{
			return null;
		}

		$factory = new Itemfactory_Model();
		
		$account = $factory->
			item_types($item_type)->
			keywords()->
			item_class($item_class)->
			keyword_query($keyword, $value);
			
		if(!empty($beneath_path))
		{
			$beneath_item = new Item_Model($beneath_path);

			$factory->beneath($beneath_item);
		}
		
		$item = $factory->load_single();
		
		return $item;
	}
    
     
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    // tree methods
    
    // items are indexed using a concatenation of item.id and item_link.id
    // this is because items appear in several places in the tree so using just the database id
    // is not enough
    //
    // this also means we have to maintain tree operations done on one item to all of its copies
    // across the tree
    //
    // if you copy and paste an item to another location - a new item_link is created
    // for the item you copied and all of its children - thus ensuring a complete tree structure
    // ready to be examined quickly
    //
    // the item you actually copied will have a new link with a different parent_id to the original link
    // this is because the item has a new additional parent that is different to the original parent
    //
    // this is known as a 'copy' - i.e. the same item in 2 different places
    //
    // when you copy an item - a recursive copy of its children is made and a new item_link created for each
    // the item_links created for each of the children have exactly the same parent_id as the original link
    //
    // this is known as a 'ghost' - i.e. the same item in the same place
    //
    // if an operation is carried out on a copy - only it will be operated upon
    //
    // if an operation is carried out on a ghost - all other ghosts are removed first
    // and then the operation is carried out - thus reflecting the change thoughout the tree
    //
    // a ghost is a copy but a copy can have different parents - a ghost must be the same parent 
    
  
  	protected function get_keyword_load_db($db = NULL)
    {
    	$db = parent::get_base_load_db($db);
    	
    	$db = Itemfactory_Model::get_keyword_load_db($db);
    	
    	return $db;
    }
    
    protected function get_item_load_db($db = NULL)
    {    	
    	$db = parent::get_base_load_db($db);
    	
    	$db = Itemfactory_Model::get_item_load_db($db);
    	
    	return $db;
    }
    
    // gives you an array of ghosts for this item
    // i.e. ONLY items in exactly the same location (same parent) elsewhere
    //
    // we dont want a system id here because a ghost or copy could be living in a different tree
    function load_ghosts_of_item($parent_item_id = null)
    {    	
    	if(!isset($parent_item_id))
    	{
    		$parent_item_id = $this->parent_item_id();
    	}
    	
    	$clause = array(
    		'item_link.item_id' => $this->database_id(),
    		'item_link.parent_item_id' => $parent_item_id
    	);
    	
    	$ghost_items = Itemfactory_Model::factory($clause);
    	
    	return $ghost_items;
    }
    
    // gives you an array of copies for this item
    // i.e. ALL items regardless of where they are
    function load_copies_of_item()
    {	
    	$clause = array(
    		'item_link.item_id' => $this->database_id()
    	);
    	
    	$copied_items = Itemfactory_Model::factory($clause);
    	
    	return $copied_items;
    }
    
    function save_form_data($passed_item_data)
    {
    	// we have an id so load the item and remove the existing (old) keywords
    	if($this->exists())
    	{
    		$this->_old_name = $this->name;
    		$this->remove_keywords_from_database();
    	}
    	
    	$item_data = $passed_item_data;
    	
    	if(is_object($passed_item_data))
    	{
    		$item_data = statictools::object_to_array($passed_item_data);
    	}
    	
    	// update the item's name and save it
    	$this->item_type = $item_data['item_type'];
    	$this->name = $item_data['name'];
    	
    	$this->save_or_create();
    	
    	if($this->is_model())
    	{
			$db = statictools::database_instance();
    		$db->update('item', array( 'item_type' => $this->name ), array( 'item_type' => $this->_old_name, 'installation_id' => installation::id() ));
    	}
    	
    	statictools::devsql('Save Item');
    	
    	$schema = $this->ensure_schema_model();
    	
    	// nows lets have a look at what field data has been submitted and create a keyword for each entry
    	foreach($item_data['fields'] as $field => $value)
    	{
    		$field_def = $schema->get_field_definition($this->item_type, $field);
    		
    		if(isset($field_def['hidden']))
    		{
    			
    		}
    		else
    		{
    			$this->create_field($field, $value);
    		}
    		
    		statictools::devsql('Create Field');
    	}
    	
    	// same for the plain old keywords
    	foreach($item_data['keywords'] as $keyword)
    	{
    		$this->create_keyword($keyword['name'], $keyword['value']);
    		
    		statictools::devsql('Create Keyword');
    	}
    }
	
 	// hardcore actual removal of an item and all its links + descendants
    // this should be the only place an item is removed from the system
	public function destroy()
	{
		// before we delete the tree info - lets make sure we load our
		// children otherwise the data will be lost
		$this->load_children(true);
		
		// so lets remove all link data for this item
		// this will happen across different systems
		//
		// permissions obviously come in the play here!
		$item_ghosts = array();
		
		if($this->is_ghost())
		{
			$item_ghosts = $this->load_ghosts_of_item();
		}
		else
		{
			$item_ghosts = $this->load_copies_of_item();
		}
		
		foreach($item_ghosts->asarray as $ghost)
		{
			$ghost->detatch_from_tree();
		}
		
		// now lets go about deleting the actual item and its keywords
		
		$this->remove_from_database();
	}
	
	public function remove_from_database()
	{
		foreach($this->children as $child)
		{
			$child->remove_from_database();	
		}
		
		$id = $this->database_id();
		
		$db = $this->db();
		
		$db->query("delete from item_keyword where item_id = $id");
		
		statictools::devsql('Delete Item Keywords');
    	
		$db->query("delete from item where id = $id");
		
		statictools::devsql('Delete Item');
	}
	
	public function remove_field_from_database($field_name)
	{
		$id = $this->database_id();
		
		$db = $this->db();
		
		$db->query("delete from item_keyword where item_id = $id and keyword_type = 'field' and name = '$field_name' ");

		statictools::devsql('Delete Item Keywords');
	}
	
	public function remove_keywords_from_database()
	{
		$id = $this->database_id();
		
		$db = $this->db();
		
		$clauses = array();
		
		foreach($this->_keep_keywords as $keep)
		{
			$clauses[] = "name != '$keep'";
		}
		
		$clause = implode(' and ', $clauses);
		
		$sql = "delete from item_keyword where item_id = $id";
		
		if(!empty($clause))
		{
			$sql .= " and $clause";	
		}
		
		$db->query($sql);
		
		statictools::devsql('Delete Item Keywords');
	}

    // creates a clone of this item in another item
    // creates a fresh copy of the items link tree in the parent
    function ghost_to_parent_item($parent_item)
    {
    	if(!$parent_item->has_link())
    	{
			throw new Kohana_User_Exception("Cannot move item", "the parent has no link id");    		
    	}
    	
    	$parent_item->add_child_item($this, false, true);
    }
    
    // creates a true copy of the item and moves it to the parent
    // the copy is a totally fresh copy (save as)
	function copy_to_parent_item($parent_item)
	{
		if(!$parent_item->has_link())
    	{
			throw new Kohana_User_Exception("Cannot move item", "the parent has no link id");    		
    	}
    	
    	$parent_item->add_child_item($this, true);
	}
	
    // moves the existing links to line up with the new parent
    //
    // note - we don't need to delete and re-insert - just make space, shift and remove space
    //
    // as long as we line up the parent_link_ids the rest of the tree will follow
    //
    // because we are moving note copying - we must delete any ghost links first
    //
    // if we want to merge this so it takes all copies with it - specify allcopies = true
    
    function move_to_parent_item($parent_item, $allcopies = null, $bin_mode = null)
    {
    	if(!$parent_item->has_link())
    	{
			throw new Kohana_User_Exception("Cannot move item", "the parent has no link id");    		
    	}
    	
    	$item_ghosts = null;
    	$target_ghosts = null;
    	
    	statictools::dev('Loading Item Ghosts');
    	if($allcopies)
    	{
    		$item_ghosts = $this->load_copies_of_item();
    	}
    	else
    	{
    		$item_ghosts = $this->load_ghosts_of_item();
    		$target_ghosts = $this->load_ghosts_of_item($parent_item->database_id());
    	}
    	
    	// we have to call this here otherwise we are about to lose the children
    	// below when we clear the link table for all the ghosts
    	statictools::dev('Loading Item Children');
    	$this->load_children(true);

    	foreach($item_ghosts->asarray as $ghost)
    	{
    		// we have found a ghost copy - which means the same thing must happen to it
    		// in this case - as we are moving - we will delete the ghost copy
    		// leaving us with the one copy left ready to move
    		//
    		// because we are asking for ghosts - any version of this item in different folder
    		// will be left alone
    		//
    		// we must load the ghost again - because having removed the previous ghosts
    		// will have changed the left and right values for everything else
    		// (because we have closed the gap left by removing it from the tree)
    		
    		$updated_ghost = new Item_Model($ghost->id);
    		
    		statictools::dev('Detatching Ghost From Tree');
    		$updated_ghost->detatch_from_tree();
    	}
    	
    	if($bin_mode)
    	{
    		return;
    	}
    	
    	$original_found_already = false;
    	
    	if($target_ghosts)
    	{
    		foreach($target_ghosts as $target_ghost)
    		{
    			$updated_ghost = new Item_Model($target_ghost->id);
    		
    			if($updated_ghost->is_ghost())
    			{
    				statictools::dev('Detatching Ghost From Tree');
    				$updated_ghost->detatch_from_tree();
    			}
    			else
    			{
    				// this means we are dragging a ghost into a folder that
    				// already has the original
    				$original_found_already = true;	
    			}
    		}
    	}
    	
    	// nows lets check that there are no copies of the item in the folder
    	// we want to move it to
    	$item_copies = $this->load_copies_of_item();
    	
    	$has_found_equivalent = false;
    	
    	foreach($item_copies as $item_copy)
    	{
			if($item_copy->parent_id == $parent_item->id)
			{
				$has_found_equivalent = true;
			}
    	}
    	
    	if(!$has_found_equivalent && !$original_found_already)
    	{
    		statictools::dev('Ghosts Detatched From Tree');
    		$parent_item->add_child_item($this);
    	}
    }
	 
    
    // creates a link for this item to the provided parent
    //
    // the the special case that a system is being constructed - pass 'root' as the parent_item
    // and the top level will be built - otherwise you must pass a parent item
    //
    // this is desiged for new items - if the item exists with a link_id - you should move it or copy it
    //
    // This will load all duplicate links for the parent and apply a link for this item to all of them
    
    function add_child_item($child_item, $copy = false, $ghost = false)
    {
    	if(!isset($child_item))
    	{
			throw new Kohana_User_Exception("Cannot create link", "Cannot create a link for empty child");
    	}

		if(!$this->has_link())
    	{
    		throw new Kohana_User_Exception("Cannot create link", "Cannot create a link because the parent has no link");
    	}
    	
    	$parent_copies = $this->load_copies_of_item();
    	
    	// so - we have each copy of the parent - lets proceed to add the entire child tree to it
    	// for this we do 2 things:
    	//	1. explicitly link the parent & child
    	//  2. load the child tree and clear all link references
    	//  3. recurse the child tree to make new references based on new link as root
    	
    	$insert_gap = 0;
    	
    	foreach($parent_copies->asarray as $parent_copy)
    	{
    		// we need to grab a fresh copy because things might have been adjusted
    		$updated_parent_ghost = new Item_Model($parent_copy->id);

    		// make space for the child and its number of children
    		// doing this here means we only need one update rather than one per addition
   			$insert_gap = $updated_parent_ghost->make_space_for_children(1 + $child_item->get_child_count());
   			
   			// lets build up the link info for the addition
    		$child_item->build_link_info($updated_parent_ghost);
    		
    		$child_item->commit_link_info($updated_parent_ghost, $copy, $ghost);
    		
    		// we only want one true copy - if we are adding to several ghosts
    		$copy = false;
    	}
    	
    	$this->r += $insert_gap;
    }
    
    // recursive function to set a whole tree from the point of one item
    // this assumes the space has been made and the parent has been linked
    //
    // it also assumes that the left and right values have been set
    function commit_link_info($parent, $copy = false, $ghost = false)
    {
    	if($copy)
    	{
    		$this->create_carbon_copy();
    	}
    	
    	$link_id = null;
    	$item_id = null;
    	
		$link_type = 'item';
		
		if($this->is_ghost())
		{
			$link_type = 'ghost';
		}
		
		if($ghost)
		{
			$link_type = 'ghost';
		}
    	
    	if((isset($parent))&&(is_object($parent)))
    	{	
    		$link_id = $parent->link_id();
    		$item_id = $parent->database_id();
    			
    		// if the parent is a ghost then this MUST be a ghost
    		if($parent->is_ghost())
    		{
		   		$link_type = 'ghost';
    		}
    	}
    	
    	$link_data = array(
			'item_id' => $this->database_id(),
    		'parent_link_id' => $link_id,
    		'parent_item_id' => $item_id,
    		'link_type' => $link_type,
    		'l' => $this->l,
    		'r' => $this->r
    	);
    	
    	// dispatches the actual creation of the link to the child itself
    	$this->insert_link($link_data);
    	
    	foreach($this->children as $child)
    	{
    		$child->commit_link_info($this, $copy, $ghost);
    	}
    }
    
 	
    function build_link_info($parent, $copy = NULL)
    {
		// do we already exist?
    	// therefore we will have to insert our entire tree
    	if($this->has_link())
    	{
    		// so lets load the tree for the thing we are adding with all of its keywords
    		$this->load_children(true, $copy);
    	}
    	 	
    	// we need to build the tree info for the child_item so lets clear any
    	// existing link data so it can all be created fresh
    	$this->clear_link_data();
    	
    	if(isset($parent))
    	{
    		// check to see if the parent provided is an object
    		// if not we will assume that it is a system_id
    		if(is_object($parent))
    		{
    			$this->l = $parent->r;
    			$this->r = $this->l + 1;
    			$this->system_id = $parent->system_id;
    		}
    		else
    		{
    			$this->l = 1;
    			$this->r = 2;
    			$this->system_id = $parent;
    		}
    	}
    	else
    	{
    		throw new Kohana_User_Exception("Cannot build link info", "No parent or system id given");
    	}
    	
    	foreach($this->children as $child)
    	{
    		$child->build_link_info($this, $copy);
    		
    		$this->r = $child->r + 1;
    	}
    }    
    
    // removes all tree data from the link table for this item
    // it is assumed that this is being run on a ghost otherwise
    // this is tantamount to a delete!
    function detatch_from_tree()
    {
    	$installation_id = installation::id();
    	$system_id = $this->system_id;
    	
    	if(!preg_match("/^\d+$/", $system_id))
    	{
			throw new Kohana_User_Exception("Cannot detatch item", "No system id");
    	}
    	
    	statictools::dev('Loading Children For Item Being Detatched From Tree');
    	$this->load_children();
    
    	$l = $this->l;
		$r = $this->r;
		$gap = ($r - $l) + 1;
			
		$clause = <<<EOT
system_id = $system_id
and installation_id = $installation_id			
EOT;

		$db = $this->db();

		$this->begin_transaction("item_link");
		
		$db->query("delete from item_link where l >= $l and r <= $r and $clause");
		
		statictools::devsql('Detatch - Delete Item Links');
    	
		$db->query("update item_link set l = l - $gap where l > $r and $clause");
		
		statictools::devsql('Detatch - Update Item Links After Removing Item');
    	
	    $db->query("update item_link set r = r - $gap where r > $r and $clause");
	    
	    statictools::devsql('Detatch - Update Item Links After Removing Item');
	    
	    $this->commit_transaction();
	    
	    $this->clear_link_data();
    }   
    
    function make_space_for_children($child_count)
    {
    	return $this->alter_space_for_children($child_count);
    }
    
    function remove_space_for_children($child_count)
    {
    	return $this->alter_space_for_children($child_count * -1);
    }
    
    // NOTE - this does NOT update the object - just the database
    
    function begin_transaction($tables)
    {
    	$db = $this->db();
    	
    	mysql_query("LOCK TABLES $tables WRITE");
    }
    
    function commit_transaction()
    {
    	$db = $this->db();
    	
    	mysql_query("UNLOCK TABLES");
    }
    
    function alter_space_for_children($child_count)
    {
    	$db = $this->db();
    	
    	$installation_id = installation::id();
    	$system_id = $this->system_id;
    	
    	if(!preg_match("/^\d+$/", $system_id))
    	{
			throw new Kohana_User_Exception("Cannot create space for children", "No system id");
    	}
    	
		$r = $this->r;
		
		$increase = $child_count * 2;
			
		$clause = <<<EOT
system_id = $system_id
and installation_id = $installation_id			
EOT;
		
		$this->begin_transaction("item_link");

		// first - lets shift everything that is to the right of here 2 spaces to the right
		// we will include the right hand side of this item also - after all it is the parent :>
		$db->query("update item_link set l = l + $increase where l > $r and $clause");
		
		statictools::devsql('Make Space In Tree For Item');
    	
	    $db->query("update item_link set r = r + $increase where r >= $r and $clause");
	    
	    statictools::devsql('Make Space In Tree For Item');
	    
	    $this->commit_transaction();
	    
	    return $increase;
    }
    
    // creates a gap in the tree ready for a new item to be inserted
    // adds 2 to everything on this items right hand-side
    function make_space_for_child()
    {
    	return $this->make_space_for_children(1);
    }
     
    // inserts the provided link data to the database and then updates itself with the new info
    function insert_link($link_data)
    {
    	if(empty($this->system_id))
    	{
    		throw new Kohana_User_Exception("No System id for item", "Cannot create a link for an item with no system_id");
    	}
    	
    	if(!$this->exists())
    	{
    		throw new Kohana_User_Exception("Item Not Loaded", "Cannot create a link for an item that is not loaded");
    	}
    	
    	$link_data['installation_id'] = installation::id();
    	$link_data['system_id'] = $this->system_id;
    	
    	$db = $this->db();
    	
    	$query = $db->insert('item_link', $link_data);
    	
    	statictools::devsql('Insert New Item Link');
    	
    	$link_data['id'] = $query->insert_id();

   		$this->copy_from_link_data($link_data);
    }
    
    // clears out any unrequired data for this object ready to
    // print back with json
    
    function clean_for_output()
    {
 		unset($this->installation_id);
 		unset($this->_keywords_loaded);
 		unset($this->_children_loaded);
 		unset($this->_path_array);
 		unset($this->_website_address_array);
 		unset($this->_children_by_path);
 		unset($this->_path_prepend);
 		
 		$this->keywords = array();
    }
    
    // cleans for output and then returns a json string of this item
    function jsonize()
    {
    	$this->clean_for_output();
    	
    	return json_encode($this);
    }
    
    // useful if you have already built a tree and want to copy the structure somewhere else
    // it means you can remove all link data from the whole tree - traverse it creating links
    // along the way and then clear the link data ready to traverse again (useful when copying
    // existing structures to multiple ghosts)
  	
    function clear_link_data()
    {
    	unset($this->system_id);
    	unset($this->parent_id);
    	unset($this->l);
    	unset($this->r);
    	
    	$this->id = $this->database_id();
    	
    	foreach($this->children as $child)
    	{
    		$child->clear_link_data();	
    	}
    }
    
    // updates this item with new link data
    function copy_from_link_data($link_data)
    {
    	$this->id = $link_data['item_id'].'.'.$link_data['id'];
    	$this->parent_id = $link_data['parent_item_id'].'.'.$link_data['parent_link_id'];
    	$this->l = $link_data['l'];
    	$this->r = $link_data['r'];
    	$this->link_type = $link_data['link_type'];
    }
    
    // loads the parent item for this item in the tree
    
    function load_parent($with_keywords = null)
    {
    	$parent = new Item_Model($this->parent_id, $with_keywords);
    	
    	return $parent;
    }
       
	function load_children_of_type($deep = null, $item_types = null, $item_class = null)
	{
		return $this->load_children($deep, true, false, $item_types, $item_class);
	}
	
	function sort_children($sort_on, $sort_direction)
	{
		$sorter = new ItemSorter($this->children);
	 						
		$this->children = $sorter->get_sorted_items($sort_on, $sort_direction);

		return $this->children;
	}
	
  	// loads all of this items children - only in the context of one ghost
    // because you are loading children for an item in the context of a link_id
    // you will not get ghost copies here - only the single tree for the item - link combination
    //
    // specify deep to mean you want all children (recursive) or just ones that live here directly
    //
    // we have to specifiy a system if otherwise another tree will pollute the items
    
    function load_children($deep = null, $with_keywords = null, $with_path = null, $item_types = null, $item_class = null)
    {
    	if($this->_children_loaded && empty($item_types)) { return; }
    	$this->_children_loaded = true;
    	
    	// this item does not have a system id - this means we cannot load its children
    	// because it is detatched from any tree and therefore has no children
    	if(empty($this->system_id))
    	{
    		return;
    	}

    	$children_items = null;
    	
    	$factory = new Itemfactory_Model();

    	$this->add_load_children_to_factory($factory, $deep);
    	
    	$items = $factory->
    				system_id($this->system_id)->
    				keywords($with_keywords)->
    				tree();
    				
    	if(isset($item_types))
    	{
    		$factory->item_types($item_types);
    	}
    	
    	if(isset($item_class))
    	{
    		$factory->item_class($item_class);
    	}

		$items = $factory->load($this->dev_mode);

    	$this->children = $items->astree;
    	
    	if($with_path)
    	{
    		$this->load_path();
    		$this->_children_by_path = array();
    		foreach($this->children as $child)
    		{
    			$child->assign_path($this, true);
    		}
    		
    		foreach($items->asarray as $child)
    		{
    			$this->_children_by_path[$child->path] = $child;	
    		}
    	}
    	
    	return $items;
    }
    
    function add_load_children_to_factory($factory, $deep = NULL)
    {
    	if($deep)
    	{
    		$factory->beneath($this);
    	}
    	else
    	{
    		$item_id = $this->database_id();
    		$link_id = $this->link_id();
    		
    		$factory->add("item_link.parent_link_id = $link_id");
    	}
    }
    
    function load_deep_children($with_keywords = NULL)
	{
		return $this->load_children(true, $with_keywords);
	}
	
	function load_children_with_keywords($deep = NULL, $keywords = NULL)
	{
		if(!isset($keywords))
		{
			$keywords = true;
		}
		
		return $this->load_children($deep, $keywords, true);	
	}
	
	// creates the full url for this item based on its ancestors
	function load_path()
	{
		if($this->_path_loaded)
		{
			return;
		}
		
		$this->_path_loaded = true;
		
		$this->load_ancestors(array('hostname'));
		$this->_path_array = array();
		$this->_website_path_array = array();
		$this->_found_website = false;
		
		foreach($this->ancestors as $ancestor)
		{			
			$this->add_path_item($ancestor);
		}
		
		$this->_path_array[] = $this->item_url();
		
		if($this->is_website())
		{
			$this->website_hostname = $this->hostname;
			$this->_website_path_array[] = '/';
		}
		else if($this->_found_website)
		{
			$this->_website_path_array[] = $this->item_url();
		}

		$this->path = implode('/', $this->_path_array);
		$this->website_path = '';
		
		if(count($this->_website_path_array)>0)
		{
			$this->website_path = implode('/', $this->_website_path_array);
		}
		
		$this->path = preg_replace('/\/+/', '/', $this->path);
		$this->website_path = preg_replace('/\/+/', '/', $this->website_path);
		
		$this->website_address = $this->website_hostname.'/'.$this->website_path;
		
		foreach($this->children as $child)
		{
			$child->assign_path($this, true);
		}
	}

	function add_path_item($item)
	{
		$the_url = $item->item_url();
		
		if($this->_found_website)
		{
			$this->_website_path_array[] = $item->item_url();
		}
		
		$this->_path_array[] = $item->item_url();
		
		if($item->is_website())
		{
			$this->_found_website = true;

			$this->website_hostname = $item->hostname;
		}
		
		foreach($item->children as $child)
		{
			$this->add_path_item($child);
		}
	}
	
	function create_path($parent = null)
	{
		$this->assign_path($parent);
		
		//$this->add_field('path', $this->path);
		//$this->replace_field('url', $this->url);
		

		$this->save_link_path();
	}
	
	function save_link_path()
	{
		if(!$this->has_link()) { return; }
		
		$db = $this->db();
		
		$db->update('item_link', array( 
		
			'path' => $this->path ), array( 
			
			'id' => $this->link_id(),
			'installation_id' => installation::id()
		));		
	}
	
	function save_path()
	{
		$this->load_children_with_keywords(true);
    	$parent = $this->load_parent(true);
    	
    	$this->assign_path($parent, true, true);
	}
	
	function assign_path($parent = null, $with_children = null, $replace_keyword)
	{
		if($parent)
		{
			$new_path = $this->item_url();
			
			if(!empty($parent->path))
			{
				$new_path = $parent->path.'/'.$new_path;
			}
			
			$this->path = $new_path;
			
			
/*
			if(!empty($parent->website_path))
			{
				$this->website_hostname = $parent->website_hostname;
				$this->website_address = $parent->website_address.'/'.$this->item_url();
				$this->website_path = $parent->website_path.'/'.$this->item_url();
			}
*/			
		}
		else
		{
			$this->path = '';
		}
		
		$this->path = preg_replace('/\/+/', '/', $this->path);
		
		/*
		$this->website_address = preg_replace('/\/+/', '/', $this->website_address);
		$this->website_path = preg_replace('/\/+/', '/', $this->website_path);
		*/
		
		if(!preg_match('/^\//', $this->path))
		{
			$this->path = '/'.$this->path;
		}
		
		if($replace_keyword)
		{
			//$this->replace_field('path', $this->path);
			$this->save_link_path();
		}
		
		if($with_children)
		{
			foreach($this->children as $child)
			{
				$child->assign_path($this, true, $replace_keyword);	
			}	
		}
	}

	
	// loads all of the parent items for this to the very top of the tree
	function load_ancestors($with_keywords = null)
    {
    	if($this->_ancestors_loaded) { return; }
    	$this->_ancestors_loaded = true;
    	
    	// this item does not have a system id - this means we cannot load its children
    	// because it is detatched from any tree and therefore has no children
    	if(empty($this->system_id))
    	{
    		return;
    	}
    	
    	$factory = new Itemfactory_Model();
    		
    	$this->add_load_ancestors_to_factory($factory);
    	
    	$factory->
    		system_id($this->system_id)->
    		tree();
    				
    	if(is_array($with_keywords) || is_string($with_keywords))
    	{
    		$factory->keyword_words($with_keywords);
    	}
    	else if(isset($with_keywords))
    	{
    		$factory->keywords(true);
    	}
    				
    	$items = $factory->load();
    	
    	$this->ancestors = $items->astree;
    	
    	return $items;
    }
    
    function add_load_ancestors_to_factory($factory)
    {
    	$factory->add("item_link.l < {$this->l} and item_link.r > {$this->r}");
    }
    
    // loads the items keywords and creates a new instance of this item in the database
    // the ids are then loaded back
    function create_carbon_copy()
    {
    	$this->load_keywords();
    	
    	parent::create_carbon_copy();
    	
    	foreach($this->keywords as $keyword)
    	{
    		$keyword->item_id = $this->database_id();
    		$keyword->create_carbon_copy();
    	}
    }
    
    function recursive_get_children_by_type($item_type, $inherit = null)
    {
    	$ret = array();
    	
    	if($inherit)
		{
			if($this->is_of_type($item_type))
    		{
    			$ret[] = $this;
    		}
    	}
    	else
    	{
    		if($this->item_type == $item_type)
    		{
    			$ret[] = $this;
    		}
    	}
    	
    	foreach($this->children as $child)
    	{
			$arr = $child->recursive_get_children_by_type($item_type, $inherit);
			
			foreach($arr as $elem)
			{
				$ret[] = $elem;
			}
    	}
    	
    	return $ret;
    }    
    
    function get_children_by_type($item_type, $inherit = null)
    {
    	$ret = array();
    	
    	foreach($this->children as $child)
    	{
    		if($inherit)
    		{
    			if($child->is_of_type($item_type))
    			{
    				$ret[] = $child;
    			}
    		}
    		else
    		{
    			if($child->item_type == $item_type)
    			{
    				$ret[] = $child;
    			}
    		}
    	}
    	
    	return $ret;
    }
    
    // uses the tree data to get how many children this item has
    // if you say use_left_right it will use the gap between the left and right values to 
    // calculate the number of children - use this when you havn't loaded the children from the db
    function get_child_count($use_left_right = null)
    {
    	if(!$this->has_link()) { return 0; }
    	
    	$gap = ($this->r - $this->l) - 1;
    	
    	$children_count = $gap/2;
    	
    	return $children_count;

		/*
    	
    	if(isset($this->_child_count))
    	{
    		return $this->_child_count;
    	}
    	
    	$counter = 0;
    	
    	foreach ($this->children as $child)
    	{
    		$counter++;
    		$counter += $child->get_child_count();
    	}
    	
    	$this->_child_count = $counter;
    	
    	return $counter;
    	
    	*/
    	
    }    

    // creates the top-level tree link - should only ever be done by a system object
    function create_root_link()
    {
    	$link_data = array(
    		'item_id' => $this->database_id(),
    		'parent_link_id' => NULL,
    		'parent_item_id' => NULL,
    		'l' => 1,
    		'r' => 2
    	);
    	
    	$this->insert_link($link_data);
    }
    
    // changes the flattened value of any keywords that are pointing to this one
    function update_linked_keywords()
    {
    	$db = $this->db();
    	
    	$db->
    		select('item_keyword.*, item.item_type')->
    		from('item_keyword, item')->
    		where('item_keyword.item_id = item.id')->
    		where(array(
    			'item_keyword.installation_id' => installation::id(),
    			'item_keyword.id_value' => $this->database_id()
    		))->
    		groupby('item_keyword.id');
    		
    	$model = new Generic_Model();

    	$keywords = $model->load_objects($db->get(), 'Keyword_Model');
    	
    	$schema = $this->ensure_schema_model();
    	
    	foreach ($keywords->asarray as $keyword)
    	{
    		if(($keyword->field_type == 'item_pointer')||($keyword->field_type == 'model_pointer'))
    		{
    			$new_value = array(
    				'id' => $this->database_id(),
    				'name' => $this->name,
    				'icon' => $this->get_icon_name()
    			);
    			
    			$schema->set_keyword_value($keyword, $keyword->item_type, $new_value);
    			
    			$keyword->save();
    		}
    	}
    }

	function load_keywords()
    {
    	if($this->_keywords_loaded) { return; }
    	$this->_keywords_loaded = true;
    	
    	$db = $this->db();
    	
    	$db->
    		from('item_keyword')->
    		where(array(
    			'installation_id' => installation::id(),
    			'item_id' => $this->database_id()
    		))->
    		orderby('item_keyword.name');
    		
    	$model = new Generic_Model();

    	$keywords = $model->load_objects($db->get(), 'Keyword_Model');
    	
    	statictools::devsql('Load Item Keywords');
    	
    	$this->keywords = $keywords->asarray;
    	
    	foreach ($this->keywords as $keyword)
    	{
    		$this->add_keyword($keyword);
    	}
    	
    	return $keywords;
    }
    
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    // Property Helpers
    
    function safe_name()
    {
    	$ret = $this->name;
    	
    	$ret = preg_replace('/[\'\"]/', '\\$0', $ret);
    	
    	return $ret;
    }
    
    function item_id()
    {
    	return $this->database_id();
    }
    
    function get($field)
    {
    	if($field == 'id')
    	{
    		return $this->database_id();
    	}
    	else
    	{
    		return parent::get($field);
    	}	
    }
    
    function link_id()
    {
    	if(preg_match($this->_id_regexp, $this->id, $matches))
    	{
    		return $matches[2];
    	}
    	
    	return NULL;
    }
    
    function exists()
    {
    	if(preg_match('/^\d+$/', $this->database_id()))
    	{
    		return true;
    	}
    	else
    	{
    		return false;
    	}
    }
    
    function database_id()
    {
    	if(preg_match($this->_id_regexp, $this->id, $matches))
    	{
    		return $matches[1];
    	}
    	
    	return $this->id;
    }
    
    function parent_link_id()
    {
    	if(preg_match($this->_id_regexp, $this->parent_id, $matches))
    	{
    		return $matches[2];
    	}
    }
    
    function parent_item_id()
    {
    	if(preg_match($this->_id_regexp, $this->parent_id, $matches))
    	{
    		return $matches[1];
    	}
    }
    
    function has_link()
	{
		return preg_match($this->_id_regexp, $this->id);
	}
    
	function has_parent()
	{
		return preg_match($this->_id_regexp, $this->parent_id);
	}
	
	function get_icon_name()
	{
		if(!empty($this->foldericon))
		{
			return $this->foldericon;
		}
		
		$schema = $this->ensure_schema_model();
		
		$icon = $schema->get_icon($this->item_type);
		
		if($icon == 'item_type')
		{
			$icon = $this->item_type;
		}
		
		return $icon;
	}
	
	function assign_url()
	{
//		$this->remove_field_from_database('url');
		
		$this->url = $this->item_url();
		
		/*
		if(is_array($url))
		{
			foreach($url as $u)
			{
				$this->create_field('url', $u);	
			}
		}
		else
		{
			$this->create_field('url', $url);
		}
		*/
	}
	
	function item_url_part()
	{
		$ret = $this->item_url();
		
		$ret = preg_replace('/\//', '', $ret);
		
		if($this->is_of_type('disk'))
		{
			$ret = '/';
		}
		
		return $ret;
	}
	
	// gives you the url just for this item - not in its path
	function item_url()
	{
		$url = statictools::get_url_from_string($this->name);

		if($this->is_system_object())
		{
			if($this->is_system())
			{
				$url = '';
			}
			else if($this->is_disk())
			{
				$url = '/';
			}
			else
			{
				$url .= ':/';
			}
		}
		
		return $url;
	}
	
	function has_name_changed()
	{
		if(!isset($this->_old_name)) { return false; }
		
		if($this->_old_name != $this->name) { return true; }
		else { return false; }	
	}
	
	function is_of_type($item_type)
	{
		$schema = $this->ensure_schema_model();
		
		return $schema->does_schema_inherit_from($this->item_type, $item_type);
	}
	
	function get_flat_value($field_name, $args)
	{
		$schema = $this->ensure_schema_model();
		
		return $schema->get_flat_field_value($field_name, $this->item_type, $this->$field_name, $args);
	}
	
	function get_field_definition($field_name)
	{
		$schema = $this->ensure_schema_model();
		
		return $schema->get_field_definition($this->item_type, $field_name);
	}
	
	function get_fields()
	{
		$schema = $this->ensure_schema();
		
		return $schema['fields'];
	}
	
	function has_field_in_schema($field_name)
	{
		$schema = $this->ensure_schema_model();

		return $schema->has_item_got_field($this->item_type, $field_name);
	}
	
	// tells you if this instance in the tree is a ghost
	function is_ghost()
	{
		return $this->link_type == 'ghost' ? true : false;
	}
	
	// tells you if this item is a system item (based on the schema)
	function is_system_object()
	{
		$access = $this->get_schema_property('access');
		
		return $access == 'system' ? true : false;
	}
	
	function is_disk()
	{
		return $this->is_of_type('disk');	
	}
	
	function is_model()
	{
		return $this->is_of_type('model');	
	}
	
	function is_website()
	{
		return $this->is_of_type('ftp_website');	
	}
	
	function is_system()
	{
		return $this->is_of_type('system');	
	}
	
	function get_schema_property($property_name)
	{
		$schema = $this->ensure_schema();
		
		return $schema[$property_name];
	}
    
    // gives you a nice new fresh item with the given name + type
    public static function create_fresh($name, $item_type)
    {
    	$item = new Item_Model();
    	
    	$item->name = $name;
    	$item->item_type = $item_type;
    	$item->installation_id = installation::id();
    	
    	return $item;
    }
    

	///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    // storage methods
    
    function email_updates()
    {
    	$this->email_updates_array = array();
    	
    	$ancestors = $this->load_ancestors(true);
    	
    	$top_ancestor = $ancestors->astree[0];
    	
    	$this->gather_email_updates($top_ancestor);
    	$this->gather_email_updates($this);
    	
    	if(count($this->email_updates_array)<=0) { return; }
    	
    	$changed_item_view = new View('item/default_text');
    	
    	$changed_item_view->item = $this;
    	
    	$change_item_content = $changed_item_view->render();
    	
    	$notify_name = $this->notify_item_change->name;
    	$notify_path = $this->notify_item_change->path;
    	
    	$email_content = <<<EOT
An item in $notify_path has been updated - here are the details:

$change_item_content
EOT;

    	foreach($this->email_updates_array as $email_update)
    	{
    		$email_user = Itemfactory_Model::instance($email_update->id, true);
    		
    		statictools::SendEmail('folders@wk1.net', $email_user->email, $notify_path.' has been updated', $email_content);
    	}
    }
    
    function gather_email_updates($item)
    {
    	foreach($item->email_updates as $email_update)
    	{
    		$this->notify_item_change = $item;
    		$this->email_updates_array[] = $email_update;	
    	}
    	
    	foreach($item->children as $child)
    	{
    		$this->gather_email_updates($child);
    	}
    }

	// these methods deal with the customized storage of items - so if an item needs to do specific
	// stuff upon a save or create - it can be done here
	//
	// the specific behaviour is defined by storage drivers - which in turn are activated by the schema
	//
	// NOTE - these get called once the item has been put into the tree (not when it has been actually created)
	// i.e. it will have its link info
	
	function load_storage_driver()
	{
		$driver_name = $this->get_schema_property('storage_driver');
		
		if(empty($driver_name))
		{
			return;
		}
		
		$driver_class = "Storage_{$driver_name}_Driver";

		// Load the driver
		if ( ! Kohana::auto_load($driver_class))
			throw new Kohana_Exception('core.driver_not_found', $driver_class, get_class($this));

		// Initialize the driver
		$driver = new $driver_class($this);
		
		return $driver;
	}
	
	function storage_driver_save_or_create()
    {
    	if($this->exists())
    	{
    		$this->storage_driver_save();
    	}
    	else
    	{
    		$this->storage_driver_create();
    	}
    }
	
	function storage_driver_create()
	{
		$this->create_field('modified', date("d/m/Y H:i:s"));
		$this->create_field('created', date("d/m/Y H:i:s"));
		
		$this->email_updates();
		
		$driver = $this->load_storage_driver();
		
		if(!$driver) { return; }
		
		// we don't want to do the create if this already existed - this should never happen
		if($this->existed()) { return; }
		
		$driver->do_create();
	}
	
	function storage_driver_save()
	{
		$this->create_field('modified', date("d/m/Y H:i:s"));
		
		$this->email_updates();

		$driver = $this->load_storage_driver();
		
		if(!$driver) { return; }
		
		// we don't want to do the save if this never existed - this should never happen
		if(!$this->existed()) { return; }
		
		$driver->do_save();
	}
	
	///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    // Auto-Create
    
    // these functions will create you new items based on the given configuration
    // this is useful for XML imports or auto-creation based on storage drivers
    //
    // $child_info should be an array of data with a children property which in turn is an array of info
    // Info should be:
    //
    //		name
    //		item_type
    //		fields []
    //		keywords []
    //
    // the tree info will be created automatically
    
    function auto_create_children($child_info_array, $path_map = null)
    {	
    	if(!isset($child_info_array))
    	{
    		return;	
    	}
    	
    	if(!is_array($child_info_array))
    	{
    		throw new Kohana_User_Exception("Info is not an array", "Cannot create children without an array of child info - ");
    	}
    	
    	if(!isset($path_map))
    	{
    		$path_map = $this->_children_by_path;
    	}
    	
    	foreach($child_info_array as $child_info)
    	{
    		$component_item = $path_map[$child_info->check_url];
    		
    		if($child_info->fields['name'])
    		{
    			$child_info->name = $child_info->fields['name'];
    		}
    		else if($child_info->fields['title'])
    		{
    			$child_info->name = $child_info->fields['title'];
    		}
    		
    		if(!$component_item)
    		{
    			$component_item = new Item_Model();
    		
    			$component_item->save_form_data($child_info);
    			$component_item->assign_url();

    			$this->add_child_item($component_item);
    		}
    		
    		$component_item->auto_create_children($child_info->children, $path_map);
    	}
    }
   
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    // Keywords
    
    function load_fields()
    {
    	if(!$this->exists())
    	{
    		throw new Kohana_User_Exception("Item does not exist", "Cannot load keywords for an non-existent item -> ".$this->id);
    	}
    	
    	$model = new Generic_Model();
    	
    	$db = $this->db();
    	
    	$db->
    		from('item_keyword')->
    		where(array('item_keyword.item_id' => $this->id, 'keyword_type' => 'field'))->
    		orderby('item_keyword.name');
    		
		$keywords = $model->load_objects($db->get());
		
		statictools::devsql('Load Item Fields');
		
		foreach($keywords->asarray as $keyword)
		{
			$this->add_keyword($keyword);	
		}
    }
    
    function add_keyword($keyword, $flat_value = FALSE)
    {	
    	$this->_keywords_loaded = true;
    	
    	if($keyword->keyword_type == 'field')
    	{
    		$this->add_field($keyword, $flat_value);	
    	}
    	else if($keyword->keyword_type == 'keyword')
    	{
    		$keyword_arr = array(
    			'id' => $keyword->id,
    			'name' => $keyword->name,
    			'value' => $keyword->value
    		);
    		
    		$this->keywords[] = $keyword_arr;	
    	}
    }
    
    public function add_field($keyword, $flat_value = FALSE)
    {
    	if($keyword->name == 'id') { return; }
    	
    	if($keyword->name == 'url') { return; }

    	if($keyword->name == 'path') { return; }

    	
		//////////////////////////////////////////////////////////////////////
    	// now we use the schema object to convert the keyword value into 
    	// something usable for the front end website i.e. it wont return JSON structures
    	// but the most sensible value for front end web pages (e.g. image fields will equate to a direct url)    	
    	
    	$schema = $this->ensure_schema_model();
    	
    	$field_name = $schema->get_field_name($keyword->name);
    	
    	$keyword_value = NULL;
    	
    	if($flat_value)
    	{	
    		$keyword_value = $schema->get_flat_keyword_value($keyword, $this->item_type);
    	}
    	else
    	{
    		$keyword_value = $schema->get_keyword_value($keyword, $this->item_type);
    	}
    	
    	if($schema->is_field_list($this->item_type, $field_name))
    	{
    		$field_index = $schema->get_field_index($keyword->name);
    		
    		if(!isset($this->$field_name))
    		{
    			$this->$field_name = array();
    		}
    		
    		$arr = $this->$field_name;
    		
    		$arr[] = $keyword_value;

    		$this->$field_name = $arr;
    	}
    	else
    	{
    		$this->$field_name = $keyword_value;
    	}
    }
    
	public function replace_field($field_name, $field_value)
    {
		$this->remove_field_from_database($field_name);
		
		$this->create_field($field_name, $field_value);
    }
    
    function create_field($name, $value)
    {
    	$schema = $this->ensure_schema_model();
    	
    	if($schema->is_field_list($this->item_type, $name))
    	{
    		$counter = 0;
    		
    		foreach($value as $single_value)
    		{
    			$array_name = $name.'.'.statictools::get_padded_string($counter, '0', 10);
    			
    			$this->create_single_field($array_name, $single_value);	
    			
    			$counter++;
    		}	
    	}
    	else
    	{
    		$this->create_single_field($name, $value);
    	}
    }
    
    // this knows what kind of field each value is and therefore how it should be saved
    function create_single_field($name, $value)
    {	    	
    	if(empty($value)) { return; }
    	
    	if($value === 'null') { return; }

    	$keyword = new Keyword_Model();
    	
    	$keyword->item_id = $this->database_id();
    	$keyword->keyword_type = 'field';
    	$keyword->name = $name;
		
		$schema = $this->ensure_schema_model();
		
		if($schema->set_keyword_value($keyword, $this->item_type, $value))
		{
			$keyword->create();
		}
		
		$this->add_keyword($keyword);
    }
    
	// THIS IS FOR NORMAL KEYWORDS!!!
    function create_keyword($name, $value)
    {
    	$keyword = new Keyword_Model();
    	
    	$keyword->item_id = $this->database_id();
    	$keyword->keyword_type = 'keyword';
    	$keyword->name = $name;
    	$keyword->value = $value;
    	
    	$keyword->create();
    	
    	$this->add_keyword($keyword);
    }
    
  
  	///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////
    // tools
	
	function add_to_children($child)
    {
    	$this->_children_loaded = true;
    	$this->children[] = $child;
    }
  
  
	// this gives you the entire schema - i.e. the singleton instance of Schema_Model
    function ensure_schema_model()
    {
    	$ret = Schema_Model::instance();
    	
    	return $ret;
    }
    
    function ensure_schema()
    {
    	$schema_model = $this->ensure_schema_model();
    	
    	$schema = $schema_model->get_schema($this->item_type);
    	
    	return $schema;
    }
    
    public function is_path_descendent_of($parent)
    {
    	if(strrpos($this->path, $parent->path)==0) { return true; }
    	else { return false; }
    }
    
    public function is_session_enabled()
    {
    	return $this->exists();
    }
    
    function get_sitemap_div($vertical = null)
	{
		$child_count = count($this->children);
		
		$html = '';
		
		$blank_image = Kohana::config('webkitfolders.blank_image_url');
		$icon_folder = Kohana::config('webkitfolders.icon_folder');

		$icon = $this->get_icon_name();
		
		if($vertical)
		{
			$html = <<<EOT
<table width=100% border=1 cellpadding=0 cellspacing=0>
<tr>
	<td valign=middle align=left>{$this->l} {$this->name} - {$this->item_type} - {$this->link_type} - ({$this->id}) {$this->r}</td>
	<td>
EOT;
		}
		else
		{
			$html = <<<EOT
<td align=center valign=top>		
	<table width=100% border=0 cellpadding=0 cellspacing=0>
	<tr>
		<td height=1 colspan=$child_count><img width=300 height=1 src="{$blank_image}"></td>
	</tr>
	<tr>
		<td height=80 align=center valign=bottom>
			<img align="absmiddle" src="{$icon_folder}/icons/$icon.png"><br>{$this->l} {$this->name} - {$this->link_type} - ({$this->id}) {$this->r}
		</td>
	</tr>
	<tr>
		<td>
			<table width=100% border=0 cellpadding=0 cellspacing=0>
			<tr>
EOT;
		}

		foreach ($this->children as $child)
		{
			$html .= $child->get_sitemap_div($vertical);	
		}
		
		if($vertical)
		{
			$html .= <<<EOT
</td>
</tr>
</table>			
EOT;
		}
		else
		{
			$html .= <<<EOT
			</tr>
			</table>
		</td>
	</tr>
	</table>
</td>
EOT;
		}
		
		return $html;
	}
	
	function __get($name)
	{
		if($name == 'url')
		{
			return $this->item_url();
		}
	}
}

?>