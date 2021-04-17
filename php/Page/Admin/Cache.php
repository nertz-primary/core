<?php
/**
 * Страница редактора структуры конфигурационных файлов
 *
 */
include_once(Nertz::class_path('Nertz_Form'));
class Page_Admin_Cache extends Nertz_Page 
{
	function Page_Admin_Cache($name)
	{
		parent::Nertz_Page($name);
	}
	function show()
    {
    	global $cache;
    	$cache->drop_all();
    	
    	return "Кэш очищен!!!";
    }
}