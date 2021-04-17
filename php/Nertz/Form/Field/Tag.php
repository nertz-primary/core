<?php
include_once(Nertz::class_path('Nertz_Form_Field'));

class Nertz_Form_Field_Tag extends Nertz_Form_Field
{
	function Nertz_Form_Field_Tag($name, &$form)
	{
		parent::Nertz_Form_Field($name, $form);
	}
	function field_get_form_html()
	{
		global $url, $template;
		$template->add_js('jquery');
        $template->add_js('ui-full');
        $width = '';
        if (!empty($this->params['width'])) {
        	$width = 'style="width:'.$this->params['width'] . 'px;"';
        }
		$s = '<div class="tag-cont" ' . $width . '>';
		$s .= "<input type='text' name='". $this->_get_post_name() . "_taginput'  id='". $this->_get_post_name() . "_taginput'  class='taginput'/>";
		$disp = ' style="display:none" ';
		$s .= "<div id='". $this->_get_post_name() . "_tags' class='form-multiselect'{$disp}>";
		$s .= "</div>";
		$s .= "</div>";
		
		$u = $this->form->params['url'];
		$u['act']    = '_lookup';
		$u['_field'] = $this->name;
		$u['no_amp'] = true;
		$s .= "<script type='text/javascript'>\n";
		$v = $this->get_value();
		if ($v && is_array($v)) {
			foreach ($v as $c) {
				$s .= "form_add_tag('" . $this->_get_post_name() ."', '" . $c . "');\n"; 
			}
			$s .= "form_init_tag_divs();\n";
		}
		$s .= "form_init_tag('" . $this->_get_post_name() ."', '" . $url->gen_url($u) . "');\n</script>";
		
		return $s;
	}
	function field_get_table_html($row)
	{
		if (!isset($row[$this->name]) || !is_array($row[$this->name])) {
			return "&nbsp;";
		}
		return implode('<span>,</span> ', $row[$this->name]);
	}
	
	function query_values($p)
	{
		global $db;
		$type_ind = $this->get_type_ind();
		$search = $db->getAll('SELECT 
				t.ind id,
				t.name label,
				t.name value
			FROM ?_tag_item ti
			INNER JOIN ?_tag t  ON t.ind = ti.tag_ind AND ti.type_ind = ?
			WHERE t.name LIKE "%?#%"
			',
			array($type_ind, $p['s'])
		);
		$search[] = array('id' => 0, 'label' => "Добавить «{$p['s']}»", 'value' => $p['s']);
		
		return $search;
	}
	/**
	 * Получить текущий тип тэга
	 *
	 * @return int
	 */
	function get_type_ind() 
	{
		if (!empty($this->type_ind)) {
			return $this->type_ind;
		}
		global $db;
		$params = array( 
			'type_table' => $this->form->params['sql']['table'],
			'type_field' => $this->name
		);
		$ind = $db->getOne('SELECT ind FROM ?_tag_type WHERE type_table = ? AND type_field = ?', $params);
		if (!$ind) {
			$ind = $db->save('tag_type', $params, array(), true);
		}
		$this->type_ind = $ind;
		return $ind;	
	}
	function after_get(&$params) {
		global $db;
		$type_ind = $this->get_type_ind();
		$search = $db->getAssoc('SELECT 
				t.ind,
				t.name
			FROM ?_tag_item ti
			INNER JOIN ?_tag t  ON t.ind = ti.tag_ind AND ti.type_ind = ? AND ti.item_ind = ?
			
			',
			array($type_ind, $params[$this->form->index_name()])
		);
		$params[$this->name] = $search;
		return true;
	}
	function load_posted_value() 
	{
		parent::load_posted_value();	
		// Подгрузим тэги из поля ввода
		$values = $this->get_value();
		if(!is_array($values)) {
			$values = array();
		}
		$str = '';
		if(!empty($_REQUEST[$this->_get_post_name("", true) . '_taginput'])) {
			$str = $_REQUEST[$this->_get_post_name("", true) . '_taginput'];
		}
		if ($str) {
			$arr = explode(',', $str);
			foreach ($arr as $v) {
				$v = trim($v);
				if (!in_array($v, $values)) {
					$values[] = $v;
				}
			}
		}
		$this->set_value($values);
	}
	
