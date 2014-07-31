<?php defined('SYSPATH') OR die('No direct access allowed.');

// ------------------------------------------------------------------------

/**
 * Itemfactory Class
 *
 * Makes you items : )
 * 
 */
 
// ------------------------------------------------------------------------

class Itemfactory_Model extends Generic_Model
{
	private $_query = array();
	
	private $_id_regexp = '/^(\d+)\.(\d+)\.?/';
	private $_item_id_regexp = '/^(\d+)$/';
	private $_url_regexp = '/^(\/?\w+:)?\/[\w\/\.-]*$/';
	
	private $_loaded = false;
	
	// constructor - used when you want a new query object that can be programatically manipulated
	// - call 'load' to run the query and get results
	function __construct($q = NULL)
    {
    	parent::__construct($q);

    	$this->add($q);
    }
    
    public static function get_keyword_load_db($db = NULL, $with_keyword_query = null)
    {    	
    	$db->select(Itemfactory_Model::config('merged_keyword_fields'));
    	
    	if($with_keyword_query)
    	{
    		$db->from('item, item_link, item_keyword, item_keyword as query_keyword');
    		$db->where('query_keyword.item_id = item.id');
    	}
    	else
    	{
    		$db->from('item, item_link, item_keyword');
    	}

    	$db->where('item_link.item_id = item.id');
    	$db->where('item_keyword.item_id = item.id');
    	$db->where('item.installation_id = '.installation::id());
    	$db->groupby('item_keyword.id');
    	$db->orderby('item_keyword.name');
    	
    	return $db;
    }
    
    public static function get_item_load_db($db, $with_keyword_query = null)
    {    	
    	$db->select(Itemfactory_Model::config('merged_item_fields'));
    	
    	if($with_keyword_query)
    	{
    		$db->from('item, item_link, item_keyword as query_keyword');
    		$db->where('query_keyword.item_id = item.id');
    	}
    	else
    	{
    		$db->from('item, item_link');
    	}
    	
    	$db->where('item_link.item_id = item.id');
    	$db->where('item.installation_id = '.installation::id());
    	$db->groupby('item_link.id');
    	
    	return $db;
    }
   
 	// returns information useful across all of the database queries
    public static function load_config()
    {
    	$config = array(
    	
    		// what fields do we want from the item & item_link merging
    		// important because it is what the result set from the database
    		// and therefore our item objects will contain
    		
    		'merged_item_fields' => array(
    			'concat(item.id,\'.\',item_link.id) as id',
    			'concat(item_link.parent_item_id,\'.\',item_link.parent_link_id) as parent_id',
    			'item_link.system_id as system_id',
    			'item_link.link_type as link_type',
    			'item_link.l as l',
    			'item_link.r as r',
    			'item_link.path as path',
    			'item.name as name',
    			'item.item_type as item_type'
    		),
    			
    			
    		// we will map keywords onto items using the keyword.link_id -> item.id mapping
    			
    		'merged_keyword_fields' => array(
    			'item_keyword.id as id',
    			'concat(item_keyword.item_id,\'.\',item_link.id) as item_id',
    			'item_keyword.keyword_type as keyword_type',
    			'item_keyword.name as name',
    			'item_keyword.value as value',
    			'item_keyword.id_value as id_value',
    			'item_keyword.number_value as number_value',
    			'item_keyword.date_value as date_value',
    			'item_keyword.long_value as long_value'
    		)
    	);
    	
    	return $config;
    }
    
    public static function config($field = NULL)
    {
    	if(empty($field)) { return NULL; }
    	
    	$config = Itemfactory_Model::load_config();
    	
    	return $config[$field];
    }    
    
    // factory - used when you have a simple query and want its results in one line:
    
    // $results = Itemfactory_Model::factory(5656.564)->load() - will return the item with that id
    // $results = ItemFactory_Model::factory('item_link.left > 5 and item_link.left < 8')->load() - will return all items between left and right
   	// 
   	// method chaining:
   	//
   	// $results = Itemfactory_Model::factory()->item_types('folder')->keywords('foldericon')->load();
   
    public static function factory($q)
    {
    	$factory = new Itemfactory_Model($q);
    	
    	return $factory->load();
    }
    
