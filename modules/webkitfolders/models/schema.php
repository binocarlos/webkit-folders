<?php

// ------------------------------------------------------------------------

/**
 * Schema Class
 *
 * Object representing the schema of an item
 *
 * This class has static methods for created Schema Objects
 *
 * An instance of this class represents one class schema
 * 
 */
 
// ------------------------------------------------------------------------


class Schema_Model extends Model
{
	
	
// ------------------------------------------------------------------------

/**
 * Schemas
 *
 * This is what is communicated to the client to tell it the schema for each type of object
 * A schema describes how an object behaves and what fields it has
 *
 * NOTE - this will come from the database at some point and will remain static with memcache
 *
 */
 
// ------------------------------------------------------------------------

	// these are the field definitions i.e. they desribe how different types of field behave
	// if you create a new type of field (e.g. 'xmlstockmarketfeed') - you can define how it should
	// behave here
	//
	// if a field type has no entry here - it is treated as default
	
	private $field_type_map = array(
	
		'image' => array(
			'save_to' => 'long_value',
			'storage' => 'json' ),
			
		'video' => array(
			'save_to' => 'long_value',
			'storage' => 'json' ),
			
		'file' => array(
			'save_to' => 'long_value',
			'storage' => 'json' ),
			
		'item_pointer' => array(
			'value_field' => 'name',
			'id_value_field' => 'id',
			'save_to' => 'long_value',
			'storage' => 'json' ),
			
		'model_pointer' => array(
			'value_field' => 'name',
			'id_value_field' => 'id',
			'save_to' => 'long_value',
			'storage' => 'json' ),			
			
		'field' => array(
			'save_to' => 'long_value',
			'storage' => 'json' ),	
			
		'checkbox' => array(
			'value_type' => 'boolean' ),			
			
		'html' => array(
			'save_to' => 'long_value' ),
			
		'text' => array(
			'save_to' => 'long_value' ),
			
		'comment' => array(
			'save_to' => 'long_value' ),

		'date' => array(
			'storage' => 'date' ),
			
		'datetime' => array(
			'storage' => 'date' ),

		'integer' => array(
			'save_to' => 'number_value' ),
			
		'password' => array(
			'encrypted' => true ),
			
		'float' => array(
			'save_to' => 'number_value' )			
			
	); 
 
// ------------------------------------------------------------------------ 

	// this tells you what values should not be copied into the final schema
	// but that are used by the schema processor to do the building
	//
	// internal values are removed while the processing is happening
	
	private $internal_schema_variables = array(
	
		'children' => 'remove',
		
		'remove_fields' => 'remove',
		
		'replace_fields' => 'remove',
		
		'path' => 'remove'
		
	);
	
	
	
	// this tells you which properties can be configured by prepending default_ and add_ before the field name
	// these values are removed from the schema before being returned
	
	private $properties_using_default_bubble = array(
	
		'fields' => 'remove'
		
	);
	
	// helper variable so you can access fields quickly
	// This means we can ask item_type->fieldname to get a field definition
	
	private $schema_field_maps = array();
	

	// this is the static holding place for the processed schemas
	// once this is written to it acts as a cache allowed any subsequent request for schemas to be returned quickly
	private $processed_schemas = NULL;

	//
	// If a field begins with 'default_' - then every subclass will have those fields whatever it defined
	//
	// NOTE - _default makes the field appear before any other (but after existing defaults)
	//
	// If a field begins with 'add_' - then the values will be added to the parent class values
	//
	// If a field is simply defined - it will overwrite any existing values (although the defaults will be included)
	//
	// NOTE - if default_ OR add_ are used - it must be an ARRAY!

