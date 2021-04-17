<?php
include_once( Nertz::class_path('Nertz_Url_Handler'));
class Nertz_Url_Console extends Nertz_Url_Handler
{
	/**
	 * Конструктор
	 *
	 */
	function Nertz_Url_Console()
	{
		$this->params = array();
		for ($i = 1; $i < count($_SERVER["argv"]); $i++)
		{
			if (strpos($_SERVER["argv"][$i], "=") > 0)
			{
				$str = explode("=", $_SERVER["argv"][$i]);
				$this->params[$str[0]] = $str[1];
			}
			else
			{
				$this->params[$_SERVER["argv"][$i]] = 1;
			}
		}
	}
	/**
	 * Получение текущей страницы из урля
	 *
	 */
	function get_page()
	{
		if ( empty( $this->page )) $this->page = $this->get_value( 'page', 'default' );
		return $this->page;
	}
	/**
	 * Получение текущего действия из урля
	 *
	 */
	function get_act()
	{
		if ( !isset( $this->act )) $this->act = $this->get_value( 'act', '' );
		return $this->act;
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
		if ( isset($this->params[$name]))
		{
			$value = $this->params[$name];
			if (get_magic_quotes_gpc()) {
				$value = stripslashes_arr($value);
			}
			$type   = 'string';
			$lenght = 0;
			if( $name != 'page' && $name != 'act' )
			{
				$page   = $this->get_page();
				$config = Nertz::config( 'pages/' . $page );
				if( isset( $config['params'][$name] ))
				{
					$config = $config['params'][$name];
					$type   = isset( $config['type'] ) ? $config['type'] : $type;
					$lenght = isset( $config['lenght'] ) ? $config['lenght'] : $lenght;
				}
			}
			return $this->check_value($value, $type, $lenght);
		} else {
			return null;
		}
	}
	/**
	 * Получение УРЛя который пришёл
	 *
	 */
	function get_url()
	{
		return $this->params;
	}

	/**
	 * Генерация урля на основе массива параметров
	 *
	 * @param array $params Ассоциативный массив имя_параметра => значение_параметра
	 */
	function gen_url($params)
	{
		return "Нафиг все урли";
	}
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
	 * Установка параметров для распознавания урля
	 *
	 * @param array $params Ассоциативный массив параметров
	 */
	function set_params($params)
	{

	}
	/**
	 * Установим текущую страницу
	 *
	 * @param string $p
	 */
	function set_page($p)
	{
		$this->page  = $p;
		$_REQUEST['page'] = $p;
	}
}