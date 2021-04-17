<?php
/**
 * Класс работы с древовоидным меню из конфигов
 *
 */
class Nertz_Menu
{
	function Nertz_Menu()
	{
		$this->template = 'Nertz_Menu';
	}
	function get_data()
	{
		$this->data  = Nertz::config('/menu');
		$this->pages = Nertz::config('/pages');
		global $auth;
		$user  = $auth->user_info();
		$this->user_groups = isset($user['groups']) ? $user['groups'] : array(0);
		$this->default = Nertz::config('/main/default_page');
		$this->logged_in = $auth->logged_in();
		// Трехуровневое праздно шатание
		$result = array();
		if (is_array($this->data)) {
			foreach ($this->data as $capt1 => $item1) {
				$cnt1 = 0;
				if (isset($item1['items']) && is_array($item1['items']) && $capt1 != ORDER_FIELD) {
					foreach ($item1['items'] as $capt2 => $item2) {
						$cnt2 = 0;
						if (isset($item2['items']) && is_array($item2['items']) && $capt2 != ORDER_FIELD) {
							foreach ($item2['items'] as $capt3 => $item3) {
								if ($this->check_access($item3['page'])  && $capt3 != ORDER_FIELD) {
									$cnt2++;
								} else {
									unset($this->data[$capt1]['items'][$capt2]['items'][$capt3]);
								}
							}
						}
						if (isset($item2['page']) && $this->check_access($item2['page']) && $capt2 != ORDER_FIELD) {
							$cnt1++;
						} else {
							unset($this->data[$capt1]['items'][$capt2]);
						}
					}
				}
				if ((!$cnt1 && empty($item1['page'])) || !$this->check_access($item1['page'])) {
					unset($this->data[$capt1]);
				}
			}
		}
		$this->data = Nertz_Hook::apply_filters('menu', $this->data, array());
		
		return $this->data;
	}
	/**
	 * Рендеринг меню
	 *
	 * @return unknown
	 */
	function render()
	{
		global $cache, $template, $auth;
		$digest = 'd_' . $auth->get_digest() . '_' . $this->template;
		//if (!($res = $cache->get_value('menu',$digest, false))) {
			$template->set_value('menu', $this->get_data());
			$res = $template->render($this->template);
			$cache->set_value('menu',$digest, $res, false);
		//}
		return $res;
	}
	function check_access($page)
	{
		if (!$page) {
			return true;
		}
		if (!isset($this->pages[$page])) {
			return false;
		}
		$page_groups = $this->pages[$page]['groups'];
		$is_anonym = false;
		// Костыль чтобы анонимам не показывать logout
		if ($page == 'logout') {
			return $this->logged_in;
		}
		// Костыль чтобы вошедшему пользователю не показывался login
		if ($page == 'login') {
			return !$this->logged_in;
		}
		if (is_array($page_groups)) {
			foreach ($page_groups as $grp) {
				if (in_array($grp, $this->user_groups)) {
					return  true;
				}
			}
		}
		return false;
	}
	function set_template($name)
	{
		$this->template = $name;
	}
	/**
	 * загрузить меню
	 *
	 * @param unknown_type $data
	 * @param unknown_type $current_ind
	 * @param unknown_type $caption_field
	 * @param unknown_type $parent_ind_field
	 * @param unknown_type $ind_field
	 * @param unknown_type $additional_fields
	 */
	function load_db_result($data, $current_ind = 0, $caption_field = 'caption', $parent_ind_field = 'parent_ind', $ind_field = 'ind', $additional_fields = array() )
	{
		$res = array();
		$refs = array();
		if (is_array($data)) {
			foreach ($data as $row) {
				if (!$row[$parent_ind_field]) {
					$refs[$row[$ind_field]] = &$res[$row[$ind_field]];
				} else {
					$refs[$row[$ind_field]] = &$refs[$row[$parent_ind_field]]['items'][$row[$ind_field]];
				}
				$refs[$row[$ind_field]] = array('caption' => $row[$caption_field], 'items' => array(), 'parent_ind' => $row['parent_ind']);
				if (is_array($additional_fields) && count($additional_fields)) {
						foreach ($additional_fields as $old_key => $new_key) {
							$refs[$row[$ind_field]][$new_key] = $row[$old_key];
						}
				}
			}
		}
		// Пройдемся вверх и пометим всех активных родителей
		$i = $current_ind;
		while ($i) {
			$refs[$i]['active'] =true;
			$i = isset($refs[$i]['parent_ind']) ? $refs[$i]['parent_ind'] : 0;
		}
		$this->additional_fields = $additional_fields;
		$this->data = $res;
	}
	/**
	 * Рендеринг меню по новому
	 *
	 * @param unknown_type $template_name
	 * @param unknown_type $additional_fields
	 * @param unknown_type $level
	 * @param unknown_type $data
	 * @return unknown
	 */
	function render_new($template_name ='Menu', $additional_fields = array(), $level = 1, $data = null)
	{
		$s = '';
		global $template;
		if (!$data) {
			$data = $this->data;
		}
		foreach ($data as $i => $item) {
			if (count($item['items']) && $level < 10) {
				$template->set_value('content', $this->render_new($template_name, $additional_fields, $level + 1, $item['items']));
			} else {
				$template->set_value('content', '');
			}
			$template->set_value('ind', $i);
			$template->set_value('caption', $item['caption']);
			$template->set_value('level', $level);
			$template->set_value('item',  $item);
			$template->set_value('active', empty($item['active']) ? false : $item['active']);
			if (is_array($this->additional_fields) && count($this->additional_fields)) {
					foreach ($this->additional_fields as $old_key => $new_key) {
						$template->set_value($new_key, $item[$new_key]);
					}
			}
			$s .= $template->render($template_name);

		}
		return $s;
	}
}