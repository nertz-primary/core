<?php
include_once(Nertz::class_path('Nertz_Form_Field'));

class Nertz_Form_Field_Password extends Nertz_Form_Field
{
	function Nertz_Form_Field_Password($name, &$form)
	{
		parent::Nertz_Form_Field($name, $form);
	}
	function field_get_form_html()
	{
		$size     = (isset($this->params['length'])) ? "size='" . $this->params['length'] . "'" : "";
		$readonly = (isset($this->params['read_only']) && $this->params['read_only']) ? " readonly " : "";
		return "<input type='password' $size name='". $this->_get_post_name() . "'  id='". $this->_get_post_name() . "'  value='{$this->get_value()}' $readonly />";
	}
	function field_get_table_html($row)
	{
	    return $row[$this->name];
	}
}