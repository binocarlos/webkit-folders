<?php

// ------------------------------------------------------------------------

/**
 * Query Model Class
 *
 * 	provides a mechanism for loading items from the database
 *
 *	you can add customized methods here for use from within different pages on the site
 *
 *	the general pattern of usage for this module is as follows:
 *
 *	1) 	you decide what type of items you want to include on the page - 
 *		for instance news_release and news_release_collection
 *		also it could be an item with a particular name or id
 *
 *	2)	you write a helper method in this class which you will call from the php page
 *		for instance loadWidgetGuideCollection - this can take whatever parameters you need to build a query
 *		e.g. the loadWidgetGuideCollection method could accept the name of the widget guide you want to load
 *		this could then be passed to it from the php page using this method
 *
 *	3)	your helper method then needs to construct a quasi-sql statement which will load the items you want
 *		using the example above:
 *
 *			select * from 
 *			widget_guide_collection, widget_guide, widget_guide_step
 *			where 
 *				widget_guide_collection.name = $name
 *
 *	4)	your helper method then calls loadItems providing the quasi-sql you constructed
 *
 *	5)  the processor will then call buildItemTree - this will assign items to their parent item's children array
 *		and return an array of top-level items (i.e. items that had no parent)
 *		NOTE - the top level items dosn't mean that they live at the very top of the tree - just that no parent
 *		was found in the current results - this is offers the flexibility to load items you know are related to each
 *		other in the tree but are buried down somewhere in amoung folders
 *
 *	6) 	you then have access to the item_array, item_map and item_by_type_map to access the items you have loaded
 *		if you have called buildItemTree then each item will have a 'children' property that is an array
 *		of its child items
 *
 *	7)	how you then choose to print this data back with the php page is up to you - each of the values given
 *		to an item from its keywords will be as useful as possible (for example an image value will be a flat url to the image)
 *		and a data value will be a nice formatted string
 *
 *
 */
 
// ------------------------------------------------------------------------

class Query_Model extends Generic_Model
{
	//////////////////////////////////////////////////////////////////////
	// This is a field map for the item and keyword table
	// this is important until we get a framework with some ORM on the go
	// IMPORTANT - if you change the database tables you MUST change this!!!
    public $item_fields = array("id", "item_id", "item_type", "name");
  	public $keyword_fields = array("id", "item_id", "keyword_type", "field_type", "name", "value", "number_value", "date_value", "long_value");
  	
  	private $log_entries = array();
  	
  	//////////////////////////////////////////////////////////////////////
	// a map that tells us what ids we will interpret as the root item
  	private $root_ids = array(
		'root' => TRUE,
		'/' => TRUE,
		'0' => TRUE
	);

	//////////////////////////////////////////////////////////////////////
	// a map that holds an array of items indexed by their item_type
	public $item_by_type_array = array();
	
	//////////////////////////////////////////////////////////////////////
	// a map that holds items indexed by their id
	public $item_map = array();
	
	//////////////////////////////////////////////////////////////////////
	// an array of all items loaded
	public $item_array = array();
	
	//////////////////////////////////////////////////////////////////////
	// an array of items that have no parent
	public $top_item_array = array();
	
	//////////////////////////////////////////////////////////////////////
	// holding place for the clause function - the function that tells you
	// if an items matches the current clause
	public $where_function = NULL;
	
	//////////////////////////////////////////////////////////////////////
	// constructor
    function __construct()
    {
        parent::__construct();
        
        $this->parser = new Sqlparser;
        $this->schema = & Schema_Model::instance();
    }
	
