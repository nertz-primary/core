<?php
include_once(Nertz::class_path('Nertz_Seo'));

class Nertz_Form
{
	// Вся соль в том, что все настройки класс будут хранится в конфиге.
	// и редактироваться будут с помощью редактора построенного на Nertz_Form
	/**
	 * Инциализайия таблиц
	 *
	 * @param assoc_array $name
	 * @param Nertz_Form_Event $name Обрабочик событий, если есть
	 * @return Nertz_Form
	 */
	function Nertz_Form($params)
	{
		global $log;
		// Если нам передали не массив а лишь название формы, то придется загрузить ее самостоятельно
		if (!is_array($params)) {
			include_once(Nertz::class_path('Nertz_Config_Forms'));
			$cf = new Nertz_Config_Forms;
			$cf->load_from_file($params);
			$data =  $cf->get_data();
			if (!$data) {
				Nertz::log('Не могу загрузить параметры формы "' . $params . '"','error',1);
			} else {
				$params = $data;
			}
		}
		// Установим обработчик событий
		$this->event_handler = null;
		if (!empty($params['event_handler_class'])) {
			$class_name = $params['event_handler_class'];
			include_once(Nertz::class_path($class_name));
			if (!class_exists($class_name)) {
				Nertz::log("Не найден Класс \"{$class_name}\" ", LOG_ERROR, 1);
			} else {
				$this->event_handler =  new $class_name($this);
			}
		}
		// Зачитаем имя таблиы
		if (empty($params['name'])) {
			Nertz::log("Не определен параметр \"name\" ", LOG_WARN, 1);
			return false;
		}
		// ... Урль для форм
		if (empty($params['url']) || !is_array($params['url'])) {
			$params['url'] = array();
		}
		// Если в урле нет страницы, то возьмем текущую
		if (empty($params['url']['page'])) {
			global $url;
			$params['url']['page'] = $url->get_page();

		}
		// ... Заголовок для форм
		if (empty($params['caption'])) {
			$params['caption'] = "";
		}
		// ... Имя индексного поля
		if (empty($params['index_field'])) {
			$params['index_field'] = "";
		}
		// ... Кнопки для форм
		if (!(isset($params['buttons']) && is_array($params['buttons']) && count($params['buttons']))) {
			$params['buttons'] = array();
		}
		unset($params['buttons'][ORDER_FIELD]);
		// ... Фильтры таблицы
		if (!(isset($params['filters']) && is_array($params['filters']) && count($params['filters']))) {
			$params['filters'] = array();
		}
		unset($params['filters'][ORDER_FIELD]);
		// ... Уникальности
		if (!(isset($params['uniques']) && is_array($params['uniques']) && count($params['uniques']))) {
			$params['uniques'] = array();
		}
		// ... Обработчик показа заголовка
		if (empty($params['on_table_header'])) {
			$params['on_table_header'] = "";
		}
		// ... Обработчик показа подвала
		if (empty($params['on_table_footer'])) {
			$params['on_table_footer'] = "";
		}
		// ... Обработчик показа заголовка
		if (empty($params['movable_rows'])) {
			$params['movable_rows'] = false;
		}
		// ... Шаблон отображения форм
		if (empty($params['form_template'])) {
			$params['form_template'] = 'Nertz_Form_Table';
		}
		// ... Шаблон отображения таблиц
		if (empty($params['table_template'])) {
			$params['table_template'] = 'Nertz_Table_Editor';
		}
		// ... Количество элементов на страницу
		if (empty($params['items_per_page'])) {
			$params['items_per_page'] = 16;
		}
		// ... Обработчик доступа к событиям
		if (empty($params['on_access'])) {
			$params['on_access'] = "";
		}
		// ... Поля форм
		if(!(isset($params['fields']) && is_array($params['fields']))) {
			Nertz::log("Не определен параметр \"fields\" ", LOG_WARN, 1);
			return false;
		}
		unset($params['fields'][ORDER_FIELD]);
		
		$this->params = $params;
		
		if (!empty($this->params['seo'])) {
			$this->seo = new Nertz_Seo();
			$this->seo->connect_form($this);
		}

		// Запустим обработчик создания формы
		if (!empty($this->params['on_create'])) {
			$this->call_event_handler($this->params['on_create'], array(&$this));
		}
		$this->load_fields();
		$this->load_filters();
		global $template;
		$this->handle_ajax();
		// Добавляем необходимы CSS и JS файлы
		$template->add_css('core/css/nertz/console.css');
		$template->add_css('core/css/nertz/form.css');

		$template->add_js('core/js/nertz.js');
		$template->add_js('core/js/nertz/form.js');

	}
	/**
	 * Функция первичной инициализации всех полей формы
	 *
	 */
	function load_fields()
	{
		global $log;
		$types = $this->get_field_types();
		foreach ($this->params['fields'] as $name => $params) {
			if (isset($params['type'])) {
				$type = ucfirst(strtolower($params['type']));
				if (isset($types[$type])) {
					if (isset($this->params['fields'][$name]['class'])) {
						unset($this->params['fields'][$name]['class']);
					}
					$class_name = 'Nertz_Form_Field_' . $type;
					include_once(Nertz::class_path($class_name));
					$this->params['fields'][$name]['class'] = new $class_name($name, $this);
				} else {
					$log->write("Неправильный Тип(type) у поля {$name} ", LOG_WARN, 1);
				}
			} else {
				$log->write("Отсутствует Тип(type) у поля {$name} ", LOG_WARN, 1);
			}
		}
	}
	/**
	 * Функция первичной инициализации всех фильтров таблицы
	 *
	 */
	function load_filters()
	{
		global $log;
		$types = $this->get_filter_types();
		foreach ($this->params['filters'] as $name => $params) {
			if (isset($params['type'])) {
				$type = ucfirst(strtolower($params['type']));
				if (isset($types[$type])) {
					if (isset($this->params['filters'][$name]['class'])) {
						unset($this->params['filters'][$name]['class']);
					}
					$class_name = 'Nertz_Form_Filter_' . $type;
					include_once(Nertz::class_path($class_name));
					$this->params['filters'][$name]['class'] = new $class_name($name, $this);
					$this->params['filters'][$name]['class']->load_posted_value();
				} else {
					$log->write("Неправильный Тип(type) у фильтра {$name} ", LOG_WARN, 1);
				}
			} else {
				$log->write("Отсутствует Тип(type) у фильтра {$name} ", LOG_WARN, 1);
			}
		}
	}
	/**
	 * Показать таблицу с данными
	 *
	 * @param string $template_name Каноническое имя шаблона для отображения таблицы
	 * @return HTML Код полученной таблицы
	 */

