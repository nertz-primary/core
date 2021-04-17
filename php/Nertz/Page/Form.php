<?php
include_once(Nertz::class_path('Nertz_Page'));
include_once(Nertz::class_path('Nertz_Form'));

/**
 * Базовый класс для страниц основанных на Forms
 *
 */
class Nertz_Page_Form extends Nertz_Page
{
	/**
	 * Форма
	 *
	 * @var Nertz_Form
	 */
	public $form;
	
	/**
	 * Конструктор
	 * @abstract Всегда переопределяйте
	 * Вызывайтей его как parent::Nertz_Page_Form($params); в конце своего конструктора
	 * В собственном конструкторе необходимо задать:
	 * 1. $this->form_params - Настройки формы Nertz_Form
	 * 2. $this->item - Класс эллемента, наследника от Nertz_Item.
	 * Если это свойство не будет задано, то используется автомат на основе Nertz_Form_Item
	 *
	 * @param array $params Свойства страницы, просто передаем дальше Nertz_Page
	 * @return Nertz_Page_Form
	 */
	function Nertz_Page_Form($params)
	{
		global $url;
		// Если не определенн эллемент, то задествуем автомат
		if (!isset($this->form_params)) {
			if (isset($params['form_name'])) {
				include_once(Nertz::class_path('Nertz_Config_Forms'));
				$cf = new Nertz_Config_Forms;
				$cf->load_from_file($params['form_name']);
				$data =  $cf->get_data();
				if (!$data) {
					Nertz::log('Не могу загрузить параметры формы ' . $params['form_name'],'error',1);
				} else {
					$this->form_params = $data;
				}
			} else {
				Nertz::log('Не определенно свойство класса->form_params','error',1);
			}
		}
		if ($back_page = $url->get_value('back_page', '')) {
			$this->form_params['url']['back_page'] = $back_page;
		}
		if ($p = $url->get_value('p', '')) {
			$this->form_params['url']['p'] = $p;
		}
		$this->form = new Nertz_Form($this->form_params);
		// Если не определенн эллемент, то задествуем автомат
		if (!isset($this->item)) {
			include_once(Nertz::class_path('Nertz_Item_Form'));
			$this->item = new Nertz_Item_Form($this->form);
		}
		parent::Nertz_Page($params);
	}
	function show()
	{
  	    global $url;
        $form_act = $this->form->get_posted_act();
        $index    = $this->form->get_posted_index();

        if(!$this->has_access($form_act)) {
        	return  'Нет доступа!!!';
        }
        $this->show_table = true;
       if (!empty($this->form->params['on_act_' . $form_act])) {
	        if(!is_callable($this->form->params['on_act_' . $form_act])) {
	        	Nertz::log("Не могу обнаружить функцию \"{$this->form->params['on_act_' . $form_act]}\" определенную как on_{$form_act} для формы", 'error', 2);
	        }
	        $res = call_user_func_array($this->form->params['on_act_' . $form_act], array(&$this));
	        if ($res) {
	        	return $res;
	        }
	    }
		else if (!empty($this->form->params['on_act'][$form_act])) {
	        if(!$this->form->has_event_handler($this->form->params['on_act'][$form_act])) {
	        	Nertz::log("Не могу обнаружить функцию \"{$this->form->params['on_act'][$form_act]}\" определенную как on_{$form_act} для формы", 'error', 2);
	        }
	        $res = $this->form->call_event_handler($this->form->params['on_act'][$form_act], array(&$this));
	        if ($res) {
	        	return $res;
	        }
	    }
        // Изменение существующей записи
        else if ($form_act == 'edit' && $index) {
            $vars = $this->item->get($index);
            $res = $this->_run_handlers('after_get', true, false, array(&$vars));
        	$this->form->set_vars($vars);
            
            $this->form->params['url']['index'] = $index;
            return $this->form->show_form();
            $this->show_table = false;
        }
        // Добавление новой записи
        else if ($form_act == 'add') {
            return $this->form->show_form();
            $this->show_table = false;
        }
        // Удаление группы записей
        else if ($form_act == 'delete') {
            $this->form->load_posted_vars();
            $inds = $this->form->get_value('delete');
        	$res = $this->_run_handlers('before_delete', true, false, array(&$inds));
			if ($res) {
				$res = $this->item->delete($inds);
				if ($res) {
					$this->_run_handlers('after_delete', true, false, array($inds));
				}
			}
			$this->check_back_page();
        }
		else if ($form_act == 'order') {
            $this->form->load_posted_vars();
            $this->item->set_order($this->form->get_order());
        }
        // Сохранение записи
        else if ($form_act == 'save') {
        	
        	$load_res = $this->form->load_posted_vars();
        	//if ($load_res) { // Заккоментировал, хочется чтобы работали все проверки.
        		$params = $this->form->get_vars();
        		if ($index) {
	        		$res = $this->_run_handlers('before_save', true, true, array(&$params, 'update', $index));
	        	} else {
	        		$res = $this->_run_handlers('before_save', true, true, array(&$params,'insert', 0));
	        	}

	        //}
        	if (!$load_res || !$this->item->check_uniques($index, $params) || !$res) {
        		if ($index) {
        			$this->form->params['url']['index'] = $index;
        		}
        		return  $this->form->show_form();
        		$this->show_table = false;
        	} else if ($index) {
        		$res = $this->item->update($index, $params);
        		if($res) {
					$this->_run_handlers('after_save', true, true, array(&$params, 'update', $index));
				}
        	} else {
        		$res = $this->item->add($params);
        		if ($res) {
        			
					$this->_run_handlers('after_save', true, true, array(&$params,'insert', $res));
				}
        	}
        	$this->check_back_page();
        }
        // Отмена чего либо
        else if ($form_act == 'cancel') {
        	$this->check_back_page();
        }
        if ($this->show_table) {
        	$res = $this->item->fetch($url->get_value('p',0));
        	$this->_run_handlers('after_fetch', true, false, array(&$res));
            $this->form->set_table_values($res);
            return $this->form->show_table();
        }
	}
	/**
	 * Заппуск обработчика по всем полям таблицы
	 *
	 * @param string $act Название обработчика, например before_save
	 * @param any $init_val Начальное значение для return
	 * @param bool $use_visibles Если true, то обработчики вызываются только для видимых полей
	 * @param array $params Дополнительные параметры передаваемые обработчику
	 * @return unknown
	 */
	function _run_handlers($act,  $init_val = false, $use_visibles = true, $params = array())
	{
		$tres = $init_val;
		if ($use_visibles) {
			$this->form->set_visibles();
		}
		foreach ($this->form->params['fields'] as $name => $field) {
			//  Проверим есть ли у поля свои одноименные обрабочики, если да то будем их выполнять после пользовательских
			$has_feild_handler =  (!empty($field[$act])? $this->form->has_event_handler($field[$act]) : false) | is_callable(array(&$field['class'], $act));
			if ((!empty($field[$act]) || $has_feild_handler) && (!$use_visibles || ($use_visibles && $field['class']->is_visible()))) {
				if (!empty($field[$act]) && !$has_feild_handler) {
					Nertz::log("Не могу обнаружить функцию \"{$field[$act]}\" определенную как \"{$act}\" для формы {$this->form->params['name']}", 'error', 1);
				}
				// Почему-то array_unshift  портил ссылки, пришлось лепить массив вручную
				//$p = $params;
				//array_unshift($p, $this->form->params['fields'][$name]['class']);
				$p = array();
				$p[] = & $this->form->params['fields'][$name]['class'];
				if(is_array($params)) {
					foreach ($params as $i => $param) {
						$p[] = &$params[$i];
					}
				}
				$res = true;
				if (!empty($field[$act])) {
					//$res = call_user_func_array($field[$act], $p);
					$res = $this->form->call_event_handler($field[$act], $p);
				}
				// Вызовем обработчик самого поля, если нам разрешил пользовательский
				if ($res && is_callable(array(&$field['class'], $act))) {
					array_shift($p);// Выкидываем ссылку на класс
					$res = call_user_func_array(array(&$field['class'], $act), $p);
				}
				if ($res !== $init_val) {
					$tres = $res;
				}
			}
		}
		return $tres;
	}
	function check_back_page()
	{
		global $url;
		if ($back_page = $url->get_value('back_page','')) {
			Nertz::redirect(array('page' => $back_page));
		}
	}
	function has_access(&$act)
	{
		$handler_name = 'on_access'; 
		if ($this->form->has_event_handler($handler_name)) {
			$res = $this->form->call_event_handler($handler_name, array(&$this->form, &$act));
			return $res;
			
		}
		return true;
	}
}