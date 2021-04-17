<?php

include_once(Nertz::class_path('Nertz_Page_Form'));
include_once(Nertz::class_path('Nertz_Image'));
class Page_Admin_Article extends Nertz_Page_Form
{
    function Page_Admin_Article($params)
    {
    	global $db, $template, $url;
    	$page = $url->get_page();
    	$n = 0;
    	$this->type = strbtw($url->get_page(), 'admin_', '',$n);
    	$this->types = Nertz::config('/articles/');
        $this->form_params =  array(
			"name"         => "main",
			"caption"      => $this->types[$this->type]['caption'],
			"edit_caption" => "Редактировать статью",
			"add_caption"  => "Добавить статью",
			"index_field"  => "ind",
			"url"          => array(),
			
			"sql"     => array(
				'table'  => 'article',
				'select' => 'SELECT * FROM ?_article WHERE type = \'' . $db->quote($this->type) . '\' ORDER BY created DESC'
				),
			"fields" => array(
				"caption" => Array(
			    		"form_caption"  => "Заголовок",
			    		"table_caption" => "Заголовок",
			    		"description"   => "",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "String",
			    		"length"        => 80,
			    		"max_lenght"    => 255,
			    		),
			    "type" => Array( 
			    		"form_caption"  => "Тип",
			    		"table_caption" => "",
			    		"description"   => "",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "Simpleselect",
			    		"values" 		=> array_extract($this->types, 'caption', null), 
			    		"value"			=> $this->type
			    		), 		
			     "created" => Array( 
			    		"form_caption"  => "Дата",
			    		"table_caption" => "Дата",
			    		"description"   => "",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "Date",
			    		),
			    "short_body" => Array(
			    		"form_caption"  => "Корткий текст",
			    		"table_caption" => "",
			    		"description"   => "",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"width"			=> 600,
			    		"height"		=> 250,
			    		"type"          => "Html",
			    		),				
				"body" => Array(
			    		"form_caption"  => "Текст",
			    		"table_caption" => "",
			    		"description"   => "",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"width"			=> 600,
			    		"height"		=> 400,
			    		"type"          => "Html",
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

}