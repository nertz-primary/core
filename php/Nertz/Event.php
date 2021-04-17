<?php
/**
 * Класс создания и обработки разных событий
 *
 */
class Nertz_Event {
	
	var $data;
	var $debug;
	
	function Nertz_Event()
	{
		$this->data = array();
		$this->debug = true;
	}
	
	/**
	 * Зарегистрировать новый обработчик события
	 *
	 * @param string $name Имя события
	 * @param function $handler Обработчик события
	 */
	function register_handler($name, $handler) 
	{
		if (!isset($this->data[$name])) {
			$this->data[$name] = array();
		}
		
		$arr = array(
			'handler' => $handler,
			);
		if ($this->debug) {
			$tip = call_user_func( array( 'Nertz_Log', '_get_file_and_line' ), 1 );
			$arr['file'] = isset($tip['file']) ? $tip['file'] : "";
			$arr['line'] = isset($tip['line']) ? $tip['line'] : "";
			$arr['func'] = isset($tip['func']) ? $tip['func'] : "";
		}
		$this->data[$name][] = $arr;
			
	}
	/**
	 * Вызов всех обработчиков события
	 *
	 * @param string $name Имя события
	 * @param function $handler Обработчик события
	 */
	function call($name, $params) 
	{
		global $log;
		if (isset($this->data[$name]) && is_array($this->data[$name])) {
			foreach ($this->data[$name] as $item) {
				if (is_callable($item['handler'])) {
					call_user_func_array($item['handler'], $params);
				} else if($this->debug) {
					$log->messages_array[LOG_ERROR][] = array(
						'message' => 'Не могу вызвать обработчик ' . $name,
						'level'   => 0,
						'file'    => $item['file'] ,
						'line'    => $item['line'],
						'func'    => $item['func'],
					);
				}
			}
		}
	}
}

