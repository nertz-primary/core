<?php
/**
 * Класс редктора струкутры хэша
 *
 */

include_once(Nertz::class_path('Nertz_File'));

class Nertz_Config_Forms
{
	/**
	 * Конструктор
	 *
	 * @return Nertz_Config_Forms
	 */
	function  Nertz_Config_Forms()
	{
		$this->ext = '.frm';
	}
	function assign_data(&$data)
	{
		$this->data = & $data;
	}
	/**
	 * Получить список форм для указанных уровней
	 *
	 * @param array $levels Уровние
	 * @return array Имена форм
	 */
	function fetch($levels)
	{
		if (!is_array($levels) || !count($levels)) {
			$levels = $this->get_site_levels($file_name);
		}
		$res = array();
		$names = array(); // Запоминаем имена вновь найденных форм
		foreach ($levels as $level) {
			$ls = Nertz_File::ls('root', $this->get_level_path($level));
		    if (is_array($ls) && count($ls)) {
		    	foreach ($ls as $name) {
		    		$i = strpos($name,$this->ext);
		    		if ( $i && !isset($names[$name])) {
		    			$n = substr($name, 0, $i);
		    			$stats = get_form_stats($n, $levels);
		    			if ($stats != UNSET_VALUE) {
			    			$arr = array('name' => $n, 'file' => $name);
			    			$arr = array_merge($arr, $stats);
			    			$res[] = $arr;
			    			$names[$name] = 1;
		    			} else {
		    				
		    			}
		    		}
		    	}
		    }
		}
		array_sort_rows($res,array('name'));
		return $res;
	}
	/**
	 * Удаление формы
	 *
	 * @param string $name Имя формы
	 * @param array $levels Уровни на которых форма должна быть удуалена
	 */
	function delete($name, $levels)
	{
		foreach ($levels as $level) {
			$path = $this->get_level_name($name, $level);
			$path = Nertz_File::_get_real_name('root', $path);
				if (!is_array($level) || !in_array('core', $level)) {
					$ncf = new Nertz_Config_Forms();
					if (file_exists($path)) {
						$ncf->load_from_file($name,array($level));
					}
					$ncf->data = UNSET_VALUE . '?';
					$ncf->save_to_file($name, array($level));
				} else {
					if (file_exists($path)) {
						unlink($path);
					}
				}
		}

	}

	function set_main_params($data)
	{
		if (!(isset($this->data) && is_array($this->data))) {
			$this->data = array();
		}
		$this->data = array_copy_default($data, $this->get_main_fields(), $this->data);
	}
	function get_main_params()
	{
		return array_copy_default($this->data, $this->get_main_fields());
	}
	/**
	 * Установить параметры поля формы
	 *
	 * @param assoc $data Параметры поля
	 */
	function set_field_params($data)
	{
		if (!(isset($data['name']) && $data['name'])) {
			return;
		}
		$name = $data['name'];
		unset($data['name']);
		if (!(isset($this->data['fields']) && is_array($this->data['fields']))) {
			$this->data['fields'] = array();
		}
		if (!(isset($this->data['fields'][$name]) && is_array($this->data['fields'][$name]))) {
			$this->data['fields'][$name] = array();
		}
		$this->data['fields'][$name] = array_copy_default($data, $this->get_field_fields(), $this->data['fields'][$name]);
	}
	/**
	 * Получить парметры поля формы
	 *
	 * @param string $name Имя поля
	 * @return assoc Параметры поля
	 */
	function get_field_params($name)
	{
		$arr = array_copy_default($this->data['fields'][$name], $this->get_field_fields());
		$arr['name'] = $name;
		return $arr;
	}
	/**
	 * Получить все поля формы
	 *
	 */
	function fetch_fields()
	{
		if (!(isset($this->data) && is_array($this->data) && isset($this->data['fields']) && is_array($this->data['fields']))) {
			return array();
		}
		$res = array();
		foreach ($this->data['fields'] as $name => $field) {
			if ($name != ORDER_FIELD) {
				$field['name'] = $name;
				$res[] = $field;
			}
		}
		return $res;
	}
	/**
	 * Удaление полей
	 *
	 * @param array $names Имена полей
	 */
	function delete_fields($names)
	{
		if (is_array($names) && count($names)) {
			foreach ($names as $name) {
				unset($this->data['fields'][$name]);
			}
		}
	}
	/**
	 * Определить порядок полей
	 *
	 * @param array $order Имена полей в нужном порядке
	 */
	function order_fields( $order)
	{
		$this->data['fields'][ORDER_FIELD] = $order;
	}
	/**
	 * Установить параметры кнопки формы
	 *
	 * @param assoc $data Параметры кнопки
	 */
	function set_button_params($data)
	{
		if (!(isset($data['name']) && $data['name'])) {
			return;
		}
		$name = $data['name'];
		unset($data['name']);
		if (!(isset($this->data['buttons']) && is_array($this->data['buttons']))) {
			$this->data['buttons'] = array();
		}
		if (!(isset($this->data['buttons'][$name]) && is_array($this->data['buttons'][$name]))) {
			$this->data['buttons'][$name] = array();
		}
		$this->data['buttons'][$name] = array_copy_default($data, $this->get_button_fields(), $this->data['buttons'][$name]);
	}
	/**
	 * Получить парметры кнопки формы
	 *
	 * @param string $name Имя кнопки
	 * @return assoc Параметры поля
	 */
	function get_button_params($name)
	{
		$arr = array_copy_default($this->data['buttons'][$name], $this->get_button_fields());
		$arr['name'] = $name;
		return $arr;
	}
	/**
	 * Получить все кнопки формы
	 *
	 */
	function fetch_buttons()
	{
		if (!(isset($this->data) && is_array($this->data) && isset($this->data['buttons']) && is_array($this->data['buttons']))) {
			return array();
		}
		$res = array();
		foreach ($this->data['buttons'] as $name => $button) {
			if ($name != ORDER_FIELD) {
				$button['name'] = $name;
				$res[] = $button;
			}
		}
		return $res;
	}
	/**
	 * Удaление кнопок
	 *
	 * @param array $names Имена кнопок
	 */
	function delete_buttons($names)
	{
		if (is_array($names) && count($names)) {
			foreach ($names as $name) {
				unset($this->data['buttons'][$name]);
			}
		}
	}
	/**
	 * Определить порядок кнопок
	 *
	 * @param array $order Имена кнопок в нужном порядке
	 */
	function order_buttons($order)
	{
		$this->data['buttons'][ORDER_FIELD] = $order;
	}
	/**
	 * Установить параметры SQL формы
	 *
	 * @param assoc $data Параметры SQL формы
	 */
	function set_sql_params($data)
	{
		$this->data['sql'] = array_copy_default($data, $this->get_sql_fields(), $this->data['sql']);
	}
	/**
	 * Получить парметры SQL формы
	 *
	 * @return assoc Параметры SQL формы
	 */
	function get_sql_params()
	{
		$arr = array_copy_default($this->data['sql'], $this->get_sql_fields());
		return $arr;
	}

