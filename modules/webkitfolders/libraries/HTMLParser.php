<?php defined('SYSPATH') OR die('No direct access allowed.');

// HTMLParser - a library able to load a page and replace
// tags with values

class HTMLParser
{
	private $content = '';
	private $output = '';
	private $item_definitions = array();
	
	private $tag = 'xtreme';
	
	// these are the actual items that can be created
	// everything else is a property of those things
	private $item_types = array(
		'textarea' => 1,
		'newsrelease' => 1
	);
	
	private $ignore_tags = array(
		'CDATA' => true,
		'if' => true,
		'endif' => true
	);
	
	// these are fields of an item that should be run as methods on the item rather than fetching fields
	private $method_fields = array(
		'database_id' => true
	);
	
	private $parent_cache = array();
	private $item_cache = array();
		
	public function __construct($htmlcontent, $params)
	{
		if(empty($htmlcontent))
		{
			throw new Kohana_User_Exception("No HTML Given", "There is no content for this page");
		}
		
		$this->content = $htmlcontent;
		$this->params = $params;
		$this->schema = Schema_Model::instance();
	}
	
	public function get_item_definitions()
	{
		$item_definitions = new StdClass();
		
		$this->process($this->content);
		
		return $this->root_data->children;
	}
	
	// renders the page back to the browser - is passed a reference to the database
	// page object that holds the data
	public function render_item($item, $template_content = null)
	{		
		//$this->template_content = $content;
		
		if($item)
		{
			$children = $item->load_children_with_keywords(true);
			$item->load_path(true);
		}
		
		$html = $this->process($this->content, $item);

		return $html;
	}
	
	private function get_arguments($string)
	{
		$string = ' '.$string;
		
		$args_array = array();
		
		$args_regexp = '/\s([\w\.]+)\s*?([!=><]+)\s?[\"\'](.*?)[\"\']/is';
		$args_regexp2 = '/\s([\w\.]+)\s*?([!=><]+)\s?(\w+)/is';
		$args_regexp3 = '/\s([\w\.]+)\s?/is';

	 	if(preg_match_all($args_regexp, $string, $args_matches, PREG_SET_ORDER))
	 	{
	 		foreach($args_matches as $args_match)
	 		{
	 			$key = $args_match[1];
	 			$operator = $args_match[2];
	 			$value = $args_match[3];
	 			
	 			if($operator != '=')
	 			{
	 				$value = $operator.$value;
	 			}
	 			
	 			if(!isset($args_array[$key]))
	 			{
	 				$args_array[$key] = $value;
	 			}
	 			else
	 			{
	 				$args_array[$key] = array($args_array[$key]);
	 				
	 				$args_array[$key][] = $value;
	 			}
	 		}
	 	}
	 	else if(preg_match_all($args_regexp2, $string, $args_matches, PREG_SET_ORDER))
	 	{
			foreach($args_matches as $args_match)
			{
				$key = $args_match[1];
				$operator = $args_match[2];
				$value = $args_match[3];
				
				if($operator != '=')
	 			{
	 				$value = $operator.$value;
	 			}
	 					
				if(!isset($args_array[$key]))
				{
					$args_array[$key] = $value;
				}
				else
	 			{
	 				$args_array[$key] = array($args_array[$key]);
	 				
	 				$args_array[$key][] = $value;
	 			}
			}
		}
		else if(preg_match_all($args_regexp3, $string, $args_matches, PREG_SET_ORDER))
	 	{
			foreach($args_matches as $args_match)
			{
				$key = $args_match[1];
	 					
				if(!isset($args_array[$key]))
				{
					$args_array[$key] = true;
				}
				else
	 			{
	 				$args_array[$key] = array($args_array[$key]);
	 				
	 				$args_array[$key][] = $value;
	 			}
			}
		}
		
		foreach($args_array as $prop => $value)
		{
			$val = $this->get_argument_value($value);
			
			if(strrpos($val, '<or>')>0)
			{
				$val = explode('<or>', $val);
			}
			
			$args_array[$prop] = $val;
		}

		return $args_array;
	}
	
	private function get_tag()
	{
		$ret = 'xara';
		
		if(!empty($_SERVER['webkitfolders_tag']))
		{
			$ret = $_SERVER['webkitfolders_tag'];
		}
		
		return $ret;
	}
	
