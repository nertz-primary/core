<?php

include_once(Nertz::class_path('Nertz_Page_Form'));
include_once(Nertz::class_path('Nertz_File'));
include_once(Nertz::class_path('Nertz_Image'));
include_once(Nertz::class_path('Page_Admin_Gallery'));

class Page_Admin_GalleryItem extends Nertz_Page_Form
{
    function Page_Admin_GalleryItem($params)
    {
    	global $db,$template, $url;
    	$this->gallery_ind = $url->get_value('gallery_ind', 0);
        $this->form_params =  array(
			"name"        => "main",
			"caption"      => "Файлы галереи",
			"edit_caption" => "Изменить файл",
			"add_caption"  => "Добавить файл",
			"index_field" => "ind",
			"url"         => array('gallery_ind' => $this->gallery_ind),
			"on_act_back" => array(&$this, "on_act_back"),
			"on_act_regen" => array(&$this, "on_act_regen"),			
			"sql"     => array(
				'table'  => 'gallery_item',
				'select' => 'SELECT * FROM ?_gallery_item WHERE gallery_ind = \'' . $db->quote($this->gallery_ind) . '\' ORDER BY created'
				),
			"buttons" => array(
				"back" => array(
			    	 "form_caption"  => "",
			    	 "table_caption" => "Назад",
			    	 "image"         => "core/img/button/back.gif"
		    	 ),
				"add" => array(
			          "form_caption"  => "",
			   	      "table_caption" => "Добавить",
			   	      "image"         => "core/img/button/add.gif"
			     ),
			     "delete" => array(
			          "form_caption"  => "",
			   	      "table_caption" => "Удалить",
			   	      "image"         => "core/img/button/delete.gif"
			     ),
			   /*  "regen" => array(
			    	 "form_caption"  => "",
			    	 "table_caption" => "Перегенерить",
			    	//"image"         => "core/img/button/back.gif"
		    	 ),*/
			     "save" => array(
			          "form_caption"  => "Сохранить",
			   	      "table_caption" => "",
			   	      "image"         => "core/img/button/ok.gif"
			     ),
			     "cancel" => array(
			          "form_caption"  => "Отменить",
			   	      "table_caption" => "",
			   	      "image"         => "core/img/button/cancel.gif"
			     ),
		    	 ),
			"fields"      => array(
				"pic" => Array(
			    		"form_caption"  => "",
			    		"table_caption" => "Превью",
			    		"description"   => "",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "String",
			    		"on_table_show" => array(&$this, 'pic_show'),
			    		),
				"caption" => Array(
			    		"form_caption"  => "Заголовок",
			    		"table_caption" => "Заголовок",
			    		"description"   => "",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "String",
			    		"length"        => 32,
			    		"max_lenght"    => 255,
			    		),
			    "img_ind" => Array(
			    		"form_caption"  => "Фотография",
			    		"table_caption" => "",
			    		"description"   => "Для видео необходимо сделать скриншот одного из кадров.",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "Storefile",
			    		"extensions"	=> array('jpg', 'gif', 'png'),
						"before_save"   => array(&$this, "before_save"),			    		
						"after_save"    => array(&$this, "after_save"),
						"before_delete" => array(&$this, "before_delete"),
			    		),
			    "delete" => Array(
			    		"form_caption"  => "",
			    		"table_caption" => "Удалить",
			    		"description"   => "Удалить группу",
			    		"type"          => "CheckBox",
			    		),
			    "edit" => Array(
			    		"form_caption"  => "",
			    		"table_caption" => "Редактор",
			    		"description"   => "Редактировать группу",
			    		"type"          => "Button",
			    		"pic_url"       => "core/img/button/edit.gif",
			    		"act"           => "edit",
			    		),	
			    ),
			);
    	parent::Nertz_Page_Form($params);
    }
    function before_delete(&$p, &$inds)
    {
		$rows = $this->item->get($inds);
		$finds = array();
		foreach ($rows as $row) {
			if ($row['img_ind']) {
				$finds[] = $row['img_ind'];	
			}
		}
		Nertz_Store::delete($finds);
		Page_Admin_Gallery::update_file_count($this->gallery_ind, -1 * count($rows));
		return true;
    }
    function before_save(&$p, &$row, $act, $ind)
    {
    	// Заполним недостающие поля
    	$this->files[$p->name] = $_FILES[$p->_get_post_name()];
    	$row['gallery_ind'] = $this->gallery_ind;
    	return true;
    }
    function after_save(&$p, &$row, $act, $ind)
    {
    	if ($act == 'insert') {
    		Page_Admin_Gallery::update_file_count($this->gallery_ind, 1);
    	}
    	if ($p->name == 'img_ind' && $this->files[$p->name]['name']) {
		//	Nertz_Store::gen_preview($row[$p->name],PREVIEW_WIDTH, PREVIEW_HEIGHT, PREVIEW_BG, $this->files[$p->name]['name']);    			$img = new Nertz_Image();
    		$img = new Nertz_Image();
    		// Генерим превьюху
    		$img->load(Nertz_Store::gen_path($row[$p->name], $this->files[$p->name]['name']));
    		$img->resize(PREVIEW_WIDTH, PREVIEW_HEIGHT);
    		$img->save(Nertz_Store::gen_path($row[$p->name], PREVIEW_NAME));
    		// Генерим попап
    		$img->load(Nertz_Store::gen_path($row[$p->name], $this->files[$p->name]['name']));
    		$img->resize(POPUP_WIDTH, POPUP_HEIGHT);
    		$img->save(Nertz_Store::gen_path($row[$p->name], POPUP_NAME));
    		$sz = getimagesize(Nertz_Store::gen_path($row[$p->name], PREVIEW_NAME));
    		global $db;
    		$db->save('gallery_item', array('w' => $sz[0], 'h' => $sz[1]), array('ind' => $ind));
    	}
    	return true;
    }
    function size_show(&$field, $row)
    {
    	return Nertz_File::show_size($row[$field->name]);
    }
    function pic_show(&$field, $row)
    {
    	global $url;
    	return '<img src="' . $url->gen_static_url(Nertz_Store::gen_url($row['img_ind']['ind'], PREVIEW_NAME)). '" />';
    }
    function on_act_back()
    {
    	Nertz::redirect(array('page' => 'admin_gallery'));
    }
    function on_act_regen()
    {
    	global $db;
 		$res = $db->getAll('SELECT * FROM ?_gallery_item WHERE gallery_ind = ?', array($this->gallery_ind));
 		if (is_array($res)) {
 			foreach ($res as $file) {
 				$img = new Nertz_Image();
    			$img->load($this->make_file_name($file['ind'], $file['img_name']));
    			$img->make_normal_preview(PREVIEW_WIDTH, PREVIEW_HEIGHT, PREVIEW_BG);
    			$img->save($this->make_file_name($file['ind'], PREVIEW_NAME));
 			}
 		}
    }
}