	/**
	 * Установить параметры фильтра формы
	 *
	 * @param assoc $data Параметры фильтра
	 */
	function set_filter_params($data)
	{
		if (!(isset($data['name']) && $data['name'])) {
			return;
		}
		$name = $data['name'];
		unset($data['name']);
		if (!(isset($this->data['filters']) && is_array($this->data['filters']))) {
			$this->data['filters'] = array();
		}
		if (!(isset($this->data['filters'][$name]) && is_array($this->data['filters'][$name]))) {
			$this->data['filters'][$name] = array();
		}
		$this->data['filters'][$name] = array_copy_default($data, $this->get_filter_fields(), $this->data['filters'][$name]);
	}
	/**
	 * Получить парметры фильтра формы
	 *
	 * @param string $name Имя фильтра
	 * @return assoc Параметры фильтра
	 */
	function get_filter_params($name)
	{
		$arr = array_copy_default($this->data['filters'][$name], $this->get_filter_fields());
		$arr['name'] = $name;
		return $arr;
	}
	/**
	 * Получить все фильтры формы
	 *
	 */
	function fetch_filters()
	{
		if (!(isset($this->data) && is_array($this->data) && isset($this->data['filters']) && is_array($this->data['filters']))) {
			return array();
		}
		$res = array();
		foreach ($this->data['filters'] as $name => $filter) {
			if ($name != ORDER_FIELD) {
				$filter['name'] = $name;
				$res[] = $filter;
			}
		}
		return $res;
	}
	/**
	 * Удaление фильтров
	 *
	 * @param array $names Имена фильтров
	 */
	function delete_filters($names)
	{
		if (is_array($names) && count($names)) {
			foreach ($names as $name) {
				unset($this->data['filters'][$name]);
			}
		}
	}
	/**
	 * Определить порядок фильтров
	 *
	 * @param array $order Имена фильтров в нужном порядке
	 */
	function order_filters($order)
	{
		$this->data['filters'][ORDER_FIELD] = $order;
	}

