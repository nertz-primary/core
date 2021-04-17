<?php
/**
 * Страница редактора структуры конфигурационных файлов
 *
 */
include_once(Nertz::class_path('Nertz_Form'));
class Page_Nertz_Logout extends Nertz_Page
{
	function Page_Nertz_Logout($name)
	{
		parent::Nertz_Page($name);
	}
	function show()
    {
    	global $auth;
    	$auth->logout();
    	Nertz::redirect(array('page' => Nertz::config('/main/default_page')));
    }
}