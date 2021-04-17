<?php
include_once(Nertz::class_path('Nertz_Form_Event'));
include_once(Nertz::class_path('Nertz_Shop'));

class Form_Event_Shop_Item extends Nertz_Form_Event
{
	function form_create(&$form)
	{
		global $db, $url, $auth;
		$this->category_ind = $url->get_value('category_ind', 0);
		if (!$this->category_ind) {
			Nertz::redirect(array('page' => 'admin_shop_category'));
		}
		$form->params['url']['category_ind'] = $this->category_ind;
		$form->params['fields']['producer_ind']['values'] = Nertz_Shop::fetch_producer();
		$form->params['sql']['select'] = "SELECT
			*
			FROM ?_shop_item 
			WHERE category_ind = '" . $db->quote($this->category_ind) . "'";
	}
	function on_act_back(&$page) {
		Nertz::redirect(array('page' => 'admin_shop_category'));
	}
	function before_name_save(&$field, &$params, $act, $ind) {
		$params['category_ind'] = $this->category_ind;
		return true;
	}
	function after_name_save(&$field, &$params, $act, $ind) {
		Nertz_Shop::upadte_category_count($this->category_ind);
		return true;
	}
	function after_name_delete(&$field, &$inds) {
		Nertz_Shop::upadte_category_count($this->category_ind);
		return true;
	}
	
}