<?php

include_once('UTF8.php');
include_once(Nertz::class_path('Nertz_Hook'));
class Nertz
{
	static function init_instace($ob_start = false)
	{
		// Проверим ситуацию с консолью
		$in_cmd = false;
		if (empty($_SERVER["REQUEST_METHOD"])) {
			$in_cmd = true;
		}
		$GLOBALS['__in_cmd'] = $in_cmd;
		if ($ob_start && !$in_cmd) {
			error_reporting(E_ALL);
			ini_set('track_errors', 1);
			ob_start('_nertz_ob_handler');
		}
		$GLOBALS['__ob_start'] = $ob_start;
		//register_shutdown_function('_nertz_shutdown');
		$GLOBALS['__base_path']  = realpath(dirname(__FILE__) . "/../..");
		if(!$GLOBALS['__base_path']) {
			$GLOBALS['__base_path']  = dirname(__FILE__) . "/../..";
		}
		if ($in_cmd) {
			$GLOBALS['__old_cwd'] = getcwd();
			chdir($GLOBALS['__base_path']);
		}
		// Сформируем путь для инклюдов
		if (!defined("PATH_SEPARATOR"))
		define("PATH_SEPARATOR", getenv("COMSPEC")? ";" : ":");
		ini_set("include_path",
			PATH_SEPARATOR . $GLOBALS['__base_path'] .
			PATH_SEPARATOR . $GLOBALS['__base_path'] . "/site/php" .
			PATH_SEPARATOR . $GLOBALS['__base_path'] . "/includes" .
			PATH_SEPARATOR . $GLOBALS['__base_path'] . "/includes/Pear" .
			PATH_SEPARATOR . $GLOBALS['__base_path'] . "/core/php" .
			PATH_SEPARATOR . $GLOBALS['__base_path'] . "/site/templates" .
			PATH_SEPARATOR . $GLOBALS['__base_path'] . "/core/templates".
			ini_get("include_path")
		);
		if (function_exists('mb_internal_encoding')) {
			mb_internal_encoding("UTF-8");
		}
		umask(0000);
		ini_set('file_uploads', 1);
		ini_set('upload_tmp_dir', $GLOBALS['__base_path'] . "/temp/upload");
		ini_set('upload_max_filesize', 0);
		ini_set('default_charset', 'UTF-8');
		ini_set('mbstring.http_output', 'UTF-8');
		ini_set('mbstring.func_overload', "7");
		date_default_timezone_set('Europe/Moscow');
		// Подмутим еще профайлер если есть
		if (isset($_REQUEST['xhprof']) && extension_loaded('xhprof')) {
		//	xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
			xhprof_enable();
			$GLOBALS['__xhprof'] = $_REQUEST['xhprof'];
		}
	}
	/**
	 * Генератор нормального имени файла содержащего указанный класс
	 * В дебаг моде функция оповещает об отсутсвии желаемого файла
	 * @param string $name Имя класса
	 * @return string Путь к файлу класса для операции include
	 */
	static function class_path($name)
	{
		$path = str_replace('_', '/',$name) . ".php";
		if (Nertz::is_debug())
		{
			if(!file_exists($GLOBALS['__base_path'] ."/includes/" . $path) &&
			!file_exists($GLOBALS['__base_path'] ."/core/php/" . $path) &&
			!file_exists($GLOBALS['__base_path'] ."/site/php/" . $path))
			{
				Nertz::log("Не найден файл для класса  \"{$name}\"", LOG_WARN, 1);
			}
		}
		return $path;
	}
	/**
	 * Функция выхода из скрипта, использовать вместо exit()
	 * Здесь осуществляются все основные оперции необходимые для
	 * полноценного завершения скрипта
	 *
	 */
	static function terminate($no_db_close = false)
	{
		$GLOBALS['session']->write();
		$GLOBALS['db']->close();
		Nertz::do_ob_handling();
		Nertz::write_logs();
		if (Nertz::in_cmd()) {
			chdir($GLOBALS['__old_cwd']);
		}
		$GLOBALS['__normal_exit'] = true;
		show_xhproof();
	}
	static function write_logs()
	{
		$need_email = false;
		if (Nertz::config('/main/debug')) {
			try {
				$GLOBALS['log']->save("FirePHP", array());
			} catch (Exception $e) {
				$need_email = true;
			}
		}
		if ($emails = Nertz::config('/main/error_emails') || $need_email) {
			if($emails) {
				$GLOBALS['log']->save("Mail", array('emails' => $emails));
			}
		}
	}
	/**
	 * Преобразователь PHP-шных данных в JSON формат, для боле удобного назначения в JS
	 *
	 * @param any $a Данное
	 * @return string Результат в JSON
	 */
	static function json($a=false)
	{
		if (is_null($a)) {
			return 'null';
		}
		if ($a === false) {
			return 'false';
		}
		if ($a === true) {
			return 'true';
		}
		if (is_scalar($a)) {
			if (is_float($a)) {
				$a = str_replace(",", ".", strval($a));
			}
			static $jsonReplaces = array(
			array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'),
			array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"')
			);
			return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
		}
		$isList = true;
		for ($i = 0, reset($a); $i < count($a); $i++, next($a)) {
			if (key($a) !== $i) {
				$isList = false;
				break;
			}
		}
		$result = array();
		if ($isList) {
			foreach ($a as $v) {
				$result[] = Nertz::json($v);
			}
			return '[ ' . join(', ', $result) . ' ]';
		} else {
			foreach ($a as $k => $v) {
				$result[] = Nertz::json($k) . ': ' . Nertz::json($v);
			}
			return '{ ' . join(', ', $result) . ' }';
		}
	}
	/**
	 * Безопасное определение находимся ли мы в дебагмоде, али нет
	 *
	 * @return bool Флаг нахождения в дебаг моде
	 */
	static function is_debug()
	{
		if(isset($GLOBALS['__is_debug'])) {
			return $GLOBALS['__is_debug'];
		}
		$debug = false;
		global $config;
		if (isset($config)) {
			if ($config->get_value('main/debug',0)) {
				$debug = true;
			}
		}
		$GLOBALS['__is_debug'] = $debug;
		return $debug;
	}
	/**
	 * Безопасная запись сообщения в лог
	 *
	 * @param string $message Текст сообщения
	 * @param string $category Категория сообщения
	 * @param int $stack_level Уровень поднятия по стеку вызова для указания пользователю места в коде
	 * @param int $level Уровень ошибки
	 */
	static function log($message, $category = "user", $stack_level = 0, $level = 0)
	{
		if (isset($GLOBALS['log'])) {
			$GLOBALS['log']->write($message, $category, $stack_level+1, $level);
		}
	}
	/**
	 * Замена print_r для вывода в Консоль
	 *
	 * @param void $var Любая переменная
	 */
	static function dump($var)
	{
		Nertz::log($var, LOG_DUMP, 1);
	}
	/**
	 * Для получения конфиругационных параметров
	 *
	 * @param string $value Имя параметра конфига
	 * @return mixed Значение конфига
	 */
	static function config( $value, $default = null)
	{
		if (isset($GLOBALS['config'])) {
			return $GLOBALS['config']->get_value( $value, $default);
		}
		return $default;
	}
	/**
	 * Правильные redirect
	 *
	 * @param array $u На который отправить пользователя
	 */
	static function redirect($u)
	{
		global $url;
		if (is_array($u)) {
			$u['no_amp'] = 1;
			header('Location: ' . $url->gen_url($u));
		} else {
			header('Location: ' . $u);
		}
		Nertz::terminate();
		exit();
	}
	/**
	 * Исли нам надо досрочно прервать буферизациию вывода то воспользуемся этой функцией
	 *
	 */
	static function do_ob_handling()
	{
		global $log;
		if ($GLOBALS['__ob_start']) {
			if (!isset($log->messages_array['screen'])) {
				$log->messages_array['screen'] = "";
			}
			$GLOBALS['__ob_start'] = false;
			$log->messages_array['screen'] .= ob_get_contents();
			ob_clean();
		}
	}
	/**
	 * Создаем обработчик Аяксовых запросов только так
	 *
	 * @return JsHttpRequest
	 */
	static function init_ajax()
	{
		// Небольшая возня с собственной буферизацией вывода, дабы избежать проблемм с Котеровым
		Nertz::do_ob_handling();
		// Инциируем класс Котерова
		return $GLOBALS['__ajax'] = new JsHttpRequest("UTF-8");
	}
	/**
	 * Проверка того запущены ли мы из коммандной строки
	 */
	static function in_cmd()
	{
		return $GLOBALS['__in_cmd'];
	}