	private function parse_component_data($info)
	{
		$data = new StdClass();
		$data->fields = array();
		$data->children = array();
		$data->arguments = $this->get_arguments($info['args']);
		$data->html = $info['html'];
		$data->template = $info['template'];
		$data->tagname = $info['name'];
		
		//$global_tag = $this->get_tag();
		
		//$info['name'] = preg_replace('/^'.$global_tag.'\./', '', $info['name']);
		
		if(preg_match('/^(\w+)\.(\w+)\.?(\w+)?$/', $info['name'], $match))
		{
			$data->item_type = $match[1];
			$data->field = $match[2];
			$data->field_property = $match[3];
		}
		else
		{
			$data->item_type = $info['name'];
		}
		
		return $data;
	}

	// returns an array of tag information as an array
	// the info is ordered in the order that the tags are discovered in the page
	//
	// the parsing happens in 3 steps
	//
	// STEP 1 - get a list of the tags that are present so we decide which tags to do first
	//
	// STEP 2 - replace <xtreme xtreme type="newsrelease"> with <xtreme_newsrelease
	//
	// STEP 3 - do the grouping of info so things have parent and child
	private function process($parent_data, $item = null)
	{ 		
		$flag = false;
		
		// if the data is a string then its the original template
		if(is_string($parent_data))
		{
			$this->root_data = new StdClass();
			$this->root_data->children = array();
			$this->root_data->item_types_parsed_map = array();
			$this->root_data->template = $parent_data;
			
			$parent_data = $this->root_data;	
		}
		else
		{
			$flag = true;
		}
		
		$html = $parent_data->template;
		
		$match_data_array = array();
		
		$component_match = ''
		.'/'
		.	'[\[\{]'.$this->get_tag().'\.([\w\.]+)\s?([^\[\{\]\}]+)?[\]\}](.*?)'
		.	'[\[\{]\/'.$this->get_tag().'\.\\1[\]\}]'
		.'/is';
		
		$field_match = ''
		.'/'
		.	'[\[\{]'.$this->get_tag().'\.([\w\.]+)\s?([^\]\}]+)?\\s?\/[\]\}]'
		.'/is';
		
		if(preg_match_all($component_match, $html, $matches, PREG_SET_ORDER))
	 	{
	 		foreach($matches as $match)
	 		{
	 			$data = $this->parse_component_data(array(
	 				'html' => $match[0],
	 				'name' => $match[1],
	 				'args' => $match[2],
	 				'template' => $match[3]
	 			));

	 			if($this->ignore_tags[$data->item_type]){ continue; }
	 			
	 			$match_data_array[] = $data;
	 		}
	 	}
	 	
		if(preg_match_all($field_match, $html, $matches, PREG_SET_ORDER))
	 	{
	 		foreach($matches as $match)
	 		{
	 			$data = $this->parse_component_data(array(
	 				'html' => $match[0],
	 				'name' => $match[1],
	 				'args' => $match[2],
	 				'template' => ''
	 			));
	 			
	 			if($this->ignore_tags[$data->item_type]){ continue; }	 	
	 			
	 			$match_data_array[] = $data;
	 		}
	 	}
	 	
	 	$tag_count = 0;
	 	$found_content_count = 0;
	 	
	 	
	 	
	 	foreach($match_data_array as $data)
	 	{	
	 		$process_tag = true;
	 		
	 		// do we have limits on which tags we should process?
	 		if($parent_data->item_type == 'tag_filter')
	 		{
	 			if(!empty($parent_data->arguments['limit']))
	 			{
	 				if($tag_count >= $parent_data->arguments['limit'])
	 				{
		 				$process_tag = false;	
	 				}
	 			}
	 			
	 			if(!empty($parent_data->arguments['content_limit']))
	 			{
	 				if($found_content_count >= $parent_data->arguments['content_limit'])
	 				{
		 				$process_tag = false;	
	 				}
	 			}
	 		}
	 		
	 		$replace_html = '';
	 		
	 		if($process_tag)
	 		{
	 			$replace_html = $this->process_hit($html, $parent_data, $data, $item);
	 		}
	 		
	 		$tag_count++;
	 		
	 		if(!empty($replace_html))
	 		{
	 			$found_content_count++;
	 		}
	 		
	 		$html = str_replace($data->html, $replace_html, $html);
		}
		
		return $html;
	}
	
