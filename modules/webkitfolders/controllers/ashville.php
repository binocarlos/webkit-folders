<?php defined('SYSPATH') OR die('No direct access allowed.');

// ------------------------------------------------------------------------

/**
 * Ashville Controller
 *
 * Loads their menu bar for each request
 *
 *
 *
 *
 */
 
// ------------------------------------------------------------------------ 

class Ashville_Controller extends Website_Controller
{
	public function adjust_full_path()
	{
		$fulluri = preg_replace('/^\//', '', $_SERVER["REQUEST_URI"]);
		
		$page = new Item_Model('/website/'.$fulluri, true);
		
		$usetemplate = '';
		
		if(!empty($page->template))
		{
			$usetemplate = $page->template;
		}
		else if($page->item_type=='team_member')
		{
			$usetemplate = 'team_view.htm';
		}
		else if($page->item_type=='project')
		{
			$usetemplate = 'projects_view.htm';
		}
		
		if(!empty($usetemplate))
		{
			$parts = explode('/', $this->full_path);
			$last = array_pop($parts);
			$parts[] = $usetemplate;
			
			$this->full_path = implode('/', $parts);
			$this->uri = '/'.$usetemplate;
		}
	}
	
	protected function process_render($string)
	{
		$menu = new Item_Model('/website');
		$menu->load_children(true, true);
		
		$menuhtml = '';
		
		$sorter = new ItemSorter($menu->children);
	 						
		$main_items = $sorter->get_sorted_items('position', 'asc');
		
		foreach($main_items as $child)
		{
			$menuhtml .= $this->render_menu_child($child);
		}
		
		$string = preg_replace('/<menuhtml>/', $menuhtml, $string);
		
		if(preg_match('/<sitemaphtml>/', $string))
		{
			$sitemaphtml = '<div id="sitemap"><ul>';
			
			foreach($main_items as $child)
			{
				$sitemaphtml .= $this->render_sitemap_child($child);
			}
			
			$sitemaphtml .= '</ul></div>';
			
			$string = preg_replace('/<sitemaphtml>/', $sitemaphtml, $string);
		}
		
		return $string;
	}
	
	protected function render_sitemap_child($item)
	{
		$url = $item->path;
		$title = $item->page_title;
		
		$url = preg_replace('/^\/website\//', '', $url);
		
		if(empty($title))
		{
			$title = $item->title;	
		}
		
		$fulluri = preg_replace('/^\//', '', $_SERVER["REQUEST_URI"]);
		$parts = explode('/', $fulluri);

		$class = "";
				
		$ret=<<<EOT
		
<li><a href="/{$url}"><span>{$title}</span></a></li>	
EOT;

		if(count($item->children)>0)
		{
			$section = $parts[0];
			
			$ret .= '<ul>';
			
			$sorter = new ItemSorter($item->children);
		 						
			$child_items = $sorter->get_sorted_items('position', 'asc');
	
			foreach($child_items as $child)
			{
				if($child->item_type=='contact') { continue; }
				
				$ret.=$this->render_sitemap_child($child);				
			}
			
			$ret .= '</ul>';
		}

		return $ret;
	}
	
	protected function render_menu_child($item)
	{
		$url = $item->url;
		$title = $item->page_title;
		
		if(!$item->menu) { return ''; }
		
		$fulluri = preg_replace('/^\//', '', $_SERVER["REQUEST_URI"]);
		$parts = explode('/', $fulluri);

		$class = "";
		
		if($item->url==$parts[0])
		{
			$class = 'selected';
		}
				
		$ret=<<<EOT
		
<li><a class="{$class} menuLink" href="/{$url}/"><span>{$title}</span></a></li>	
EOT;

		$section = $parts[0];
		
		if($item->url!=$section)
		{
			return $ret;
		}
		
		$ret .= '<div id="sublinks" style="display:none;"><ul>';
		
		$sorter = new ItemSorter($item->children);
	 						
		$child_items = $sorter->get_sorted_items('position', 'asc');

		foreach($child_items as $child)
		{
			if($child->item_type=='contact') { continue; }
			
			$childtitle = $child->page_title;
			$childurl = $child->url;
			$childclass = '';
			
			if($childurl==$parts[1])
			//if($url.'/'.$childurl==$fulluri)
			{
				$childclass = 'selected';
			}
			
			$ret.=<<<EOT
		
<li><a class="{$childclass}" href="/{$url}/{$childurl}/"><span>{$childtitle}</span></a></li>

		
EOT;

		}
		
		$ret .= '</ul></div>';

		return $ret;
	}
	
}
?>