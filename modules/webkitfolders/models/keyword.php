<?php defined('SYSPATH') OR die('No direct access allowed.');

// ------------------------------------------------------------------------

/**
 * Item Class
 *
 * Object representing one item and its data
 * 
 */
 
// ------------------------------------------------------------------------

class Keyword_Model extends Simpleorm_Model
{
	protected $_table_name = 'item_keyword';
	protected $_fields = array('installation_id', 'item_id', 'keyword_type', 'field_type', 'name', 'value', 'id_value', 'number_value', 'date_value', 'long_value');
	
    function __construct($id = NULL)
    {
        parent::__construct($id);
    }
    
    
    
    
}
?>