	/**
	 * Преобразование строки из одной кодировки в другую
	 *
	 * @param string $data Строка
	 * @param string $from Исходная кодировка
	 * @param string $to Необходимая кодировка
	 * @return string Результат
	 */
	static function convert($data, $from, $to)
	{
		if (function_exists('mb_convert_encoding')) {
			return mb_convert_encoding($data, $to, $from);
		} else if (function_exists('iconv')) {
			return @iconv($from, $to . "//IGNORE//TRANSLIT", $data);
		} else if (function_exists('recode_string')) {
			return recode_string($from .'..' . $to, $data);
		}
	}
	static function call($func, $params, $message="Не могу вызвать функцию")
	{
		if (!is_callable($func)) {
			Nertz::log($message . ' "' . print_r($func, 1) . '"', "error", 1);
			return false;
		}
		return call_user_func_array($func, $params);
	}
	static function server_name()
	{
		if (isset($_SERVER['SERVER_NAME'])) {
			return $_SERVER['SERVER_NAME'];
		} else {
			return $GLOBALS['__base_path'];
		}
	}
	/**
	 * Получить индекс цели по ее имени
	 *
	 * @param string $name имя цели, если '' то возвращается массив целиком
	 * @return int Индекс цели
	 */
	static function get_target($name = '')
	{
		$targets = array(
		'0' => 'none',
		'1' => 'static',
		'2' => 'article',
		'3' => 'gallery',
		'4' => 'file',
		'5' => 'forum_tg',
		'6' => 'forum_topic'
		);
		if (!$name) {
			return $targets;
		} else {
			$i = array_search($name, $targets);
			if ($i) {
				return $i;
			}
		}
		return 0;
	}