	private $schemas = array(
	
		//////////////////////////////////////////////////////////////////////
		//////////////////////////////////////////////////////////////////////
		//////////////////////////////////////////////////////////////////////
		/// Guide to the different elements of a schema:
		//
		//	id - 	the name of the item_type that this schema belongs to
		//			this will default to the array index if not defined
		//
		//	
	
		////////////////////////////////////////////////////////////////////////////////////////////////////////////
		////////////////////////////////////////////////////////////////////////////////////////////////////////////
		//
		// DEFAULT
		////////////////////////////////////////////////////////////////////////////////////////////////////////////
		//
		// the default schema - i.e. all items will inherit this
	
		'default' => array(
		
			'id' => 'default',
		
			////////////////////////////////////////////////////////////////////////////////////////
			// lists the type of item that can be added to this item
			// this also controls whether an item can be added
			// if it is empty it means no children are allowed
			//
			// default is that anything can be added
			
			'child_filter' => array(
				'*'
			),
			
			'parent_filter' => array(
				'*'
			),
			
			////////////////////////////////////////////////////////////////////////////////////////
			// tells you the icon to use for this type of item
			//
			// if the icon is set to item_type then the items type is used as the name of the icon
			
			'icon' => 'item_type',
			
			////////////////////////////////////////////////////////////////////////////////////////
			// tells you if this item can be added publically or if it is private to other itemss
			// the default type is private because it is abstract
			// everything should override item or folder
			
			'access' => 'private',
			
			
			////////////////////////////////////////////////////////////////////////////////////////
			// allows one type of item to take precedence over others when sorting
			// useful for grouping items of a particular type or property together at the beggining
			// the more the priority - the more towards the start it will appear
			
			'sort_priority' => 0,
			
			
			'form_width' => 650,
			
			
			////////////////////////////////////////////////////////////////////////////////////////
			// the fields for this item - the default schema contains the base fields (name, item_id etc) as default
			// subclasses of the default schema will add more fields that will point to keyword values
			
			'default_fields' => array(
				
				array(
					'title' => 'Name',
					'name' => 'name',
					'required' => 'yes',
					'type' => 'string'
				),
				
				array(
					'title' => 'ID',
					'name' => 'id',
					'read_only' => true,
					'type' => 'string',
					'tab' => 'Properties'
				),		
				
				array(
					'title' => 'Link Type',
					'name' => 'link_type',
					'read_only' => true,
					'type' => 'string',
					'tab' => 'Properties'
				),				
				
				array(
					'title' => 'Url',
					'name' => 'url',
					'hidden' => true,
					'type' => 'string',
					'tab' => 'Properties'
				),
				
				array(
					'title' => 'Path',
					'name' => 'path',
					'read_only' => true,
					'type' => 'string',
					'tab' => 'Properties'
				),
				
				array(
					'title' => 'Created',
					'name' => 'created',
					'read_only' => true,
					//'hidden' => true,
					'type' => 'datetime',
					'tab' => 'Properties'
				),
				
				array(
					'title' => 'Modified',
					'name' => 'modified',
					'read_only' => true,
					//'hidden' => true,
					'type' => 'datetime',
					'tab' => 'Properties'
				),
				
				array(
					'title' => 'Position Number',
					'name' => 'poisitionnumber',
					//'read_only' => true,
					//'hidden' => true,
					'type' => 'string',
					'tab' => 'Properties'
				),
				
				/*
				array(
					'title' => 'Website',
					'name' => 'website_hostname',
					'read_only' => true,
					'type' => 'string',
					'tab' => 'Properties'
				),
				
				array(
					'title' => 'Website Address',
					'name' => 'website_address',
					'read_only' => true,
					'type' => 'string',
					'tab' => 'Properties'
				),
				*/
				
				array(
					'title' => 'Icon',
					'name' => 'foldericon',
					'type' => 'icon_choice',
					'tab' => 'Properties'
				),
				
				array(
					'title' => 'Email Updates',
					'name' => 'email_updates',
					'tab' => 'Properties',
					'list' => true,
					'list_title_field' => 'name',
					'allow_clear' => true,
					'clear_title' => 'Clear',
					'type' => 'item_pointer',
					'base_url' => 'users:/',
					'allowed_types' => array(
						'user'
					)
				)
				
			),
			
			
			////////////////////////////////////////////////////////////////////////////////////////
			// the children array - schemas that extend this one - because this is the default schema
			// it is the root of every other schema - this allows for definition overriding
			
			'children' => array(
			
				////////////////////////////////////////////////////////////////////////////////////////////////////////////
				////////////////////////////////////////////////////////////////////////////////////////////////////////////
				//
				// FOLDER
				////////////////////////////////////////////////////////////////////////////////////////////////////////////
				//
				// the folder schema - the basic building block!
				//
				// this is something that contains other things	
		
				'folder' => array(
				
					'id' => 'folder',
					
					'access' => 'public',

					// Folders should come first
					'sort_priority' => 100,
					
					'fields' => array(					
						array(
							'title' => 'Description',
							'name' => 'description',
							'type' => 'comment',
							'tab' => 'Properties'
						)
					),
					
					'children' => array(

						'disk' => array(

							'id' => 'disk',
							
							'icon' => 'hard_drive_network',
							
							'sort_priority' => 1000, // always first
							
							'access' => 'system',
							
							'parent_filter' => array(
								
							),
							
							'child_filter' => array(
								'*'
							)
							
						),
						
						'users' => array(

							'id' => 'users',
							
							'icon' => 'users',
							
							
							
							'sort_priority' => 100,
							
							'access' => 'system',
							
							'parent_filter' => array(
								
							),
							
							'child_filter' => array(
								'folder',
								'user'
							)
							
						),
						
						
						
						'user' => array(

							'id' => 'user',
							
							'icon' => 'user3',
							
							'access' => 'system',
							
							'parent_filter' => array(
								'folder',
								'users'
							),
							
							'child_filter' => array(

							),
							
							'fields' => array(
								
								array(
									'title' => 'Email',
									'name' => 'email',
									'type' => 'string'
								),
								
								array(
									'title' => 'Password',
									'name' => 'password',
									'type' => 'string'
								)
								
							)
							
						),
						
						'models' => array(

							'id' => 'models',
							
							'sort_priority' => 200,
							
							'icon' => 'cube_molecule',
							
							'access' => 'system',
							
							'parent_filter' => array(
								
							),
							
							'child_filter' => array(
								'folder',
								'model'
							)
							
						),
						
						'model' => array(

							'id' => 'model',
							
							'on_save' => 'refresh_schema',
							
							'icon' => 'cube_molecule',
							
							'parent_filter' => array(
								'models',
								'folder'
							),
							
							'child_filter' => array(

							),
							
							'fields' => array(
							
								array(
									'title' => 'Inherits From',
									'name' => 'inherits_from',
									'type' => 'model_pointer',
									'default_value' => array(
										'id' => 'system_model:folder',
										'name' => 'folder',
										'icon' => 'folder'
									),
									'initialized_callback' => 'inherits_from_changed',
									'value_changed_callback' => 'inherits_from_changed',
									'base_url' => 'models:/',
									'allowed_types' => array(
										'model'
									)
								),
								
								array(
									'title' => 'Children',
									'name' => 'child_filter',
									'list' => true,
									'list_title_field' => 'name',
									'default_value' => array(
										'name' => 'All',
										'id' => '*'
									),
									'default_title' => 'Allow All',
									'allow_clear' => true,
									'clear_title' => 'Allow None',
									'type' => 'model_pointer',
									'base_url' => 'models:/',
									'allowed_types' => array(
										'model'
									)
								),
								
								array(
									'title' => 'Parents',
									'name' => 'parent_filter',
									'list' => true,
									'list_title_field' => 'name',
									'default_value' => array(
										'name' => 'All',
										'id' => '*'
									),
									'default_title' => 'Allow All',
									'allow_clear' => true,
									'clear_title' => 'Allow None',
									'type' => 'model_pointer',
									'base_url' => 'models:/',
									'allowed_types' => array(
										'model'
									)
								),
								
								array(
									'title' => 'Inherited Fields',
									'name' => 'inherited_fields',
									'height' => 100,
									'hide_buttons' => true,
									'list' => true,
									'hide_toolbar' => true,
									'auto_value' => true,
									'tab' => 'Form Fields',
									'type' => 'field'
								),
								
								array(
									'title' => 'New Fields',
									'name' => 'fields',
									'height' => 200,
									'list' => true,
									'tab' => 'Form Fields',
									'type' => 'field'
								)
								
							),
							
							'replace_fields' => array(
								array(
									'title' => 'Icon',
									'name' => 'foldericon',
									'type' => 'icon_choice',
									'tab' => 'default'
								)
							)
							
						),
						
						
						
						'bin' => array(

							'id' => 'bin',
							
							'sort_priority' => 50,
							
							'icon' => 'garbage',
							
							'access' => 'system',
							
							'parent_filter' => array(
								
							)
							
							
							
						),
						
						
						'installation' => array(

							'id' => 'installation',
							
							'icon' => 'earth2',
							
							'access' => 'system',
							
							'parent_filter' => array(
				
							),
							
							'child_filter' => array(
								'system'
							)
							
						),
						
						'system' => array(

							'id' => 'system',
							
							'icon' => 'earth_network',
							
							'access' => 'system',
							
							'parent_filter' => array(
				
							),
							
							'child_filter' => array(
								'disk',
								'users',
								'models'
							)
							
						),
						
						
						'pagelocwebsite' => array(

							'id' => 'pagelocwebsite',
							
							'display_title' => 'Pageloc Website',
							
							'icon' => 'folder_network',
							
							'sort_priority' => 2000,
							
							//'storage_driver' => 'FTPSite',
							
							'child_filter' => array(
								'*'
							),
							
							'parent_filter' => array(
								'*'
							),
							
							'fields' => array(
							
								array(
									'title' => 'Site URL',
									'name' => 'siteurl',
									'type' => 'string'
								),
								
								array(
									'title' => 'Site Folder',
									'name' => 'folder',
									'type' => 'string'
								)
							
							
							)
						),
						
						'pagelocwebpage' => array(

							'id' => 'pagelocwebpage',
							
							'display_title' => 'Pageloc Webpage',
							
							'icon' => 'document',

							'child_filter' => array(
								'*'
							),
							
							'parent_filter' => array(
								'*'
							)
							
							
						),
						
						'pagelocfile' => array(

							'id' => 'pagelocfile',
							
							'display_title' => 'Pageloc File',
							
							'icon' => 'document_gear',

							'child_filter' => array(
								'*'
							),
							
							'parent_filter' => array(
								'*'
							)
							
							
						)						
						
						/*,
																		
						'ftp_website' => array(

							'id' => 'ftp_website',
							
							'display_title' => 'FTP Website',
							
							'icon' => 'folder_network',
							
							'sort_priority' => 2000,
							
							'storage_driver' => 'FTPSite',
							
							'child_filter' => array(
								'*'
							),
							
							'parent_filter' => array(
								'*'
							),
							
							'fields' => array(
							
								array(
									'title' => 'FTP host address',
									'name' => 'hostname',
									'type' => 'string'
								),
								
								array(
									'title' => 'FTP username',
									'name' => 'username',
									'type' => 'string'
								),

								array(
									'title' => 'FTP password',
									'name' => 'password',
									'type' => 'string'
								),
								
								array(
									'title' => 'FTP sub-folder',
									'name' => 'folder',
									'type' => 'string'
								),
								
								array(
									'title' => 'Template',
									'name' => 'template',
									'type' => 'item_pointer',
									'base_url' => 'disk:/',
									'allowed_types' => array(
										'ftp_webpage'
									)
								),
							
							
							)
						),
						
						'ftp_webpage' => array(

							'id' => 'ftp_webpage',
							
							'display_title' => 'FTP Webpage',
							
							'icon' => 'document_web',

							'child_filter' => array(
								'*'
							),
							
							'parent_filter' => array(
								'folder',
								'ftp_website'
							),
							
							'replace_fields' => array(
								array(
									'title' => 'Name',
									'name' => 'name',
									'required' => 'yes',
									'read_only' => true,
									'type' => 'string'
								)
							)
							
							
						) */
						
					)
					
				// END OF FOLDER
				),
				
				
				
				////////////////////////////////////////////////////////////////////////////////////////////////////////////
				////////////////////////////////////////////////////////////////////////////////////////////////////////////
				////////////////////////////////////////////////////////////////////////////////////////////////////////////
				////////////////////////////////////////////////////////////////////////////////////////////////////////////
				////////////////////////////////////////////////////////////////////////////////////////////////////////////
				////////////////////////////////////////////////////////////////////////////////////////////////////////////
				//
				// item
				//
				// the file schema - the basic item
				//
				// this is a collection of data representing a 'thing' in the system
				//
				// it can contain other items (like a folder) but these items will normally be private to this object
				//
				// note - in this context a 'file' is an object - not neccessarily a file on the hard disk
				// there is a disk_file type that extends file to do that
				
					
				
		
				'item' => array(
				
					'id' => 'item',
					
					'access' => 'public',
					
					'icon' => 'document',
		
					// the default for a file is that it dosn't contain anything
					// you can override this to allow specific parent/child relationships
					
					'child_filter' => array(
			
					),
					
					'children' => array(
					
						'textarea' => array(
						
							'parent_filter' => array(
								'ftp_webpage'
							),
							
							'id' => 'textarea',
					
							'access' => 'public',
					
							'icon' => 'document',	
							
							'form_width' => 800,			
					
							'child_filter' => array(
			
							),
							
							'fields' => array(
							
								array(
									'title' => 'Content',
									'name' => 'content',
									'type' => 'html'
								)
							)
						)
						
					)
					
				// END OF FILE
				)
			)
			
		// END OF DEFAULT
		)
	);
	
