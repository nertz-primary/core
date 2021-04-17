<?php
include_once(Nertz::class_path('Nertz_Form_Field'));

class Nertz_Form_Field_Caption extends Nertz_Form_Field
{
	function Nertz_Form_Field_Caption($name, &$form)
	{
		parent::Nertz_Form_Field($name, $form);
	}
	function field_get_form_html()
	{
		$this->form->params['fields'][$this->name]['whole_line'] = true;
		return "<legend>" . $this->params['form_caption'] . "</legend>";
	}
	
	function load_posted_value()
	{
		
	}
	function get_value()
	{
		return null;
	}

}