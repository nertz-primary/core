<?php

include_once(Nertz::class_path('Nertz_Page_Form'));
class Page_Admin_Static extends Nertz_Page_Form
{
    function Page_Admin_Static($params)
    {
    	global $db, $template;
        $this->form_params =  array(
			"name"        => "main",
			"caption"      => "Статические страницы",
			"edit_caption" => "Редактирование страницы",
			"add_caption"  => "Добавление страницы",
			"index_field" => "ind",
			"url"         => array(),
			
			"sql"     => array(
				'table'  => 'static',
				'select' => 'SELECT * FROM ?_static ORDER BY created'
				),
			"fields" => array(
				"ind" => Array(
			    		"form_caption"  => "Внутр. имя страницы.",
			    		"table_caption" => "Имя",
			    		"description"   => "",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "String",
			    		"length"        => 32,
			    		"max_lenght"    => 255,
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
			    		"description"   => "Удалить страницу",
			    		"type"          => "CheckBox",
			    		),		
			    "edit" => Array(
			    		"form_caption"  => "",
			    		"table_caption" => "Редактор",
			    		"description"   => "Редактировать страницу",
			    		"type"          => "Button",
			    		"pic_url"       => "core/img/button/edit.gif",
			    		"act"           => "edit",
			    		),	
			    ),
			    "uniques" => array(
			    	"ind" => array(
			    		"fields"  => array('ind'),
			    		"message" => 'Страница с таким внутренним именем уже существует',
			    	)
			    ), 
			);
    	parent::Nertz_Page_Form($params);
    }
}