	// Schema instances
	public static $instances = array();
	
    function __construct()
    {
        parent::__construct();
    }
    
    /**
	 * Returns a singleton instance of Schema.
	 *
	 * @param   
	 * @return  Schema
	 */
	public static function instance($name = 'default')
	{
		if ( ! isset(Schema_Model::$instances[$name]))
		{
			// Create a new instance
			Schema_Model::$instances[$name] = new Schema_Model();
		}

		return Schema_Model::$instances[$name];
	}
    
    public function get_field_index($name)
    {
    	if(preg_match('/^([\w ]+)\.(\d+)$/i', $name, $match))
    	{
    		$name = $match[2];
    	}
    	
    	return (int)$name;
    }
    
    public function get_field_name($name)
    {
    	if(preg_match('/^([\w ]+)(\.\d+)$/i', $name, $match))
    	{
    		$name = $match[1];
    	}
    	
    	return $name;
    }
    
	// takes a reference to a keyword array/object and assigns the correct value
    // in the correct place depending upon the field definition
    //
    // returns true if the value was set and false is there was no value to set
    
    public function set_keyword_value($keyword, $item_type, $value = NULL)
    {
    	$field_name = $this->get_field_name($keyword->name);
    	
       	$field_def = $this->get_field_type_definition($item_type, $field_name);
		
		$keyword->field_type = $field_def['type'];
		
		if(empty($value))
		{
			return FALSE;
		}
		
    	$property_name = 'value';

		if(isset($field_def['save_to']))
		{
			$property_name = $field_def['save_to'];
		}
		
		$storage_type = NULL;
		
		if($field_def['encrypted'] == 'true')
		{
			$value = md5($value);
		}
		
		if($field_def['value_field'])
		{
			$keyword->value = $value[$field_def['value_field']];
		}
		
		if($field_def['id_value_field'])
		{
			$id_value = $value[$field_def['id_value_field']];
			
			if(preg_match('/^\d+$/', $id_value))
			{
				$keyword->id_value = $id_value;
			}
			else if(preg_match('/^(\d+)\.\d+$/', $id_value, $match))
			{
				$keyword->id_value = $match[1];
			}
		}		

		if(isset($field_def['storage']))
    	{
    		$storage_type = $field_def['storage'];
    		
    		if($storage_type == 'json')
    		{
    			if(!is_string($value))
    			{
    				$value = json_encode($value);
    			}
    		}
    		else if($storage_type == 'date')
    		{
    			// if its a date we copy the value into the date_value field as a proper sql date
				// we leave the string value alone however cos it can be punted right back at the client
    			if(preg_match('/^(\d{2})\/(\d{2})\/(\d{2,4})$/', $value, $matches)>0)
				{
					$day = $matches[1];
					$month = $matches[2];
					$year = $matches[3];
					
					if($year<100)
					{
						$year += 2000;
						
						$value = $day.'/'.$month.'/'.$year;
					}
					
					$date_value = $year.'-'.$month.'-'.$day.' 00:00:00';
					
					$keyword->date_value = $date_value;
				}
    		}
    		else if($storage_type == 'datetime')
    		{
    			// if its a date we copy the value into the date_value field as a proper sql date
				// we leave the string value alone however cos it can be punted right back at the client
    			if(preg_match('/^(\d{2})\/(\d{2})\/(\d{2,4}) (\d{2}):(\d{2}):(\d{2})$/', $value, $matches)>0)
				{
					$day = $matches[1];
					$month = $matches[2];
					$year = $matches[3];
					
					$hour = $matches[4];
					$minute = $matches[5];
					$second = $matches[6];
					
					if($year<100)
					{
						$year += 2000;
						
						$value = $day.'/'.$month.'/'.$year;
					}
					
					$date_value = $year.'-'.$month.'-'.$day.' '.$hour.':'.$minute.':'.$second;
					
					$keyword->date_value = $date_value;
				}
    		}
    	}
    	
		if($value === 'null')
		{
			return FALSE;
		}
		
		if($field_def['value_type'] == 'boolean')
		{
			if(!empty($value))
			{
				$value = 'true';
			}
		}
		
		$keyword->$property_name = $value;

		return TRUE;
    }
    