	function before_save(&$params, $act, $ind) 
	{
		$this->items = $params[$this->name];
		unset($params[$this->name]);
		return true;
	}
	function after_save(&$params, $act, $ind) 
	{
		global $db;
		$type_ind = $this->get_type_ind();
		$db->sql_query('DELETE FROM ?_tag_item WHERE type_ind = ? AND item_ind = ?', array($type_ind, $ind));
		if (!empty($this->items) && is_array($this->items) && count($this->items)) {
			$arr = $this->get_tag_ids($this->items);
			$d = array();
			foreach ($arr as $i) {
				$d[] = array('type_ind' => $type_ind, 'item_ind' => $ind, 'tag_ind' => $i);
			}
			$db->mass_insert('tag_item', array('type_ind', 'item_ind', 'tag_ind'), $d);
		}
		return true;
	}
	/**
	 * Получить ID тэгов
	 *
	 * @param unknown_type $tags
	 */
	function get_tag_ids($tags) 
	{
		global $db;
		$res = array();
		if (is_array($tags) && count($tags)) {
			$res = $db->getAssoc('SELECT name, ind FROM ?_tag WHERE name IN (?a)', array($tags));
			foreach ($tags as $tag) {
				if (!isset($res[$tag])) {
					$res[$tag] = $db->save('tag', array('name' => $tag), array(), true);
				}
			}
		}
		return $res;
	}
	/**
	 * Полсуить список тэгов эллемента
	 *
	 * @param string $table Имя таблицы
	 * @param string $field Имя поля
 	 * @param array $inds Индексы эллементов
	 */
	static function fetch_tag($table, $field, $inds)
	{
		global $db;
		$type_ind = $db->getOne('SELECT ind FROM ?_tag_type WHERE type_table = ? AND type_field = ?', array($table, $field));		
		if (!$type_ind) {
			return array();
		}
		if(!is_array($inds)) {
			return $db->getAssoc('SELECT 
				t.ind ind,
				t.name name
			FROM ?_tag_item ti
			LEFT JOIN ?_tag t  ON t.ind = ti.tag_ind 
			WHERE ti.type_ind = ? AND ti.item_ind = ?
			',
			array($type_ind, $inds)
		);
		}
		$inds = array_unique($inds, SORT_NUMERIC);
		return $db->getAll('SELECT 
				t.ind ind,
				ti.item_ind item_ind,
				t.name name
			FROM ?_tag_item ti
			LEFT JOIN ?_tag t  ON t.ind = ti.tag_ind 
			WHERE  ti.type_ind = ? AND ti.item_ind IN (?a)
			',
			array($type_ind, $inds)
		);
		
	}
	/**
	 * Установить тэги для записи
	 *
	 * @param string $table Имя таблицы
	 * @param string $field Имя поля
	 * @param int    $ind   Индексы эллементов массива
 	 * @param array $arr Массив значений тэгов
	 */
	static function set_tag($table, $field, $ind, $arr)
	{
		global $db;
		$type_ind = $db->getOne('SELECT ind FROM ?_tag_type WHERE type_table = ? AND type_field = ?', array($table, $field));		
		if (!$type_ind) {
			return false;
		}
		if(!is_array($arr)) {
			return false;
		}
		$db->sql_query('DELETE FROM ?_tag_item WHERE type_ind = ? AND item_ind = ?', array($type_ind, $ind));
		$res = $db->getAssoc('SELECT name, ind FROM ?_tag WHERE name IN (?a)', array($arr));
		foreach ($arr as $tag) {
			if (!isset($res[$tag])) {
				$res[$tag] = $db->save('tag', array('name' => $tag), array(), true);
			}
		}
		$d = array();
		foreach ($res as $n => $i) {
			$d[] = array('type_ind' => $type_ind, 'item_ind' => $ind, 'tag_ind' => $i);
		}
		$db->mass_insert('tag_item', array('type_ind', 'item_ind', 'tag_ind'), $d);
	}
	/**
	 * Обработчик поля после загрузки
	 *
	 * @param Nertz_Form_Field_Tag $field Экземпляр меня любимого
	 * @param array $rows Значения загруженные из таблицы
	 */
	function after_fetch(&$rows)
	{
		global $db;
		// Соберем все индексы
		$bids = array(); // Индексы для быстрого обратного сопоставления строк
		$inds = array();
		if (is_array($rows) && count($rows)) {
			foreach ($rows as $id => $row) {
				$bids[$row['ind']] = $id;
				$rows[$id][$this->name] = array();
			}
			$type_ind = $this->get_type_ind();
			$tags = $db->getAll('SELECT 
					ti.item_ind item_ind,
					t.name name
				FROM ?_tag_item ti
				INNER JOIN ?_tag t  ON t.ind = ti.tag_ind AND ti.type_ind = ? AND ti.item_ind IN (?a)
				',
				array($type_ind, array_keys($bids))
			);
			if (is_array($tags)) {
				foreach ($tags as $tag) {
					$rows[$bids[$tag['item_ind']]][$this->name][] = $tag['name'];
				}
			}

		}
	}
}