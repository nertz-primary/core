<?php
include_once(Nertz::class_path('Nertz_Form_Field'));

class Nertz_Form_Field_Checkbox extends Nertz_Form_Field
{
	function Nertz_Form_Field_Checkbox($name, &$form)
	{
		parent::Nertz_Form_Field($name, $form);
	}
	function field_get_table_html($row)
	{
	    global $url;
	    return "<input type=\"checkbox\" name=\"" . $this->_get_post_name() . "[]\" value=\"{$row[$this->form->index_name()]}\"/>";
	}
}