    // provides an oppertunity to process the value of a keyword in some way
    // before it will be given to the front end website for display
    // in this case we convert images into direct urls (rather than Json structures)
    
    public function get_flat_keyword_value($keyword, $item_type)
    {
    	$value = $this->get_keyword_value($keyword, $item_type);
    	
    	$value = $this->get_flat_field_value($keyword->name, $item_type, $value);
    	
    	return $value;
    }
    
    public function get_flat_field_value($field_name, $item_type, $value, $args = null)
    {
    	$field_name = $this->get_field_name($field_name);
    	
       	$field_def = $this->get_field_type_definition($item_type, $field_name);

    	if(empty($value))
    	{
    		return $value;
    	}
    	
    	if(!isset($field_def))
    	{
    		return $value;
    	}
    	
    	if(($field_def['type'] == 'file') || ($field_def['type'] == 'image') || ($field_def['type'] == 'video'))
    	{    	    	    		
			if(!empty($args['property']))
    		{    	
    			$field = $args['property'];

    			if($field=='smallestdimension')
    			{
    				$file_location = Kohana::config('webkitfolders.full_upload_folder').'/'.$value->folder.'/'.$value->file;
    				
    				$imageinfo = getimagesize($file_location);
    				
    				$width = $imageinfo[0];
    				$height = $imageinfo[1];
    				
    				if($width<$height)
    				{
    					$value = $width;
    				}
    				else
    				{
    					$value = $height;
    				}
    			}
    			else if($field=='largestdimension')
    			{
    				$file_location = Kohana::config('webkitfolders.full_upload_folder').'/'.$value->folder.'/'.$value->file;
    				
    				$imageinfo = getimagesize($file_location);
    				
    				$width = $imageinfo[0];
    				$height = $imageinfo[1];
    				
    				if($width>$height)
    				{
    					$value = $width;
    				}
    				else
    				{
    					$value = $height;
    				}
    			}
    			else if($field=='dimensions')
    			{
    				$file_location = Kohana::config('webkitfolders.full_upload_folder').'/'.$value->folder.'/'.$value->file;
    				
    				$imageinfo = getimagesize($file_location);
    				
    				$width = $imageinfo[0];
    				$height = $imageinfo[1];
    				
    				$value = $value->width.'x'.$value->height;
    			}
    			/*
    			else if($field=='width')
    			{
    				echo 'testw';
    				exit;
    				$file_location = Kohana::config('webkitfolders.full_upload_folder').'/'.$value->folder.'/'.$value->file;
    				
    				$imageinfo = getimagesize($file_location);
    				$width = $imageinfo[0];
    				$value = $width;
    			}
    			else if($field=='height')
    			{
    				$file_location = Kohana::config('webkitfolders.full_upload_folder').'/'.$value->folder.'/'.$value->file;
    				
    				$imageinfo = getimagesize($file_location);
    				$height = $imageinfo[1];
    				$value = $height;
    			}
    			*/
    			else
    			{
    				if(is_object($value))
    				{
	    				$value = $value->$field;
    				}
    				else if(is_array($value))
    				{
	    				$value = $value[$field];
    				}
    			}
    		}
    		else if($field_def['type'] == 'image')
    		{
    			$file_location = $value->folder.'/'.$value->file;
    		
    			if(!preg_match('/^\//', $file_location))
    			{
    				$file_location = Kohana::config('webkitfolders.upload_folder').'/'.$file_location;
    			}

				$path_parts = explode('/', $file_location);
 				$image_filename = array_pop($path_parts);
 						
 				foreach(Thumbnailcache::$argument_tags as $tag => $realtag)
 				{
					$tagvalue = $args[$tag];
 						
 					if(!empty($tagvalue))
 					{
						$path_parts[] = '_'.$realtag.'_'.urlencode($tagvalue);
 					}
 				}
 						
 				$file_location = implode('/', $path_parts);
 							
 				// if we are tiling then we only want the folder
 				if(empty($arguments['tile']))
 				{
	 				$file_location .= '/'.$image_filename;
 				}

    			$value = $file_location;
    		}
			else if($field_def['type'] == 'file')
    		{
    			$file_location = $value->folder.'/'.$value->file;
    		
    			if(!preg_match('/^\//', $file_location))
    			{
    				$file_location = Kohana::config('webkitfolders.upload_folder').'/'.$file_location;
    			}
    			
    			$value = $file_location;
    		}		
    		else if($field_def['type'] == 'video')
    		{
    			$file_location = $value->folder.'/'.$value->file;
    		
    			if(!preg_match('/^\//', $file_location))
    			{
    				$file_location = Kohana::config('webkitfolders.upload_folder').'/'.$file_location;
    			}

				$path_parts = explode('/', $file_location);
 				$image_filename = array_pop($path_parts);
 				
 				// this is if we are making jpegs from videos so the extension needs to change
 				$is_jpeg = false;
 						
 				foreach(Thumbnailcache::$video_tags as $tag => $realtag)
 				{
					$tagvalue = $args[$tag];
 						
 					if(!empty($tagvalue))
 					{
						$path_parts[] = '_'.$realtag.'_'.urlencode($tagvalue);
						
						if(preg_match('/^thumbnail/', $tag))
 						{
	 						$is_jpeg = true;	
 						}
 					}
 				}
 				
 				$thumbnailstart = empty($value->thumbnailstart) ? 0 : $value->thumbnailstart;
 				
 				if($is_jpeg)
 				{
 					$path_parts[] = '_thumbnail_start_'.urlencode($thumbnailstart);
 				}
 						
 				$file_location = implode('/', $path_parts);
 				$file_location .= '/'.$image_filename;
 							
 				if($is_jpeg)
 				{
 					$file_location .= '.jpg';
 				}
 				
    			$value = $file_location;
    		}
    	}
    	// comments are plain text fields that have newlines transformed into <br>s
    	else if($field_def['type'] == 'comment')
    	{
    		$value = preg_replace('/\r?\n/', '<br/>', $value);
    	}
    	// date manipulation
    	else if($field_def['type'] == 'date')
    	{
    		if(empty($args['format']))
    		{
    			$args['format'] = 'd/m/Y';
    		}

  			if(preg_match('/^(\d+)\/(\d+)\/(\d+)$/', $value, $match))
  			{
    			$date_values = array(
  					'day' => $match[1],
  					'month' => $match[2],
  					'year' => $match[3]
  				);
  			
					$datetime = date_create($date_values['year'].'-'.$date_values['month'].'-'.$date_values['day'].' 12:00:00');
  			
					$value = date_format($datetime, $args['format']);
				}
    	}
    	else if($field_def['type'] == 'datetime')
    	{
    		if(empty($args['format']))
    		{
    			$args['format'] = 'd/m/Y h:i:s';
    		}
    		
  			if(preg_match('/^(\d+)\/(\d+)\/(\d+) (\d+):(\d+):(\d+)$/', $value, $match))
  			{
    			$date_values = array(
  					'day' => $match[1],
  					'month' => $match[2],
  					'year' => $match[3],
  					'hour' => $match[4],
  					'minute' => $match[5],
  					'second' => $match[6]
  				);
  			
					$datetime = date_create($date_values['year'].'-'.$date_values['month'].'-'.$date_values['day'].' '.$date_values['hour'].':'.$date_values['minute'].':'.$date_values['second']);
  			
					$value = date_format($datetime, $args['format']);
  			}
    		
    	}
    	
    	if($args['case'] == 'lower')
    	{
    		$value = strtolower($value);
    	}
    	
    	if($args['case'] == 'upper')
    	{
    		$value = strtoupper($value);
    	}
    	
    	if($args['case'] == 'firstletter')
    	{
    		$value = ucfirst($value);
    	}
    	
    	if($args['json'] == 'true')
    	{
    		$value = json_encode($value);
    	}
    	
    	if($args['urlencode'] == 'true')
    	{
    		$value = urlencode($value);
    	}
    	
		if($args['base64'] == 'true')
    	{
    		$value = base64_encode($value);
    	}
    	
    	return $value;
    }
    