    public static function instance($id, $with_keywords = null)
    {
    	if(!isset($id))
    	{
    		return new Item_Model(null, null);
    	}
 
    	$factory = new Itemfactory_Model($id);

    	if($with_keywords)
    	{
    		$factory->keywords(true);
    	}
    	
    	$ret = $factory->limit(1)->load();
    	
    	if($ret->count()>0)
    	{
    		return $ret->asarray[0];
    	}
    	else
    	{
    		return null;
    	}
    }
    
    private function apply_query_to_db($db, $for_keywords = null)
    {
    	if(!isset($db))
    	{
    		$db = $this->db;
    	}
    	
    	if(isset($this->_query['link_ids']))
    	{    		
    		$sql = $this->get_group_clause('item_link.id', $this->_query['link_ids']);
    		
    		$db->where($sql);
    	}
    	
    	if(isset($this->_query['item_ids']))
    	{    		
    		$sql = $this->get_group_clause('item.id', $this->_query['item_ids']);
    		
    		$db->where($sql);
    	}
    	
    	if(isset($this->_query['item_types']))
    	{
			$sql = $this->get_group_clause('item.item_type', $this->_query['item_types'], true);
    		
    		$db->where($sql);
    	}
    	
    	if(isset($this->_query['clause_additions']))
    	{
    		foreach($this->_query['clause_additions'] as $clause)
    		{
				$db->where($clause);
    		}
    	}
    	
    	// if the query is for keywords or we are doing a URL lookup - then
    	// we will want more than 1 result!
    	if(!$for_keywords && !isset($this->_url_must_match_array))
    	{
    		if(isset($this->_query['limit']))
    		{
	    		$db->limit($this->_query['limit']);
    		}
		}
		    	
    	return $db;
    }
    
    public function limit($mode = NULL)
    {
    	if(isset($mode))
    	{
    		$this->_query['limit'] = $mode;
    	}
    
    	return $this;
    }
    
    // allows you to add a keyword based key, value question to the item load
    // for instance ->keyword_query('uri', '/index.htm')
    // would load only objects that have a keyword with word = 'uri' and value = '/index.htm'
    // if only the word is given then any value will do
    public function keyword_query($word, $value = null)
    {
    	$query_arr = array(
    		'query_keyword.name' => $word
    	);
    	
    	if(!empty($value))
    	{
    		$query_arr['query_keyword.value'] = $value;
    	}
    	
    	$this->add($query_arr);
    	
    	return $this;
    }
    
    public function keyword_date_query($word, $value = null, $function = null)
    {
    	$query_arr = array(
    		'query_keyword.name' => $word
    	);
    	
    	if(!empty($value))
    	{
    		if(!empty($function))
    		{
    			$this->add("$function(query_keyword.date_value) = '$value'");
    		}
    		else
    		{
    			$query_arr['query_keyword.date_value'] = $value;
    		}
    	}
    	
    	$this->add($query_arr);
    	
    	return $this;
    }
    
    public function beneath($item)
    {
    	$left = $item->l;
   		$right = $item->r;
    		
   		$this->add("item_link.l > $left and item_link.r < $right");
   		
   		return $this;
    }
    
    public function keywords($mode = true)
    {
    	if(isset($mode))
    	{
    		$this->_query['keywords'] = $mode;
    	}
    
    	return $this;
    }
    
    public function item_class($mode = 'Item_Model')
    {
    	$this->_query['item_class'] = $mode;
    	
    	return $this;
    }
    
    public function keyword_words($mode = NULL)
    {
    	if(isset($mode))
    	{
    		$this->_query['keywords']['words'] = $mode;
    	}
    
    	return $this;
    }
    
    public function keyword_types($mode = NULL)
    {
    	if(isset($mode))
    	{
    		$this->_query['keywords']['types'] = $mode;
    	}
    
    	return $this;
    }
    
    public function sitemap()
    {
    	$this->tree();
    	$this->_query['sitemap'] = true;
    	
    	return $this;
    }
    
    public function tree()
    {
    	$this->_query['tree'] = true;
    	
    	return $this;
    }
    
    public function system_id($system_id)
    {
    	$this->add(array(
			'item_link.system_id' => $system_id
		));
    	
    	return $this;
    }
    