	//////////////////////////////////////////////////////////////////////
	// gives you the where clause for the item select based on the query
	public function build_item_clause()
	{
		if(empty($this->select))
		{
			$this->error = 'The select portion of your query is empty';
			return FALSE;
		}
		
		//////////////////////////////////////////////////////////////////////
		// first lets get the item types we want from the select statement
		// if it is a 'select *' then we will ommit an item.item_type = clause
		$item_types = explode(",", $this->select);
    	$item_type_clause_parts = array();
    	
    	foreach($item_types as $item_type)
    	{
    		if($item_type != '*')
    		{
    			$item_type = preg_replace('/\W/', '', $item_type);
    			
    			if(!$this->schema->has_schema($item_type))
    			{
    				$this->error = 'The select portion of your query contains an invalid item_type: '.$item_type;
    				return FALSE;	
    			}
    			
    			$item_type_clause_parts[] = "item.item_type = '$item_type'";
    		}
    	}
    	
    	//////////////////////////////////////////////////////////////////////
		// now we have the item_types - lets build an sql chunk for it
		// (item.item_type = 'news') or (item.item_type = 'folder')
    	$item_clause = '';
    	$item_clause_parts = array("( item.installation_id = ".installation::id()." )");
    	
    	if(count($item_type_clause_parts)>0)
    	{
    		$item_type_clause = implode(' or ', $item_type_clause_parts);
    		
    		$item_clause_parts[] = '('.$item_type_clause.')';
    	}
    	
    	//////////////////////////////////////////////////////////////////////
		// now lets see if we have a from part to the statement
		// if we do it dictates what item we want to select from
		// if the from is a path then we need to do tree algorithms
		// if the from is an id (or 'root') then we need to add a item.item_id to the item clause
		
		if(!empty($this->from))
		{
			// are we asking for the root folder?
			if(isset($this->root_ids[$this->from]))
			{
				$item_clause_parts[] = 'item.item_id IS NULL';
			}
			else
			{
				// have we got a path as an id (e.g. /news/current)
				if(preg_match('/^\//', $this->from))
				{
				
				}
				// no we have an id instead (e.g. 4353)
				else
				{
					if(!preg_match('/^\d+$/', $this->from))
					{
						$this->error = 'Invalid id specified in your from statement: '.$this->from;
						return FALSE;
					}
					else
					{
						$item_clause_parts[] = 'item.item_id = '.$this->from;
					}
				}
			}
		}
    	
    	$this->item_clause = implode(' and ', $item_clause_parts);
    	
    	return TRUE;
	}
    
    // --------------------------------------------------------------------

	/**
	* 	
	*	The main query parser
	*
	*	This function is responsible for interpreting the query given to it
	*	and loading the items that match
	*
	*	A query is given using a very basic, quasi SQL
	*
	*	The table names (i.e. in the FROM portion of the query)
	*	are actually item_types - e.g.
	*
	*		select * from widget_guide
	*
	*	translates into
	*
	*		select * from item where item_type = 'widget_guide'
	*
	*	you can give multiple table names in order to build a collection, e.g.
	*
	*		select * from news_release, news_release_collection
	*
	*	this would grab all items where item_type = 'news_release' or 'news_release_collection'
	*
	*	TO COME (features the query parser will be able to deal with):
	*
	*		- 	column selector - select image, date, title from news_release
	*			this allows you to choose specific fields from items rather than everything (select *)
	*
	*		-	where clauses - select * from news_release where YEAR(date)=2009
	*			this allows you to choose only items whos keyword values match the given clause
	*
	*		-	order by clauses - select * from news_release order by date DESC
	*			this allows you to define in what order things should come (at the moment this is done internally)
	*
	*	IMPORTANT - for the moment the query parser just knows how to give you items of a specific type
	*	so only queries like
	*
	*		select * from widget_guide,widget_guide_collection
	*
	*	will work!
	*
	*
	*/

