<?php
include_once(Nertz::class_path('Nertz_Form_Field'));

class Nertz_Form_Field_Float extends Nertz_Form_Field
{
	function Nertz_Form_Field_Float($name, &$form)
	{
		parent::Nertz_Form_Field($name, $form);
	}
	function field_get_form_html()
	{
		$size = (isset($this->params['length'])) ? "size='" . $this->params['length'] . "'" : "";
		$readonly = (isset($this->params['read_only']) && $this->params['read_only']) ? " readonly " : "";
		return "<input type='text' $size name='". $this->_get_post_name() . "' id='". $this->_get_post_name() . "' value='{$this->get_value()}' $readonly />";
	}
	function field_get_table_html($row)
	{
	    return $row[$this->name];
	}
	function load_posted_value()
	{
	    $value = parent::load_posted_value();
	    $this->set_value(str_replace(",", ".", $this->get_value()));
	}
	function check()
	{
		if (($res = parent::check()) !== true) {
			return $res;
		}
		if (!is_numeric($this->params['value']) ) {
			return "Это поле должно содержать число с точкой";
		}
		return true;
	}
}