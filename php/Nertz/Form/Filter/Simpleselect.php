<?php
/**
 * Базовый класс фильтра таблицы класса Nertz_Form
 *
 */
include_once(Nertz::class_path('Nertz_Form_Filter'));
class Nertz_Form_Filter_Simpleselect extends Nertz_Form_Filter 
{
	function filter_get_html()
	{
		$on_change = "";
		if (!empty($this->params['autosubmit']))
		{
			$on_change = "onchange='this.form.submit();'";
		}
		$val = $this->get_value();
		$s  = "<select name='". $this->_get_post_name() . "' id='". $this->_get_post_name() . "' $on_change>";
		if (isset($this->params['values']) && is_array($this->params['values']) && count($this->params['values']))
		{
			foreach ($this->params['values'] as $key => $value)
			{
			    $selected = ($key == $val) ? "SELECTED " : "";
				$s .= "<option {$selected}value='{$key}'>{$value}&nbsp;</option>";
			}
		}
		$s .= "</select>";
		return $s;
	}
	/**
	 * Переопределим для того чтобы если нет значения был выбран первый эллемент
	 *
	 */
	function get_value()
	{
		 if (empty($this->params['value']))
		 {
		 	// Берем дефолтное значение
		 	if (!empty($this->params['default']))
		 	{
		 		return $this->params['default'];
		 	}
		 	// Или индекс первого эллемента в списке
		 	else if (isset($this->params['values']) && is_array($this->params['values']) && count($this->params['values']))
		 	{
		 		foreach ($this->params['values'] as $key => $value)
		 		{
					return $key;
		 		}
		 	}
		 }
		 return $this->params['value'];
	}
}