	function show_table($template_name='')
	{
		global $template;
		if (empty($template_name)) {
			$template_name = $this->params['table_template'];
		}
		$fields = array();
		$values = array();
		foreach ($this->params['fields'] as $name => $field) {
			if (isset($field['table_caption']) && $field['table_caption']) {
				$fields[$name] = array('caption' => $field['table_caption'], 'type' => $field['type']);
			}
		}
		$indexes = array();
		if (is_array($this->table_values)) {
			$index_name = $this->index_name();
			foreach ($this->table_values as $id => $row) {
				$values[$id] = array();
				foreach ($fields as $name => $field) {
					$val = $this->params['fields'][$name]['class']->get_table_html($row);
					$values[$id][$name] = empty($val) ? '&nbsp' : $val;
				}
				if (isset($row['nertz_form_row_style'])) {
					$values[$id]['nertz_form_row_style'] = $row['nertz_form_row_style'];
				}
				if (isset($row['nertz_form_row_class'])) {
					$values[$id]['nertz_form_row_class'] = $row['nertz_form_row_class'];
				}
				if ($this->params['movable_rows']) {
					$indexes[$id] = $row[$index_name];
				}
			}
		}
		$buttons = array();
		if (count($this->params['buttons'])) {
			foreach ($this->params['buttons'] as $name => $params) {
				if (isset($params['table_caption']) && $params['table_caption']) {
					$n = $this->get_post_name('button_' . $name);
					$params['caption'] = $params['table_caption'];
					$buttons[$n] = array_copy_default($params, array('caption' => '', 'image' => '', 'bootstrap_class' => '' ));
				}
			}
		}
		$table_header = $this->call_event_handler($this->params['on_table_header'], array(&$this));
		$table_footer = $this->call_event_handler($this->params['on_table_footer'], array(&$this));
		$filters = array();
		foreach ($this->params['filters'] as $name => $filter) {
			$filters[$name] = array('caption' => $filter['caption'], 'html' => $this->params['filters'][$name]['class']->get_html());
		}

		$template->set_value('table_header', $table_header);
		$template->set_value('table_footer', $table_footer);
		$template->set_value('caption',      $this->params['caption']);
		$template->set_value('url',          $this->params['url']);
		$template->set_value('movable_rows', ($this->params['movable_rows']) ? $this->get_post_name('rows_order') : "");
		$template->set_value('indexes',      $indexes);
		$template->set_value('index_name',   $this->index_name());
		$template->set_value('buttons',      $buttons);
		$template->set_value('fields',       $fields);
		$template->set_value('values',       $values);
		$template->set_value('filters',      $filters);
		return $template->render($template_name);
	}
	/**
	 * Показать форму
	 *
	 * @param string $template Имя шаблона (формируется по правилам имен классов)
	 */
	function show_form($template_name = '', $read_only = false)
	{
		global $template;
		if (empty($template_name)) {
			$template_name = $this->params['form_template'];
		}
		$hidden_fields = array();
		$fields        = array();
		$visibles      = array();
		// Для режима Read-only создадим фэйковую строку
		$row = array();
		if ($read_only) {
			$row = $this->get_vars();
		}
		foreach ($this->params['fields'] as $name => $field)
		{
			if ($field['class']) {
				if (strtolower($field['type']) == 'hidden')	{
					$hidden_fields[$field['class']->_get_post_name($name)] = !empty($field['value']) ? $field['value'] : '';
				} else if (isset($field['form_caption']) && $field['form_caption'])	{
					$fields[$field['class']->_get_post_name()] = array(
					'html'          => $read_only ? "<span>{$field['class']->get_table_html($row)}</span>" : $field['class']->get_form_html(),
					'caption'       => $field['form_caption'],
					'error_message' => !empty($field['error_message']) ? $field['error_message'] : "",
					'description'   => !empty($field['description']) ? $field['description'] : "",
					'whole_line' => isset($this->params['fields'][$name]['whole_line']) ? $this->params['fields'][$name]['whole_line'] : false,
					'type' => $field['type'],
					'error_status' => $field['error_status'],
					'reqired' => empty($field['reqired']) ? 0 : 1,
					);
					if (isset($field['visibles']) && is_array($field['visibles']) && count($field['visibles'])) {
						$visibles[$field['class']->_get_post_name()] = array();
						foreach ($field['visibles'] as $vname => $values) {
							//$values = explode(',',$val);
							foreach ($values as $id => $v) {
								$values[$id] = $field['class']->_get_post_name(trim($v));
							}
							$visibles[$field['class']->_get_post_name()][$vname] = $values;
						}
					}
				}
			}
		}
		$buttons = array();
		if (count($this->params['buttons'])) {
			foreach ($this->params['buttons'] as $name => $params) {
				if (isset($params['form_caption']) && $params['form_caption']) {
					$n = $this->get_post_name('button_' . $name);
					$params['caption'] = $params['form_caption'];
					$buttons[$n] = array_copy_default($params, array('caption' => '', 'image' => '', 'bootstrap_class' => ''  ));
				}
			}
		}
		if($this->get_posted_index() && !empty($this->params['edit_caption'])) {
			$template->set_value('caption', $this->params['edit_caption']);
		} else if(!$this->get_posted_index() && !empty($this->params['add_caption'])) {
			$template->set_value('caption', $this->params['add_caption']);
		} else {
			$template->set_value('caption', "");
		}
		$template->set_value('css_class',     isset($this->params['css_class']) ? $this->params['css_class'] : 'form');
		$template->set_value('css_id',        isset($this->params['css_id'])    ? $this->params['css_id']    : 'form_' . $this->params['name']);
		$template->set_value('buttons',       $buttons);
		$template->set_value('url',           $this->params['url']);
		$template->set_value('visibles',      $visibles);
		$template->set_value('hidden_fields', $hidden_fields);
		$template->set_value('fields',        $fields);
		$template->set_value('form_name',     $this->params['name']);
		return $template->render($template_name);
	}
	function set_vars( $values )
	{
		if (is_array($values) && count($values)) {
			$this->setting_values = $values;
			foreach ($values as $name => $value) {
				if (!empty($this->params['fields'][$name]) && !empty($this->params['fields'][$name]['class'])) {
					$this->params['fields'][$name]['class']->set_value($value);
				} else {
					//Nertz::log('Не могу установить значение для поля "' . $name . '"','error',2);
				}
			}
		}

	}
	function get_vars()
	{
		$res = array();
		foreach ($this->params['fields'] as $name => $field) {
			if ( !empty($this->params['fields'][$name]['class']) && $this->params['fields'][$name]['class']->is_visible() &&((isset($field['form_caption']) && $field['form_caption']) || strtolower($field['type']) == 'hidden')) {
				$value = $this->params['fields'][$name]['class']->get_value();
				if ($value !== null) {
					$res[$name] = $value;
				}


			}
		}
		return $res;
	}
	/**
	 * Получить значение поля формы
	 *
	 * @param string $name Имя поля
	 * @return mixed Значение
	 */
	function get_value($name)
	{
		if (isset($this->params['fields'][$name]) && isset($this->params['fields'][$name]['class'])) {
			return $this->params['fields'][$name]['class']->get_value();
		}
	}
	/**
	 * Установить значение поля формы
	 *
	 * @param string $name Имя поля
	 * @param mixed $value Значение
	 */
	function set_value($name, $value)
	{
		if (isset($this->params['fields'][$name]) && isset($this->params['fields'][$name]['class'])) {
			return $this->params['fields'][$name]['class']->set_value($value);
		}
	}
	/**
	 * Получить значение фильтра
	 *
	 * @param string $name Имя фильтра
	 * @return mixed Значение
	 */
	function get_filter_value($name)
	{
		if (isset($this->params['filters'][$name]) && isset($this->params['filters'][$name]['class']))  {
			return $this->params['filters'][$name]['class']->get_value();
		}
	}
	/**
	 * Сбросить значения фильтров
	 *
	 */
	function reset_filters()
	{
		foreach ($this->params['filters'] as $name => $filter) {
			if (isset($this->params['filters'][$name]) && isset($this->params['filters'][$name]['class']))  {
				$this->params['filters'][$name]['class']->reset();
			}
		}
	}
	/**
	 * Проверим
	 *
	 * @return bool True - если нет ошибок, иначе false
	 */
	function check_vars()
	{
		$res = true;
		$this->set_visibles();
		foreach ($this->params['fields'] as $name => $field) {
			if (!empty($this->params['fields'][$name]['class']) && $this->params['fields'][$name]['class']->is_visible() && !empty($field['form_caption'])) {
				$field_res = $this->params['fields'][$name]['class']->check();
				if ($field_res !== true) {
					$this->params['fields'][$name]['error_message'] = $field_res;
					$res  = false;
					$this->params['fields'][$name]['error_status'] = 'error';
				} else {
					$this->params['fields'][$name]['error_status'] = 'ok';
				}
			}
		}
		return $res;
	}
	/**
	 * Для каждого поля устанавливает свосто visible относительно того какие значения у SELECT-ов влияющих на нее
	 *
	 */
	function set_visibles()
	{
		$res = array();
		// Сделаем все эллементы видимыми
		foreach ($this->params['fields'] as $name => $field) {
			$this->params['fields'][$name]['visible'] = true;
		}
		// Установим видимость в соответсвии со значениями SimpleSelect-ов
		foreach ($this->params['fields'] as $name => $field) {
			if (isset($field['form_caption']) && $field['form_caption'] && ucfirst(strtolower($field['type'])) == 'Simpleselect'
			&& isset($field['visibles']) && is_array($field['visibles']) && count($field['visibles'])) {
				// Сначала сделаем все зависимые поля невидимыми
				foreach ($field['visibles'] as $vn => $v) {
					
					//$v = explode(',', $vv);
					if (is_array($v) && count($v)) {
						foreach ($v as $f) {
							if (isset($this->params['fields'][$f])) {
								$this->params['fields'][$f]['visible'] = false;
							}
						}
					}
				}
				// Теперь если сами видимы сделаем видимыми и те эллементы что связанны с нами
				$this->params['fields'][$name]['class']->load_posted_value();
				$value = $this->params['fields'][$name]['class']->get_value();
				if (!empty($field['visibles'][$value])) {
					// Немного повтора кода и нужные эллементы видимы
					//$v = explode(',', $field['visibles'][$value]);
					$v = $field['visibles'][$value];
					if (is_array($v) && count($v)) {
						foreach ($v as $f) {
							if (isset($this->params['fields'][$f])) {
								$this->params['fields'][$f]['visible'] = true;
							}
						}
					}
				}
			}
		}
	}
	/**
	 * Загрузим в форму данные из поста
	 *
	 * @return Результат проверки функцией $this->check_vars()
	 */
	function load_posted_vars($no_check = false)
	{
		$this->set_visibles();
		foreach ($this->params['fields'] as $name => $field) {
			if (!empty($this->params['fields'][$name]['class']) && $this->params['fields'][$name]['class']->is_visible()) {
				$this->params['fields'][$name]['class']->load_posted_value();
			}
		}
		if ($no_check) {
			return true;
		}
		return $this->check_vars();
	}
	/**
	 * Заменим в строке все [Имя_поля] на значения этих полей,
	 * а (Имя_фильтра) на значения фильтров
	 *
	 * @return Строка с заменой переменных
	 */
	function replace_db_vars( $s )
	{
		global $db;
		foreach ($this->params['fields'] as $name => $field) {
			if (isset($this->params['fields'][$name]['class'])) {
				$val = $this->params['fields'][$name]['class']->get_db_value();
				$val = is_scalar($val) ? strval($val) : null;
				$s1 = $db->quote($val);
				// Заменим слова в скобочках  На значения соотвествующих переменных обслэшивая в них квадратные скобки
				if ($s1 !== "" && $s1 !== null) {
					$s = str_replace("[$name]", "'" . str_replace(Array(']','['), Array('\\]','\\['),$s1) . "'", $s);
				} else {
					$s = str_replace("[$name]", "NULL", $s);
				}
			}
		}
		foreach ($this->params['filters'] as $name => $filter) {
			$value = $this->params['filters'][$name]['class']->get_db_value();
			if (is_array($value)) {
				$xxx = '';
				foreach ($value as $v) {
					$s1 .= $xxx. "'". $db->quote($v)."'";
					$xxx = ', ';
					$s1 = trim($s1,"'");
				}
			} else {
				$s1 = $db->quote($value);
			}

			// Заменим слова в скобочках  На значения соотвествующих переменных обслэшивая в них круглые скобки
			if ($s1 !== "" && $s1 !== null) {
				$s = str_replace("($name)", "'" . str_replace(Array(')','('), Array('\\)','\\('),$s1) . "'", $s);
			} else {
				$s = str_replace("($name)", "NULL", $s);
			}
		}
		return $s;
	}
	/**
	 * Получить массив всех доступных типов полей
	 * При добавлении новго поля, его необходимо отметить и здесь
	 * @see $nertz_form_field_types
	 *
	 * @return array Массив доступных типов полей
	 */
	static function get_field_types()
	{
		return array(
		'Int'           => 'Число',
		'Float'         => 'Число с точкой',
		'String'        => 'Строка',
		'Password'      => 'Пароль',
		'Text'          => 'Простой текст',
		'Html'          => 'HTML текст',
		'Date'          => 'Дата',
		'Bool'		=> 'Флаг',
		'Simpleselect'  => 'Выпадающий список',
		'Multiselect'   => 'Множественный выбор',
		'Dbmultiselect' => 'Множественный выбор из БД',
		'Lookup'	=> 'Поисковик',
		'Assocarray'    => 'Ассоциативный массив',
		'File'          => 'Загрузка файла',
		'Storefile'     => 'Файл из хранилища',
		'Storeimage'    => 'Картинка из хранилища',
		'Caption'       => 'Подзаголовок для формы',
		'Button'        => 'Табличная кнопка',
		'Checkbox'      => 'Табличный checkbox',
		'Tag'           => 'Тэги',
		'Hidden'        => 'Скрытое поле',
		'Tel3'          => 'Телефон (3 поля)',
		);
	}
	/**
	 * Получить список типов фильтров используемых в формах
	 *
	 * @return array
	 */
	static function get_filter_types()
	{
		return array(
		'Mask'          => 'Маска',
		'Simpleselect'  => 'Выпадающий список',
		'Date'          => 'Дата',
		'Multiselect'   => 'Множественный выбор',
		'Bool'          => 'Флаг',
		);
	}
	/**
	 * Установить двумерный массив для отображения в таблице
	 *
	 * @param array $values
	 */
	function set_table_values($values)
	{
		$this->table_values = $values;
	}
	/**
	 * Получить имя индексного поля
	 *
	 */
	function index_name()
	{
		// Если таковое не определенно в настройках, то возьмем первое попавшееся.
		if (!$this->params['index_field']) {
			foreach ($this->params['fields'] as $name => $params) {
				$this->params['index_field'] = $name;
				break;
			}
		}
		return $this->params['index_field'];
	}
	/**
	 * Получить act после постинга формы
	 * Необходимо использовать эту функцию так как обрабатываются некоторые частные нюансы.
	 *
	 */
	function get_posted_act()
	{
		global $url;
		// По умолчанию возмем act из Урля
		$act = $url->get_value('act','');
		// И прошерудим пост, на тему известных кнопок.
		if (!empty($this->params['buttons']) && count($this->params['buttons'])) {
			foreach ($this->params['buttons'] as $name => $data) {
				$n = $this->get_post_name('button_' . $name);
				if ($url->get_value($n,'')) {
					$act = $name;
				}
			}
		}
		return $act;
	}
	function get_posted_index()
	{
		global $url;
		return $url->get_value('index','');
	}
	/**
	 * Получить имя поля для POST
	 * @see Nertz_Url::gen_post_name()
	 *
	 * @param string $name Имя поля
	 * @param bool $no_gen_post_name Если TRUE, то не использовать $url->gen_post_name
	 * @return string
	 */
	function get_post_name($name, $no_gen_post_name = false, $form_name = '')
	{
		if ($form_name === '') {
			$form_name = $this->params['name'];
		}
		global $url;
		if ($no_gen_post_name) {
			return $form_name . '_' .$name;
		} else{
			return $url->gen_post_name($form_name . '_' .$name);
		}
	}
	/**
	 * Хитрая функция вызываемая как можно раньше для обработки Lookup полей
	 * Пока для универсальности не придумал ничего лучше как просто вернуть данные запроса и оборвать все нафиг
	 *
	 */
	function handle_ajax()
	{
		global $url;
		if ($this->get_posted_act() == '_lookup' && isset($_REQUEST['_field']) &&
		isset($this->params['fields'][$_REQUEST['_field']])) { 
			
			if ($this->params['fields'][$_REQUEST['_field']]['type'] == 'Lookup'
			|| $this->params['fields'][$_REQUEST['_field']]['type'] == 'String') {
				$req = Nertz::init_ajax();
				$term = !empty($_REQUEST['s']) ? $_REQUEST['s'] : (!empty($_REQUEST['term']) ? $_REQUEST['term'] : '');
				$req->RESULT['a'] = $this->params['fields'][$_REQUEST['_field']]['class']->query_values(array('s' => $term));				
			} else {
				$term = !empty($_REQUEST['s']) ? $_REQUEST['s'] : (!empty($_REQUEST['term']) ? $_REQUEST['term'] : '');
				echo Nertz::json($this->params['fields'][$_REQUEST['_field']]['class']->query_values(array('s' => $term)));
			}
			Nertz::terminate();
			exit();
		}
	}
	/**
	 * Получить порядок столбцов при movable_rows = true
	 *
	 */
	function get_order()
	{
		if (!empty($_REQUEST[$this->get_post_name('rows_order')])) {
			return $_REQUEST[$this->get_post_name('rows_order')];
		} else {
			return array();
		}
	}
	/**
	 * Обертка для вызова всех обрабочиков форм
	 * @param handler $handler Обрабочик
	 * @param array $params Массив параметров
	 */
	function call_event_handler($handler, $params)
	{
		if (empty($handler)) {
			return false;
		}
		if (!isset($params) || !is_array($params)) {
			$params = array();
		}
		// Проверяем возможно запуска нашего события из event_handler-а
		if (is_string($handler) && $this->event_handler && is_callable(array(&$this->event_handler, $handler))) {
			if (class_exists('ReflectionMethod')) {
				 $reflectionMethod = new ReflectionMethod($this->event_handler, $handler);
				 return $reflectionMethod->invokeArgs($this->event_handler, $params);
			} else {
				return call_user_func_array(array(&$this->event_handler, $handler), $params);
			}
		} else if(is_callable($handler)){
			return call_user_func_array($handler, $params);
		} else {
			Nertz::log("Не могу обнаружить функцию \"" . print_r($handler,1) , "\" определенную как on_table_header для формы {$this->params['name']}", 'error', 0);
		}
	}
	function has_event_handler($handler)
	{
		if (empty($handler)) {
			return false;
		}
		if (is_string($handler) && $this->event_handler && is_callable(array(&$this->event_handler, $handler))) {
			return true;
		}
		if (is_callable($handler)){
			return true;
		}
		return false;
	}
	function set_error($name, $message) 
	{
		$this->params['fields'][$name]['error_message'] = $message;
		$this->params['fields'][$name]['error_status'] = 'error';		
	}
}
