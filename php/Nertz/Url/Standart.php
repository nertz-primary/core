<?php
include_once( Nertz::class_path('Nertz_Url_Handler'));
class Nertz_Url_Standart extends Nertz_Url_Handler
{
	/**
	 * Конструктор
	 * 
	 */
	function Nertz_Url_Standart()
	{
		$this->mode = Nertz::config('url/mode', 1);
		$this->prefix = trim(Nertz::config('url/prefix', ''),"/");
		if ($this->prefix) {
			$this->prefix = '/' . $this->prefix;
		}
	}
	/**
	 * Получение текущей страницы из урля
	 *
	 */
	function get_page()
	{
		if (empty( $this->page )) $this->page = $this->get_value( 'page', '');
		if (!$this->page) {
			$this->page = Nertz::config('/main/default_page');
		}
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
	function get_value($name)
	{
		$post = 0;
		if ( isset( $_POST[$this->gen_post_name( $name )])) $post = 1;
		if ( isset($_REQUEST[$name]) || $post )
		{
			$value = $post ? $_POST[$this->gen_post_name($name)] : $_REQUEST[$name];
			if (get_magic_quotes_gpc()) {
		    	$value = stripslashes_arr($value);
			} 
			
		/*	$type   = 'string';
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
		*/
			return $value;
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
		$arr1 = parse_url( $_SERVER['REQUEST_URI'] );
		if (!empty($arr1['query'])) {
			$arr2 = array();
			parse_str($arr1['query'], $arr2);
			return $arr2;
		}
		return false;
	}
	
	/**
	 * Генерация урля на основе массива параметров
	 *
	 * @param array $params Ассоциативный массив имя_параметра => значение_параметра
	 */
	function gen_url($params)
	{
		$url = $this->prefix."/?";
		$amp = "";
		$sep = "&amp;";
		$encode = false;
		$custom_server_name = false;
		$full_url = isset($params['full_url']) ? true : false;
		unset($params['full_url']);
		if (isset($params['no_amp'])) {
			$sep = "&";
			unset($params['no_amp']);
		}
		if (!empty($params['custom_server_name'])) {
			$custom_server_name = $params['custom_server_name'];
			unset($params['custom_server_name']);
		}
		if (isset($params['need_encode'])) {
			$encode = true;
			unset($params['need_encode']);
		}
		if (is_array($params)) {
			foreach ($params as $name => $value) {
				// Пришлось заюзать urlencode, так как русские UTF8 символы плохо передаются в IE
				if (is_array($value)) {
					foreach ($value as $i => $v) {
						$url .= $amp . "{$name}[{$i}]=" . urlencode($v); 	
						$amp = $sep;
					}
				} else {
					$url .= $amp . "{$name}=" . urlencode($value); 	
				}
				
				$amp = $sep;
			}
		}
		if ($full_url || $custom_server_name) {
			$url =  'http://' . ($custom_server_name ? $custom_server_name : Nertz::server_name()) . '/' . ltrim($url, '/') ;
		}
		if ($encode) {
			$url = urlencode($url);
		}
		return $url;
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
	function set_value($name, $p)
	{
		$_REQUEST[$name] = $p; 
	}
	
}