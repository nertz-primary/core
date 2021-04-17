<?php
class Nertz_Config_Special_Select_Values
{
	function get_form_params()
	{
		global $url;
		return array(
			"name"        => "main",
			"caption"     => "Редактор структуры хэша",
			"index_field" => "name",
			"url"         => array('path'=>$url->get_value('path','/')),
			"on_table_header" => nertz_config_structure_on_table_header,
			"buttons" => array(
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
			    "name" => Array(
			    		"form_caption"  => "Значение",
			    		"table_caption" => "Значение",
			    		"description"   => "Значение свойства value тэга <option> поля <select>",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "String",
			    		"length"        => 32,
			    		"max_lenght"    => 32,
			    		),
			    "caption" => Array(
			    		"form_caption"  => "Заголовок",
			    		"table_caption" => "Заголовок",
			    		"description"   => "Заголовок тэга <option> поля <select>",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "String",
			    		"length"        => 32,
			    		"max_lenght"    => 32,
			    		),		
			    "delete" => Array(
			    		"form_caption"  => "",
			    		"table_caption" => "Удалить",
			    		"description"   => "Удалить ноду",
			    		"type"          => "CheckBox",
			    		),
			    "edit" => Array(
			    		"form_caption"  => "",
			    		"table_caption" => "Редактор",
			    		"description"   => "Редактировать ноду",
			    		"type"          => "Button",
			    		"pic_url"       => "core/img/button/edit.gif",
			    		"act"           => "edit"
			    		),	
			    ));
	}
}