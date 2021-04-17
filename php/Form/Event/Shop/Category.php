<?php
include_once(Nertz::class_path('Nertz_Form_Event'));

class Form_Event_Shop_Category extends Nertz_Form_Event
{
	function form_create(&$form)
	{
		global $db, $url, $auth;
		$this->hotel_ind = $auth->get_hotel_ind();
	}
	
	function item_count_show(&$field, &$row)
	{
		global $url;
    	$u = array();
    	$u['page'] = 'admin_shop_item';
    	$u['category_ind'] = $row['ind'];
    	$s = "<a href='{$url->gen_url($u)}'>Товары";
    	if ($row[$field->name])
    	{
    		$s .= "({$row[$field->name]})";
    	}
    	$s .= "</a>";
    	return $s;

	}
}