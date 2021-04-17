<?php
include_once(Nertz::class_path('Nertz_Form_Field'));

class Nertz_Form_Field_Int extends Nertz_Form_Field
{
	function Nertz_Form_Field_Int($name, &$form)
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
		if (!empty($this->params['ajaxed'])) {
			global $url, $template;
	    	$template->add_js('core/js/JsHttpRequest.js');	    	
			$u = $this->form->params['url'];
			$u['act']    = '_lookup';
			$u['_field'] = $this->name;
			$u['no_amp'] = 1;
			$ind =  $row[$this->form->index_name()];
	    	$size     = (isset($this->params['length'])) ? "size='" . $this->params['length'] . "'" : "size='12'";
	    	return "<input type='text' $size name='". $this->_get_post_name() . "[{$ind}]'  id='". $this->_get_post_name() . "[$ind]'  value=\"" . $row[$this->name] . "\" onkeyup=\"javascript:ajaxed( '" . $row[$this->form->index_name()]. "', this, '{$url->gen_url($u)}', 'Int')\" onchange=\"javascript:ajaxed( '" .$ind. "', this, '{$url->gen_url($u)}', 'Int')\" autocomplete='off' />";
	    }
	    if (!empty($row[$this->name])) {
	    	return intval($row[$this->name]);
	    } else {
	    	return "&nbsp;";
	    }
	}
	function check()
	{
		if (($res = parent::check()) !== true) {
			return $res;
		}
		
		if (!empty($this->params['value']) && (!is_numeric($this->params['value']) || intval($this->params['value']) != $this->params['value'])) {
			return "Это поле должно содержать простое число";
		}
		return true;
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