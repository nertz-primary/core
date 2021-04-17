<?php
include_once(Nertz::class_path('Nertz_Form_Field'));
include_once(Nertz::class_path('Nertz_Date'));
class Nertz_Form_Field_Date extends Nertz_Form_Field
{
	function Nertz_Form_Field_Date($name, &$form)
	{
		parent::Nertz_Form_Field($name, $form);
	}
	function field_get_form_html()
	{
		global $template, $url;
		$size     = (isset($this->params['length'])) ? "size='" . $this->params['length'] . "'" : "";
		$readonly = (isset($this->params['read_only']) && $this->params['read_only']) ? " readonly " : "";
		$init = "";
		$value = Nertz_Form_Field_Date::timestamp2calendar($this->get_value(), !empty($this->params['mysql_format']));
		// Поступили пожелания в датах без календеря, так можем реализовать.
		if (!empty($this->params['combo_view'])) {
			list($day, $month, $year) = sscanf($value, "%d-%d-%d");
			$s = '';
			$s .= '<select name="'. $this->_get_post_name() . '[day]" ' . $readonly. ' id="'. $this->_get_post_name() . '_day">';
			$s .= '<option value="0">День&nbsp;</option>';
			for ($i = 1; $i <= 31; $i++) {
				$selected = $day == $i ? 'SELECTED' : '';
				$s .= '<option value="' . $i . '" ' . $selected . '>' . $i . '&nbsp;</option>';
			}
			$s .= '</select>&nbsp;<select name="'. $this->_get_post_name() . '[month]" id="'. $this->_get_post_name() . '_month">';
			$s .= '<option value="0">Месяц&nbsp;</option>';
			$monthes = Nertz_Date::get_monthes();
			foreach ($monthes as $i => $n) {
				$selected = $month == $i ? 'SELECTED' : '';
				$s .= '<option value="' . $i . '" ' . $selected . '>' . $n . '&nbsp;</option>';
			}
			$s .= '</select>&nbsp;<select name="'. $this->_get_post_name() . '[year]" ' . $readonly. ' id="'. $this->_get_post_name() . '_year">';
			$s .= '<option value="0">Год&nbsp;</option>';
			for ($i = 1930; $i <= date('Y', time()); $i++) {
				$selected = $year == $i ? 'SELECTED' : '';
				$s .= '<option value="' . $i . '" ' . $selected . '>' . $i . '&nbsp;</option>';
			}
			$s .= '</select>';
			return $s;
		}
		// Календарь кривой поэтому приходится подключать JS именно так
		if (!isset($GLOBALS['calendar_init'])) {
			$init = '<script language="JavaScript" src="' . $url->gen_static_url('/includes/calendar/calendar.js'). '"></script>';
			$GLOBALS['calendar_init'] = 1;
		}
		$s = '';
		if (!isset($this->params['no_calendar']) && empty($readonly)) {
			$s = "onfocus=\"this.select();lcs(this);\" onclick=\"event.cancelBubble=true;this.select();lcs(this,'');\"";	
		}
		return $init . "<input type='text' $size name='". $this->_get_post_name() . "'  id='". $this->_get_post_name() . "'  value='" . $value . "' $readonly $s />";
	}
	function field_get_table_html($row)
	{
	    //return date("d-m-Y",$row[$this->name]);
	    return $this->timestamp2calendar($row[$this->name], !empty($this->params['mysql_format']));
	}
	function timestamp2calendar($time, $mysql_format=false)
	{
		if(!$time) {
			if ($mysql_format) {
				$time = date("Y-m-d");
			} else {
				$time = time();
			}
		}
		if ($mysql_format) {
			list($year, $month, $day) = sscanf($time, "%d-%d-%d");
			return $day . "-" . $month . "-" . $year;
		}
		return date("d-m-Y", $time);
	}
	function calendar2timestamp($time, $mysql_format)
	{
		list($day, $month, $year) = sscanf($time, "%d-%d-%d");
		if( !is_int($day) || !is_int($month) || !is_int($year) ) {
			return  null;
		}
		if ($mysql_format) {
			return $year . "-" . $month . "-" . $day;
		}
		return mktime( 0, 0, 0, $month, $day, $year );
	}
	function load_posted_value()
	{
	    parent::load_posted_value();
	    $value =  $this->get_value();
	    if (!empty($this->params['combo_view']) && is_array($value)) {
	    	$value = $value['day'] . '-' . $value['month'] . '-' . $value['year'];
	    }
	    $this->set_value($this->calendar2timestamp($value, !empty($this->params['mysql_format'])));
	}
	function get_value()
	{
		$value = parent::get_value();
		
		if (!$value && !empty($this->params['default'])) {
			$value = $this->params['default'];
		}
		return $value;
	}
	function check()
	{
		if (!empty($this->params['combo_view'])) {
			if (isset($this->params['reqired']) && $this->params['reqired']) {
				$val = $this->timestamp2calendar($this->params['value'], !empty($this->params['mysql_format']));
				$arr = explode('-', $val);
				if (!$arr[0] || !$arr[1] || !$arr[2]) {
		            return "Это поле должно быть заполнено";
		        }
		    }	
	    return true;
		}
		return parent::check();
	}

}