    public function loadItems($sql_query = NULL)
    {
    	//////////////////////////////////////////////////////////////////////
    	// we don't really want to allow every single item to be selected?
    	if(!isset($sql_query))
    	{
    		$this->error = 'There was no query supplied to loadItems';
    		return FALSE;
    	}
    	
    	$this->add_log("parsing sql:<p>$sql_query");
    	
    	//////////////////////////////////////////////////////////////////////
    	// the sql parser is created ready to look through the quasi-sql and determine what real sql we need to make
    	$this->parser->ParseString($sql_query);
    	
    	//////////////////////////////////////////////////////////////////////
    	// has the parser found a problem with the statement?
    	if($this->parser->hasError())
    	{
    		$this->error = $this->parser->getError();
    		return FALSE;
    	}

    	//////////////////////////////////////////////////////////////////////
    	// now we interrogate $parser to see what item_types we are after
    	// we then use those types to construct the following style statement:
    	//
    	//		select *
    	//		from '/news'
    	//		where 
    	//			item.item_type = 'news_release' 
    	//			or
    	//			item.item_type = 'news_release_collection'
    	//
    	
    	$this->select = $this->parser->get('select');
    	$this->from = $this->parser->get('from');
    	
    	//////////////////////////////////////////////////////////////////////
    	// now we build up an item clause based on the query
    	// the main things are - what type of item are we after (the select) and
    	// where are the items from (the from)
    	//
    	// this translates onto item.item_type = ? and item.item_id = ?
    	
    	// if we have an error here it is because get_item_clause found a problem with the sql
    	if(!$this->build_item_clause())
    	{
    		return FALSE;
    	}
    	
    	$item_clause = $this->item_clause;
    	
    	$this->db->from('item');
    	$this->db->orderby('name');
    	
    	if(!empty($item_clause))
    	{
    		$this->db->where($item_clause);
    	}
    	
    	$items = $this->load_objects($this->db->get(), 'Item_Model');

    	$top_item_array = array();

    	//////////////////////////////////////////////////////////////////////
    	// now we need to craft an SQL statement to load all keywords that are used by the items loaded above
    	// at the moment this is as simple as load keywords for items of a specific type
    	// but as the parser gets more features this is where the heavy lifting will be done
    	//
    	// NOTE - we only load keyword_type = 'field' keywords because those are the ones
    	// that will be used to fill in item values
    	
    	$keyword_clause = <<<EOT
    	
item_keyword.item_id = item.id
and
item_keyword.keyword_type = 'field'
    	
EOT;
    	
    	
    	$this->db->select('item_keyword.*');
    	$this->db->from('item, item_keyword');
    	$this->db->where($keyword_clause);
    	
    	if(!empty($item_clause))
    	{
    		$this->db->where($item_clause);
    	}
    	
    	$this->db->groupby('item_keyword.id');
    	
    	$keywords = $this->load_objects($this->db->get());
    	
    	//////////////////////////////////////////////////////////////////////
    	// now we loop through each of the item rows, first creating an array of its basic values
    	// and then assigning the correct property to the corresponding item
    	
    	foreach ($keywords->asarray as $keyword)
    	{
    		//////////////////////////////////////////////////////////////////////
    		// lets get the corresponding item for this keyword
    		
    		$item = $items->asmap[$keyword->item_id];
    		
    		if(isset($item))
    		{
    			$item->add_keyword($keyword, TRUE);	
    		}
		}
		
		//////////////////////////////////////////////////////////////////////
    	// This loops through each item and tries to fetch its parent from the item_map
		// If a parent is found, the item is added to its 'children' property (an array)

		foreach($items->asmap as $item_id => $item)
		{
			$parent_item = $items->asmap[$item->item_id];
			
			if(isset($parent_item))
			{
				$parent_item->add_child($item);
			}
			else
			{
				if(isset($item->id))
				{
					$top_item_array[] = $item;
				}
			}
		}

		//////////////////////////////////////////////////////////////////////
    	// now we have some clauses to check with which we will filter what items are allowed
    	// into the index - we will loop though the top level items from the tree and recursivley check
    	// each items and its children - this way we can ensure only items within the tree of selected items are included
    	
    	$where_statement = $this->parser->get('where');
    	
    	$this->create_where_function($where_statement);
    	
		foreach ($top_item_array as $top_item)
		{
			if($this->does_item_match_clause($top_item) == TRUE)
			{		
				$this->top_item_array[] = $top_item;
			}
		}
		
		return $this->top_item_array;
    }
    
    // --------------------------------------------------------------------

	/**
	* 	
	*	This inserts a fully selected item into the internal maps for later reference
	*	It will call the same for each of the children recursivly
	*
	*/
    