    // takes a reference to a keyword array/object and returns the correct value
    // from the correct place depending upon the field definition
    
    public function get_keyword_value($keyword, $item_type)
    {
		$field_name = $this->get_field_name($keyword->name);
    	
    	$field_def = $this->get_field_type_definition($item_type, $field_name);

    	$property_name = 'value';

		if(isset($field_def['save_to']))
		{
			$property_name = $field_def['save_to'];
		}
		
    	$value = $keyword->$property_name;
    	
    	if(isset($field_def['storage']))
    	{
    		if($field_def['storage'] == 'json')
    		{
    			if(is_string($value))
    			{
    				$value = json_decode($value);
    			}
    		}
    	}
    	
    	return $value;
    }     
    
    public function get_schemas()
    {
    	return $this->process_schemas();
    }
    
    // returns the names of schemas that either have a property or if a value
    // if given - also match that value
    public function get_schemas_that_have_property($property_name, $property_value = null)
    {
    	$schemas = $this->get_schemas();
    	$matching_schema_names = array();
    	
    	foreach($schemas as $schema_name => $schema)
    	{
    		if(!isset($property_value))
    		{
    			if(isset($schema[$property_name]))
    			{
    				$matching_schema_names[] = $schema_name;
    			}	
    		}
    		else
    		{
    			if($schema[$property_name] == $property_value)
    			{
    				$matching_schema_names[] = $schema_name;	
    			}
    		}
    	}
    	
    	return $matching_schema_names;
    }
    
