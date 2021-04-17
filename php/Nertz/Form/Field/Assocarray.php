<?php
include_once(Nertz::class_path('Nertz_Form_Field'));

class Nertz_Form_Field_Assocarray extends Nertz_Form_Field
{
	function Nertz_Form_Field_Assocarray($name, &$form)
	{
		parent::Nertz_Form_Field($name, $form);
		$this->use_key = false;
		if (!empty($this->params['use_key']) && $this->params['use_key']) {
			$this->use_key = true;
		}
	}
	function field_get_form_html()
	{
		global $url;
		$s = "<div style='display:inline-block;'>";
		$s .= "<table class='form_table' cellspacing='0' cellpadding='0'><tbody id='" . $this->_get_post_name() . "'>";
		$s .=  "<tr>";
		if ($this->use_key) {
			$s .=  "<th>" . ( !empty($this->params['key_caption']) ? $this->params['key_caption'] : "Ключ" ) . "</th>";
		}
		if(!empty($this->params['value_caption']) && is_array($this->params['value_caption']) && count($this->params['value_caption'])) {
			foreach ($this->params['value_caption'] as $cpt) {
				$s .=  "<th>" . $cpt . "</th>";
			}
			$s .=  "<th>*</th></tr>";
		} else {
			$s .=  "<th>" . ( !empty($this->params['value_caption']) ? $this->params['value_caption'] : "Значение" ) . "</th><th>*</th></tr>";			
		}
		
		$s .=  "<tr id='" . $this->_get_post_name() . "_rowedit'>";
		if ($this->use_key) {
			$fn = $this->_get_post_name() . "_edit_key";
			$s .=  "<td><input id='{$fn}' name='{$fn}' size='5'/></td>";
		}
		if(!empty($this->params['value_caption']) && is_array($this->params['value_caption']) && count($this->params['value_caption'])) {
			foreach ($this->params['value_caption'] as $i => $cpt) {
				$fn = $this->_get_post_name() . "_edit_value[" . $i . "]";
				$s .=  "<td><input id='{$fn}' name='{$fn}' size='20'/></td>";	
			}
		} else {
			$fn = $this->_get_post_name() . '_edit_value';
			$s .=  "<td><input id='{$fn}' name='{$fn}' size='20'/></td>";	
		}
		$s .=  "<td><img src='". $url->gen_static_url('core/img/button/add.gif'). "' onclick=\"multiselect_add_new('" . $this->_get_post_name() . "')\"/></td>
			</tr>";
		$s .= "</tbody></table>";
		$s .= "</div>";
		if (!empty($this->params['value']) && is_array($this->params['value']) && count($this->params['value'])) {
			$s .= "<script type='text/javascript'>";
			foreach ($this->params['value'] as $id => $value) {
				$s .= "multiselect_add_row('" . $this->_get_post_name() . "', ";
				if(!empty($this->params['value_caption']) && is_array($this->params['value_caption']) && count($this->params['value_caption'])) {
					$arr = array();
					foreach ($this->params['value_caption'] as $k => $v) {
						$arr[$k] = $value[$k];
					}
					$s .= Nertz::json($arr);
				} else {
					$s .= "'" . $value . "'";
				}
				if ($this->use_key) {
						$s .= ", '" . $id . "'";
				}
				$s .= ");";
			}
			if(!empty($this->params['value_caption']) && is_array($this->params['value_caption']) && count($this->params['value_caption'])) {
				$s .= "var {$this->_get_post_name()}_names = " . Nertz::json(array_keys($this->params['value_caption']));
			}
			$s .= "</script>";
		}
		// Введем имена полей
		$s .= "<script type='text/javascript'>";
		if(!empty($this->params['value_caption']) && is_array($this->params['value_caption']) && count($this->params['value_caption'])) {
			$s .= "var {$this->_get_post_name()}_names = " . Nertz::json(array_keys($this->params['value_caption']));
		} else {
			$s .= "var {$this->_get_post_name()}_names = ''";
		}	
		$s .= "</script>";
		
		
		return $s;
	}
	function field_get_table_html($row)
	{
		$value = $row[$this->name];
		$s = "";
		$xxx = "";
		if (is_array($value) && count($value)) {
			foreach ($value as $id => $str) {
				$s .= $xxx;
				if ($this->use_key) {
					$s .= '[' . $id . ']';
				}
				$s .= $str;
				$xxx = ", ";
			}
		}
		return $s;
	}
	function load_posted_value()
	{
		// Думаю если ничего не пришло, то не надо ругани а просто выведем пустую строку
	    $default = array();
	    // Если есть дефолное значение в свойствах поля, то будем иметь его ввиду
	    if (isset($this->params['default'])) {
	        $default = $this->params['default'];
	    }
	    global $url;
	    $name = $this->_get_post_name("", true);
	    $keys = $url->get_value($name . '_key', array());
	    $values = $url->get_value($name . '_value', array());
	    $res = array();
	   	if (is_array($values) && count($values)) {
	   		foreach ($values as $ind => $value) {
	   			if ($this->use_key) {
	   				$res[$keys[$ind]] = $value;
	   			} else {
	   				$res[] = $value;
	   			}
	   		}
	   	} 
	   	// Добавим значения из поля ввода
	   	$edit_key = $url->get_value($name . '_edit_key', array());
	    $edit_value = $url->get_value($name . '_edit_value', array());
	    if ($edit_value) {
	    		if ($this->use_key) {
	   				$res[$edit_key] = $edit_value;
	   			} else {
	   				$res[] = $edit_value;
	   			}
	    }
	   	if (!count($res)) {
	   		$res = $default;
	   	}
	   $this->set_value($res);
	}
}
?>