    public function insert_item($item)
    {
    	$this->item_array[] = $item;
    	$this->item_map[] = $item;
    	$this->item_by_type_array[$item->item_type][] = $item;
    }
    
    // --------------------------------------------------------------------

	/**
	* 	
	*	This examines the clause of the current statement and checks the given item
	*	to see if it lines up with the required values
	*
	*	It then checks this items children recursivly for the same match
	*	The following rules are used to determine a match or not
	*
	*		- 	If there is no clause then every item loaded will match
	*
	*		- 	If there is a clause - it is first checked againsts the item
	*			if this fails the children won't be checked because this item
	*			has taken itself and all of its children out of play
	*
	*		-	If an item passes the clause but does not have children - it matches straight away
	*
	*		-	If an item passes the clause and has children - each child is checked recursivly
	*
	*			If ONE child passes the clause (i.e. it should be included) - then this item is also included
	*			
	*			If NO children pass that clause (even though this one did) - the item will NOT be included
	*			This means that only objects that either match the clause will show up (in context of their tree)
	*/
	
	public static function get_where_compare_value($value = NULL)
	{
		if(!isset($value))
		{
			return NULL;
		}
		
		$value = preg_replace("/\s/", "", $value);
		$value = strtolower($value);
		
		return $value;	
	}
	
	public static function get_where_compare_value_year($value = NULL)
	{
		if(!isset($value))
		{
			return NULL;
		}
		
		if(preg_match("/(\d+)\/(\d+)\/(\d+)/", $value, $date_matches))
		{
			return $date_matches[3];	
		}
		
		return $value;
	}
	
    public function does_item_match_clause($item)
    {
    	$does_match = TRUE;
    	
    	$function_text = $this->where_function;
    	
    	if(isset($function_text))
    	{
    		$does_eval_match = 0;
    		
    		$eval_string = '$does_eval_match = '.$function_text.';';
    		
    		eval($eval_string);
    		
    		if($does_eval_match != 1)
    		{
    			$does_match = FALSE;
    		}
    	}
    	
    	// This item has failed the match and so we can return false straight away and not check its children
    	if($does_match == FALSE)
    	{
    		return FALSE;
    	}
    	
    	$children = $item->children;

    	// There are no children so we can just return this items match result straight away 
    	// (could be TRUE or FALSE)
    	if(count($children)<=0)
    	{
    		if($does_match == TRUE)
    		{
    			$this->insert_item($item);
    		}
    			
    		return $does_match;	
    	}

    	$child_match = FALSE;
    	
    	$new_children = array();
    	
   		foreach($children as $child)
   		{
   			if($this->does_item_match_clause($child))
   			{
   				$new_children[] = $child;
   				$child_match = TRUE;		
   			}
   		}
   		
   		if($child_match == FALSE)
   		{
   			return FALSE;
   		}
   		
   		$item->children = $new_children;
   		
   		$this->insert_item($item);
   		
   		return TRUE;   		
    }
    
   
    // needs to be a reference to this string cos we are replacing it!
	public function create_where_function($where)
	{
		if(isset($this->where_function)) { return; }
		
		if(isset($where))
		{
			$where = preg_replace("/\s+$/", "", $where);
			
			if(preg_match("/;$/", $where)<=0)
			{
				$where .= ';';
			}
		}

		if(preg_match_all("/[^\w\.]?([\(\)\w\.]+)\s*([^\s]+)\s*([\w\'\"].*?)(\)|\Wor\W|\Wand\W|;)/i", $where, $matches, PREG_SET_ORDER)>0)
    	{
    		foreach($matches as $match)
    		{
    			$token = $match[0];
    			
    			$token = preg_replace("/(\)|\Wor\W|\Wand\W|;)$/", "", $token);
		
    			$replace_token = $token;
    		
    			$field_token = $match[1];
    			$operator = $match[2];
				$value_token = $match[3];
				
				$field_parser_function = 'get_where_compare_value';
				$value_parser_function = 'get_where_compare_value';
			
				$value_token = preg_replace("/\s+$/", "", $value_token);
			
				$field = $field_token;
				$table = NULL;
			
				$replace_field = NULL;
				$replace_value = NULL;
				
				if(preg_match("/^(\w+)\(([\w\.]+)\)/", $field, $field_matches))
				{
					$field_parser_function .= '_'.$field_matches[1];
					$field = $field_matches[2];
				}
			
				if(preg_match("/(\w+)\.(\w+)/", $field, $field_matches))
				{
					$field = $field_matches[2];
					$table = $field_matches[1];
				}
				
				$replace_value = $value_token;

				// no quotes in the value being checked so it points to a property of the item
				if((preg_match("/[\'\"]/", $value_token)<=0)&&(preg_match("/^[\d\.]+$/", $value_token)<=0))
				{
					$replace_value = ' $item->$value_token ';
				}
				
				$replace_value = ' Query_Model::'.$value_parser_function.'('.$replace_value.') ';
				$replace_token = str_replace($value_token, $replace_value, $replace_token);			
				
				$replace_field = ' Query_Model::'.$field_parser_function.'($item->'.$field.') ';
				$replace_token = str_replace($field_token, $replace_field, $replace_token);
				
				// THIS IS HOW YOU APPLY A QUERY TO ONLY ONE TYPE OF ITEM
				// i.e query AND item_type = 'xxx'
				//
				// this means this particular part of the query dosn't apply to this item_type
				// so we should equate this to true

				if(isset($table))
				{
					$replace_token = ' ( ( $item->item_type != \''.$table.'\' ) or ( '.$replace_token.' ) ) ';
				}
				else
				{
					$replace_token = ' ( '.$replace_token.' ) ';
				}
			
				$where = str_replace($token, $replace_token, $where);
			}
			
			$where = preg_replace('/\Wand\W/i', ' && ', $where);
    		$where = preg_replace('/\Wor\W/i', ' || ', $where);
    		$where = preg_replace('/[^!]=/', '==', $where);
    		$where = preg_replace('/\Wis null\W/i', ' == NULL ', $where);
    		$where = preg_replace('/\s*;\s*$/', '', $where);
    	}

    	$this->where_function = $where;
	}    
    
