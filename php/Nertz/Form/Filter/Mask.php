<?php
/**
 * Базовый класс фильтра таблицы класса Nertz_Form
 *
 */
include_once(Nertz::class_path('Nertz_Form_Filter'));
class Nertz_Form_Filter_Mask extends Nertz_Form_Filter 
{
	function filter_get_html()
	{
		$size = (isset($this->params['length'])) ? "size='" . $this->params['length'] . "'" : "";
		$val = $this->get_value();
		return "<input type='text' $size name='". $this->_get_post_name() . "'  id='". $this->_get_post_name() . "'  value='{$val}' />";
	}
	/**
	 * Переопределим подставляемое в запрос значение
	 *
	 * @return string
	 */
	function get_db_value()
	{
		return $this->get_value() . "%";
	}
}