<?php
include_once(Nertz::class_path('Nertz_Form_Field_File'));
include_once(Nertz::class_path('Nertz_Store'));
class Nertz_Form_Field_Storefile extends Nertz_Form_Field_File
{
	function Nertz_Form_Field_Storefile($name, &$form)
	{
		parent::Nertz_Form_Field_File($name, $form);
	}
	function field_get_form_html()
	{
		$s = "";
		$name = $this->_get_post_name();
		$file = isset($_FILE[$name]) ? $_FILE[$name] : array();
		$value = $this->get_value();
		if ($value) {
			$file = Nertz_Store::get($value);
			$s .= "<a href=\"{$file['url']}\">{$file['name']}&nbsp;(" . Nertz_File::show_size($file['size']) . ")</a><br/>";
		} 
		
		$s .= "<input type='hidden' name='{$this->ind_field_name()}' value='{$value}'>";
		$s .= "<input type='file' name='{$this->_get_post_name()}'>";
		return $s;
	}
	function field_get_table_html($row)
	{
		if (!isset($row[$this->name]) || !is_array($row[$this->name])) {
			return "&lt;Нет&gt;";
		}
		$s = "<a href=\"{$row[$this->name]['url']}\">{$row[$this->name]['name']}&nbsp;(" . Nertz_File::show_size($row[$this->name]['size']) . ")</a>";
		return $s;
	}
	/**
	 * Обработчик поля псоле загрузки
	 *
	 * @param Nertz_Form_Field_Storefile $field Экземпляр меня любимого
	 * @param array $rows Значения загруженные из таблицы
	 */
	function after_fetch(&$rows)
	{
		// Соберем все индексы
		$bids = array(); // Индексы для быстрого обратного сопоставления строк
		$inds = array();
		if (is_array($rows)) {
			foreach ($rows as $id => $row) {
				$bids[$row[$this->name]] = $id;
				$inds[] = $row[$this->name];
				$rows[$id][$this->name] = "";
			}
			if (count($inds)) {
				$files = Nertz_Store::get($inds);
				if (is_array($files)) {
					foreach ($files as $file) {
						$rows[$bids[$file['ind']]][$this->name] = array(
						'ind'  => $file['ind'],
						'name' => $file['name'],
						'size' => $file['size'],
						'url'  => $file['url'],
						);
					}
				}
			}
		}
	}
	function check()
	{
		$name = $this->_get_post_name();
		$value = $this->get_value();
		if (!$this->params['reqired'] && !$_FILES[$name]['name']) {
			return true;
		} else if (!isset($_FILES[$name]) && $this->params['reqired'] && empty($_FILES[$name]['name']) && !$value) {
			return "Файл  не загружен!";
		} elseif ($_FILES[$name]['error'] ==  UPLOAD_ERR_NO_FILE && $this->params['reqired']  && empty($_FILES[$name]['name'])&& !$value) {
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
			if (empty($pi['extension']) && $value) {
				return true;
			}
			if (array_search(strtolower($pi['extension']), $this->params['extensions']) === false) {
				return "Допустимы только файлы с расширениями " . implode(', ', $this->params['extensions']);
			}
		}
	    return true;
	}
	function before_save(&$row, $type, $ind)
	{
		$ind  = intval($row[$this->name]);
		$name = $this->_get_post_name();
		$file = $_FILES[$name];
		if ($file['name']) {
			if ($type == 'insert' || !$ind) {
				$ind = Nertz_Store::upload($file);
			} else {
				$ind = Nertz_Store::upload($file, $ind);
			}
		}
		$row[$this->name] = $ind;
		return true;
	}
	function before_delete($inds)
	{
		Nertz_Store::delete($inds);
		return true;
	}
	function ind_field_name()
	{
		return $this->_get_post_name(). '__ind';
	}
	function load_posted_value()
	{
	    if (isset($_REQUEST[$this->ind_field_name()])) {
    			$this->set_value($_REQUEST[$this->ind_field_name()]);
	    }
	}
}