	// --------------------------------------------------------------------

	/**
	* 	
	*
	*/
	
  


	// --------------------------------------------------------------------

	/**
	* 	Helper functions
	*
	*	these are the custom sorting functions that are used when loading items
	*
	*/
	
	public static function cmp_order($itema, $itemb)
	{		
		$a = $itema->order;
		$b = $itemb->order;
		
    	if ($a == $b) {
        	return 0;
    	}
    	return ($a < $b) ? -1 : 1;
	}
	
	public static function cmp_date_field($itema, $itemb)
	{
		$datea = Query_Model::normalize_date($itema->date, TRUE);
		$dateb = Query_Model::normalize_date($itemb->date, TRUE);
		
    	if ($datea == $dateb) {
        	return 0;
    	}
    	return ($dateb < $datea) ? -1 : 1;
	}
	
	public static function cmp_number_name($itema, $itemb)
	{		
		$a = intval($itema->name);
		$b = intval($itemb->name);
		
    	if ($a == $b) {
        	return 0;
    	}
    	return ($a < $b) ? -1 : 1;
	}	
	
	public static function normalize_date($date_st, $should_reverse = NULL)
	{
		if(preg_match("/(\d+)\/(\d+)\/(\d+)/", $date_st, $matches))
		{
			$day = $matches[1];	
			$month = $matches[2];
			$year = $matches[3];
				
			if(strlen($day)==1)
			{
				$day = '0'.$day;
			}
				
			if(strlen($month)==1)
			{
				$month = '0'.$month;
			}
			
			if($should_reverse)
			{
				$date_st = $year.'/'.$month.'/'.$day;
			}
			else
			{
				$date_st = $day.'/'.$month.'/'.$year;
			}
			
			return $date_st;
		}
		
		return '';
	}
	
	public function add_log($message)
	{
		$this->log_entries[] = $message;
	}
	
	public function get_log()
	{
		$ret = implode("\n<hr>\n", $this->log_entries);
		
		return $ret;	
	}
	
	
}

?>