    public function get_schemas_that_inherit_from($inherits_from_name)
    {
    	$schemas = $this->get_schemas();
    	$child_schema_names = array();
    	
    	foreach($schemas as $schema_name => $schema)
    	{
			if($this->does_schema_inherit_from($schema_name, $inherits_from_name))
			{
				$child_schema_names[] = $schema_name;
			}
    	}
    	
    	return $child_schema_names;
    }
    
    public function does_schema_inherit_from($check_schema_name, $inherits_from_name)
    {
    	if($check_schema_name == $inherits_from_name) { return TRUE; }
    	
		$check_schema = $this->get_schema($check_schema_name);
		
		if(!isset($check_schema['path'])) { return FALSE; }
		
		foreach($check_schema['path'] as $in_path)
		{
			if($in_path == $inherits_from_name) { return TRUE; }
		}
		
		return FALSE;
    }
    
    public function has_schema($item_type)
    {
    	$schemas = $this->get_schemas();
    	
    	return isset($schemas[$item_type]);
    }
    
    public function get_schema($item_type)
    {
    	$schemas = $this->get_schemas();
    	
    	if(!isset($schemas[$item_type]))
    	{
    		return array();	
    	}
    	else
    	{
    		return $schemas[$item_type];
    	}
    }
    
    public function get_icon($item_type)
    {
    	$schema = $this->get_schema($item_type);
    	
    	return $schema['icon'];	
    }
    
    public function get_fields($item_type)
    {
    	$schemas = $this->get_schemas();
    	
    	if(isset($this->schema_field_maps[$item_type]))
    	{
    		return $this->schema_field_maps[$item_type];
    	}
    	else
    	{
    		return array();
    	}
    }
    
    public function has_item_got_field($item_type, $field_name)
    {
    	$type = $this->get_field_type($item_type, $field_name);
    	
    	if(isset($type))
    	{
    		return true;
    	}
    	else
    	{
    		return false;
    	}	
    }
    
    public function is_field_list($item_type, $field_name)
    {
    	$def = $this->get_field_definition($item_type, $field_name);
    	
    	return $def['list'];
    }
    
    public function get_field_definition($item_type, $field_name)
    {
    	$field_map = $this->get_fields($item_type);
    	
    	return $field_map[$field_name];
    }
    
    public function get_field_type($item_type, $field_name)
    {
    	$field_map = $this->get_fields($item_type);
    	
    	if(isset($field_map[$field_name]))
    	{
    		$field_def = $field_map[$field_name];
    	
			return $field_def['type'];
		}
		else
		{
			return NULL;
		}
    }
    
    public function get_field_type_definition($item_type, $field_name)
    {
    	$field_type = $this->get_field_type($item_type, $field_name);

    	$ret = $this->field_type_map[$field_type];
    	
    	$ret['type'] = $field_type;
    	
    	return $ret;
    }
    
    public function get_field_storage_location($item_type, $field_name)
    {
    	$field_type = $this->get_field_type($item_type, $field_name);
    	
		$ret = 'value';
		
		if(!isset($this->field_type_map[$field_type]))
		{
			return $ret;
		}
		
		$field_props = $this->field_type_map[$field_type];
		
		if(isset($field_props))
		{
			if(isset($field_props['save_to']))
			{
				$ret = $field_props['save_to'];
			}
		}
		
		return $ret;
    }
    
    public function get_field_storage_type($item_type, $field_name)
    {
    	$field_type = $this->get_field_type($item_type, $field_name);
    	
		$ret = NULL;
		
		if(!isset($this->field_type_map[$field_type]))
		{
			return $ret;
		}
		
		$field_props = $this->field_type_map[$field_type];
		
		if(isset($field_props))
		{
			if(isset($field_props['storage']))
			{
				$ret = $field_props['storage'];
			}
		}
		
		return $ret;
    }
    
    /**
	* 	responsible for processing the Item::schemas variable
	*
	*	it calculates the inheritance based on each schema entry and reflows the tree
	*	to provide a flat map of schemas but with each having its inherited values
	*	assigned
	*
	*	this is used to construct the schemas for the different object types
	*
	* 	@access	public
	* 	@return	array
	*/

	
	private function get_schema_data_from_object($model)
	{
		if(!isset($parent_array))
		{
			$parent_array = array();
		}
		
		$children = array();
		
		foreach($model->_model_children as $child_model)
		{
			$children[$child_model->name] = $this->get_schema_data_from_object($child_model);
		}

		$parent_filter = array();
		$child_filter = array();
		$fields = array();
		
		foreach($model->parent_filter as $config)
		{
			if($config->id != '_none')
			{
				$value = $config->name;
				
				if($config->id == '*')
				{
					$value = '*';
				}
				
				$parent_filter[] = $value;
			}
		}
		
		foreach($model->child_filter as $config)
		{
			if($config->id != '_none')
			{
				$value = $config->name;
				
				if($config->id == '*')
				{
					$value = '*';
				}
				
				$child_filter[] = $value;
			}
		}
		
		foreach($model->fields as $field_config)
		{
			$field_array = array(
				'name' => $field_config->name,
				'title' => $field_config->title,
				'type' => $field_config->type,
				'tab' => $field_config->tab,
				'config' => $field_config->config
			);
			
			$fields[] = $field_array;
		}
		
		$icon = $model->foldericon;
		
		if(empty($icon))
		{
			$icon = 'cube_molecule';
		}
				
		$data = array(
			'id' => $model->name,
			'icon' => $icon,
			// this lets us know which are system models and which are item models
			'is_dynamic' => true,
			'parent_filter' => $parent_filter,
			'child_filter' => $child_filter,
			'add_fields' => $fields,
			'children' => $children
		);
		
		return $data;
	}
	