    public function item_types($mode = NULL)
    {
    	if(isset($mode))
    	{
    		$schema = Schema_Model::instance();
    	
    		$final_types = array();
    	
    		if(is_string($mode))
    		{
	    		$final_types = $schema->get_schemas_that_inherit_from($mode);
    		}
    		else
    		{
	    		foreach($mode as $item_type)
    			{
	    			$foundtypes = $schema->get_schemas_that_inherit_from($item_type);
    			
    				foreach($foundtypes as $foundtype)
    				{
	    				$final_types[] = $foundtype;
    				}
    			}
    		}
    		
    		foreach ($final_types as $final_type)
    		{
    			$this->_query['item_types'][] = $final_type;
    		}
    	}
    
    	return $this;
    }
    
    
    public function load($dev_mode = false)
    {
    	if($this->_loaded)
    	{
    		return $this->_result;
    	}
    	
    	$this->_loaded = true;

    	$db = Itemfactory_Model::get_item_load_db($this->db, $this->_include_keyword_in_query);
    	
    	$this->apply_query_to_db($db);
    	
    	$load_class = 'Item_Model';
    	
    	if(!empty($this->_query['item_class']) && is_string($this->_query['item_class']))
    	{
    		$load_class = $this->_query['item_class'];
    	}

    	$items = $this->load_objects($db->get(), $load_class);
    	
    	//$dev_mode = true;
    	if($dev_mode)
    	{
    		echo $db->last_query();
    		exit;
    	}
    	
    	if(is_array($this->_query['item_class']))
    	{    	
    		$items = $this->remap_item_classes($items, $this->_query['item_class']);
    	}

    	$keyword_config = $this->_query['keywords'];
    	
    	if(isset($keyword_config))
    	{
    		$keyword_db = Itemfactory_Model::get_keyword_load_db($this->db, $this->_include_keyword_in_query);
    		
    		$this->apply_query_to_db($keyword_db, true);
    		
    		if(is_array($keyword_config['words']) || preg_match('/[a-z]/i', $keyword_config['words']))
    		{
    			$sql = $this->get_group_clause('item_keyword.name', $keyword_config['words'], true);
	    		
	    		$keyword_db->where($sql);
    		}
    		
    		if($keyword_config['types'] || preg_match('/[a-z]/', $keyword_config['types']))
    		{
    			$sql = $this->get_group_clause('item_keyword.keyword_type', $keyword_config['types'], true);
	    		
	    		$keyword_db->where($sql);
    		}
    		
			if($keyword_config['group'])
    		{
    			$sql = $this->get_multigroup_clause($keyword_config['group'], true);

	    		$keyword_db->where($sql);
    		}
    		
    		$keywords = $this->load_objects($keyword_db->get());
    	
    		// this is so the problem with paths loading ghosts is worked around for the moment
    		$temp_item_map = array();

    		foreach($items->asarray as $item)
    		{
    			$dbid = $item->database_id();
    			
    			if(!isset($temp_item_map[$dbid]))
    			{
    				$temp_item_map[$dbid] = array();
    			}
    			
    			$temp_item_map[$dbid][] = $item;
    		}
    		
	    	foreach($keywords->asarray as $keyword)
    		{
    			$parts = explode('.', $keyword->item_id);
    			$itemid = $parts[0];
    			
    			foreach($temp_item_map[$itemid] as $placeitem)
    			{
    				//echo $keyword->name.'<p>';
    				
    				$placeitem->add_keyword($keyword);
    			}
    			
    			/*
    			
		    	$item = $items->asmap[$keyword->item_id];
    		
    			if(isset($item))
    			{
	    			$item->add_keyword($keyword);
    			}
    			else
    			{
    				$id_parts = explode('.', $keyword->item_id);
    			
		    		$item = $temp_item_map[$id_parts[0]];
		    		
		    		if(isset($item))
		    		{
		    			$item->add_keyword($keyword);
		    		}
    			}
    			
    			*/
	    	}
    	}
    	
    	if(isset($this->_query['tree']))
    	{
    		$top_items = array();
    	
    		foreach($items->asarray as $item)
    		{
    			$item->leaf = true;
    			
	    		$parent_item = $items->asmap[$item->parent_id];
    		
    			if(isset($parent_item))
    			{  			
    				$parent_item->add_to_children($item);
    			}
    			else
    			{
	    			$top_items[] = $item;
    			}
    		}
    		
    		foreach($items->asarray as $item)
    		{
    			if(count($item->children)>0)
    			{
    				$item->leaf = false;
    			}	
    		}
    	
    		$items->astree = $top_items;
    	}	
    	
    	if(isset($this->_url_must_match_array))
    	{
    		$matching_item = null;
    		
    		foreach($items->astree as $top_item)
    		{    			
    			$matching_item = $this->find_matching_url_item($top_item, $this->_url_must_match_array);
	
    			if(isset($matching_item))
    			{
    				break;	
    			}
    		}

   			$items = $this->get_single_object_result($items, $matching_item);
    	}

    	$this->_result = $items;

    	return $items;
    }
    
