<?php

define('LOG_ERROR',  'error');
define('LOG_WARN',   'warn');
define('LOG_NOTICE1', 'notice');
define('LOG_DB',    'db');
define('LOG_DUMP',  'dump');
define('LOG_MAX',  'dump');
class Nertz_Log
{
	function Nertz_Log($catch_errors = true)
	{
		$this->messages_array = array();
		error_reporting(E_ALL);
		// Ловим все ошибки кроме фаталов
		if (!Nertz::in_cmd() && $catch_errors) {
			$old_error_handler = set_error_handler(array(&$this, "_user_error_handler"), E_ALL | E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING | E_STRICT );
			$this->catch = true;		
		}
		$this->load_savers();
	}
	/**
	 * Добавлние сообщения в лог
	 *
	 * @param string $message Текст сообщения
	 * @param string $category Категория сообщения
	 * @param int $stack_level Уровень поднятия по стеку вызова для указания пользователю места в коде
	 * @param int $level Уровень ошибки
	 */
	function write($message, $category = LOG_DUMP, $stack_level = 0, $level = 0)
	{
		$tip = call_user_func( array( 'Nertz_Log', '_get_file_and_line' ), $stack_level );
		if (!isset($this->messages_array[$category])) {
			$this->messages_array[$category] = array();
		}
		$this->messages_array[$category][] = array(
		'message' => $message,
		'level' => $level,
		'file' => isset($tip['file']) ? $tip['file'] : "" ,
		'line' => isset($tip['line']) ? $tip['line'] : "",
		'func' => isset($tip['func']) ? $tip['func'] : "",
		);
	}
	function save($type, $params)
	{
		if (!empty($this->savers[$type])) {
			$this->savers[$type]->save($this->messages_array);	
		}
	}
	static function _get_file_and_line( $stack_level )
	{
		$_hc_entries = Nertz_Log::_debug_backtrace_smart( 'call_user_func.*' );
		$tip = '';
		if( isset( $_hc_entries[$stack_level] )) {
			$tip = array(
			'file' => isset($_hc_entries[$stack_level]['file']) ? Nertz_Log::_compact_path($_hc_entries[$stack_level]['file']): "",
			'line' =>  isset($_hc_entries[$stack_level]['line']) ? $_hc_entries[$stack_level]['line'] : "",
			'func' => !empty( $_hc_entries[$stack_level]['function'] )? " {$_hc_entries[$stack_level]['function']}" : ""
			);
		} elseif( isset( $_hc_entries[0] ))	{
			$stack_level = 0;
			$tip = array(
			'file' => Nertz_Log::_compact_path($_hc_entries[$stack_level]['file']),
			'line' => $_hc_entries[$stack_level]['line'],
			'func' => !empty( $_hc_entries[$stack_level]['function'] )? " {$_hc_entries[$stack_level]['function']}" : ""
			);
		}
		return $tip;
	}
	static function _compact_path($str) 
	{
		if (strpos($str, $GLOBALS['__base_path'] . '/') === 0) {
			return substr($str, strlen($GLOBALS['__base_path'] . '/'));
		}
		return $str;
	}
	/**
     * array debug_backtrace_smart($ignoresRe=null, $returnCaller=false)
     *
     * Return stacktrace. Correctly work with call_user_func*
     * (totally skip them correcting caller references).
     * If $returnCaller is true, return only first matched caller,
     * not all stacktrace.
     *
     * @version 2.03
     * @author Koterov Dmitriy - dklab.ru
     */
	static function _debug_backtrace_smart( $ignoresRe = null, $returnCaller = false )
	{
		if (!is_callable( $tracer = 'debug_backtrace')) {
			return array();
		}
		$trace = $tracer();
		if( $ignoresRe !== null ) {
			$ignoresRe = "/^(?>{$ignoresRe})$/six";
		}
		$smart = array();
		$framesSeen = 0;
		for ($i = 0, $n = count($trace); $i < $n; $i++) {
			$t = $trace[$i];
			if (!$t) {
				continue;
			}
			// Next frame.
			$next = isset( $trace[$i+1] )? $trace[$i+1] : null;
			// Dummy frame before call_user_func* frames.
			if (!isset($t['file']) && $next) {
				$t['over_function'] = $trace[$i+1]['function'];
				//$t = $t + $trace[$i+1];
				$trace[$i+1] = null; // skip call_user_func on next iteration
			}
			// Skip myself frame.
			if (++$framesSeen < 2)	{
				continue;
			}
			// 'class' and 'function' field of next frame define where
			// this frame function situated. Skip frames for functions
			// situated in ignored places.
			if ($ignoresRe && $next) {
				// Name of function "inside which" frame was generated.
				$frameCaller = (isset($next['class'])? $next['class'].'::' : '') . (isset($next['function'])? $next['function'] : '');
				if( preg_match($ignoresRe, $frameCaller)) {
					continue;
				}
			}

			// On each iteration we consider ability to add PREVIOUS frame
			// to $smart stack.
			if ($returnCaller) {
				return $t;
			}
			$smart[] = $t;
		}
		return $smart;
	}
	
/**
 * Useful mecho function
 *
 * @param mixed $text
 */
	function mecho( $text )
	{
		if (is_array($text)|| is_object($text)) {
			print "<PRE>";				
			print_r( $text );
			print "</PRE>";
		} else {
			print "<br />$text<br />";
		}
	}
	function catch_php_errors() 
	{
		
	}
	function _user_error_handler($errno, $errmsg, $filename, $linenum, $vars) 
	{
		$errortype = array (
		1   =>  "Error",
		2   =>  "Warning!",
		4   =>  "Parsing Error",
		8   =>  "Notice",
		16  =>  "Core Error",
		32  =>  "Core Warning!",
		64  =>  "Compile Error",
		128 =>  "Compile Warning!",
		256 =>  "User Error",
		512 =>  "User Warning!",
		1024=>  "User Notice",
		2048=>  "Undefined Error"
		);
		$error_lev=2;
		if ($errno == 1 || $errno == 4  || $errno == 16  || $errno == 64 || $errno == 256 || $errno == 248) $error_lev = LOG_ERR;
		if ($errno == 2 || $errno == 32 || $errno == 128 || $errno == 512) $error_lev = LOG_WARNING;
		if ($errno == 8 || $errno == 1024) $error_lev = LOG_NOTICE1;
		//ErrorReport( $error_lev, $linenum, Ner$filename,"<b>".$errortype[$errno]."</b> ".$errmsg);
		if ($this->catch || !$error_lev == LOG_NOTICE1) {
			$this->messages_array[$error_lev][] = array(
			'message' => $errmsg,
			'level' => 0,
			'file' => $this->_compact_path($filename),
			'line' => $linenum,
			);
		}
		return true;
	}
	/**
	 * Разбор фатальных ошибок если они есть
	 *
	 */
	function parse_last_error()
	{
		if (function_exists('error_get_last')) {
			$err = error_get_last();
			$this->messages_array[LOG_ERROR][] = array(
				'message' => $err['message'],
				'file'    => isset($err['file']) ? $err['file'] : "" ,
				'line'    => isset($err['line']) ? $err['line'] : "",
				'level' => 1,
			);
		}
	}
	function load_savers() 
	{
		$this->savers = array();
		$this->savers['FirePHP'] = $this->get_saver('FirePHP');
		$this->savers['Mail']   = $this->get_saver('Mail');
	}
	function get_saver($type, $params = array()) 
	{
		$class_name = 'Nertz_Log_Saver_' . $type;
		include_once(Nertz::class_path($class_name));
		return new $class_name($params);
	}
}