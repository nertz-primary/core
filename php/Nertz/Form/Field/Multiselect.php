<?php
include_once(Nertz::class_path('Nertz_Form_Field'));

class Nertz_Form_Field_Multiselect extends Nertz_Form_Field
{
	function Nertz_Form_Field_Multiselect($name, &$form)
	{
		parent::Nertz_Form_Field($name, $form);
	}
	function check()
	{
		return true;
	}
	function field_get_form_html()
	{
		$s = '';
		if (isset($this->params['values']) && is_array($this->params['values']) && count($this->params['values'])) {
			$has_value  = isset($this->params['value']) && is_array($this->params['value']) && count($this->params['value']);
			$cols = !empty($this->params['cols']) ? intval($this->params['cols']) : 1;
			if($cols<1) {
				$cols = 1;
			}
			// В одну колонку
			if($cols == 1) {
				$s = "<div class='multiselect'>";
				foreach ($this->params['values'] as $key => $value) {
					$s .= $this->get_form_item($has_value, $key, $value);
				}
				$s .= "</div>";
			// Вексколько колонок
			} else {
				$table = kvadratura($this->params['values'], $cols);
				$s  = "<table class='multiselect'>";
					foreach ($table as $row) {
						$s .= "<tr>";
							foreach ($row as $key => $value) {
									$s .= "<td>";
										if($value) {
											$s .= $this->get_form_item($has_value, $key, $value);
										} else {
											$s .= "&nbsp;";
										}
									$s .= "</td>";
  							}
						$s .= "</tr>";
					}
				$s .= "</table>";
				
			}
		}
		return $s;
	}
	function get_form_item($has_value, $key, $value)
	{
		$checked = "";
		if ($has_value && in_array($key, $this->params['value']) ) {
	    	$checked = " CHECKED ";
	    }
	    $s  = "<label class='checkbox'>";
	    $s .= "<input type='checkbox' name='". $this->_get_post_name() . "[$key]' $checked /> {$value}<br/>";
	    $s .= "</label>";
	    return  $s;
	}
	function field_get_table_html($row)
	{
	    if (is_array($row[$this->name]) && count($row[$this->name])) {
	    	$data = array();
	    	foreach ($row[$this->name] as $ind) {
	    		if(!empty($this->params['values'][$ind])) {
	    			$data[] = $this->params['values'][$ind];
	    		}
	    		
	    	}
	    	return implode(', ', $data);
	    }
	    return "";
	    
	}
	function load_posted_value()
	{
	    $default = '';
	    if (isset($this->params['default']))
	    {
	        $default = $this->params['default'];
	    }
	    global $url;
	    $value = $url->get_value($this->_get_post_name("", true), $default);
	    $res = array();
	    if (is_array($value) && isset($this->params['values']) && is_array($this->params['values']) && count($this->params['values'])) 
	    {
	    	foreach ($this->params['values'] as $id => $val)
	    	{
	    		if (isset($value[$id]))
	    		{
	    			$res[] = $id;
	    		}
	    	}
	    }
	    $this->set_value($res);
	}
}