    // digs down into the children of the given item to see if there is a matching url path
    private function find_matching_url_item($item, $match_array)
    {
    	//$top_items = $items->astree;
    	
		$arr_test = implode('/', $match_array);
		
    	$must_match = array_shift($match_array);
    	
    	statictools::dev("<br>Checking ---{$item->url}--- vs ---$must_match---");
    	
    	if($must_match != $item->url)
    	{
    		statictools::dev("failed check");
    		return null;	
    	}
    	
    	if(count($match_array)<=0)
    	{
    		statictools::dev("found item");
    		return $item;
    	}
    	
    	foreach($item->children as $child)
    	{
    		$result = $this->find_matching_url_item($child, $match_array);
    		
    		if(isset($result))
    		{
    			return $result;
    		}
    	}
    	
    	return null;
    }
    
    public function last_query()
    {
    	return $this->db->last_query();
    }
    
    public function load_single($dev_mode = false)
    {
    	$result = $this->limit(1)->load($dev_mode);
    	
    	if($result->count()>0)
    	{
			return $result->asarray[0];
    	}
    	else
    	{
    		return null;
    	}
    }
    
	private function get_group_clause($field_name, $field_values, $quote = NULL)
    {
    	if(!is_array($field_values))
    	{
    		$field_values = array($field_values);	
    	}
    	
    	$clause_array = array();
    	
		foreach($field_values as $field_value)
		{
			$qt = '';
    	
    		if($quote)
    		{
    			$qt = '\'';
	    	}
	    	
	    	if(preg_match('/[^\d\.]/', $field_value))
	    	{
	    		$qt = '\'';	
	    	}
    	
			$clause_array[] = "$field_name = $qt$field_value$qt";
		}
		
		$clause = implode(" or ", $clause_array);
			
		$sql = "( $clause )";
		
		return $sql;
    }
    
    private function get_multigroup_clause($field_values, $quote = NULL)
    {
    	$clause_array = array();
    	
		foreach($field_values as $field_name => $field_value)
		{
			$qt = '';
    	
    		if($quote)
    		{
    			$qt = '\'';
	    	}
	    	
	    	if(preg_match('/[^\d\.]/', $field_value))
	    	{
	    		$qt = '\'';	
	    	}
    	
			$clause_array[] = "$field_name = $qt$field_value$qt";
		}
		
		$clause = implode(" or ", $clause_array);
			
		$sql = "( $clause )";
		
		return $sql;
    }

	// this adds the given query data to the current query
	//
	// query data can be:
	//
	//	string
	//		id (5665.56)
	//
	//		id_array_string (3454.4545:34534.4434:2322.344)
	//
	//		path (/folder1/folder2)
	//
	//		clause (item.item_type = 'folder')
	//
	//	float
	//		id (6456.454)
	//
	//	array
	//		id array (4455.454, 343.6345, 343.44) or (4545, 45454, 3343)
	//
	//	hash
	//		clauses (item.item_type = 'folder', item_link.left > 4)
	//
	    
    public function add($q)
    {
    	$db = $this->db;
    	
    	if(is_object($q))
    	{
    		$this->db = $q;
    		$db = $q;
    	}
    	else if($this->is_id($q))
    	{
    		$this->add_id($q);
    	}
    	else if($this->is_url($q))
    	{
    		$this->item_url_is($q);	
    	}
    	else if(is_string($q))
    	{
			if(preg_match('/^[\d\.]+:[\d\.]+/', $q))
    		{
    			$id_array = explode(':', $q);
    			
    			foreach($id_array as $id)
    			{
    				$this->add_id($id);
    			}
    		}
    		else
    		{
    			$this->add_clause($q);
    		}
    	}
    	else if(is_array($q))
    	{
			$test_elem = $q[0];
			
			if($this->is_id($test_elem))
			{
				foreach($q as $id)
				{
    				$this->add_id($id);
    			}
    		}
    		else
    		{
    			$this->add_clause($q);
    		}
    	}
    }
    