	/**
	 * Получить IP пользователя
	 *
	 */
	function get_ip() {
		return !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
	}
	/**
	 * Определяем перед нами посиковый бот или кто
	 *
	 * @return false если не бот, или имя поисковика
	 */
	static function is_bot()
	{
		$bots = array(
		'rambler','googlebot','aport','yahoo','msnbot','turtle','mail.ru','omsktele',
		'yetibot','picsearch','sape.bot','sape_context','gigabot','snapbot','alexa.com',
		'megadownload.net','askpeter.info','igde.ru','ask.com','qwartabot','yanga.co.uk',
		'scoutjet','similarpages','oozbot','shrinktheweb.com','aboutusbot','followsite.com',
		'dataparksearch','google-sitemaps','appEngine-google','feedfetcher-google',
		'liveinternet.ru','xml-sitemaps.com','agama','metadatalabs.com','h1.hrn.ru',
		'googlealert.com','seo-rus.com','yaDirectBot','yandeG','yandex',
		'yandexSomething','Copyscape.com','AdsBot-Google','domaintools.com',
		'Nigma.ru','bing.com','dotnetdotcom'
		);
		if (!empty($_SERVER['HTTP_USER_AGENT'])) {
			foreach($bots as $bot) {
				if (stripos($_SERVER['HTTP_USER_AGENT'], $bot) !== false){
					return $bot;
				}
			}
		}
		return false;
	}

}
/**
 * Обработчик вывода, постараемся перехватить все фатальные ошибки
 *
 * @param unknown_type $text
 * @return unknown
 */