	private function process_schemas()
	{
		if(isset($this->processed_schemas))
		{
			return $this->processed_schemas;
		}

		$this->processed_schemas = array();
		$this->dynamic_schemas = array();
			
		$flat_schemas = & $this->schemas;

		$generic_model_loader = new Generic_Model();
		
		$this->db->
    		select('item.*')->
    		from('item')->
    		where("item.installation_id = ".installation::id()." and item.item_type = 'model'")->
    		orderby('item.name');
    	
    	$models = $generic_model_loader->load_objects($this->db->get());

    	$this->db->
    		select('item_keyword.*')->
    		from('item, item_keyword')->
    		where("item.installation_id = ".installation::id()." and item.item_type = 'model' and item_keyword.item_id = item.id")->
    		groupby('item_keyword.id');
    		
    	$keywords = $generic_model_loader->load_objects($this->db->get());
    	
    	foreach($keywords->asarray as $keyword)
    	{
    		$model = $models->asmap[$keyword->item_id];	

    		if(preg_match("/^(\w+)\.(\d+)$/", $keyword->name, $match))
    		{
    			$field_name = $match[1];
    			$field_index = $match[2];
    			
    			if(!isset($model->$field_name))
    			{
    				$model->$field_name = array();
    			}
    			
    			$arr = & $model->$field_name;
    			
    			$arr[$field_index] = json_decode($keyword->long_value);
    		}
    		else
    		{
    			$field_name = $keyword->name;
    			
    			if(preg_match("/^[\{\[]/", $keyword->long_value))
    			{
    				$model->$field_name = json_decode($keyword->long_value);
    			}
    			else
    			{
    				$model->$field_name = $keyword->value;
    			}
    		}
    	}
    	
    	$models_inheriting_from_static_schema = array();
    	
    	foreach($models->asarray as $model)
    	{
    		if(preg_match("/^\d+$/", $model->inherits_from->id))
    		{
    			$parent_model = $models->asmap[$model->inherits_from->id];
    			
    			if(!isset($parent_model->_model_children))
    			{
    				$parent_model->_model_children = array();
    			}
    			
    			$parent_model->_model_children[] = $model;
    		}
    		else
    		{
    			$models_inheriting_from_static_schema[] = $model;
    		}
    	}
    	
    	foreach($models_inheriting_from_static_schema as $model)
    	{
    		#$static_schema_array = & $this->schema_index[$model->inherits_from->name];
    		
    		$data = $this->get_schema_data_from_object($model);
    		
    		$parent_prop = $model->inherits_from->name;
    		$array = $this->dynamic_schemas[$parent_prop];
    		$array[] = $data;
    		
    		$this->dynamic_schemas[$parent_prop] = $array;
    	}

		foreach($flat_schemas as $name => $schema)
		{
			$this->process_schema($name, $schema);	
		}

		$final_schemas = $this->processed_schemas;
			
		foreach($final_schemas as $schema_name => $schema)
		{
			$bubble_map = $this->properties_using_default_bubble;
				
			foreach($bubble_map as $prop => $value)
			{
				unset($schema['default_'.$prop]);
				unset($schema['add_'.$prop]);
			}
				
			$this->processed_schemas[$schema_name] = $schema;
		}
		
		foreach($this->processed_schemas as $schema_name => $schema)
		{
			$field_map = array();
		
			foreach($schema['fields'] as $field)
			{
				$field_map[$field['name']] = $field;
			}
			
			$this->schema_field_maps[$schema_name] = $field_map;
		}
		
		return $this->processed_schemas;
	}
    
    
    private function process_schema($name, $passed_schema, $parent_schema = NULL)
    {
		$blank_schema = array(
			'id' => $name,
			'path' => array() );
	
		// If we havn't been given a parent - it means this is the default one
		// we will create a quasi parent instead	
		
		if(!isset($parent_schema))
		{
			$parent_schema = array();
		}
		else
		{
			// If we do have a parent schema lets use its path information
			// to build the path to this schema
			// this is useful for asking if a definition inherits from another
		
			$new_path = array();
			
			foreach($parent_schema['path'] as $path)
			{
				$new_path[] = $path;
			}
		
			$new_path[] = $parent_schema['id'];
			
			$blank_schema['path'] = $new_path;
		}
	
		// So - lets start by looping through each of the parent schemas
		// properties so we can populate the current definition with its default
		// values
		//
		// we wont copy children over however because we will do this manually
		//
		// internal values holds a copy of the private processing values
	
		$internal_values = array();
		
		foreach($parent_schema as $prop => $value)
		{
			if(!isset($this->internal_schema_variables[$prop]))
			{
				$blank_schema[$prop] = $value;
			}
			else
			{
				$internal_values[$prop] = $value;
			}
		}
	
		//////////////////////////////////////////////////////////////////////
		//////////////////////////////////////////////////////////////////////
		//
		// DEFAULT
		//
		// Now we have assigned the values from the parent
		// Lets see if there are any defaults to populate
		//
		// if there are any default definitions (i.e. ones that will always apply)
		// - they need to be merged with the defaults from the parent
	
		foreach($passed_schema as $prop => $value)
		{
			// is this field a default_merge?
			if(preg_match('/^default_(.*)$/i', $prop, $matches))
			{
				// what field needs to be merged
				$field_name = $matches[1];
				
				$existing_default_values = NULL;
				$existing_normal_values = NULL;
					
				// the default values from the parent
				if(isset($blank_schema[$prop]))
				{
					$existing_default_values = $blank_schema[$prop];
				}
			
				// the actual values from the parent
				if(isset($blank_schema[$field_name]))
				{
					$existing_normal_values = $blank_schema[$field_name];
				}
			
				// lets merge the existing values with the current ones
				$existing_default_values = $this->merge_schema_values($value, $existing_default_values);
			
				$existing_normal_values = $this->merge_schema_values($value, $existing_normal_values);
			
				// finally apply the merged value back into the current definition
				$blank_schema[$prop] = $existing_default_values;
				$blank_schema[$field_name] = $existing_normal_values;
			}
		}
	
		
		//////////////////////////////////////////////////////////////////////
		//////////////////////////////////////////////////////////////////////
		//
		// ADD
		//
		// now lets see if we need to add anything into the existing values
	
		foreach($passed_schema as $prop => $value)
		{
			// is this field a default_merge?
			if(preg_match('/^add_(.*)$/i', $prop, $matches))
			{
				// what field needs to be added to
				$field_name = $matches[1];
			
				// the existing values from the parent
				$existing_values = $blank_schema[$field_name];
			
				$existing_values = $this->merge_schema_values($value, $existing_values);

				// finally apply the merged value back into the current definition
				$blank_schema[$field_name] = $existing_values;
			}
		}	
	
		// at this point - we have a copy of the parents schema
		//
		// the default fields are merged with the current entry however
		//
		// So - we now want to loop the given schema again and assign
		// its values in the current one
	
		foreach($passed_schema as $prop => $value)
		{
			if(!isset($this->internal_schema_variables[$prop]))
			{
				$value = $passed_schema[$prop];
		
				$default_match = preg_match('/^default_(.*)$/i', $prop);
				$add_match = preg_match('/^add_(.*)$/i', $prop);
		
				if(($default_match)||($add_match))
				{
				
				}
				else
				{
					$default_value = NULL;
					
					if(isset($blank_schema['default_'.$prop]))
					{
						$default_value = $blank_schema['default_'.$prop];
					}
				
					$overwrite_value = $this->merge_schema_values($value, $default_value);
			
					$blank_schema[$prop] = $overwrite_value;
				}
			}
		}
		
		////////////////////////////////////////////////
		////////////////////////////////////////////////
		////////////////////////////////////////////////
		////////////////////////////////////////////////
		// before we pass the schema into its children for processing
		// we must apply any instructions given by the schema
		//
		// we must do this BEFORE passing on to the children so the child has the correct fields to base itself upon
		
		$replace_map = array();
		$remove_map = array();
		
		if(isset($passed_schema['replace_fields']))
		{
			foreach($passed_schema['replace_fields'] as $replace)
			{
				$replace_map[$replace['name']] = $replace;
			}
		}
		
		if(isset($passed_schema['remove_fields']))
		{
			foreach($passed_schema['remove_fields'] as $remove)
			{
				$remove_map[$remove] = TRUE;
			}
		}
		
		$new_fields = array();
		$new_default_fields = array();

		foreach($blank_schema['fields'] as $field)
		{
			$field_name = $field['name'];
			
			if(!isset($remove_map[$field_name]))
			{
				$new_field = $field;
				
				if(isset($replace_map[$field_name]))
				{
					$new_field = $replace_map[$field_name];
				}
				
				$new_fields[] = $new_field;
			}
		}
		
		foreach($blank_schema['default_fields'] as $field)
		{
			$field_name = $field['name'];
			
			$new_field = $field;
				
			if(isset($replace_map[$field_name]))
			{
				$new_field = $replace_map[$field_name];
			}
				
			$new_default_fields[] = $new_field;
		}
			
		$blank_schema['fields'] = $new_fields;
		$blank_schema['default_fields'] = $new_default_fields;
		
		$dynamic_schemas = $this->dynamic_schemas[$passed_schema['id']];
		
		if(isset($dynamic_schemas))
		{
			if(!isset($passed_schema['children']))
			{
				$passed_schema['children'] = array();
			}
			
			foreach($dynamic_schemas as $dynamic_schema)
			{
				$passed_schema['children'][$dynamic_schema['id']] = $dynamic_schema;
			}
		}
			
		if(isset($passed_schema['children']))
		{
			foreach($passed_schema['children'] as $child_name => $child_schema)
			{
				$this->process_schema($child_name, $child_schema, $blank_schema); 	
			}
		}
		
		////////////////////////////////////////////////
		////////////////////////////////////////////////
		////////////////////////////////////////////////
		////////////////////////////////////////////////
		// now we have the final schema with inherited values
		//
		// we can write this schema to the global cache now because it is complete
		//
		// we will also cache a field_map so we can get to the field defs quickly
		
		
		$this->processed_schemas[$name] = $blank_schema;
    }
    
