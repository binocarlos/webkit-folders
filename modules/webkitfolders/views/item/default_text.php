<?php
$fields = $item->get_fields();
$item_type = $item->item_type;

$schema = Schema_Model::instance();	

foreach($fields as $field)
{
	$fieldname = $field['name'];
	$fieldtitle = $field['title'];
	$fieldtype = $field['type'];
	$value = $schema->get_flat_field_value($fieldname, $item->item_type, $item->$fieldname);
	
	$line = <<<EOT
$fieldtitle: $value

EOT;

	echo $line;
}
?>