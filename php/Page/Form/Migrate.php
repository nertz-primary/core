<?php
/**
 * Страница редактора структуры конфигурационных файлов
 *
 */

class Page_Form_Migrate extends Nertz_Page 
{
    function Page_Form_Migrate($name)
    {
        parent::Nertz_Page($name);
    }
    function show()
    {
        global $template, $url, $db, $auth, $session;
        include_once(Nertz::class_path('Nertz_Config_Forms'));
        $arr = $this->forms->fetch(array('core'));
        print_r($arr);
    	
    }
}
