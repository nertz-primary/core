<?php
include_once(Nertz::class_path('Nertz_Form_Field'));

class Nertz_Form_Field_Tel3 extends Nertz_Form_Field
{
	function Nertz_Form_Field_Tel3($name, &$form)
	{
		parent::Nertz_Form_Field($name, $form);
	}
	function check()
	{
		return true;
	}
	function field_get_form_html()
	{
		$value = $this->get_value();
		return "
		+<input type='text' name='{$this->_get_post_name()}[1]' class='tel-con' id='{$this->_get_post_name()}_1'  value='{$value[1]}' maxlength='3' />
		(<input type='text' name='{$this->_get_post_name()}[2]' class='tel-cod' id='{$this->_get_post_name()}_2'  value='{$value[2]}' maxlength='5' />)-
		<input type='text'  name='{$this->_get_post_name()}[3]' class='tel-num' id='{$this->_get_post_name()}_3' value='{$value[3]}' maxlength='10' />
		";
	}
	function field_get_table_html($row)
	{
		return "+{$row[$this->name][1]}({$row[$this->name][2]})-{$row[$this->name][3]}";
	}
	function after_fetch(&$rows)
	{
		$name = $this->name;
		if (is_array($rows)) {
			foreach ($rows as $id => $row) {
				$rows[$id][$name] = array(
					'1' => !empty($row[$name.'_1']) ? $row[$name.'_1'] : '',
					'2' => !empty($row[$name.'_2']) ? $row[$name.'_2'] : '',
					'3' => !empty($row[$name.'_3']) ? $row[$name.'_3'] : ''
				);
			}
		}
	}
	function after_get(&$params)
	{
		$name = $this->name;
		$params[$name] = array(
			'1' => !empty($params[$name.'_1']) ? $params[$name.'_1'] : '',
			'2' => !empty($params[$name.'_2']) ? $params[$name.'_2'] : '',
			'3' => !empty($params[$name.'_3']) ? $params[$name.'_3'] : ''
		);
	}
	function before_save(&$row, $type, $ind)
	{
		$row[$this->name.'_1'] = !empty($row[$this->name][1]) ? $row[$this->name][1] : '';
		$row[$this->name.'_2'] = !empty($row[$this->name][2]) ? $row[$this->name][2] : '';
		$row[$this->name.'_3'] = !empty($row[$this->name][3]) ? $row[$this->name][3] : '';
		unset($row[$this->name]);
		print_r($row);
		return true;
	}

}