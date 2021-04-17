<?php

include_once(Nertz::class_path('Nertz_Store'));

/**
 * Страница редактора структуры конфигурационных файлов
 *
 */
class Page_Image extends Nertz_Page 
{
    function Page_Image($name)
    {
        parent::Nertz_Page($name);
    }
    function show()
    {
        global $url;
    	$width  = $url->get_value('width', 100);
    	$height = $url->get_value('height', 100);
    	$ind    = $url->get_value('ind', 0);
    	if (!$ind) {
    		header("HTTP/1.0 404 Not Found");
    		return false;	
    	}
    	$file = Nertz_Store::get($ind);
    	session_cache_limiter( FALSE );
    	header('Etag: '. $file['md5']); 
    	header("Last-Modified: ".gmdate("D, d M Y H:i:s", $file['updated'])." GMT"); 
    	
		if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $file['updated'] || trim($_SERVER['HTTP_IF_NONE_MATCH']) == $file['md5']) {
    		header("HTTP/1.1 304 Not Modified");
    		return false;
		} 
		
    	
        return 123;
    }
}