	/**
	 * Установить параметры уникальности формы
	 *
	 * @param assoc $data Параметры уникальности
	 */
	function set_unique_params($data)
	{
		if (!(isset($data['name']) && $data['name'])) {
			return;
		}
		$name = $data['name'];
		unset($data['name']);
		if (!(isset($this->data['uniques']) && is_array($this->data['uniques']))) {
			$this->data['uniques'] = array();
		}
		if (!(isset($this->data['uniques'][$name]) && is_array($this->data['uniques'][$name]))) {
			$this->data['uniques'][$name] = array();
		}
		$this->data['uniques'][$name] = array_copy_default($data, $this->get_unique_fields(), $this->data['uniques'][$name]);
	}
	/**
	 * Получить парметры уникальности формы
	 *
	 * @param string $name Имя уникальности
	 * @return assoc Параметры уникальности
	 */
	function get_unique_params($name)
	{
		$arr = array_copy_default($this->data['uniques'][$name], $this->get_unique_fields());
		$arr['name'] = $name;
		return $arr;
	}
	/**
	 * Получить все уникальности формы
	 *
	 */
	function fetch_uniques()
	{
		if (!(isset($this->data) && is_array($this->data) && isset($this->data['uniques']) && is_array($this->data['uniques']))) {
			return array();
		}
		$res = array();
		foreach ($this->data['uniques'] as $name => $unique) {
			if ($name != ORDER_FIELD) {
				$unique['name'] = $name;
				$res[] = $unique;
			}
		}
		return $res;
	}
	/**
	 * Удaление уникальностей
	 *
	 * @param array $names Имена уникальностей
	 */
	function delete_uniques($names)
	{
		if (is_array($names) && count($names)) {
			foreach ($names as $name) {
				unset($this->data['uniques'][$name]);
			}
		}
	}
	/**
	 * Определить порядок уникальностей
	 *
	 * @param array $order Имена уникальностей в нужном порядке
	 */
	function order_uniques($order)
	{
		$this->data['uniques'][ORDER_FIELD] = $order;
	}

