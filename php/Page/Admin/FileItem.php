<?php

include_once(Nertz::class_path('Nertz_Page_Form'));
include_once(Nertz::class_path('Nertz_File'));
include_once(Nertz::class_path('Nertz_Image'));
include_once(Nertz::class_path('Page_Admin_File'));

class Page_Admin_FileItem extends Nertz_Page_Form
{
    function Page_Admin_FileItem($params)
    {
    	global $db,$template, $url;
    	$this->file_ind = $url->get_value('file_ind', 0);
        $this->form_params =  array(
			"name"        => "main",
			"caption"      => "Файлы галлереи",
			"edit_caption" => "Изменить файл",
			"add_caption"  => "Добавить файл",
			"index_field" => "ind",
			"url"         => array('file_ind' => $this->file_ind),
			"on_act_back" => array(&$this, "on_act_back"),
			"on_act_regen" => array(&$this, "on_act_regen"),			
			"sql"     => array(
				'table'  => 'file_item',
				'select' => 'SELECT fi.ind ind, fi.caption caption, fi.store_ind strore_ind, sf.size size, sf.name name FROM ?_file_item fi INNER JOIN ?_store_file sf ON sf.ind=fi.store_ind WHERE file_ind = \'' . $db->quote($this->file_ind) . '\' ORDER BY fi.created'
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
		    	"type" => Array(
			    		"form_caption"  => "",
			    		"table_caption" => "Тип",
			    		"description"   => "",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "String",
			    		"on_table_show" => array(&$this, 'icon_show'),
			    		),
				"caption" => Array(
			    		"form_caption"  => "Описание",
			    		"table_caption" => "Описание",
			    		"description"   => "",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "String",
			    		"length"        => 32,
			    		"max_lenght"    => 255,
			    		),
			    "size" => Array(
			    		"form_caption"  => "",
			    		"table_caption" => "Размер",
			    		"description"   => "",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "Int",
			    		"on_table_show" => array(&$this, 'size_show'),
			    		),		
			    "store_ind" => Array(
			    		"form_caption"  => "Файл",
			    		"table_caption" => "",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "Storefile",
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
		Page_Admin_File::update_file_count($this->file_ind, -1 * count($rows));
		return true;
    }
    function before_save(&$p, &$row, $act, $ind)
    {
    	// Заполним недостающие поля
    	$this->files[$p->name] = $_FILES[$p->_get_post_name()];
    	$row['file_ind'] = $this->file_ind;
    	return true;
    }
    function after_save(&$p, &$row, $act, $ind)
    {
    	if ($act == 'insert') {
    		Page_Admin_File::update_file_count($this->file_ind, 1);
    	}
    	return true;
    }
    function size_show(&$field, $row)
    {
    	return Nertz_File::show_size($row[$field->name]);
    }
    function icon_show(&$field, $row)
    {
    	return "<img src='" . Nertz_File::icon_path($row['name']) . "' />";
    }
    function on_act_back()
    {
    	Nertz::redirect(array('page' => 'admin_file'));
    }
    function on_act_regen()
    {

    }
}