<?php
/**
 * Страница отображения статического содержимого
 *
 */
class Page_Static extends Nertz_Page 
{
    function Page_Static($name)
    {
        parent::Nertz_Page($name);
    }
    function show()
    {
    	global $template, $url, $db;
    	$ind  = $url->get_value('name', $url->get_value('act',$url->get_page()));
        $page = $db->getRow('SELECT * FROM ?_static WHERE ind = ?', array($ind));
        $template->set_value('title', $page['caption']);
        $template->set_value('body',  $page['body']);
       	return $template->render('Page_Static');		
    }
}