function _nertz_ob_handler($text)
{

	// Если это нормальное завершение страницы то отдадим текст
	if (!$GLOBALS['__ob_start']) {
		return $text;
	}
	$text = preg_replace("/((?:in|at) \[?).*?( on line |:)\d+/s", "$1*$2*", $text);
	$text = preg_replace("/(\#\d \s*) \S+ \(\d+\)/sx", '$1*(*)', $text);

	if (empty($GLOBALS['log'])) {
		$GLOBALS['log'] = new Nertz_Log(false);
	}
	if (empty($GLOBALS['config'])) {
		$GLOBALS['config'] = new Nertz_Config();
	}
	$GLOBALS['log']->parse_last_error();
	Nertz::write_logs();

	// Иначе начинаем работу над ошибками

	// Смарти не работает, нифига не фурычит :) Пришлось шаблон читать самомому и отдавать без всякого парсинга
	header("HTTP/1.0 500 Internal Server Error");
	include_once(Nertz::class_path('Nertz_File'));
	$f = new Nertz_File('root', 'core/templates/ErrorPage.tmpl','r');
	$e = $f->get_all();
	$f->close();
	return $e;
}
/**
 * Shutdown функция для приложения
 *
 */
function _nertz_shutdown()
{
	// Если произошло нештатное завершение, то расправимся со всем проблемами сами
	if(empty($GLOBALS['__normal_exit'])) {
		$GLOBALS['log']->parse_last_error();
		Nertz::write_logs();
	}
}
/**
 * Копирование существующих эллементов массива
 *
 * @param assoc_array $arr Ассоциативный массив для обработки
 * @param assoc_array $keys Ассоциативный массив вида 'Имя_Ключа' => 'Значение по умолчанию'
 * @param assoc_array $res Первоначальное значение массива реузльтат копирования
 * Если 'Значение по умолчанию' = null тогда, даный эллемент не копируется.
 */
function array_copy_default($arr, $keys, $res = array())
{
	if (is_array($keys) && count($keys)) {
		foreach ($keys as $key => $def_value) {
			if (isset($arr[$key])) {
				$res[$key] = $arr[$key];
			} elseif ($def_value !== null) {
				$res[$key] = $def_value;
			}
		}
	}
	return $res;
}
/**
 * Извлечение данных из двумерного массива
 *
 * @param array $arr Входной массив
 * @param string $value_field Имя поля содержащего значения для массива результата, если === false - тозначениями будет ключ
 * @param string $key_field Имя поля содержащего ключи для массива результата, если === null, то ключем будет сам ключ, если === false, то ключи будут 0,1,2, ...
 */

function array_extract($arr, $value_field = false, $key_field = false)
{
	$res = array();
	if (is_array($arr) && count($arr)) {
		foreach ($arr as $key => $row) {
			if (isset($row[$value_field]) || $value_field === false) {
				$value = ($value_field === false) ?  $key : $row[$value_field];
				if ($key_field === false || (!isset($row[$key_field]) && $key_field !== null)) {
					$res[] = $value;
				} elseif ($key_field === null){
					$res[$key] = $value;
				} else {
					$res[$row[$key_field]] = $value;
				}
			}
		}
	}
	return $res;
}

/**
 * Если число слишком коротоко то добавим нулей перед ним
 *
 * @param int $a Число
 * @param int $len Желаемая длина
 */
function lead_zero($a, $len=2)
{
	while (strlen($a) < $len) {
		$a = '0' . $a;
	};
	return $a;
}

function getmicrotime()
{
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}

