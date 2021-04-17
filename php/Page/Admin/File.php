<?php

include_once(Nertz::class_path('Nertz_Page_Form'));
class Page_Admin_File extends Nertz_Page_Form
{
    function Page_Admin_File($params)
    {
    	global $db,$template;
        $this->form_params =  array(
			"name"        => "main",
			"caption"      => "Список файловых разделов",
			"edit_caption" => "Редактирование раздела",
			"add_caption"  => "Добавление раздела",
			"index_field" => "ind",
			"url"         => array(),
			
			"sql"     => array(
				'table'  => 'file',
				'select' => 'SELECT * FROM ?_file ORDER BY created DESC'
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
			    		"description"   => "Удалить галлерею",
			    		"type"          => "CheckBox",
			    		),
			    "edit" => Array(
			    		"form_caption"  => "",
			    		"table_caption" => "Редактор",
			    		"description"   => "Редактировать галлерею",
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
    	$u['page'] = 'admin_file_item';
    	$u['file_ind'] = $row['ind'];
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
    	return $db->sql_query('UPDATE ?_file SET file_count = file_count + ? WHERE ind = ?', array($div,$ind));
    }


}