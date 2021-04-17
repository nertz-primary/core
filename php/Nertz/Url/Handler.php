<?php
class Nertz_Url_Handler
{
	/**
	 * Генерация имения для поля в POST
	 * Необходимо для более гибкой работы с параметрами приходящими от пользователя.
	 * @param string $name Имя поля внутри системы
	 */
	function gen_post_name($name)
	{
		//return md5($name);
		return $name;
	}
	/**
	 * Генератор статического Урля
	 * Если путь начинается с core то статика берется из папки ядра
	 * иначе из папки сайта
	 * 
	 * @param string $path Путь к статическому контенту, например css/main.css или core/js/form.js
	 * @return string
	 */
	function gen_static_url($path)
	{
		$core     = 'core';
		$includes = 'includes';
		$files    = 'files';
		$prefix = trim($this->prefix,'/') ;
		if ($prefix) {
			$prefix = '/' . $prefix;
		}
		$path = ltrim($path,'/');
		if (strpos($path, $core) === 0) {
			return $prefix . '/core/static/' . substr($path, strlen($core)+1);	
		}
		if (strpos($path, $includes) === 0) {
			return $prefix . '/includes/' . substr($path, strlen($includes)+1);	
		}
		if (strpos($path, $files) === 0) {
			return $prefix . '/files/' . substr($path, strlen($files)+1);	
		}
		return $prefix. '/site/static/' . $path;	
	}
	/**
	 * Проверяем переменные на вшивость :-)
	 *
	 * @param mixed $value Значение для проверки
	 * @param string $type Тип значения
	 * @param int $lenght Длина занчения в символах
	 */
	function check_value( $value, $type='string', $lenght = 0 )
	{
		if ($lenght > 0) {
			$value = substr( $value, 0, $lenght );
		}
		switch ( $type ) {
			case 'int' :
				$value = intval( $value );
				break;
			case 'float' :
				$value = floatval( $value );
				break;
			case 'bool' :
				$value = intval( substr( $value, 0, 1 ));
				break;
			case 'string' :
				break;
			default:
				break;
		}
		return $value;
	}
}
?>