<?php
include_once(Nertz::class_path('Nertz_Form_Field'));

class Nertz_Form_Field_Hidden extends Nertz_Form_Field
{
	function Nertz_Form_Field_Hidden($name, &$form)
	{
		parent::Nertz_Form_Field($name, $form);
	}
	function field_get_form_html()
	{
		return "";
	}
	function field_get_table_html($row)
	{
	    return $row[$this->name];
	}
	function check()
	{
		return true;
	}
}