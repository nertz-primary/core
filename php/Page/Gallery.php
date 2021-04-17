<?php
/**
 * Страница редактора структуры конфигурационных файлов
 *
 */
include_once(Nertz::class_path('Nertz_Form'));
include_once(Nertz::class_path('Nertz_File'));
include_once(Nertz::class_path('Nertz_Store'));
include_once(Nertz::class_path('Page_Admin_GalleryItem'));

class Page_Gallery extends Nertz_Page 
{
    function Page_Gallery($name)
    {
        parent::Nertz_Page($name);
    }
    function show()
    {
        global $template, $url, $db;
        $template->add_css('includes/highslide/highslide.css');
        $template->add_js('includes/highslide/highslide.js');
        $p = intval($url->get_value('p', 0));
        $gallery_ind = intval($url->get_value('gallery_ind', 0));
         
        if (!$gallery_ind) {
        	$items = $db->getPaged('SELECT f.ind ind, f.gallery_ind gallery_ind, s.ind ind, f.ind img_ind, f.caption caption, s.name img_name, f.w w, f.h h  FROM ?_gallery_item f INNER JOIN ?_gallery g ON g.ind = f.gallery_ind INNER JOIN ?_store_file s ON s.ind = f.img_ind ORDER BY g.created DESC, f.created',array(),$p,12);
        	$gallery_inds = array_extract($items['data'],'gallery_ind', 'gallery_ind');
        	$gallery = $db->getAssoc('SELECT * FROM ?_gallery WHERE ind IN(?a) ORDER BY created DESC', array($gallery_inds));	
        } else {
        	$items = $db->getPaged('SELECT f.ind ind, f.gallery_ind gallery_ind, s.ind ind, f.ind img_ind, f.caption caption, s.name img_name, f.w w, f.h h  FROM ?_gallery_item f INNER JOIN ?_gallery g ON g.ind = f.gallery_ind INNER JOIN ?_store_file s ON s.ind = f.img_ind WHERE f.gallery_ind = ?',array($gallery_ind),$p,12);
        	$gallery_inds = array_extract($items['data'],'gallery_ind', 'gallery_ind');
        	$gallery = $db->getAssoc('SELECT * FROM ?_gallery WHERE ind IN(?a) ORDER BY created DESC', array($gallery_inds));
        }
        
        foreach ($items['data'] as $item) {
        	if (!isset($gallery[$item['gallery_ind']]['items'])) {
        		 $gallery[$item['gallery_ind']]['items'] = array();
        	}
        	$item['url'] = $url->gen_static_url(Nertz_Store::gen_url($item['ind'], POPUP_NAME));
        	$item['ml'] = intval(floatval(PREVIEW_WIDTH - $item['w'])/2.0);
        	$item['preview_url'] = $url->gen_static_url(Nertz_Store::gen_url($item['ind'], PREVIEW_NAME));
        	$gallery[$item['gallery_ind']]['items'][] = $item;
        }
        
        foreach ($gallery as $i => $g) {
        	$gallery[$i]['items'] = kvadratura($gallery[$i]['items'], 3);
        }
        $items['data'] = $gallery;
        $template->set_value('title',  "Летопись");
        $template->set_value('items',  $items);
        $template->set_value('gallery_ind',  $gallery_ind);
        return $template->render('Page_Gallery');
    }
}