    private function merge_schema_values($value, $existing_value)
	{
		$new_values = array();
		
		if(!isset($existing_value))
		{
			$existing_value = array();
		}
		
		if(!isset($value))
		{
			return $existing_value;
		}
		
		if(is_array($value))
		{
			if($this->is_normal_array($value))
			{
				foreach($existing_value as $existing_value_value)
				{
					$new_values[] = $existing_value_value;
				}
				
				foreach($value as $value_part)
				{
					$new_values[] = $value_part;
				}				
			}
			else
			{
				foreach($existing_value as $existing_value_prop => $existing_value_value)					
				{
					$new_values[$existing_value_prop] = $existing_value_value;
				}
				
				foreach($value as $key => $v)
				{
					$new_values[$key] = $v;
				}
			}
		}
		else
		{
			$new_values = $value;
		}
	
		return $new_values;
	}
	
	private function is_normal_array($arr)
	{
	    $is_hash = array_keys($arr) != range(0, count($arr) - 1);
	    
	    return !$is_hash;
	}
	
	private function flexible_get(& $obj, $field)
    {
    	if(is_array($obj))
    	{
    		return $obj[$field];
    	}
    	else if(is_object($obj))
    	{
    		return $obj->$field;
    	}
    	else
    	{
    		return NULL;
    	}
    }
    
    private function flexible_set(& $obj, $field, $value)
    {
    	if(is_array($obj))
    	{
    		$obj[$field] = $value;
    	}
    	else if(is_object($obj))
    	{
    		$obj->$field = $value;
    	}
    }
}
?>