	function get_argument_value($value)
	{
		if(preg_match('/^params\.(\w+)( or )?(\w+)?$/', $value, $match))
		{
			$prop = $match[1];
			$default = $match[3];
			
			$value = $this->params[$prop];
			
			if(empty($value))
			{
				$value = $default;
			}
		}
		
		if(preg_match('/_params\.(\w+)( or )?(\w+)?_/', $value, $match))
		{
			$prop = $match[1];
			$default = $match[3];
			
			$replace = $this->params[$prop];
			
			$value = str_replace($match[0], $replace, $value);
			
			if(empty($value))
			{
				$value = $default;
			}
		}		
		
		return $value;
	}
	
	function resolve_item_type($type)
	{
		if(preg_match('/^children_(.*)$/', $type, $matches))
	 	{
	 		$type = $matches[1];
		}
		
		return $type;
	}
	
	function process_hit($html, $parent_data, $data, $item = null)
	{
		$replace_html = '';
				
	 	$restore_item = $item;
	 	
	 	$source = $this->get_argument_value($data->arguments['source']);
	 	$item_source = $this->get_argument_value($data->arguments['item']);
	 	$item_beneath = $this->get_argument_value($data->arguments['beneath']);
	 	
	 	$source_or_item_mode = 'source';

	 	$with_subitems = true;
	 	$with_children_arg = strtolower($data->arguments['with_children']);
	 	
	 	$force_children_arg = strtolower($data->arguments['force_children']);
	 	
	 	// do we want to not load all children recursively?
	 	if($with_children_arg == 'no' || $with_children_arg == 'false')
	 	{
	 		$with_subitems = false;
	 	}
	 		
	 	// this loads the current item based on the source that is given
	 	// from the tag
	 	if(!empty($source))
	 	{
	 		$item = new Item_Model($source, true);
	 				
	 		$this->load_child_cache = $item->load_children_with_keywords($with_subitems);	
	 	}
	 	else if(!empty($item_source))
	 	{
	 		if(preg_match('/^:/', $item_source))
	 		{
	 			if($item_source==':tutorials_search')
	 			{
	 				$item = $this->run_tutorials_search();
	 				$source_or_item_mode = 'item';
	 			}
	 		}
	 		else
	 		{
		 		$item = new Item_Model($item_source, true);
	
		 		if($item->item_type==$data->item_type)
		 		{
		 			$this->load_child_cache = $item->load_children_with_keywords($with_subitems);
		 			
		 			$source_or_item_mode = 'item';
		 		}
		 		else
		 		{
		 			$item = null;
		 		}
		 	}
	 	}
	 	else if(!empty($item_beneath))
	 	{
	 		$item = new Item_Model($item_beneath, true);

 			$this->load_child_cache = $item->load_children_with_keywords(true);
	 			
 			$source_or_item_mode = 'beneath';
	 	}

		$item_type = $this->resolve_item_type($data->item_type);
		
	 	// So we have found a tag that points to an item type
	 	if($this->schema->has_schema($item_type))
	 	{			 			 				 			 		
 			// this means we have found a component inside a component
 			// the component is a new type compared to its container
 			// also - this is a CONTAINER not a FIELD
 			$shouldtraversechildren = false;
 			
 			// item type is different
 			if(($data->item_type != $parent_data->item_type)&&(empty($data->field)))
 			{
 				$shouldtraversechildren = true;
 			}
 			
 			if($shouldtraversechildren)
 			{
 				$child_html = '';
 				
 				// we will only pay attention to this item definition if some of that type can be
 				// found as children of the current item
	 			if($item)
	 			{
	 				// are we looking at the children of the current item or the item itself
	 				if($source_or_item_mode == 'source')
	 				{
	 					$should_get_inherited_items = false;
	 					
	 					if(!empty($data->arguments['inherit']))
	 					{
	 						$should_get_inherited_items = true;
	 					}
	 					
	 					$child_items = $item->get_children_by_type($item_type, $should_get_inherited_items);
	 				}
	 				else if($source_or_item_mode == 'beneath')
	 				{
	 					$child_items = array();
	 					
	 					$should_get_inherited_items = false;
	 					
	 					if(!empty($data->arguments['inherit']))
	 					{
	 						$should_get_inherited_items = true;
	 					}	 					
	 					
	 					$child_items = $item->recursive_get_children_by_type($item_type, $should_get_inherited_items);
	 				}
	 				else if($source_or_item_mode == 'item')
	 				{
	 					$child_items = array($item);
	 				}
	 				
	 				
	 				//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	 				//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	 				
	 				$matched_child_items = array();
	 				
	 				foreach($child_items as $child_item)
	 				{	 				
	 					$does_item_match_query_tags = true;
	 					
	 					foreach($data->arguments as $argument => $value)
	 					{
							if($child_item->has_field_in_schema($argument))
							{
								if(!$this->process_if($child_item, $argument, $value, $data->arguments))
								{
									$does_item_match_query_tags = false;
								}
							}
	 					}
	 					
	 					if(!$does_item_match_query_tags)
	 					{
	 						continue;
	 					}
	 					
	 					$matched_child_items[] = $child_item;
	 				}
	 				
	 				$child_items = $matched_child_items;
	 				
	 				
	 				$child_count = count($child_items);
	 				
	 				if($data->arguments['content_limit']=='none')
	 				{
	 					if($child_count<=0)
	 					{
	 						return $data->template;
	 					}
	 					else
	 					{
	 						return '';
	 					}
	 				}
	 				
	 				if($data->arguments['limit']==1 && $data->arguments['orderby']=='random')
	 				{
	 					$child_item = $child_items[array_rand($child_items)];
	 					
	 					$child_items = array($child_item);
	 				}
	 				else
	 				{
	 					if(isset($data->arguments['limit']))
	 					{
		 					if($data->arguments['limit']<=$child_count)
	 						{
		 						$child_count = $data->arguments['limit'];
	 						}
	 					}
	 				
	 					if(!empty($data->arguments['orderby']))
	 					{ 						
	 						$sorter = new ItemSorter($child_items);
	 						
	 						$child_items = $sorter->get_sorted_items($data->arguments['orderby'], $data->arguments['orderdirection']);
	 					}	
	 					
	 					if(!empty($data->arguments['previousto']))
	 					{
	 						$previousone = null;
	 						
	 						foreach($child_items as $item)
	 						{
	 							if($item->path==$data->arguments['previousto'])
	 							{
	 								break;
	 							}
	 							
	 							$previousone = $item;
	 						}
	 						
	 						$child_items = array($previousone);
	 					}
	 					
	 					if(!empty($data->arguments['nextto']))
	 					{
	 						$child_items = array_reverse($child_items);
	 						
	 						$nextone = null;
	 						
	 						foreach($child_items as $item)
	 						{	 							
	 							if($item->path==$data->arguments['nextto'])
	 							{
	 								break;
	 							}
	 							
	 							$nextone = $item;
	 						}

	 						$child_items = array($nextone);
	 					}
	 					
	 							
	 				}
	 				
	 				if(!empty($data->arguments['min_children']))
	 				{
	 					$current_children = count($child_items);
	 					
	 					if($current_children < $data->arguments['min_children'])
	 					{
	 						$child_items = array();
	 					}
	 				}
	 				
					if(!empty($data->arguments['number_filter']))
	 				{
	 					if($data->arguments['number_filter']=='dump')
	 					{
	 						print_r($child_items);
	 						exit;
	 					}
	 					
	 					$number_filter_start = $data->arguments['number_filter_start'];
	 					$number_filter = $data->arguments['number_filter'];
	 					
	 					if(empty($number_filter_start))
	 					{
	 						$number_filter_start = 0;
	 					}
	 					
	 					$new_array = array();
	 					
	 					for($i=$number_filter_start; $i<count($child_items); $i+=$number_filter)
	 					{
	 						$new_array[] = $child_items[$i];
	 					}
	 					
	 					$child_items = $new_array;

	 				}
	 				
	 				$split_counter = 0;
	 				$render_counter = 0;
	 				
	 				$child_loop_html = '';
	 					
	 				for($i=0; $i<count($child_items); $i++)
 					{
	 					$child_item = $child_items[$i];
	 					
	 					if(!isset($child_item))
	 					{
	 						continue;	
	 					}
	 					
	 					$child_item->index = $i;
	 					
	 					
	 					
	 					
 						// we want the whole match here (i.e. everything because we need to
 						// re-parse (it is a field of a different item_type
 						if(!empty($data->field))
 						{
							$data->template = $data->html;
 						}
 							
 						$child_item_html = $this->process($data, $child_item);
 						
 						$child_loop_html .= $child_item_html;
 						
 						$split_counter++;
 						$render_counter++;
 						
 						if($render_counter >= $child_count)
 						{
 							break;	
 						}
 						
 						if(isset($data->arguments['split_count']))
 						{
 							if($split_counter >= $data->arguments['split_count'])
 							{
 								$child_loop_html .= $data->arguments['split_html'];
 								$split_counter = 0;
 							}
 						}
 					}
 					
 					if(isset($data->arguments['split_count']))
 					{
 						if($split_counter < $data->arguments['split_count'])
 						{
 							if(isset($data->arguments['split_padding_html']))
 							{
 								for($i=$split_counter; $i<$data->arguments['split_count']; $i++)
 								{
 									$child_loop_html .= $data->arguments['split_padding_html'];
 								}
 							}
 						}
 					}
 					
 					if($render_counter>0)
 					{
 						if(isset($data->arguments['prepend_html']))
 						{
	 						$child_loop_html = $data->arguments['prepend_html'].$child_loop_html;
 						}
 					
 						if(isset($data->arguments['append_html']))
 						{
	 						$child_loop_html .= $data->arguments['append_html'];
 						}
 					}
 					
 					$child_html .= $child_loop_html;
 				}
 					
 				$replace_html = $child_html;
 			}
 			// this means that we are looking at the same component as the current scope
 			// i.e. it is a field of the current item
 			else if(!empty($data->field))
 			{
 				$the_field = $data->field;
 				
 				$schema = $this->schema;
 					
 				if($item)
 				{
 					if($the_field == 'parent')
 					{
 						$parent = null;
 						
 						if(isset($this->parent_cache[$item->database_id()]))
 						{
 							$parent = $this->parent_cache[$item->database_id()];
 						}
 						else if(isset($this->load_child_cache) && isset($this->load_child_cache->asmap[$item->parent_id]))
 						{
 							$parent = $this->load_child_cache->asmap[$item->parent_id];
 						}
 						else
 						{
 							$real_clone = new Item_Model($item->database_id());
 							$parent = $real_clone->load_parent(true);
 							
 							$this->parent_cache[$item->database_id()] = $parent;
 						}
 						
 						$real_field = $data->field_property;
 						
 						$replace_html = $schema->get_flat_field_value($data->field_property, $parent->item_type, $parent->$real_field, $data->arguments);
 					}
 					// this prints the contents based on a question of the item
 					else if($the_field == 'print_if' || $the_field == 'print_if_not' || $the_field == 'print_if_multiple' || $the_field == 'print_if_not_multiple')
 					{
 						$has_passed = true;
 						
						foreach($data->arguments as $arg => $array_value)
						{
							if(!is_array($array_value))
							{
								$array_value = array($array_value);
							}
							
							$hit_count = 0;
							$number_of_checks = count($array_value);
							
							foreach($array_value as $value)
							{
								if(preg_match('/^children\.(\w+)$/', $arg, $match))
								{
									$arg = $match[1];
									
									$child_hit = false;
									
									foreach($item->children as $item_child)
									{
										if($this->process_if($item_child, $arg, $value, $data->arguments))
										{
											$child_hit = true;
											break;	
										}
									}
									
									if(!$child_hit)
									{
										$has_passed = false;	
									}
								}
								else
								{
									if(!$this->process_if($item, $arg, $value, $data->arguments))
									{
										$has_passed = false;
									}
									else
									{
										$hit_count++;
									}
								}
							}
						}
						
						if($the_field == 'print_if' || $the_field == 'print_if_not')
						{
							if($has_passed)
							{
								if($the_field == 'print_if')
								{
									$replace_html = $this->process($data, $item);
								}
								else
								{
									$replace_html = '';
								}
							}
							else
							{
								if($the_field == 'print_if_not')
								{
									$replace_html = $this->process($data, $item);
								}
								else
								{
									$replace_html = '';
								}
							}
						}
						else if($the_field == 'print_if_multiple' || $the_field == 'print_if_not_multiple')
						{
							if($hit_count > 0)
							{
								if($the_field == 'print_if_multiple')
								{
									$replace_html = $this->process($data, $item);
								}
								else
								{
									$replace_html = '';
								}
							}
							else
							{
								if($the_field == 'print_if_not_multiple')
								{
									$replace_html = $this->process($data, $item);
								}
								else
								{
									$replace_html = '';
								}
							}
						}
 					}
 					// this means the field points to a value of the item
 					else
 					{
						$parent_data->fields[$the_field] = $item->$the_field;
 						
	 					$replace_html = $this->get_item_value($item, $the_field, $data->field_property, $data->arguments);
 					}
 				}
 				else
 				{
 					$parent_data->fields[$the_field] = $data->template;
					$replace_html = '';
 				}
			}
	 		
			// replace the tag with the compilation of all items against the template
 			
 			
			$item = $restore_item;
		}
		// this means the tag is not a schema item
		else
		{
			if($data->item_type == 'tag_filter')
			{		
				$processed_contents = $this->process($data, $item);
				
				$replace_html = $processed_contents;
			}
			// lets make a fake image that will be passed to the thumbnail cache
			else if($data->item_type == 'button')
			{		
				$file_location = Kohana::config('webkitfolders.upload_folder').'/button.png';
				
				$path_parts = explode('/', $file_location);
 				$image_filename = array_pop($path_parts);
 						
 				foreach(Thumbnailcache::$button_tags as $tag => $realtag)
 				{
					$tagvalue = $data->arguments[$tag];
 						
 					if(!empty($tagvalue))
 					{
						$path_parts[] = '_'.$realtag.'_'.urlencode($tagvalue);
 					}
 				}
 				
 				$path_parts[] = '_button_y';
 						
 				$file_location = implode('/', $path_parts);
 				
 				$file_location .= '/'.$image_filename;
 				
 				$replace_html = $file_location;
			}
			// we are including the contents of another file here
			// the file will be processed for tags before being inserted
			else if($data->item_type == 'include')
			{
				$include_path = $_SERVER['DOCUMENT_ROOT'].$data->arguments['file'];
				
				$include_contents = file_get_contents($include_path);
				
				$data->template = $include_contents;
				
				$processed_contents = $this->process($data, $item);
				
				$replace_html = $processed_contents;
			}
			// we are including the contents of another file here
			// the file will be processed for tags before being inserted
			else if($data->item_type == 'params')
			{
				$replace_html = $this->params[$data->arguments['attribute']];
			}			
			// we are being asked for a tag that is not an item - we will look for a view
			// with that name and render it instead
			else
			{
				$processed_contents = $this->process($data, $item);
				
				$view = new View('tags/'.$data->item_type);
			
				$view->contents = $processed_contents;
				$view->data = $data;
				$view->item = $item;
			
				$replace_html = $view->render();				
			}
		}

		return $replace_html;
	}
	
