<?php
/**
 * Страница редактора структуры конфигурационных файлов
 *
 */
include_once(Nertz::class_path('Nertz_Form'));
include_once(Nertz::class_path('Nertz_File'));
include_once(Nertz::class_path('Nertz_Store'));
include_once(Nertz::class_path('Page_Admin_File'));

class Page_File extends Nertz_Page 
{
    function Page_File($name)
    {
        parent::Nertz_Page($name);
    }
    function show()
    {
        global $template, $url, $db;
        $p = intval($url->get_value('p', 0));
        $items = $db->getPaged('SELECT f.file_ind file_ind, s.ind ind, f.caption caption, s.name name, s.size size  FROM ?_file_item f INNER JOIN ?_store_file s ON s.ind = f.store_ind ORDER BY f.file_ind DESC, f.created',array(),$p,10);
        $file_inds = array_extract($items['data'],'file_ind', 'file_ind');
        $file = $db->getAssoc('SELECT * FROM ?_file WHERE ind IN(?a) ORDER BY created DESC', array($file_inds));
        foreach ($items['data'] as $item) {
        	if (!isset($file[$item['file_ind']]['items'])) {
        		 $file[$item['file_ind']]['items'] = array();
        	}
        	$item['url']  = $url->gen_static_url(Nertz_Store::gen_url($item['ind'], $item['name']));
        	$item['ico']  = $url->gen_static_url(Nertz_File::icon_path($item['name']));
        	$item['size'] = Nertz_File::show_size($item['size']);
        	$file[$item['file_ind']]['items'][] = $item;
        }
        $items['data'] = $file;
        $template->set_value('title',  "Кладовая");
        $template->set_value('items',  $items);
        return $template->render('Page_File');
    }
}
