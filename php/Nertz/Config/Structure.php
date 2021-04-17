<?php
/**
 * Класс редктора струкутры хэша
 *
 */

include_once(Nertz::class_path('Nertz_File'));

class Nertz_Config_Structure
{
	/**
	 * Конструктор
	 *
	 * @return Nertz_Config_Structure
	 */
	function  Nertz_Config_Structure()
	{
		$this->ext = '.csf';
	}
	function assign_data(&$data)
	{
		$this->data = & $data;
	}
	/**
	 * Получить список дочерних нод
	 *
	 * @param string $path Путь к ноде
	 * @param bool $skip_items Если true, то после каждого эллемента типа Array в путях пропускается имя его подэллемента (применяется в конфигах)
	 */
	function get_node_list($path, $skip_items=false)
	{
	    $res   = array();
        $items = $this->get_ref($path, true, $skip_items);
        if (is_array($items) && count($items)) 
        {
            foreach ($items as $name => $fields)
            {
            	if($name !== ORDER_FIELD) {
            		$f = $this->_copy_params($fields, 'io');
            		$f['name'] = $name;
            		$res[] = $f;	
            	}
            }
        }
        return $res;
	}
	/**
	 * Функция параметров с внешними именами во внутренние и наоборот
	 *
	 * @param array $p Собственно массив параметров
	 * @param string $mode Режим копировани io или oi
	 */
	function _copy_params($p, $mode='io')
	{

	    $res = array();
	    $types = $this->get_params_conversion();
	    foreach ($types as $iname => $oname)
	    {
	        if (isset($p[$mode=='io' ? $oname : $iname]))
	        {
	            $res[$mode=='io' ? $iname : $oname] = $p[$mode=='io' ? $oname : $iname];
	        }
	    }
	    return $res;
	}
	/**
	 * Получить поля ноды
	 *
	 * @param string $path Путь к ноде
	 * @param bool $skip_items Если true, то после каждого эллемента типа Array в путях пропускается имя его подэллемента (применяется в конфигах)
	 * @return array Массив полей ноды
	 */
	function get_node($path, $skip_items = false)
	{
	    global $nertz_config_structure_field_params;
        $res = $this->_copy_params($this->get_ref($path, false, $skip_items), 'io');
        $p = $this->_parse_path($path);
        $res['name'] = array_pop($p);
        unset($p);
	    return $res;
	}
	/**
	 * Добавление новой ноды в структуру
	 *
	 * @param string $path Путь к родительской ноде
	 * @param assoc_array $params Массив полей будущей ноды
	 */
	function add_node($path, $params)
	{
		$ref  = & $this->get_ref($path,true);
		$name = $params['name'];
		$type = isset($params['type']) ? $params['type'] : "";
		switch ($type)
		{
			case "Folder":
			case "Array":
			    unset($params['name']);
				$ref[$name] = $this->_copy_params($params, 'oi');
				$ref[$name]['_i'] = array();
				break;
			case "Select":
				unset($params['name']);
				$ref[$name] = $this->_copy_params($params, 'oi');
				$ref[$name]['_i'] = array();
				$this->add_node( $path . '/' . $name,
				array('name' => 'values',
					  'type' => 'Array',
				   	  'caption' => 'Эллементы списка',
				   	  'special' => 'Select_Values'));
				break;
			default:
				$ref[$name] = $this->_copy_params($params, 'oi');
				break;
		}

	}
	/**
	 * Удаление ноды
	 *
	 * @param string $path Путь к родительской ноде
	 * @param array $names Массив Имен нод подлежащих удалению
	 */
	function delete_node($path, $names)
	{
		$ref  = & $this->get_ref($path);
		if (is_array($names) && count($names))
		{
		    foreach ($names as $name)
		    {
		        if ($path === '/')
		        {
		            unset($ref[$name]);
		        }
		        else
		        {
		            unset($ref['_i'][$name]);
		        }
		    }
		}
	}
	/**
	 * Разобрать путь в массив
	 *
	 * @param string $path Путь
	 * @return array
	 */
	static function _parse_path($path)
	{
	    $n = explode( "/", $path );
		// Удалим пустые эллементы пути
		foreach ( $n as $i => $v )
		{
			if(!$v)
			{
				unset($n[$i]);
			}
		}
		return $n;
	}
	/**
	 * Обновление полей ноды
	 *
	 * @param string $path Путь к ноде
	 * @param assoc_array $params Массив 'имя_поля'=>'значение', обновляемых полей ноды
	 */
	function update_node($path, $params)
	{
	    // Укоротим путь на одну ноду
	    $arr  = $this->_parse_path($path);
	    $name = array_pop($arr);
		$path = "/".implode('/',$arr);
		$res = & $this->get_ref($path, true);
		$cp = $this->get_params_conversion();
		foreach ($cp as $k => $v)
		{
		    if (isset($params[$k]))
		    {
		        $res[$name][$v] = $params[$k];
		    }
		}
		// Если есть новое имя то переимновываем ноду
		if (isset($params['name']) && $name != $params['name'])
		{
		    $res[$params['name']] = & $res[$name];
		    unset($res[$name]);
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
	/**
	 * Получение указателя на ноду по указанному пути
	 *
	 * @param string $path Путь вида main/page/title
	 * @param bool $to_items Если true, то указывать на items в ней, а не на саму ноду
	 * @param bool $skip_items Если true, то после каждого эллемента типа Array в путях пропускается имя его подэллемента (применяется в конфигах)
	 * @return pointer Ссылка на результат в искодном массиве, вида $data['main']['_i']['page']['_i']['title']
	 */
	function &get_ref($path, $to_items = false, $skip_items = false)
	{
		if ($path == "/")
		{
			return $this->data;
		}
		$n = $this->_parse_path($path);
		
		if (!is_array($n) && !count($n))
		{
			return null;
		}
		$i = 1;
		$cnt = count($n);
		$user_data = null;
		
		$data = &$this->data;
		$skip_next = false;
		foreach ( $n as $v )
		{
			if (!$skip_next)
			{
				if ( !isset($data[$v]) || ($i<$cnt && !isset($data[$v]['_i'])))
				{
					Nertz::log("Не могу найти \"$path\" в структуре.",LOG_WARN,2);
					$res = null;
					return $res;
				}
				$user_data = &$data[$v];
				$data = &$data[$v]['_i'];
				$i++;
			}
			// Пропускаем в пути еще одно звено для каждого эллемента типа Array
			if (!$skip_next && $skip_items && $user_data['_t'] == 'Array') 
			{   
				$skip_next = true;	
			}
			else 
			{
				$skip_next = false;
			}
		}
		if($to_items)
		{
		    return $data;
		}
		return $user_data;
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
			$pstruct = new Nertz_Config_Structure();
			$pstruct->load_from_file($file_name, $plevels);	
			array_diff_full($pstruct->data,$this->data);
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
		    if ($data) {
		    	$data = unserialize($data);
	    		array_merge_full($this->data, $data);
		    }
	    	$f->close();
		}
	}
	static function get_field_types()
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
	 * Генератор хлебных крошек
	 *
	 * @param array $u Базовый урль
	 * @param string $path Путь для хлебных крошек
	 * @return unknown
	 */
	static function gen_breadcrumbs($u, $path)
	{
	    global $url;
	    $path = Nertz_Config_Structure::_parse_path($path);
	    $u['path'] = '/';
	    $s  = "<p class=\"path\">";
	    $s .= "<select name=\"level\" onchange=\"this.form.submit();\">";
	    $levels = array('core' => array('levels'=> array('core')), 'project' => array( 'levels' => array('core','project')));
	    $level_name = $url->get_value('level', 'project');
		foreach ($levels as $key => $level) {
			$s .= "<option value=\"{$key}\""; 
			if ($key === $level_name){
				$s .= "SELECTED ";
			}
			$s .= ">{$key}</option>";
		}
		$s .= "</select>";
	    $s .= "&nbsp;/&nbsp;<a href=\"" . $url->gen_url($u) . "\">Корень</a>";
	    $pp = "";
	    foreach ($path as $p )
	    {
	        $pp .= "/$p";
	        $u['path'] = $pp;
	        $s .= "&nbsp;/&nbsp;<a href=\"" . $url->gen_url($u). "\">{$p}</a>";
	    }
	    return $s ."</p><br/>";
	}
	function reduce_path($path) 
	{
	    $path = Nertz_Config_Structure::_parse_path($path);
        $t    = array_pop($path);    
        return '/' . implode('/', $path);
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
	 * Получить имя файла для данного уровня
	 *
	 * @param unknown_type $name
	 * @param unknown_type $level
	 * @return unknown
	 */
	function get_level_name($name, $level)
	{
		$name = $name . $this->ext;
		switch ($level) {
			case 'core':    return '/core/config/' . $name;
			default: return '/site/config/' . $name;
		}
	}
}

		
/**
 * Метаданные формы редактирования ноды Хэша
 */
$nertz_config_structure_form = array(
"name"        => "main",
"caption"     => "Редактор структуры конфигов",
"index_field" => "name",
"url"         => array('path' => $url->get_value('path','/'), 'level' => $url->get_value('level','project')),
"on_table_header" => 'nertz_config_structure_on_table_header',
"movable_rows" => true,
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
     "order" => array(
          "form_caption"  => "",
   	      "table_caption" => "Порядок",
   	      "image"         => "core/img/button/save.gif"
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
    "type" => Array(
    		"form_caption"  => "Тип поля",
    		"table_caption" => "Т",
    		"description"   => "Тип поля",
    		"read_only"     => 0,
    		"reqired"       => 1,
    		"type"          => "SimpleSelect",
    		"values"        => Nertz_Config_Structure::get_field_types(),
    		"on_table_show" => 'nertz_config_structure_type_onshow',
    		"visibles"		=> array(
    			"Int"       => array("descr"),
    			"String"    => array("descr"),
    			"Text"      => array("descr", "highlight"),
    			"Bool"      => array("descr"),
    			"Array"     => array("a_title", "v_title"),
    			"Select"    => array("descr", "stype", "ssource", "e_value"),
    		)
    		),	
    "name" => Array(
    		"form_caption"  => "Имя поля",
    		"table_caption" => "Имя",
    		"description"   => "Уникальное имя поля в Хэше",
    		"read_only"     => 0,
    		"reqired"       => 1,
    		"type"          => "String",
    		"length"        => 32,
    		"max_lenght"    => 32,
    		"on_table_show" => 'nertz_config_structure_name_onshow'
    		),
    "caption" => Array(
    		"form_caption"  => "Заголовок поля",
    		"table_caption" => "Заголовок",
    		"description"   => "Заголовок поля показываемый в редакторе",
    		"read_only"     => 0,
    		"reqired"       => 1,
    		"type"          => "String",
    		"length"        => 32,
    		"max_lenght"    => 32,
    		),		
    "stype"  => Array(
    		"form_caption"  => "Тип выбора",
    		"table_caption" => "",
    		"description"   => "Одиночный - обычный SELECT, Множестенный - ряд CHECKBOX-ов обеспечивающих массив значений",
    		"read_only"     => 0,
    		"reqired"       => 1,
    		"type"          => "SimpleSelect",
    		"values"        => array(
    			"SimpleSelect" => "Одиночный",
    			"MultiSelect"  => "Множественный"),
    		"visibles"		=> array(
    		)
    		),				
    "ssource" => Array(
    		"form_caption"  => "Источник данных",
    		"table_caption" => "",
    		"description"   => "",
    		"read_only"     => 0,
    		"reqired"       => 1,
    		"type"          => "SimpleSelect",
    		"values"        => array(
    			"values"  => "Свои значения",
    			"config"  => "Ветка конфига"),
    		"visibles"		=> array(
    			"config" => array("source_path","source_capt")
    			)
    		),		
    "source_path" => Array(
    		"form_caption"  => "Путь конфига",
    		"table_caption" => "",
    		"description"   => "Путь должен указывать на ноду типа Массив",
    		"read_only"     => 0,
    		"reqired"       => 1,
    		"type"          => "String",
    		"length"        => 32,
    		"max_lenght"    => 255,
    		),				
    "source_capt" => Array(
    		"form_caption"  => "Поле заголовков",
    		"table_caption" => "",
    		"description"   => "Имя поля значения которого будут заголовками списка. Индексы ветки будут индексами списка. Если поле пустое, то заголовки будут идентичными идексу.",
    		"read_only"     => 0,
    		"reqired"       => 1,
    		"type"          => "String",
    		"length"        => 32,
    		"max_lenght"    => 255,
    		),		
    "a_title" => Array(
    		"form_caption"  => "Заголовок ключа",
    		"table_caption" => "",
    		"description"   => "Заголовок поля редактирования ключа в полях типа \"Массив\"",
    		"read_only"     => 0,
    		"reqired"       => 0,
    		"type"          => "String",
    		"length"        => 32,
    		"max_lenght"    => 32,
    		),		
    "e_value" => Array(
    		"form_caption"  => "Пустое значение",
    		"table_caption" => "",
    		"description"   => "Добавить пустое значение",
    		"read_only"     => 0,
    		"reqired"       => 1,
    		"type"          => "Bool",
    		),				
    "descr" => Array(
    		"form_caption"  => "Описание поля",
    		"table_caption" => "",
    		"description"   => "Детальное описание поля, показываемое пользователю при заполнении формы",
    		"read_only"     => 0,
    		"reqired"       => 0,
    		"type"          => "Text",
    		"rows"          => 10,
    		),		
	"highlight" => Array(
	    		"sql_name"      => "highlight",
	    		"form_caption"  => "Подсветка кода",
	    		"table_caption" => "",
	    		"description"   => "",
	    		"read_only"     => 0,
	    		"reqired"       => 0,
	    		"type"          => "Simpleselect",
	    		"values" 		=> array(
	    				'php' 		 => 'PHP',
	    				'javascript' => 'JavaScript',
	    				'sql'		 => 'SQL',
	    				'html'		 => 'HTML',
	    				'css'		 => 'CSS'
	    			),
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
    		"act"           => "edit",
    		),	
    ),
);

// Функция для отображения поля type в таблице
function nertz_config_structure_type_onshow($field, $row)
{
    if(!isset($row[$field->name])) {
    	return "";
    }
	$value = $row[$field->name];
    global $url;
    $types = Nertz_Config_Structure::get_field_types();
    $my_type = $value;
    return "<img alt=\"". $types[$value] . "\" title=\"". $types[$value] . "\" src=\"" . $url->gen_static_url('core/img/button/' . strtolower($value) . ".gif") ."\"/>";
}

// Функция для отображения поля name в таблице
function nertz_config_structure_name_onshow($field, $row)
{
    if ($row['type'] == 'Array' || $row['type'] == 'Folder' || ($row['type'] == 'Select' && $row['ssource'] == 'values'))
    {
        global $url;
        $path = $url->get_value('path','/');
        if ($path!=='/')
        {
            $path  .= "/";
        }
        $path  .= $row[$field->name];
        $u = $field->form->params['url'];
        $u['path'] = $path;
        return "<a href=\"" . $url->gen_url($u) . "\">{$row[$field->name]}</a>";
    }
    else 
    {
        return $row[$field->name];
    }
}
// Функция отображения подзаголовка таблицы
function nertz_config_structure_on_table_header($table)
{
	global $url;
    $path = $url->get_value('path','/');   
    return Nertz_Config_Structure::gen_breadcrumbs($table->params['url'],$path);
}