	/**
	 * Сохранение структуры в файл
	 *
	 * @param string $file_name Имя файла из папки config/structure, без расширения и пути
	 * Если этот параметр пустой, то берется имя файла которое было при загрузке.
	 */
	function save_to_file($file_name='', $levels = false)
	{
  	    if (!$file_name) {
	        $file_name = $this->file_name;
	    }
	    if ($levels === false) {
	    	$levels = $this->levels;
	    }
	    if (!is_array($levels) || !count($levels)) {
			$levels = $this->get_site_levels($file_name);
		}
		$plevels = $levels;
		$level = array_pop($plevels);
		if (count($plevels)) {
			$pform = new Nertz_Config_Forms();
			$pform->load_from_file($file_name, $plevels);
			array_diff_full($pform->data,$this->data);
		}
	    $f = new Nertz_File('root', $this->get_level_name($file_name, $level), "w");
	    $f->write(serialize($this->data));
	    $f->close();
	    $this->load_from_file($file_name, $levels);
	}
	/**
	 * Загрзука структуры из файла
	 *
	 * @param string $file_name Имя файла из папки config/structure, без расширения и пути
	 */
	function load_from_file($file_name = 'general', $levels = false)
	{
		if (!is_array($levels) || !count($levels)) {
			$levels = $this->get_site_levels($file_name);
		}
		$this->file_name = $file_name;
		$this->levels = $levels;
		$this->data = array();
		foreach ($levels as $level) {
		    $f = new Nertz_File('root', $this->get_level_name($file_name, $level), "r");
		    $data = $f->get_all();
		    if ($data && $data != UNSET_VALUE . '?') {
		    	$data = unserialize($data);
	    		array_merge_full($this->data, $data);
		    } else if ($data == UNSET_VALUE . '?') {
		    	$this->data = array();
		    }
	    	$f->close();
		}
	}
	static function get_fields()
	{
	    /**
         * Список типов полей структуры с их Русскими названиями
        */
	    $nertz_config_structure_field_types = array(
	    'Folder'        => 'Папка',
	    'Int'           => 'Число',
	    'String'        => 'Строка',
	    'Text'          => 'Простой текст',
	    'Bool'			=> 'Флаг',
	    'Array'         => 'Массив',
	    'Select'        => 'Выбор из списка',
	    );
	    return $nertz_config_structure_field_types;
	}
	function get_params_conversion()
	{
	    /**
         * Список служенбных полей с их значениями по умолчанию
        */
	    $nertz_config_structure_field_params = array(
	    'name'    => '_n',
	    'type'    => '_t',
	    'caption' => '_c',
	    'a_title' => '_at',
	    'descr'   => '_d',
	    'special' => '_s',
	    'e_value' => '_ev',
	    'stype'       => '_st',
	    'ssource'     => '_ss',
	    'source_path' => '_sp',
	    'source_capt' => '_sc');
        return $nertz_config_structure_field_params;
	}
	/**
	 * Получить список доступнык конфигов
	 *
	 * @param string $name Имя файла
	 * @return array Список файлов с конфигами
	 */
	function get_config_levels($name = 'general')
	{
		$configs = array(
		'core'    => array( 'name' => $this->get_level_name($name, 'core'), 'levels' => array('core')),
		'project' => array( 'name' => $this->get_level_name($name, 'project'), 'levels' => array('core','project')),
		);
		return 	$configs;
	}
	/**
	 * Получить список всех уровней для загрузки конфига на сайте
	 *
	 * @param unknown_type $name
	 */
	function get_site_levels($name = 'general')
	{
		$arr = array('core','project');
		return $arr;
	}
	/**
	 * Получить к папке форм для данного уровня
	 *
	 * @param unknown_type $level
	 * @return unknown
	 */
	function get_level_path($level)
	{
		switch ($level) {
			case 'core':    return '/core/config/forms/';
			default: return '/site/config/forms/';
		}
	}
	function get_level_name($name, $level)
	{
		$name = $name . $this->ext;
		return $this->get_level_path($level) . $name;
	}
	function get_main_fields()
	{
		return array('name' => '', 'caption' => '', 'edit_caption' => '', 'add_caption' => '', 'index_field' => '', 'movable_rows' => '', 'event_handler_class' => '', 'on_create' => '', 'on_table_header' => '', 'on_table_footer' => '', 'on_act' => array(), 'items_per_page' => 16, 'on_access' => '', 'seo' => '');
	}
	function get_field_fields()
	{
		return array('type' => '', 'name' => '', 'form_caption' => '', 'table_caption' => '', 'description' => '', 'read_only' => '', 'reqired' => '', 'visibles' => '', 'values' => '', 'use_key' => '', 'length' => '', 'max_length' => '', 'rows' => '', 'cols' => '', 'pic_url' => '', 'act' => '', 'on_table_show' => '', 'on_form_show' => '', 'before_save' => '','after_save' => '', 'before_delete' => '','after_delete' => '','after_fetch' => '', 'on_create' => '', 'after_get' => '', 'width' => '', 'height' => '', "mysql_format" => '', "combo_view" => '', "db_event" => '', "db_connect_table_name" => '', "db_connect_item_index" => '', "db_connect_list_index" => '', "db_list_sql" => '', 'ajaxed' => false, 'highlight' => '', 'bootstrap_class' => '','query_table' => '', 'query_ind' => '', 'query_text' => '');
	}
	function get_button_fields()
	{
		return array('name' => '', 'form_caption' => '', 'table_caption' => '', 'image' => '', 'bootstrap_class' => '');
	}
	function get_sql_fields()
	{
		return array('table' => '', 'select' => '', 'position_field' => '');
	}
	function get_filter_fields()
	{
		return array('type' => '', 'name' => '', 'caption' => '', 'description' => '', 'autosubmit' => '', 'on_create' => '', 'on_show' => '');
	}
	function get_unique_fields()
	{
		return array('name' => '', 'message' => '', 'fields' => array());
	}
	function get_data()
	{
		return $this->data;
	}
	/**
	 * Получить путь к экземпляру класса Nertz_Form_Event для данной формы
	 */
	function get_event_handler_path()
	{
		$path = false;
		foreach ($this->levels as $level){
			$p = $this->get_level_path($level) .$this->file_name . '.php';
			if (file_exists($p)) {
				$path = $p;
			}
		}
		return $path;
	}
}
function get_form_stats($name, $levels)
{
	$res = array();
	$ncf = new Nertz_Config_Forms();
	$ncf->load_from_file($name, $levels);
	if (!is_array($ncf->data) || !count($ncf->data)) {
		return UNSET_VALUE;
	}
	$res['field_count'] = 0;
	if (isset($ncf->data['fields']) && is_array($ncf->data['fields'])) {
		$res['field_count'] = count($ncf->data['fields']);
		if(isset($ncf->data['fields'][ORDER_FIELD])) {
			$res['field_count']--;
		}
	}
	$res['button_count'] = 0;
	if (isset($ncf->data['buttons']) && is_array($ncf->data['buttons'])) {
		$res['button_count'] = count($ncf->data['buttons']);
		if(isset($ncf->data['buttons'][ORDER_FIELD])) {
			$res['button_count']--;
		}
	}
	$res['unique_count'] = 0;
	if (isset($ncf->data['uniques']) && is_array($ncf->data['uniques'])) {
		$res['unique_count'] = count($ncf->data['uniques']);
		if(isset($ncf->data['uniques'][ORDER_FIELD])) {
			$res['unique_count']--;
		}
	}
	$res['filter_count'] = 0;
	if (isset($ncf->data['filters']) && is_array($ncf->data['filters'])) {
		$res['filter_count'] = count($ncf->data['filters']);
		if (isset($ncf->data['filters'][ORDER_FIELD])) {
			$res['filter_count']--;
		}
	}
	return $res;
}
