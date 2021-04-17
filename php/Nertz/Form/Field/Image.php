<?php
include_once(Nertz::class_path('Nertz_Form_Field'));

class Nertz_Form_Field_Image extends Nertz_Form_Field
{
	function Nertz_Form_Field_Image($name, &$form)
	{
		parent::Nertz_Form_Field($name, $form);
	}
	function field_get_form_html()
	{
		$s = "";
		$s .= "<input type='file' name='{$this->_get_post_name()}'>";
		return $s;
	}
	function field_get_table_html($row)
	{
	    return $row[$this->name];
	}
	function check()
	{
		$name = $this->_get_post_name();
		if (!isset($_FILES[$name]) && $this->params['reqired']) {
			return "Файл  не загружен!";
		} elseif ($_FILES[$name]['error'] ==  UPLOAD_ERR_NO_FILE && $this->params['reqired']) {
			return "Файл не выбран";
		} elseif($_FILES[$name]['error'] ==  UPLOAD_ERR_INI_SIZE ) {
			return "PHP ограничиывет размер файла";
		} elseif($_FILES[$name]['error'] ==  UPLOAD_ERR_FORM_SIZE ) {
			return "Размер файла больше разрешенного";
		} elseif($_FILES[$name]['error'] ==  UPLOAD_ERR_PARTIAL ) {
			return "Файл загруженн частично";
		} elseif($_FILES[$name]['error'] ==  UPLOAD_ERR_NO_TMP_DIR ) {
			return "Не найденн временная дирретория";
		} elseif($_FILES[$name]['error'] ==  UPLOAD_ERR_CANT_WRITE ) {
			return "Не могу записать файл";
		}
		if (isset($this->params['extensions']) && is_array($this->params['extensions']) && count($this->params['extensions'])) {
			$pi = pathinfo($_FILES[$name]['name']);
			if (array_search(strtolower($pi['extension']), $this->params['extensions']) === false) {
				return "Допустимы только файлы с расширениями " . implode(', ', $this->params['extensions']);
			}
		}
	    return true;
	}
	function load_posted_value()
	{
	    $this->set_value($_FILES[$this->_get_post_name()]);
	}
}