    private function is_url($url)
    {
    	if(is_array($url))
    	{
    		return false;
    	}
    	
    	if(preg_match($this->_url_regexp, $url)) { return true; }	
    	
    	return false;
    }
    
    private function is_id($id)
    {
    	if(is_array($id))
    	{
    		return false;
    	}
    	
    	if(preg_match($this->_id_regexp, $id)) { return true; }	
    	if(preg_match($this->_item_id_regexp, $id)) { return true; }
    	
    	return false;
    }
    
    private function add_clause($clause)
    {
    	if(is_array($clause))
    	{
    		foreach($clause as $field => $value)
    		{
    			if(preg_match('/query_keyword\./i', $field))
    			{
					$this->_include_keyword_in_query = true;
    			}
    		}	
    	}
    	else if(is_string($clause))
    	{
    		if(preg_match('/query_keyword\./i', $clause))
    		{
				$this->_include_keyword_in_query = true;
    		}
    	}
    	
    	$this->_query['clause_additions'][] = $clause;
    }
    
    // loads a single item that has the given url
    // it does this by loading each item that matches a part of the url
    // and then searching the tree to see if we have a hit
    private function item_url_is($url)
    {
    	$sql = $this->get_url_clause($url);

    	$this->add($sql);

    	//$this->keyword_words('url');
    	
    	$this->tree();
    }
    
    private function get_url_clause($url)
    {
    	$url = strtolower($url);
    	
    	$url = preg_replace('/\/$/', '', $url);
    	$url = preg_replace('/^\//', '', $url);
    	
		//$sql = "( query_keyword.name = 'path' and ( query_keyword.value = '$url'  or query_keyword.value = '$url/' or query_keyword.value = '/$url' or query_keyword.value = '/$url/' ) )";
		$sql = "( item_link.path = '$url'  or item_link.path = '$url/' or item_link.path = '/$url' or item_link.path = '/$url/' )";

		return $sql;
    	
    	/*
    	if(preg_match('/^\//', $url))
    	{
    		$url = 'disk:'.$url;	
    	}
    	
    	$parts = explode('/', $url);
    	$query_arr = array();
    	
    	$match_array = array();
    	
    	foreach ($parts as $part)
    	{
    		if(!preg_match('/\w/', $part)) { continue; }
    		// this part is a system object (like models, users or disk)
    		if(preg_match('/\w+:$/', $part)) 
    		{
    			$part .= '/';
    		}
    		
    		$match_array[] = $part;
    		
    		$query_arr[] = " ( query_keyword.name = 'url' and query_keyword.value = '$part' ) ";
    	}
    	
    	$this->_url_must_match_array = $match_array;
    	
    	$sql = '( '.implode(' or ', $query_arr).' )';
    	*/
    }
    
    private function add_id($id)
    {
    	if(preg_match($this->_id_regexp, $id, $matches))
    	{
    		$this->_query['link_ids'][] = $matches[2];
    	}
    	else if(preg_match($this->_item_id_regexp, $id, $matches))
    	{
    		$this->_query['item_ids'][] = $matches[1];
    	}
    }
    
    private function isAssoc($arr)
	{
	    return array_keys($arr) != range(0, count($arr) - 1);
	}
	
	private function remap_item_classes($items, $class_map)
	{
		$index_property = $this->last_index_property;
		
		$newarray = array();
		$newmap = array();
		$newasmaparray = array();

    	foreach($items->asarray as $item)
    	{
    		if(!isset($class_map[$item->item_type])) { continue; }
    		
    		$newitem = $this->changeClass($item, $class_map[$item->item_type]);
    		
    		$index = $newitem->$index_property;
    		$newarray[] = $newitem;
    		$newmap[$index] = $newitem;
    		
			$existing = $newasmaparray[$index];
			
			if(!isset($existing))
			{
				$existing = array();
			}
			
			$existing[] = $newitem;
			
			$newasmaparray[$index] = $existing;
    	}
    	
    	$items->asarray = $newarray;
    	$items->asmap = $newmap;
    	$items->asmaparray = $newasmaparray;
   
    	return $items;
	}
	
	private function changeClass($obj, $newClass)
	{
		$obj = unserialize(preg_replace // change object into type $new_class
        	(       "/^O:[0-9]+:\"[^\"]+\":/i", 
                	"O:".strlen($newClass).":\"".$newClass."\":", 
                	serialize($obj)
        	));
        	
		return $obj;
	}
}