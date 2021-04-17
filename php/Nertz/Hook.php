<?php
/**
 * Хуки для разных вещей
 *
 */
class Nertz_Hook
{
	/**
	 * Фильтры
	 *
	 * @var array
	 */
	static private $filters = array();
	/**
	 * Действия
	 *
	 * @var array
	 */
	static private $actions = array();
	/**
	 * Добавление фильтра
	 *
	 * @param string $name Имя хука
	 * @param callable $hanlder Имя хука
	 */
	static function add_filter($name, $hanlder)
	{
		if (empty(self::$filters[$name])) {
			self::$filters[$name] = array();
		}
		self::$filters[$name][] = array(
			'handler' => $hanlder,
		);
	}
	static function apply_filters($name, $value, $params = array()) 
	{
		array_unshift($params, $value);
		if (!empty(self::$filters[$name]) && is_array(self::$filters[$name])) {
			foreach (self::$filters[$name] as $filter) {
				if (is_callable($filter['handler'])) {
					$value = call_user_func_array($filter['handler'], $params);
				}
			}
		}
		return $value;
	}
	/**
	 * Добавление действия
	 *
	 * @param string $name Имя действия
	 * @param callable $hanlder Каллбак действия
	 */
	static function add_action($name, $hanlder)
	{
		if (empty(self::$actions[$name])) {
			self::$actions[$name] = array();
		}
		self::$actions[$name][] = array(
			'handler' => $hanlder,
		);
	}
	/**
	 * Выполнить действие
	 *
	 * @param string $name Имя действия
	 * @param array $params Параметры, например array($a, &$b, 2)
	 * @return mixed Результат выполнения действия
	 */
	static function do_action($name, $params = array())
	{
		if (!empty(self::$actions[$name]) && is_array(self::$actions[$name])) {
			foreach (self::$actions[$name] as $action) {
				if (is_callable($action['handler'])) {
					$value = Nertz::call($action['handler'], $params);
				}
			}
		}
		return $value;
	}
}
function func_get_args_byref() {
    $trace = debug_backtrace();
    return $trace[1]['args'];
}