	private function process_if($item, $arg, $value, $arguments)
	{
		$field = $arg;
		$field_property = null;
							
		if(preg_match('/^(\w+)\.(\w+)$/', $arg, $match))
		{
			$field = $match[1];
			$field_property = $match[2];
		}
		
		$field_def = $item->get_field_definition($arg);
		
		if(($field_def['type'] == 'date')||($field_def['type'] == 'datetime'))
		{
			$datetime_value = $item->$arg;
			
			// empty check
			if(empty($value))
			{
				if(empty($datetime_value))
				{
					return true;
				}
				else
				{
					return false;
				}
			}
									
			if(preg_match('/^(\d{2})\/(\d{2})\/(\d{2,4})/', $datetime_value, $match))
			{
				$date_values = array(
    				'day' => $match[1],
    				'month' => $match[2],
    				'year' => $match[3]
    			);
    			
				$datetime = date_create($date_values['year'].'-'.$date_values['month'].'-'.$date_values['day']);
				$datetimetimestamp = date_format($datetime, 'U');
										
				$nowdatetime = date_create('now');
				$nowdatetimetimestamp = date_format($nowdatetime, 'U');
							
				$days = statictools::count_days($datetimetimestamp, $nowdatetimetimestamp);
				
				$dategaps = array(
					'today' => 0,
					'day' => -1,
					'week' => -7,
					'month' => -31,
					'year' => -365
				);
				
				$operator = '>=';
							
				if(preg_match('/^([=><]+)(.*?)$/', $value, $match))
				{
					$operator = $match[1];	
					$value = $match[2];
				}
				
				if(isset($dategaps[$value]))
				{
					$check_days = $dategaps[$value];
					
					$is_true = false;
							
					$eval_st=<<<EOT
if(\$days $operator \$check_days)
{
	\$is_true = true;
}
EOT;

					eval($eval_st);
				
					return $is_true;
				}
				else if(preg_match('/^(\d{2})\/(\d{2})\/(\d{2,4})/', $value, $qmatch))
				{
					$qdate_values = array(
	    				'day' => $qmatch[1],
	    				'month' => $qmatch[2],
	    				'year' => $qmatch[3]
	    			);
    			
					$querydatetime = date_create($qdate_values['year'].'-'.$qdate_values['month'].'-'.$qdate_values['day']);
					$querydatetimetimestamp = date_format($querydatetime, 'U');
				
					$is_true = false;
							
					$eval_st=<<<EOT
if(\$datetimetimestamp $operator \$querydatetimetimestamp)
{
	\$is_true = true;
}
EOT;

					eval($eval_st);
				
					return $is_true;
				}
							
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{					
			$check_value = $this->get_item_value($item, $field, $field_property, $arguments);
							
			if($value == "*")
			{
				if(empty($check_value))
				{
					return false;
				}
				else
				{
					return true;	
				}
			}
			else
			{
				$operator = '==';
							
				if(preg_match('/^([!=><]+)(.*?)$/', $value, $match))
				{
					$operator = $match[1];	
					$value = $match[2];
				}
							
				$is_true = false;
							
				$eval_st=<<<EOT
if(\$check_value $operator \$value)
{
	\$is_true = true;
}
EOT;

				eval($eval_st);
			
				return $is_true;
			}
		}
	}
	
	private function get_item_value($item, $the_field, $field_property, $arguments)
	{
		if(isset($this->method_fields[$the_field]))
		{
			$ret = $item->$the_field();
			
			return $ret;	
		}
		else
		{
			$arguments['property'] = $field_property;
			
			$newargs = array();
			
			foreach($arguments as $prop => $val)
			{
				if(preg_match('/^self\.(.*?)$/', $val, $match))
				{
					$selffield = $match[1];
					
					$newargs[$prop] = $item->$selffield;
				}
				else
				{
					$newargs[$prop] = $arguments[$prop];
				}
			}

			$ret = $this->schema->get_flat_field_value($the_field, $item->item_type, $item->$the_field, $newargs);

 			return $ret;
 		}
 	}
 	
 	protected function run_tutorials_search()
 	{
 		$search = Tutorials_Controller::$current_search;
 		
 		$item = new Item_Model();
	 			
		$item->item_type = 'folder';
		
		$searchwords = preg_split("/[\s,]+/", $search);
		$keywords = array("tutorial_keywords", "description");
		
		$keyword_parts = array();
		$name_parts = array();
		
		foreach($searchwords as $searchword)
		{
			$name_part=<<<EOT
			LCASE(item.name) like '%{$searchword}%'	
EOT;

			$name_parts[] = $name_part;
		}
		
		$name_sql = "(\n".implode("\n			OR\n", $name_parts)."\n)";
		
		$keyword_parts[] = $name_sql;
		
		foreach($keywords as $keyword)
		{
			$searchword_parts = array();
			
			foreach($searchwords as $searchword)
			{
				$searchword_part=<<<EOT
			LCASE(item_keyword.long_value) like '%{$searchword}%'	
EOT;

				$searchword_parts[] = $searchword_part;
			}
			
			$searchword_sql = implode("\n			OR\n", $searchword_parts);
			
			$keyword_part=<<<EOT
	(
		item_keyword.name = '{$keyword}'
		and 
		(
			$searchword_sql
		)
	)		
EOT;

			$keyword_parts[] = $keyword_part;
		}
		
		$keyword_sql = implode("\n			OR\n", $keyword_parts);
		
		$search_sql=<<<EOT
		
select item.id as id
from 
	item_link,
	item
	left join item_keyword
	on item_keyword.item_id = item.id
where
	item.item_type = 'tutorial'
and item_link.item_id = item.id	
and 
(
	item_link.path like '/tutorials%'
)
and 
(
		$keyword_sql
	
)

group by item.id

		
EOT;

		$item_sql=<<<EOT
select item.*
from item,
(
	$search_sql
)
matches
where
matches.id = item.id
EOT;

		$keywords_sql=<<<EOT
select item_keyword.*
from item_keyword,
(
	$search_sql
)
matches
where
matches.id = item_keyword.item_id
EOT;

		//echo $item_sql;
		//exit;
		$item->load_children_from_sql($item_sql, $keywords_sql);
		
		return $item;
		
 	}
}
?>