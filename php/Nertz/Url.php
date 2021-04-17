<?php
class Nertz_Url
{
	public $handler;
	function Nertz_Url( $type = "" )
	{
		if (!$type) {
			$type = Nertz::config('url/mode', 0) ? "Chpu" : "Standart";
		}
		if (Nertz::in_cmd()) {
			$type = "Console";
		}
		$class_name = 'Nertz_Url_' . $type;
		include_once(Nertz::class_path($class_name));
		$this->handler = new $class_name;
	}
	function get_page()
	{
		return $this->handler->get_page(); 
	}
	/**
	 * Получение текущего действия из урля
	 *
	 */
	function get_act()
	{
		return $this->handler->get_act();
	}
	/**
	 * Получени значения из урля.
	 * Думаю этот же метод будет потрошить POST и еже с ним
	 *
	 * @param string $name Имя параметра
	 * @param void   $default Имя по-умолчанию
	 */
	function get_value($name, $default=null)
	{
		$value = $this->handler->get_value($name);
		if ($value !== null) {
			return $value;
		} else if ($value === null && $default === null) {
			Nertz::log("Параметр \"{$name}\" не найден в запросе", LOG_WARN, 1);
		}
		return $default;
	}
	/**
	 * Получение УРЛя который пришёл
	 *
	 */
	function get_url()
	{
		return $this->handler->get_url();
	}
	/**
	 * Генерация урля на основе массива параметров
	 *
	 * @param array $params Ассоциативный массив имя_параметра => значение_параметра
	 */
	function gen_url($params)
	{
		return $this->handler->gen_url($params);
	}
	/**
	 * Установим текущую страницу
	 *
	 * @param string $p
	 */
	function set_page($p)
	{
		$this->handler->set_page($p);
	}
	function set_value($name, $p)
	{
		$this->handler->set_value($name, $p);
	}
	function gen_static_url($path) 
	{
		return $this->handler->gen_static_url($path);
	}
	function gen_post_name($name)
	{
		
		return $this->handler->gen_post_name($name);
	}
	
}
function stripslashes_arr(&$value)
{
	$value = is_array($value) ? array_map('stripslashes_arr', $value) : stripslashes($value);
	return $value;
}