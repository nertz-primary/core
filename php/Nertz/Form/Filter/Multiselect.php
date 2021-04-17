<?php
/**
 * Базовый класс фильтра таблицы класса Nertz_Form
 *
 */
include_once(Nertz::class_path('Nertz_Form_Filter'));
class Nertz_Form_Filter_Multiselect extends Nertz_Form_Filter 
{
	function filter_get_html()
	{
		$on_change = "";
		if (!empty($this->params['autosubmit'])) {
			$on_change = "onchange='this.form.submit();'";
		}
		$val = $this->get_value();
//		$s  = "<select name='". $this->_get_post_name() . "' id='". $this->_get_post_name() . "' $on_change>";
		$s = '';
		if (isset($this->params['values']) && is_array($this->params['values']) && count($this->params['values'])) {
			foreach ($this->params['values'] as $key => $value) {
				$checked = "";
				if (in_array($key, $val) ) {
			    	$checked = " CHECKED ";
			    }
			    $s .= "<label class='checkbox'>";
			    $s .= "<input type='checkbox' name='". $this->_get_post_name() . "[$key]' $checked />{$value} ";
			    $s .= "</label>";
			}
		}
		// Это хитрое поле, пришлось ввести чтобы определять что запощщен фильтр а не произошол какой-то иной пост.
		$s .= "<input type='hidden' name='". $this->_get_post_name() . "_flag' value ='1' />";
		return $s;
	}
	/**
	 * Переопределим для того чтобы если нет значения был выбран первый эллемент
	 *
	 */
	function get_value()
	{
		 if (empty($this->params['value'])) {
		 	// Берем дефолтное значение
		 	if (!empty($this->params['default'])) {
		 		return $this->params['default'];
		 	}
		 	return array();
		 }
		 return $this->params['value'];
	}
	function load_posted_value()
	{
	    // Думаю если ничего не пришло, то не надо ругани а просто выведем пустую строку
	    $default = '';

	    // Если есть дефолное значение в свойствах поля, то будем иметь его ввиду
	    if (isset($this->params['default'])) {
	        $default = $this->params['default'];
	    }
	    // Загрузим из сессии ссохраненное ранее значение
	    global $session;
	    $default = $session->get_value($this->get_session_name(), $default);
	    global $url;
	    
	    if ($url->get_value($this->_get_post_name().'_flag', false)) {
	    	$value = array_keys($url->get_value($this->_get_post_name("", true), array()));
	    } else {
	    	$value = $default;
	    }
	    
	    $this->set_value($value);
	}
}