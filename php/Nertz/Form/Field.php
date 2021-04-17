<?php
/**
 * Базовый класс поля от которого будут наследоваться все оставльные поля
 *
 */
class Nertz_Form_Field
{
	/**
	 * Форма
	 * @var Nertz_Form
	 */
	var $form;
	function Nertz_Form_Field($name, &$form)
	{
		$this->name   = $name;
		$this->form   = $form;
		$this->params = & $form->params['fields'][$name];
		$this->params['error_status'] = '';
		if (isset($this->params['on_create']) && $this->form->has_event_handler($this->params['on_create'])) {
	        return $this->form->call_event_handler($this->params['on_create'], array(&$this));
	    }
	    
	}
	/**
	 * Получить HTML код для отображеия в таблице
	 * @access Не переопределять
	 * @see field_get_table_html()
	 *
	 * @param array $row Массив содержащий данные строки таблицы
	 * @return HTML
	 */
	function get_table_html($row)
	{
	    if (isset($this->params['on_table_show']) && $this->form->has_event_handler($this->params['on_table_show'])) {
	        $ret = $this->form->call_event_handler($this->params['on_table_show'], array(&$this, &$row));
	    	if ($ret !== true) {
	    		return $ret;	
	    	}
	    }
	    return $this->field_get_table_html($row);
	}
	/**
	 * Пользовательский обработчик получения HTML кода для отображения в таблице
	 * @abstract Необходимо переопределять, иначе выводится всякая шняга
	 *
	 * @param array $row Массив содержащий данные строки таблицы
	 * @return HTML
	 */
	function field_get_table_html($row)
	{
	    return "<НЕ ОПР.>";
	}
	/**
	 * Получить HTML код поля формы
	 * @access Не переопределять
	 * @see field_get_form_html()
	 *
	 * @return HTML
	 */
	function get_form_html()
	{
	    if (isset($this->params['on_form_show']) && $this->form->has_event_handler($this->params['on_form_show'])) {
	        $ret = $this->form->call_event_handler($this->params['on_form_show'], array(&$this));
	        if ($ret !== true) {
	    		return $ret;	
	    	}
	    }
	    return $this->field_get_form_html();
	}
	/**
	 * Пользовательский обработчик получения HTML кода поля формы
	 * @abstract Необходимо переопределять, иначе выводится всякая шняга
	 *
	 * @return HTML
	 */
	function field_get_form_html()
	{
		return "Не определенный вывод поля.";
	}
	/**
	 * Получить имя поля для POST
	 * @internal  Для внутреннего использования
	 * @see Nertz_Url::gen_post_name()
	 *
	 * @param string $name Имя поля
	 * @param bool $no_gen_post_name Если TRUE, то не использовать $url->gen_post_name
	 * @return string
	 */
	function _get_post_name($name="", $no_gen_post_name = false)
	{
		if (!$name)	{
			$name = $this->name;
		}
		return $this->form->get_post_name($name, $no_gen_post_name);
	}
	/**
	 * Функция получения значения из $_POST
	 * @abstract При необходимости можно переопределять
	 */
	function load_posted_value()
	{
		// Думаю если ничего не пришло, то не надо ругани а просто выведем пустую строку
	    $default = '';
	    // Если есть дефолное значение в свойствах поля, то будем иметь его ввиду
	    if (isset($this->params['default'])) {
	        $default = $this->params['default'];
	    }
	    global $url;
	    $this->set_value($url->get_value($this->_get_post_name("", true), $default));
	}
	/**
	 * Установить значение извне Forms, например, из $_POST
	 * @abstract При необходимости можно переопределять
	 *
	 * @param mixed $value Значение
	 */
	function set_value($value)
	{
	    $this->params['value'] = $value;
	}
	/**
	 * Получить значение для использования вовне, например за пределами
	 * @abstract При необходимости можно переопределять
	 *
	 * @return mixed значение пригодное для обработки вне Forms
	 */
	function get_value()
	{
	    return isset($this->params['value']) ? $this->params['value'] : "";
	}
	/**
	 * Проверка значения поля на правильность заполнения
	 *
	 * @return Возращает TRUE если все нормально, иначе строка содержащая текст ошибки
	 */
	function check()
	{
	    if (isset($this->params['reqired']) && $this->params['reqired']) {
	        if (empty($this->params['value'])) {
	            return "Это поле должно быть заполнено";
	        }
	    }
	    return true;
	}
	/**
	 * Получить значение для использования в БД
	 * @abstract При необходимости можно переопределять
	 *
	 * @return mixed значение пригодное для сохранения в БД
	 */
	function get_db_value()
	{
		return $this->get_value();
	}
	/**
	 * Установить значение полученное из БД
	 * @abstract При необходимости можно переопределять
	 *
	 * @param mixed $value Значение
	 */
	function set_db_value($value)
	{
		return $this->set_value($value);
	}
	function is_visible()
	{
		if ($this->params['visible'] === false) {
			return false;
		}
		return true;
		if ($this->params['visible'] === null) {
			return true;
		}
		if ($this->params['visible'] === true) {
			return true;
		}
		return false;
	}
	/**
	 * Обработчик вызываемый после сохранения записи в БД
	 * @example Сохранениие поля File
	 * Надпомним что этот обработчик вызывает Nertz_Item_Form и пока никто другой
	 *
	 * @param array $row Сохрняняемая в БД строка
	 * @param strint $type ='insert' для добавления новой записи, ='update' для изменения существующей
	 * @param int $ind Индекс обновляемой записи
	 * @return bool Продолжать ли вызов остальных обработчиков
	 */

	/*function after_save(&$row, $type, $ind)
	{
		return true;
	}*/

	/**
	 * Обработчик вызываемый сохранением записи в БД
	 * @example Сохранениие поля File
	 * Надпомним что этот обработчик вызывает Nertz_Item_Form и пока никто другой

	 * @param array $row Сохрняняемая в БД строка
	 * @param strint $type ='insert' для добавления новой записи, ='update' для изменения существующей
	 * @param int $ind Индекс обновляемой записи
	 * @return bool Продолжать ли вызов остальных обработчиков
	 */
	/*function before_save(&$row,$type, $ind)
	{
		return true;
	}
	*/
}