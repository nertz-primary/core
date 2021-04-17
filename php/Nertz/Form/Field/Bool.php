<?php
include_once(Nertz::class_path('Nertz_Form_Field'));

class Nertz_Form_Field_Bool extends Nertz_Form_Field
{
	function Nertz_Form_Field_Bool($name, &$form)
	{
		parent::Nertz_Form_Field($name, $form);
	}
	function check()
	{
		return true;
	}
	function field_get_form_html()
	{
		$checked = $this->get_value() ? "CHECKED " : "";
		return "<input type='checkbox' name='". $this->_get_post_name() . "' id='". $this->_get_post_name() . "' value='1' {$checked}/>";
	}
	function field_get_table_html($row)
	{
		if (!empty($this->params['ajaxed'])) {
			global $url, $template;
	    	$template->add_js('core/js/JsHttpRequest.js');	    	
			$u = $this->form->params['url'];
			$u['act']    = '_lookup';
			$u['_field'] = $this->name;
			$u['no_amp'] = 1;
	    	$checked = !empty($row[$this->name]) ? "CHECKED " : "";
	    	$ind =  $row[$this->form->index_name()];
	    	return "<input type='checkbox' name='". $this->_get_post_name() . "[{$ind}]'  id='". $this->_get_post_name() . "[{$ind}]'  value=\"1\" {$checked} onchange=\"javascript:ajaxed( '" . $ind. "', this, '{$url->gen_url($u)}')\" />";
	    }
		return !empty($row[$this->name]) ? "ДА" : "";
	}
	function ____get_va1ue()
	{
	 	if($this->params['value'] === 0 || $this->params['value'] === false) {
	 		return 0;
	 	}
	 	if($this->params['value']) {
	 		return 1;
	 	}
	 	if($this->params['default'] === 0 || $this->params['default'] === false) {
	 		return 0;
	 	}
	 	if($this->params['default']) {
	 		return 1;
	 	}
	 
		 return 0;
	}
	// Будем пользовать этот метод для реализации Ajaxed
	function query_values($p)
	{
		global $db, $url;
		$values = array();
		$ind = $url->get_value('i','');
		if (!empty($ind) && !empty($this->params['ajaxed']))
		{
			$params = array(
				&$this,
				array($this->name => $p['s']),
				'update',
				$ind
			);
			if ($this->form->has_event_handler('before_save')) {
				$this->form->call_event_handler('before_save', $params);	
			}
			
			$res = $db->sql_query("UPDATE `?_?#` SET `?#` = ? WHERE `?#` = ?",
			array($this->form->params['sql']['table'], $this->name, $p['s'], $this->form->index_name(), $ind ));
			if ($this->form->has_event_handler('after_save')) {
				$this->form->call_event_handler('after_save', $params);
			}
			return $res;
		}

	}
}