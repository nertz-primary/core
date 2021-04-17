<?php
/**
 * Базовый класс для любых списков на основе Nertz_Form.
 * Идея заключается в том что задав несколько SQL запросов мы могли
 * не заниматься рутиной вроде реализации свойств get(), add(), delete(), get_Item(), update()
 * Все настройки берутся из Nertz_Form
 */
include_once(Nertz::class_path('Nertz_Item'));
class Nertz_Item_Form extends Nertz_Item
{
	/**
	 * Конструктор
	 *
	 * @param unknown_type $form
	 * @return Nertz_Item
	 */
	function Nertz_Item_Form(&$form)
	{
		$this->form = $form;
		global $db, $url;
		if (empty($this->form->params['sql'])) {
			Nertz::log("Отсуствует поле ['sql'] в параметрах формы.", 'error', 1);
			return false;
		}
		if (empty($this->form->params['sql']['table'])) {
			Nertz::log("Отсуствует имя таблицы ['sql']['table'] в параметрах формы.", 'error', 1);
			return false;
		}
		if (empty($this->form->params['sql']['select'])) {
			//	Nertz::log("Отсуствует запрос выборки из таблицы ['sql']['select'] в параметрах формы.", 'error', 1);
			//	return false;
		}
		if (empty($this->form->params['buttons'])) {
			$this->form->params['buttons'] = array(
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
			);
			if (!empty($this->form->params['movable_rows'])) {
				$this->form->params['buttons']["order"] = array(
			          "form_caption"  => "",
			   	      "table_caption" => "Порядок",
			   	      "image"         => "core/img/button/save.gif"
			     );
			}
		}
		//$this->form->params['url']['p'] = $url->get_value('p', 0);
		$this->items_per_page = $form->params['items_per_page'];
		parent::Nertz_Item();
	}
	/**
	 * Добавление новой записи
	 *
	 * @param array $params Поля новой записи
	 * @return int Значение первичного ключа для новой записи
	 */
	function add($params)
	{
		global $db;
		if (isset($this->form->params['sql']['position_field']) &&  $this->form->params['sql']['position_field']) {
			$pf = $db->quote($this->form->params['sql']['position_field']);
			$params[$pf] = $db->getOne('SELECT MAX(' . $pf . ') FROM ?_' . $this->form->params['sql']['table'] . '') + 1;
		}
		$res = $db->save($this->form->params['sql']['table'], $params);
		return $res;
	}
	/**
	 * Удаление группы записей
	 *
	 * @param unknown_type $inds
	 */
	function delete($inds)
	{
		if (empty($inds)) {
			return false;
		}
		if(!is_array($inds)) {
			$inds = array($inds);
		}
		global $db;
		$res = $db->sql_query('DELETE FROM `?_' . $this->form->params['sql']['table'] . '` WHERE `' . $this->form->index_name() . '` IN (?a)', array($inds));
		return $res;

	}
	/**
	 * Обновление записи
	 *
	 * @param int $ind Индекс записи
	 * @param unknown_type $params
	 * @return unknown
	 */
	function update($ind, $params)
	{
		global $db;
		$res = $db->save($this->form->params['sql']['table'], $params, array($this->form->index_name() => $ind));
		return $res;
	}
	/**
	 * Получение одной записи
	 * Пока это SELECT * FROM table WHERE [form->index_name()] = ?,
	 * Если потребуется будем переделывать
	 * Если ind это массив то выбирается группа записей с соответсвующими индексами
	 *
	 * @param int $ind Индекс записи
	 * @return array Значения полей записи
	 */
	function get($ind)
	{
		global $db;
		if (is_array($ind)) {
			return $db->getAll('SELECT * FROM `?_' . $this->form->params['sql']['table'] . '` WHERE `' . $this->form->index_name() .'` IN (?a)', array($ind));
		} else {
			return $db->getRow('SELECT * FROM `?_' . $this->form->params['sql']['table'] . '` WHERE `' . $this->form->index_name() .'` = ?', array($ind));
		}
	}
	/**
	 * Получение списка записей
	 * @param int $p Номер отображаемой страницы
	 * Если установлен ['sql']['select'] то вызывается именно он и используются его результаты
	 */
	function fetch($p=0)
	{
		global $db, $template;
		$res = array();
		if (!empty($this->form->params['sql']) && !empty($this->form->params['sql']['select'])) {
			$cnt = (!empty($this->form->params['sql']['count'])) ? $this->form->params['sql']['count'] : "COUNT(*)";
			$sql   = $this->form->replace_db_vars($this->form->params['sql']['select']);
			$items = $db->getPaged($sql, array(), $p, $this->items_per_page, false, $cnt);
			$template->set_value('p',  $items['p']);
			$template->set_value('pcount', $items['pcount']);
			$template->set_value('pages',  $items['pages']);
			$res = $items['data'];
		}
		return $res;

	}
	/**
	 * Проверка уникальности новой или создаваемой записи
	 *
	 * @param int $ind Индекс записи, 0 - для новой
	 * @param array $params Поля записи
	 * @return unknown
	 */
	function check_uniques($ind, $params)
	{
		global $db;
		$res = true;
		foreach ($this->form->params['uniques'] as $name => $unique)
		{
			if (isset($unique['fields']) && is_array($unique['fields']))
			{
				$sql = 'SELECT COUNT(`' . $this->form->index_name() . '`) FROM `?_' . $this->form->params['sql']['table'] . '` WHERE `' . $this->form->index_name() . '` <> \'' . $db->quote($ind) . '\'';
				foreach ($unique['fields'] as $f) {
					if(!empty($params[$f])) {
						$sql .= " AND `{$f}` = '" . $db->quote($params[$f]) . "'";
					}
				}
				$cnt = $db->getOne($sql);
				if ($cnt) {
					$this->form->params['fields'][$name]['error_message'] = empty($unique['message']) ? "Подобная запись уже существует" : $unique['message'];
					$res = false;
				}
			}
		}
		return $res;
	}
	function clear_cache()
	{
		if (isset($this->form->params['sql']) && isset($this->form->params['sql']['cache'])) {
			$caches = $this->form->params['sql']['cache'];
			if (is_array($caches) && count($caches)) {
				global $cache;
				foreach ($caches as $cache) {
					$cache->drop_category($cache);
				}
			}
		}
	}
	function set_order($order)
	{
		if (is_array($order)) {
			$order = array_values($order);
			global $db;
			$pf = $db->quote($this->form->params['sql']['position_field']);
			$if = $this->form->index_name();
			$old = $db->getAssoc('SELECT `' . $if . '`, `' . $pf . '` FROM ?_' . $this->form->params['sql']['table'] . ' WHERE `' . $if . '` IN (?a) ORDER BY `' . $pf . '`', array($order)) ;
			$new_pos = array();
			$i = 0;
			foreach ($old as $id => $p) {
				if ($id != $order[$i]) {
					$db->save($this->form->params['sql']['table'], array($pf => $p), array($if => $order[$i]), true);
				}
				$i++;
			}
		}
	}

}