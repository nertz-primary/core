<?php
/**
 * Класс для работы с конфигами
 *
 */

include_once(Nertz::class_path('Nertz_File'));

class Nertz_Config
{
	function Nertz_Config()
	{
		$this->ext = '.cfg';
		$this->load_from_file();
	}
	/**
	 * Получение значения из конфига
	 *
	 * @param string $path Путь к значениию
	 * @param mixed $default Значение по умолчанию
	 * @return unknown
	 */
	function get_value($path, $default = null)
	{
		return $this->_get_value($path, $default);
		if (isset($this->data[$path])) {
			return $this->data[$path];
		}
		elseif (($_data = $this->_check_array($path)) !== false) {
			return $_data;
		}
		if($default !== null) {
		    return $default;
		} else {
			Nertz::log("Нет значения конфигурации для '{$path}'", LOG_ERROR, 1);
			return null;	
		}
	}
	function _get_value($path, $default = null)
	{
	    $ref = $this->get_ref($path);
	    if ($ref !== null) {
	        return $ref;
	    } else if($default !== null) {
		    return $default;
		} else {
			Nertz::log("Нет значения конфигурации для '{$path}'", LOG_ERROR, 1);
			return null;	
		}
	    
	}
	function _check_array( $path )
	{
		$_array = explode( '/', $path );
		if (sizeof($_array)) {
			$_data = $this->data;
			foreach ( $_array AS $key ) {
				if( isset( $_data[$key] )) $_data = $_data[$key];
				else return false;
			}
			return $_data;
		}
		return false;
	}
	function set_value($path, $value)
	{
	     // Укоротим путь на одну ноду
	    $arr  = $this->_parse_path($path);
	    $name = array_pop($arr);
		$path = "/" . implode('/',$arr);
		$ref = & $this->get_ref($path, true);
		if (!is_array($ref)) {
			$ref = array();
		}
		$ref[$name] = $value;
	}
	function get_keys($path)
	{
	    $ref = $this->get_ref($path);
	    $res = array();
	    if ($ref && is_array($ref) && count($ref)) {
	        foreach ($ref as $key => $v) {
	            $res[] = $key;
	        }
	    }
	    return $res;
	}
	/**
	 * Получение указателя на ноду по указанному пути
	 *
	 * @param string $path Путь вида main/page/title
	 * @return pointer Ссылка на результат в искодном массиве, вида $data['main']['_i']['page']['_i']['title']
	 */
	function &get_ref($path, $create = false)
	{
		if ($path == "/") {
			return $this->data;
		}
		$n = $this->_parse_path($path);
		if (!is_array($n) && !count($n)) {
			return null;
		}
		$data = &$this->data;
		foreach ( $n as $v ) {
    	    if (!isset($data[$v])) {
		        if ($create) {
		            $data[$v] = array();
		        } else {
		            $a = null;
		            return $a;
		        }
		    }
	        $data  = &$data[$v];
		}
		return $data;
	}
	/**
	 * Сохранение конфига в файл
	 *
	 * @param string $file_name Имя конфига, например general
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
	    if (!is_array($levels)) {
			$levels = $this->get_site_levels($file_name);
		}
		$plevels = $levels;
		$level = array_pop($plevels);
		if (count($plevels)) {
			$pconfig = new Nertz_Config();
			$pconfig->load_from_file($file_name, $plevels);	
			array_diff_full($pconfig->data,$this->data);
		}
	    $f = new Nertz_File('root', $this->get_level_name($file_name, $level), "w");
	    $f->write(serialize($this->data));
	    $f->close();
	    $this->load_from_file($file_name, $levels);
	}
	/**
	 * Загрзука конфига из файла
	 *
	 * @param string $name Имя конфига, например general
	 * @param array   $levels  Имена категорий которые стоит скомбинировать
	 */
	function load_from_file($file_name = 'general', $levels = false)
	{
		if (!is_array($levels)) {
			$levels = $this->get_site_levels($file_name);
		}
		$this->file_name = $file_name;
		$this->levels = $levels;
		$this->data = array();
		foreach ($levels as $level) {
		    $f = new Nertz_File('root', $this->get_level_name($file_name, $level), "r");
		    $data = $f->get_all();
		    if ($data) {
		    	$data = unserialize($data);
	    		array_merge_full($this->data, $data);
		    }
	    	$f->close();
		}
	}
	/**
	 * Переименовывание значения конфига
	 *
	 * @param string $path Путь к значению
	 * @param string $new_name Новое имя значения
	 */
	function rename_value($path, $new_name)
	{
	    $path = Nertz_Config_Structure::_parse_path($path);
	    $name = array_pop($path);
	    $path = '/' . implode('/', $path);
	    $res = & $this->get_ref($path, true);
	    // Если есть новое имя то переимновываем ноду
		if ($name && $name != $new_name) {
		    $res[$new_name] = & $res[$name];
		    unset($res[$name]);
		}
	}
	function delete_value($path, $names)
	{
	    $res = & $this->get_ref($path, true);
		if (is_array($names) && count($names)) {
		    foreach ($names as $name) {
		    	unset($res[$name]);
		    }
		}
	}
	/**
	 * Разобрать путь в массив
	 *
	 * @param string $path Путь
	 * @return array
	 */
	function _parse_path($path)
	{
	    $n = explode( "/", $path );
		// Удалим пустые эллементы пути
		foreach ( $n as $i => $v ) {
			if(!$v) {
				unset($n[$i]);
			}
		}
		return $n;
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
		$dir = "/site/config/";
		if (is_dir($GLOBALS['__base_path'] . $dir)) {
			if ($dh = opendir($GLOBALS['__base_path'] . $dir)) {
				while (($file = readdir($dh)) !== false) {
					if (strpos($file, '.') !== 0 &&is_dir($GLOBALS['__base_path'] . $dir . $file)) {
						$configs[$file] = array('name' => $this->get_level_name($name, $file), 'levels' =>  array('core','project', $file));
					}
				}
				closedir($dh);
			}
		}
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
		// Если есть файл .cur одноимненный конфигу то возмем из него путь к конфигу
		$cur_name = $this->get_level_name($name, 'project');
		$cur_name = str_replace('.cfg', '.cur', $cur_name);
		if (file_exists($GLOBALS['__base_path'] . $cur_name)) {
			$f = new Nertz_File('root', $cur_name,"r");
			$arr[] = trim($f->get_all());
			$f->close();
		}
		return $arr;
	}
	function get_level_name($name, $level)
	{
		$name = $name . $this->ext;
		switch ($level) {
			case 'core':    return '/core/config/' . $name;
			case 'project': return '/site/config/' . $name;
			default: return '/site/config/' . $level . '/' . $name;
		}
	}
	/**
	 * Определить порядок нод
	 *
	 * @param unknown_type $path
	 * @param unknown_type $order
	 */
	function set_order($path, $order)
	{
		$res = & $this->get_ref($path, true);
		$res[ORDER_FIELD] = & $order;
	}
	
}
