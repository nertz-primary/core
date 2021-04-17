<?php
/**
 * Базовый класс фильтра таблицы класса Nertz_Form
 *
 */
include_once(Nertz::class_path('Nertz_Form_Filter'));
class Nertz_Form_Filter_Bool extends Nertz_Form_Filter 
{
	function filter_get_html()
	{
		$on_change = "";
		if (!empty($this->params['autosubmit'])) {
			$on_change = "onchange='this.form.submit();'";
		}
		$s = '';
		$checked = $this->get_value() ? "CHECKED " : "";
	    $s .= "<input type='checkbox' name='{$this->_get_post_name()}' {$checked} {$on_change} value='1'/>";
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
		 	return  false;
		 }
		 return true;
	}
	function load_posted_value()
	{
	    $default = '';

	    global $url, $session;
	    $default = $session->get_value($this->get_session_name(), $default);
	    
	    if ($url->get_value($this->_get_post_name().'_flag', false)) {
	    	$value = $url->get_value($this->_get_post_name(), false);
	    } else {
	    	$value = $default;
	    }
	    $this->set_value($value);
	}

}