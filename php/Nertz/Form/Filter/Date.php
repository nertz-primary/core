<?php
/**
 * Базовый класс фильтра таблицы класса Nertz_Form
 *
 */
include_once(Nertz::class_path('Nertz_Form_Filter'));
include_once(Nertz::class_path('Nertz_Form_Field_Date'));
class Nertz_Form_Filter_Date extends Nertz_Form_Filter
{
	function filter_get_html()
	{
		global $template, $url;
		$size     = (isset($this->params['length'])) ? "size='" . $this->params['length'] . "'" : "";
		$init = "";
		$value = Nertz_Form_Field_Date::timestamp2calendar($this->get_value(), !empty($this->params['mysql_format']));
		// Календарь кривой поэтому приходится подключать JS именно так
		if (!isset($GLOBALS['calenar_init'])) {
			$init = '<script language="JavaScript" src="' . $url->gen_static_url('/includes/calendar/calendar.js'). '"></script>';
			$GLOBALS['calenar_init'] = 1;
		}
		return $init . "<input type='text' $size name='". $this->_get_post_name() . "'  id='". $this->_get_post_name() . "'  value='" . $value . "' onfocus=\"this.select();lcs(this);\" onclick=\"event.cancelBubble=true;this.select();lcs(this,'');\" />";
	}
	function field_get_table_html($row)
	{
	    return $this->timestamp2calendar($row[$this->name], !empty($this->params['mysql_format']));
	}
	function get_value()
	{
		$value = parent::get_value();
		
		if (!$value && !empty($this->params['default'])) {
			$value = $this->params['default'];
		}
		return $value;
	}
	function load_posted_value()
	{
	    parent::load_posted_value();
	    $this->set_value(Nertz_Form_Field_Date::calendar2timestamp($this->get_value(),!empty($this->params['mysql_format'])));
	}
}