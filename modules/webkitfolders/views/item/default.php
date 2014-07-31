<style>
	td { font-family:Tahoma; font-size:10pt; background-color:#ffffff; }
</style>
<table border=0 cellpadding=5 cellspacing=1 bgcolor=#cccccc>
<?php
$fields = $item->get_fields();
$item_type = $item->item_type;

$html = <<<EOT
<tr>
<td align=right><b>Type</b></td><td>$item_type</td>
</tr>	
EOT;

echo $html;

$schema = Schema_Model::instance();	

foreach($fields as $field)
{
	$fieldname = $field['name'];
	$fieldtitle = $field['title'];
	$fieldtype = $field['type'];
	$value = $schema->get_flat_field_value($fieldname, $item->item_type, $item->$fieldname, array(
		'size' => 100
	));

	if($fieldname == 'foldericon')
	{
		$value = $item->get_icon_name();
	}
	
	if(($fieldtype == 'file') || ($fieldtype == 'image'))
	{
		$value = '<img src="'.$value.'">';
	}
	
	$html = <<<EOT
<tr>
<td align=right><b>$fieldtitle</b></td><td>$value</td>
</tr>	
EOT;

	echo $html;
}
?>
</table>