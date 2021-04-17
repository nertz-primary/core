<?php
include_once(Nertz::class_path('Nertz_Form_Field'));

class Nertz_Form_Field_Simpleselect extends Nertz_Form_Field
{
	function Nertz_Form_Field_Simpleselect($name, &$form)
	{
		parent::Nertz_Form_Field($name, $form);
	}
	function check()
	{
		return true;
	}
	function field_get_form_html()
	{
		$on_change = "";
		if (isset($this->params['visibles']) && is_array($this->params['visibles']) && count($this->params['visibles']))
		{
			$on_change = "onchange='form_select_change(\"" . $this->_get_post_name() . "\"," . $this->form->params['name'] . "_visibles);'";
		}
		$s  = "<select name='". $this->_get_post_name() . "' id='". $this->_get_post_name() . "' $on_change >";
		
		$s .= $this->get_options($this->get_value());
		$s .= "</select>";
		return $s;
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
			$ind =  $row[$this->form->index_name()];
	    	return "<select name='". $this->_get_post_name() . "[{$ind}]'  id='". $this->_get_post_name() . "[$ind]'  onkeyup=\"javascript:ajaxed( '" . $row[$this->form->index_name()]. "', this, '{$url->gen_url($u)}', 'Simpleselect')\" onchange=\"javascript:ajaxed( '" .$ind. "', this, '{$url->gen_url($u)}', 'Simpleselect')\" >" . $this->get_options($row[$this->name]) . '</select>';
	    }
		if (isset($this->params['values']) && is_array($this->params['values']) && isset($row[$this->name]) && isset($this->params['values'][$row[$this->name]])) {
			return $this->params['values'][$row[$this->name]];
		}
		return !empty($row[$this->name]) ? $row[$this->name] : "&nbsp;";
	}
	function get_options($val)
	{
		$s = '';
		if (isset($this->params['values']) && is_array($this->params['values']) && count($this->params['values'])) {
			if (empty($this->params['reqired'])) {
				$selected = ($val) ? "" : "SELECTED ";
				$s .= "<option {$selected} value=''></option>";
			}
			foreach ($this->params['values'] as $key => $capt) {
			    $selected = ($key == $val) ? "SELECTED " : "";
				$s .= "<option {$selected} value='{$key}'>{$capt}</option>";
			}
		}
		return $s;
	}
	// Будем пользовать этот метод для реализации Ajaxed
	function query_values($p)
	{
		global $db, $url;
		$values = array();
		$ind = $url->get_value('i','');
		if (!empty($ind) && !empty($this->params['ajaxed'])) {
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