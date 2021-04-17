<?php

include_once(Nertz::class_path('Nertz_Page_Form'));
class Page_Admin_User extends Nertz_Page_Form
{
    function Page_Admin_User($params)
    {
    	global $db,$template;
        $this->form_params =  array(
			"name"        => "main",
			"caption"      => "Список пользователей",
			"edit_caption" => "Редактирование пользователя",
			"add_caption"  => "Добавление пользователя",
			"index_field" => "ind",
			"url"         => array(),

			"sql"     => array(
				'table'  => 'user',
				'select' => 'SELECT * FROM ?_user WHERE login LIKE (login) ORDER BY created DESC'
				),

			"filters"      => array(
				"login" => Array(
			    		"caption"  => "Поиск",
			    		"description"   => "",
			    		"type"          => "Mask",
			    		),
    		),
			"fields" => array(
				"login" => Array(
			    		"form_caption"  => "Логин",
			    		"table_caption" => "Логин",
			    		"description"   => "",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "String",
			    		"length"        => 32,
			    		"max_lenght"    => 64,
			    		),
			    "pass" => Array(
			    		"form_caption"  => "Пароль",
			    		"table_caption" => "Пароль",
			    		"description"   => "",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "String",
			    		"length"        => 32,
			    		"max_lenght"    => 64,
			    		),
			    "email" => Array(
			    		"form_caption"  => "E-mail",
			    		"table_caption" => "E-mail",
			    		"description"   => "",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "String",
			    		),
			    "groups" => Array(
			    		"form_caption"  => "Группы",
			    		"table_caption" => "",
			    		"description"   => "",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "Multiselect",
			    		"values"		=> Nertz_User_Group::get_all(true),
			    		"before_save"   => array(&$this, 'groups_before_save'),
			    		"after_save"    => array(&$this, 'groups_after_save'),
			    		"on_form_show"  => array(&$this, 'groups_form_show')
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

    function groups_before_save(&$field, &$values, $act, $ind)
    {
    	$this->group_values = $values[$field->name];
    	unset($values[$field->name]);
    	return true;
    }
    function groups_after_save(&$field, &$values, $act, $ind)
    {
    	include_once(Nertz::class_path('Nertz_User_Group'));
    	$ug = new Nertz_User_Group($ind);
    	$ug->save($this->group_values);
    	return true;
    }
    function groups_form_show(&$field)
    {
    	$index = $field->form->get_posted_index();
    	if ($index) {
    		include_once(Nertz::class_path('Nertz_User_Group'));
    		$ug = new Nertz_User_Group($index);
    		$field->params['value'] = $ug->fetch();
    	} else {
    		$field->params['value'] = array(1);
    	}
    	return $field->field_get_form_html();
    }


}