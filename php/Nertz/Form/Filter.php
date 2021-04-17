<?php
/**
 * Базовый класс фильтра таблицы класса Nertz_Form
 *
 */
class Nertz_Form_Filter
{
	/**
	 * Форма
	 * @var Nertz_Form
	 */
	var $form;
	function Nertz_Form_Filter($name, &$form)
	{
		$this->name   = $name;
		$this->form   = $form;
		$this->params = $form->params['filters'][$name];
		if (isset($this->params['on_create']) && $this->form->has_event_handler($this->params['on_create'])) {
	        return $this->form->call_event_handler($this->params['on_create'], array(&$this));
	    }
	}
	/**
	 * Получение значения фильтра
	 *
	 */
	function get_value()
	{
		return $this->params['value'];
	}
	/**
	 * Запись значения фильтра
	 *
	 * @param mixed $value Значение фильтра
	 */
	function set_value($value)
	{
		$this->params['value'] = $value;
		global $session;
		$session->set_value($this->get_session_name(), $value);
	}
	/**
	 * Получить HTML код поля формы
	 * @access Не переопределять
	 * @see field_get_form_html()
	 *
	 * @return HTML
	 */
	function get_html()
	{
		if (!empty($this->params['on_show']) && $this->form->has_event_handler($this->params['on_show'])) {
			return $this->form->call_event_handler($this->params['on_show'],array(&$this));
		}
		return $this->filter_get_html();
	}
	/**
	 * Пользовательский обработчик получения HTML кода для отображения в таблице
	 * @abstract Необходимо переопределять, иначе выводится всякая шняга
	 *
	 * @return HTML
	 */
	function filter_get_html()
	{
	    return "<НЕ ОПР.>";
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
	    // Загрузим из сессии ссохраненное ранее значение
	    global $session;
	    $default = $session->get_value($this->get_session_name(), $default);
	    global $url;
	    $this->set_value($url->get_value($this->_get_post_name("", true), $default));
	}

	/**
	 * Получить имя поля для POST
	 * @internal  Для внутреннего использования
	 * @see Nertz_Url::gen_post_name()
	 *
	 * @param string $name Имя фильтра
	 * @param bool $no_gen_post_name Если TRUE, то не использовать $url->gen_post_name
	 * @return string
	 */
	function _get_post_name($name="", $no_gen_post_name = false)
	{
		if (!$name) {
			$name = $this->name;
		}
		$name = 'filter_' . $name;
		return $this->form->get_post_name($name, $no_gen_post_name);
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
	function get_session_name()
	{
		global $url;
		return 'filter_' . $url->get_page() . '_' .  $this->form->params['name'] . '_' . $this->name;
	}
	function reset()
	{
		unset($_REQUEST[$this->_get_post_name("", true)]);
		unset($_POST[$this->_get_post_name("", true)]);
		unset($this->params['value']);
		global $session;
	    $default = '';
	    if (isset($this->params['default'])) {
	        $default = $this->params['default'];
	    }
	    $session->set_value($this->get_session_name(), $default);
	}
}