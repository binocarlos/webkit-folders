<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
         "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Xara Admin System</title>
	<meta name="viewport" content="width=devicewidth; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;"/>
	<link rel="apple-touch-icon" href="/iui/iui-logo-touch-icon.png" />
	<meta name="apple-mobile-web-app-capable" content="yes" />
	<link rel="stylesheet" href="/iui/iui.css" type="text/css" />
	<link rel="stylesheet" href="/iui/iui-moz.css" type="text/css" />
	<link rel="stylesheet" title="Default" href="/iui/t/default/default-theme.css"  type="text/css"/>
	<script type="application/x-javascript" src="/iui/iui.js"></script>
	<script type="text/javascript">
		iui.animOn = true;
	</script>
</head>

<body>
    <div class="toolbar">
        <h1 id="pageTitle"></h1>
        <a id="backButton" class="button" href="#"></a>
        <!--<a class="button" href="#searchForm">Search</a>-->
    </div>
    
<?php

	

	function printItemForm($item)
	{
		$id = $item->id;
		$name = $item->name;
		
		$html = <<<EOT
<form id="$id" title="$name" class="panel" method="post" action="/app/mobile/save/$id" target="_self">
<fieldset>
EOT;

		$fields = $item->get_fields();
		
		$field_type_map = array(
			'string' => 'text',
			'date' => 'text',
			'text' => 'textarea',
			'comment' => 'textarea',
			'html' => 'textarea'
		);
		
		foreach($fields as $field)
		{
			$field_title = $field['title'];
			$field_name = $field['name'];
			$field_value = $item->$field_name;
			$field_type = $field['type'];
			
			$gui_type = $field_type_map[$field_type];
			
			if(isset($gui_type))
			{				
				if ($gui_type == "text")
				{
					$html .= <<<EOT
<div class="row">
<label>$field_title</label>
<input type="text" name="$field_name" value="$field_value">
</div>			
EOT;
				}
				if ($gui_type == "textarea")
				{
					$html .= <<<EOT
<div class="row">
<label>$field_title</label>
<textarea name="$field_name">$field_value</textarea>
</div>			
EOT;
				}				
			}

		}

		$html .= <<<EOT
</fieldset>
<input type="submit" class="whiteButton" value="save">
</form>
EOT;

		echo $html;
	}
	
	function printItemBlock($item, $children = null, $selected = false)
	{
		$id = $item->id;
		$name = $item->safe_name();
		$selected_html = '';
		
		if($selected)
		{
			$selected_html = ' selected="true"';
		}
		
		$html = <<<EOT
<ul id="$id" title="$name"$selected_html>\n
EOT;

		if(isset($children))
		{
			foreach($children as $child)
			{
				$child_id = $child->id;
				$child_name = $child->safe_name();
				$icon_name = $child->get_icon_name();
				
				$html .= <<<EOT
	<li><a href="#{$child_id}"><img src="/app/icon/_20/$icon_name.png"> $child_name</a></li>\n
EOT;
			}
		}
		
		$html .= <<<EOT
</ul>\n
EOT;

		echo $html;
		
		if(isset($children))
		{
			foreach($children as $child)
			{
				$children = $child->children;
				
				if((isset($children))&&(count($children)>0))
				{
					printItemBlock($child, $child->children);
				}
				else
				{
					printItemForm($child);
				}
			}
		}
	}
	
	printItemBlock($top_item, $root_items, true);
?>
</body>
</html>