function gen_page_list( &$page, $page_count )
{
	// Количество страниц определяется как $min_wing*2+1
	$min_wing = 4;
	if ( $page >  $page_count ) {
		$page = $page_count;
	}
	if( $page == 0 )	{
		$page = $page_count;
	}
	$begin = $page - $min_wing;
	$end = $page + $min_wing;
	if( $begin < 1)	{
		$end = $end - $begin + 1;
		$begin = 1;
	}
	if( $end > $page_count)	{
		$begin = $begin - $end + $page_count-1;
		$end = $page_count;
	}
	if ( $begin < 1) $begin = 1;
	$res = array();
	for( $i = $end; $i >= $begin; $i--)	{
		$res[$i] = $page_count-$i+1;
	}
	return $res;
}

/**
 * Прямой вариант постраничного вывода
 *
 * @param int $page
 * @param int $page_count
 * @return array
 */

function gen_page_list_old( &$page, $page_count )
{
	// Количество страниц определяется как $min_wing*2+1
	$min_wing = 4;
	if( $page >  $page_count ) {
		$page = $page_count;
	}
	if( $page < 1 )	{
		$page = 1;
	}

	$begin = $page - $min_wing;
	$end = $page + $min_wing;
	if( $begin < 1)	{
		$end = $end - $begin + 1;
		$begin = 1;
	}
	if( $end > $page_count)	{
		$begin = $begin - $end + $page_count-1;
		$end = $page_count;
	}

	if ( $begin < 1) $begin = 1;
	$res = array();
	for( $i = $begin; $i <= $end; $i++)	{
		$res[$i] = $i;
	}
	return $res;
}


// Константа для работы с массивами
define('UNSET_VALUE', '**-UnSеТ+**');
define('ORDER_FIELD', '**-OrDeR+**');
/**
 * Получить ключ первого эллемента из массива
 *
 * @param array $arr Массив
 * @return int/string Клеюч Первого эллемента
 */
function array_first_key($arr)
{
	if (is_array($arr)) {
		foreach ($arr as $key => $value) {
			return $key;
		}
	}
	return null;
}

/**
 * Получить ключ последнего эллемента из массива
 *
 * @param array $arr Массив
 * @return int/string Клеюч последнего эллемента
 */

function array_last_key($arr)
{
	$res = null;
	if (is_array($arr)) {
		foreach ($arr as $key => $value) {
			$res = $key;
		}
	}
	return $res;
}
/**
 * Слияние двух массивов
 *
 * $target = $target + $src
 *
 * @param array $target Куда льем
 * @param array $src Что льем
 */
function array_merge_full(&$target, &$src)
{
	if (is_array($src)) {
		foreach ($src as $key => $value) {
			if (is_array($value)) {
				if (!isset($target[$key])) {
					$target[$key] = array();
				}
				array_merge_full($target[$key], $src[$key]);
			} else {
				if ($value === UNSET_VALUE)	 {
					unset($target[$key]);
				} else {
					$target[$key] = $src[$key];
				}
			}
		}
	}
	// Если существует порядок ключей то пересоберем массив в соответствии с ним
	if (isset($src[ORDER_FIELD]) && is_array($src[ORDER_FIELD]) && count($src[ORDER_FIELD])) {
		$res  = array();
		$old_keys = array_flip(array_keys($target)); // Получим старый порядок ключей
		foreach ($src[ORDER_FIELD] as $key) {
			if (isset($target[$key]) && $key != ORDER_FIELD) {
				$res[$key] = & $target[$key];
				unset($old_keys[$key]);
			}
		}
		foreach ($old_keys as $key => $temp) {
			if ($key != ORDER_FIELD) {
				$res[$key] = & $target[$key];
			}
		}
		$res[ORDER_FIELD] = $src[ORDER_FIELD];
		$target = $res;
	}
	if (empty($target[ORDER_FIELD])) {
		unset($target[ORDER_FIELD]);
	}
}

/**
 * Получение разницы между двумя массивами
 *
 * $second = $second - $first
 *
 * @param array $first Массив Источник
 * @param array $second Массив Потомок, который должен содержать только изменения от Источника
 */
