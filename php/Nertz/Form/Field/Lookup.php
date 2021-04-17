<?php
include_once(Nertz::class_path('Nertz_Form_Field'));

class Nertz_Form_Field_Lookup extends Nertz_Form_Field
{
	function Nertz_Form_Field_Lookup($name, &$form)
	{
		parent::Nertz_Form_Field($name, $form);
	}
	function field_get_form_html()
	{
		global $url, $template;
		$u = $this->form->params['url'];
		$u['act']    = '_lookup';
		$u['_field'] = $this->name;
		$template->add_js('core/js/JsHttpRequest.js');
		$value = $this->get_value();
		$size = (isset($this->params['length'])) ? "size='" . $this->params['length'] . "'" : "";
		$capt_value =  $this->query_values(array('i'=>$value));
		$s = "<input type='hidden' name='". $this->_get_post_name() . "'  id='". $this->_get_post_name() . "'  value='{$value}' />";
		$s .= "<input type='text' $size name='". $this->_get_post_name() . "_lookup'  id='". $this->_get_post_name() . "_lookup'  value='{$capt_value}' onblur='hide_lookup();' onkeydown='return lookup_key(event);' onkeypress='return key_killer(event);' autocomplete='off' onfocus='return lookup_focus(\"{$this->_get_post_name()}\", \"{$url->gen_url($u)}\");' class='Lookup'/>";
		$s .= "<div class='clear'></div><div class='lpopup' id='". $this->_get_post_name() . "_popup'></div>";
		return $s;
	}
	function field_get_table_html($row)
	{
	    return $row[$this->name];
	}
	function load_posted_value()
	{
		global $url;

	    $default = '';
	    if (isset($this->params['default'])) {
	        $default = $this->params['default'];
	    }
	    $this->set_value($default);
		
		$ind = $url->get_value($this->_get_post_name(), "");
		$s = $url->get_value($this->_get_post_name() . "_lookup", "");
		if (!$ind && $s) {
			$vals = $this->query_values(array('s' => $s));
			if (count($vals)) {
				$this->set_value(array_first_key($vals));
			}
		} else if($ind) {
			 $this->set_value($ind);
		}
	}
	function query_values($p)
	{
		$values = array();
		// Кастомный обработчик
		if (!empty($this->params['on_query']))
		{
			if (!is_callable($this->params['on_query']))
			{
				Nertz::log("Не могу обнаружить функцию \"{$this->params['on_query']}\" определенную как on_query для поля {$this->name}", 'error', 0);
				return $values;
			}
			$values = call_user_func_array($this->params['on_query'], array(&$this, $p));
		}
		// Автоматический обработчик
		if (!empty($this->params["query_table"]) && !empty($this->params["query_ind"]) && !empty($this->params["query_text"]))
		{
			global $db;
			$table = $this->params["query_table"];
			$ind   = $this->params["query_ind"];
			$text  = $this->params["query_text"];
			if (isset($p['i']))
			{
				if ($ind == $text)
				{
					return $p['i'];
				}
				return $db->getOne("SELECT `{$text}` FROM `?_{$table}` WHERE `{$ind}` = ? LIMIT 1", array($p['i']));
			}
			if (isset($p['s']))
			{
				return $db->getAssoc("SELECT `{$ind}` ind, `{$text}` txt FROM `?_{$table}` WHERE `{$text}` LIKE '" . $db->quote($p['s']) . "%' ORDER BY txt LIMIT 5");
			}
			return "";
		}
		return $values;
	}
}