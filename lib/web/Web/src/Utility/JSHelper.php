<?php
/**
 * Web Date Utility
 * 
 * @category Web
 * @package Utility
 */

namespace Web\Utility;

class JSHelper {
    
	public static function ElementUpdate($id, $content)
	{
		if(is_array($content)) {
			if(array_key_exists('partial', $content)) {
				$request = new Request;
				
				$view = Page::find_view($request->controller, $content['partial'], true);
				//ticket #15811 
				if($view) $content = Page::parse_template($view);
			}
		}
		
		$content = str_replace("\n", '\n', $content);
		$content = str_replace("\r", "", $content);
		$content = "'".str_replace("'", "\'", $content)."'";
		return self::output("Element.update('{$id}', $content);\n");
	}
	
	public static function reload()
	{
		return self::output("document.location=document.location;");
	}
	
	public static function RedirectTo($args)
	{
		return self::output("document.location='".$args."';");
	}
	
	public static function RedirectToTop($args)
	{
		return self::output("top.content.location='".$args."';");
	}
	
	public static function Show($id)
	{
		return self::output("Element.show('$id')\n");
	}
	
	public static function Hide($id)
	{
		return self::output("Element.hide('$id')\n");
	}
	
	private static function output($content)
	{
		return $content;
	}
}