function array_diff_full(&$first, &$second)
{
	// Пометим все недостающие эллементы в массиве $second как UNSET_VALUE
	foreach ($first as $key => $value) {
		if (!isset($second[$key]) && $key != ORDER_FIELD) {
			$second[$key] = UNSET_VALUE;
		}
	}
	// Сравним содержимое
	foreach ($second as $key => $value) {
		//Nertz::dump($key);
		if (isset($first[$key]) && $key != ORDER_FIELD) { // Если в первом массиве есть такой же эллемент, при этом не трогаем порядок полей
			if (is_array($value)) {
				array_diff_full($first[$key], $second[$key]);
			} else {
				if ($first[$key] === $second[$key]) { // Удаляем одинаковые эллементы
					unset($second[$key]);
				}
			}
		}
	}
	// Подчистим пустые массивы
	foreach ($second as $key => $value) {
		if (is_array($value) && !count($value)) {
			unset($second[$key]);
		}
	}
}

/**
 * Сортировка двумерного массива, аля выбора из БД
 *
 * @param array $arr
 * @param array $keys Столбцы сортировки, например, array('-score', 'name')
 */
function array_sort_rows(&$arr, $keys)
{
	$GLOBALS['asr_keys'] = $keys;
	uasort($arr,'_cmp_sort_rows');
}

function _cmp_sort_rows($_a, $_b)
{
	foreach ($GLOBALS['asr_keys'] as $key) {
		$desc = false;
		if ($key && strpos($key,'-')===0) {
			$desc = true;
			$key  = substr($key,1);
		}
		$a = $_a[$key];
		$b = $_b[$key];
		if ($a !== $b) {
			if (($a>$b && !$desc) || ($a<$b && $desc)) return 1;
			return -1;
		}
	}
	return 0;
}

function print_ar($array, $ob = false, $count=0) 
{
	$k = $i = 0;
	$tab ='';
	$res = '';

	while($i != $count) {
		$i++;
		$tab .= "&nbsp;&nbsp;|&nbsp;&nbsp;";
	}
	foreach($array as $key=>$value){
		if(is_array($value)){
			$res .= $tab."[<strong><u>$key</u></strong>]<br />";
			$count++;
			print_ar($value, $count);
			$count--;
		}
		else{
			$tab2 = substr($tab, 0, -12);
			$res .= "$tab2~ $key: <strong>$value</strong><br />";
		}
		$k++;
	}
	$count--;
	if ($ob) {
		return $res;
	}
	echo $res;
}

/**
 * Формирование матрицы для построения таблиц
 *
 * @param array $arr Исходный массив
 * @param int $n Количество столбцов
 * @return array Результат в несколько столбцов
 */

function kvadratura($arr, $n)
{
	$col = 0;
	$row = 0;
	$res = array();
	$i = 0;
	if (is_array($arr)) {
		foreach ($arr as $i => $v) {
			if (isset($res[$row])) {
				// $res[$row] = array();
			}
			$res[$row][$i] = $v;
			$col++;
			if($col == $n) {
				$col = 0;
				$row++;
			}
		}
		if($col) {
			$col++;
			while($col<$n) {
				$res[$row][$col] = '';
				$col++;
			}
		}
	}
	return $res;
}

function show_xhproof()
{
	if (!empty($GLOBALS['__xhprof'])) {
		$res = xhprof_disable();
		//print_r($res);
		if($GLOBALS['__xhprof'] < 2) {
			array_sort_rows($res, array("-wt"));
		}
		echo "<style>";
		echo "#xhprof td{padding: 1px 5px};";
		echo "</style>";
		echo "<table id='xhprof'>";
		echo "<tr>";
			echo "<th>Функция</th>";
			echo "<th>Количество</th>";
			echo "<th>Время</th>";
		echo "</tr>";
		foreach ($res as $n => $row)  {
			echo "<tr>";
				echo "<td>{$n}</td>";
				echo "<td>{$row['ct']}</td>";
				echo "<td>{$row['wt']}</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
}