<?php

class Nertz_Seo
{
	function Nertz_Seo()
	{
		
	}
	/**
	 * Поключиться к форме. 
	 * Вызывать на этапе form_create
	 *
	 * @param Nertz_Form $form Форма
	 */
	function connect_form(&$form)
	{
		$this->form = $form;
		if (empty($form->params['fields'])) {
			return ;
		}
		// Заголовок подформы
		$form->params['fields']['seo_capt'] = array(
			"form_caption"  => "Настройки SEO",
			"read_only"     => 0,
			"reqired"       => 0,
			"type"          => "Caption",
		);
		// SEO Title
		$form->params['fields']['seo_title'] = array(
			"form_caption"  => "Заголовок",
			"read_only"     => 0,
			"reqired"       => 0,
			"type"          => "String",
			"length"        => 37,
			"max_lenght"    => 255,
			"before_save"   => array(&$this, 'before_save'),
			"after_save"    => array(&$this, 'after_save'),
			"after_delete"  => array(&$this, 'after_delete'),
			"after_get"		=> array(&$this, 'after_get'),
		);
		// SEO Description
		$form->params['fields']['seo_description'] = array(
			"form_caption"  => "Описание",
			"read_only"     => 0,
			"reqired"       => 0,
			"type"          => "Text",
			"rows"          => 5,
			"cols"          => 37
		);
		// SEO Keywords
		$form->params['fields']['seo_keywords'] = array(
			"form_caption"  => "Ключевые слова",
			"read_only"     => 0,
			"reqired"       => 0,
			"type"          => "Text",
			"rows"          => 5,
			"cols"          => 37
		);
	}
	function before_save(&$field, &$params, $act, $ind)
	{
		$this->data = array(
			'title'       => $params['seo_title'],
			'description' => $params['seo_description'],
			'keywords'    => $params['seo_keywords'],
		);
		unset($params['seo_title']);
		unset($params['seo_description']);
		unset($params['seo_keywords']);
		return true;
	}
	function after_save(&$field, &$params, $act, $ind)
	{
		global $db;
		$name = $this->get_name();
		if (!$name) {
			return;
		}
		$seo_ind = $db->getOne('SELECT ind FROM ?_seo WHERE target_name = ? AND target_ind = ?', array($name, $ind));
		$key = array();
		if (!empty($seo_ind)) {
			$key['ind'] = $seo_ind;
		}
		$empty = true;
		
		foreach ($this->data as $k => $v) {
			if(!empty($v)) {
				$empty = false;
			}
		}
		
		if (!$empty) {
			$this->data['target_name'] = $name;
			$this->data['target_ind']  = $ind;
			$db->save('seo', $this->data, $key);
		} else {
			$db->sql_query('DELETE FROM ?_seo WHERE target_name = ? AND target_ind = ?', array($name, $ind));
		}
	}
	function after_delete(&$field, $inds)
	{
		global $db;
		$name = $this->get_name();
		if (!$name) {
			return;
		}
		$db->sql_query('DELETE FROM ?_SEO WHERE target_name = ? AND target_ind IN(?a)', array($name, $inds));
	}
	function after_get(&$field, &$params)
	{
		global $db;
		$ind = $params['ind'];
		$name = $this->get_name();
		if (!$name) {
			return;
		}
		$data = $db->getRow('SELECT * FROM ?_seo WHERE target_name = ? AND target_ind = ?', array($name, $ind));
		
		$params['seo_title']       = $data['title'];
		$params['seo_description'] = $data['description'];
		$params['seo_keywords']    = $data['keywords'];
	}
	/**
	 * Получить данные SEO
	 *
	 * @param string $name Название формы
	 * @param int $ind Индекс записи
	 * @return unknown
	 */
	function get($name, $ind)
	{
		global $db;
		$data = $db->getRow('SELECT * FROM ?_seo WHERE target_name = ? AND target_ind = ?', array($name, $ind));
		if (empty($data['title'])) {
			$data['title'] = Nertz::config('main/title');
		} else {
			$data['title'] .= ' - ' . Nertz::config('main/title');
		}
		if (empty($data['keywords'])) {
			$data['keywords'] = Nertz::config('main/keywords');
		}
		if (empty($data['description'])) {
			$data['description'] = Nertz::config('main/description');
		}
		return $data;
	}
	/**
	 * Загрузить SEO-данные в страницу
	 *
	 * @param string $name Название формы
	 * @param int $ind Индекс записи
	 * @param Nertz_Page $page Страницы
	 * @return unknown
	 */
	function load_to_page($name, $ind, &$page)
	{
		$data = $this->get($name, $ind);
		$page->title       = $data['title'];
		$page->description = $data['description'];
		$page->keywords    = $data['keywords'];
	}
	function get_name()
	{
		if(!empty($this->form->params['name'])) {
			return $this->form->params['name'];
		}
		if (!empty($this->form->params['sql']['table_name'])) {
			return $this->form->params['sql']['table_name'];
		}		
		return false;
	}
}

/**
 * 		

 */