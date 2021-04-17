<?php

// Размеры превьюхи галлереи
define('PREVIEW_WIDTH', 168);
define('PREVIEW_HEIGHT', 140);
define('PREVIEW_BG', '#000000');

// Размеры превьюхи галлереи
define('POPUP_WIDTH', 800);
define('POPUP_HEIGHT', 600);
define('POPUP_NAME', '__popup.jpg');


include_once(Nertz::class_path('Nertz_Page_Form'));
class Page_Admin_Gallery extends Nertz_Page_Form
{
    function Page_Admin_Gallery($params)
    {
    	global $db,$template;
        $this->form_params =  array(
			"name"        => "main",
			"caption"      => "Список галерей",
			"edit_caption" => "Редактирование галереи",
			"add_caption"  => "Добавление галереи",
			"index_field" => "ind",
			"url"         => array(),
			
			"sql"     => array(
				'table'  => 'gallery',
				'select' => 'SELECT * FROM ?_gallery ORDER BY created DESC'
				),
			"fields" => array(
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
			    "created" => Array( 
			    		"form_caption"  => "Дата",
			    		"table_caption" => "Дата",
			    		"description"   => "",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "Date",
			    		),		
			    "file_count" => Array(
			    		"form_caption"  => "",
			    		"table_caption" => "Файлы",
			    		"description"   => "",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "String",
			    		"on_table_show" => array(&$this, 'file_count_table_show'),
			    		),		
			    "delete" => Array(
			    		"form_caption"  => "",
			    		"table_caption" => "Удалить",
			    		"description"   => "Удалить галерею",
			    		"type"          => "CheckBox",
			    		),
			    "edit" => Array(
			    		"form_caption"  => "",
			    		"table_caption" => "Редактор",
			    		"description"   => "Редактировать галерею",
			    		"type"          => "Button",
			    		"pic_url"       => "core/img/button/edit.gif",
			    		"act"           => "edit",
			    		),	
			    ),
			);
    	parent::Nertz_Page_Form($params);
    }
    
    function file_count_table_show(&$field, $row)
    {
    	global $url;
    	$u = array();
    	$u['page'] = 'admin_gallery_item';
    	$u['gallery_ind'] = $row['ind'];
    	$s = "<a href='{$url->gen_url($u)}'>Файлы";
    	if ($row[$field->name])
    	{
    		$s .= "({$row[$field->name]})";
    	}
    	$s .= "</a>";
    	return $s;
    }
    function update_file_count($ind, $div)
    {
    	global $db;
    	return $db->sql_query('UPDATE ?_gallery SET file_count = file_count + ? WHERE ind = ?', array($div,$ind));
    }


}