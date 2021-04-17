<?php
include_once(Nertz::class_path('Nertz_Form_Field_File'));
include_once(Nertz::class_path('Nertz_Store'));
class Nertz_Form_Field_Storeimage extends Nertz_Form_Field_File
{
	function Nertz_Form_Field_Storeimage($name, &$form)
	{
		global $template;
		parent::Nertz_Form_Field_File($name, $form);
		if (!isset($GLOBALS['highslide_init'])) {
			$template->add_css('includes/highslide/highslide.css');
        	$template->add_js('includes/highslide/highslide.js');
        	$GLOBALS['highslide_init'] = true;
		}
		$this->preview_width  = !empty($this->params['preview_width']) ? $this->params['preview_width'] : 30;
		$this->preview_height = !empty($this->params['preview_height']) ? $this->params['preview_height'] : 30;
	}
	function field_get_form_html()
	{
		$s = "";
		$name = $this->_get_post_name();
		$file = isset($_FILE[$name]) ? $_FILE[$name] : array();
		$value = $this->get_value();
		if ($value) {
			$file = Nertz_Store::get($value);
			$s .= "<p style='line-height:" . $this->preview_height . "px; vertical-align: middle;'><a href=\"{$file['url']}\" class=\"highslide\" onclick=\"return hs.expand(this)\"><img src='" . Nertz_Store::gen_image_url($file['ind'], $this->preview_width, $this->preview_height,2, $file['updated']). "' border=0 /></a>&nbsp;<a href=\"{$file['url']}\">{$file['name']}&nbsp;(" . Nertz_File::show_size($file['size']) . ")</a></p>";
		} 
		
		$s .= "<input type='hidden' name='{$this->ind_field_name()}' value='{$value}'>";
		$s .= "<input type='file' name='{$this->_get_post_name()}'>";
		if (empty($this->params['reqired']) && $this->form->get_posted_index()) {
			$s .= "<p class='chb'><label class='checkbox'><input type='checkbox' name='{$this->_get_post_name()}_delete'>Удалить</label></p>";
		}
		return $s;
	}
	function field_get_table_html($row)
	{
		if (!isset($row[$this->name]) || !is_array($row[$this->name])) {
			return "&lt;Нет&gt;";
		}
		
		$s = "<a href=\"{$row[$this->name]['url']}\" class=\"highslide\" onclick=\"return hs.expand(this)\"><img src='" . Nertz_Store::gen_image_url($row[$this->name]['ind'], $this->preview_width, $this->preview_height, 2, $row[$this->name]['updated']). "' border=0 /></a>";
		return $s;
	}
	/**
	 * Обработчик поля после загрузки
	 *
	 * @param Nertz_Form_Field_Storefile $field Экземпляр меня любимого
	 * @param array $rows Значения загруженные из таблицы
	 */
	function after_fetch(&$rows)
	{
		// Соберем все индексы
		$bids = array(); // Индексы для быстрого обратного сопоставления строк
		$inds = array();
		if (is_array($rows) && count($rows)) {
			foreach ($rows as $id => $row) {
				if (!empty($row[$this->name])) {
					$inds[] = $row[$this->name];
				}
			}
			if (count($inds)) {
				$files = Nertz_Store::get($inds);
				$file_inds = array();
				if (is_array($files)) {
					foreach ($files as $id => $file) {
						$file_inds[$file['ind']] = $id;
					}
				}
				foreach ($rows as $id => $row) {
					if (!empty($row[$this->name])) {
						$file_id = $file_inds[$row[$this->name]];
						if(!empty($files[$file_id])) {
							$data = array(
								'ind'      => $files[$file_id]['ind'],
								'name'     => $files[$file_id]['name'],
								'size'     => $files[$file_id]['size'],
								'url'      => $files[$file_id]['url'],
								'updated'  => $files[$file_id]['updated'], 
							);
							$rows[$id][$this->name] = $data;
						} else {
							$rows[$id][$this->name] = '';
						}
					} else {
						$rows[$id][$this->name] = '';
					}
				}
			}
		}
	}
	function check()
	{
		$name = $this->_get_post_name();
		$value = $this->get_value();
		if (!$this->params['reqired'] && empty($_FILES[$name]['name'])) {
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
		if(!empty($this->params['form_caption'])) {
			$ind  = intval($row[$this->name]);
			$name = $this->_get_post_name();
			$file = $_FILES[$name];
			if(!$file['error']) {
				if ($file['name']) {
					if ($type == 'insert' || !$ind) {
						$ind = Nertz_Store::upload($file);
					} else {
						$ind = Nertz_Store::upload($file, $ind);
					}
				}
			}
			// Если стоит галочка удалить, то удалим файл
			if ($type == 'update' && $ind && isset($_REQUEST[$name.'_delete'])) {
				Nertz_Store::delete($ind);
				$ind = 0;
			}
			$row[$this->name] = $ind;
		}
		return true;
	}
	function before_delete($inds)
	{
		global $db;
		if (is_array($inds) && count($inds) && !empty($this->params['form_caption'])) {
			// Нам надо получить индексы удаляемых файлов
			// Пока это кустарный запрос, как сделать более универсально не знаю
			$file_inds = $db->getCol('SELECT ?# FROM ?_?# WHERE ?# IN (?a)', array($this->name, $this->form->params['sql']['table'], $this->form->index_name(), $inds));
			Nertz_Store::delete($file_inds);
		}
		return true;
	}
	function after_save(&$row, $type, $ind) 
	{
		/*if (!empty($this->params['image_sizes']) && is_array($this->params['image_sizes']) && count($this->params['image_sizes'])) {
    		$img = new Nertz_Image();
    		foreach ($this->params['image_sizes'] as $n => $sizes) {
    			print_r($row[$this->name]);
    			// Генерим превьюху
    			$img->load(Nertz_Store::gen_path($row[$this->name], $this->files[$p->name]['name']));
    			$img->resize(PREVIEW_WIDTH, PREVIEW_HEIGHT);
    			$img->save(Nertz_Store::gen_path($row[$p->name], PREVIEW_NAME));	
    		}
    		// Генерим попап
    		$img->load(Nertz_Store::gen_path($row[$p->name], $this->files[$p->name]['name']));
    		$img->resize(POPUP_WIDTH, POPUP_HEIGHT);
    		$img->save(Nertz_Store::gen_path($row[$p->name], POPUP_NAME));
    		$sz = getimagesize(Nertz_Store::gen_path($row[$p->name], PREVIEW_NAME));
    		
    	}*/
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