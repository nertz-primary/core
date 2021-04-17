<?php

define('AD_STATUS_NEW', 0);
define('AD_STATUS_INPROGRESS', 1);
define('AD_STATUS_DELETED', 9);
define('AD_STATUS_PARSED', 10);
define('AD_STATUS_ERROR', 19);
define('AD_STATUS_POSTED', 20);

/**
 * Класс граббер
 *
 */
class Nertz_Curl
{
	/**
	 * Массив экземпляров Curl-а
	 *
	 * @var array
	 */
	private $curls = array();
	/**
	 * Количество одновременных запросов
	 *
	 * @var int
	 */
	private $request_limit = 10;
	/**
	 * Текущее количестов запросов
	 *
	 * @var int
	 */
	private $request_count = 0;
	/**
	 * Очередь запросов
	 *
	 * @var array
	 */
	private $queue = array();
	/**
	 * Количество добавлений в очередь
	 *
	 * @var int
	 */
	private $iterate_count = 0;
	/**
	 * Ограничение PHP по памяти
	 *
	 * @var int
	 */
	private $memory_limit = 0;
	/**
	 * Файл куда сохраняются куки
	 *
	 * @var string
	 */
	private $cookie_file = '';
	/**
	 * Констурктор класса граббера
	 *
	 * @param int $request_limit Количество параллельных запросов
	 * @param bool $auto_load Автоматическая подгрузка объявления из очереди
	 * @return Core
	 */

	function Nertz_Curl($request_limit = 10, $auto_load = false)
	{
		$this->mh = curl_multi_init();
		$this->request_limit = $request_limit;
		$this->auto_load = $auto_load;
		$this->memory_limit = convertBytes(ini_get('memory_limit'));
		$this->cookie_file = Nertz_File::tmp_name('curl-cookie');
	}
	/**
	 * Добавление нового урля в очередь
	 *
	 * @param string $url Урль
	 * @param string $handler Обработчик
	 */
	function add_url($url, $handler, $params = array(), $wait_for = 0)
	{
		$url = str_replace('&amp;','&', $url);
		// Если памяти мало, то больше не добавляем урлей
		if (memory_get_usage() > round($this->memory_limit) * 0.9) {
			return;
		}
		global $url_types;
		$p = $params;
		$item = array('url' => $url, 'handler' => $handler, 'params' => $p);
		if ($wait_for) {
			$item['wait_for'] = $wait_for;
			array_unshift($this->queue, $item);
		} else {
			array_push($this->queue, $item);
		}
		unset($p);
	}
	/**
	 * Запуск новых закачек из очереди
	 *
	 */
	function start_from_queue()
	{
		global $proxy;
		$found = 1;
		while ($this->request_count < $this->request_limit && $found) {
			$found = false;
			$task = false;
			foreach ($this->queue as $id => $item) {
				if (empty($item['wait_for']) || ((empty($item['wait_for']) && $item['wait_for'] <= time()))) {
					$task = $item;
					unset($this->queue[$id]);
					$found = true;
					break;
				}
			}
			if ($task) {
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL , $task['url'] );
				curl_setopt($curl, CURLOPT_HEADER, false);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_TIMEOUT, 60);
				curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
				curl_setopt($curl, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
				
				curl_setopt($curl, CURLOPT_COOKIEFILE, $this->cookie_file);
				curl_setopt($curl, CURLOPT_COOKIEJAR,  $this->cookie_file);
				
				
				/*$proxy_id = 0;
				if (!isset($task['params']['no_proxy'])) {
					$proxy_id = $proxy->set_to_curl($curl);
				}*/
				if (!empty($task['params']['curl']) && is_array($task['params']['curl']) && count($task['params']['curl'])) {
					curl_setopt_array($curl, $task['params']['curl']);
				}
				$h = curl_multi_add_handle($this->mh, $curl);
				$arr = array('curl' => $curl, 'handler' => $task['handler'], 'params' => $task['params']);
	//			if ($proxy_id) {
	//				$arr['proxy_id'] = $proxy_id;
	//			}
				$this->curls[intval($curl)] = $arr;
				$this->request_count++;
			}
		}
		/*if ($found == 1) {
			echo "2";
		} else if ($found === true) {
			echo "1";
		} else {
			echo "0";
			print_r($this->queue);
		}*/
		// Если размер очереди шибко маленький и хватет памяти то добавим еще эллементов
		if (count($this->queue) < $this->request_limit && $this->auto_load && memory_get_usage() < round($this->memory_limit) * 0.8){
			global $DB;
			$DB->load_to_Core($this, $this->request_limit);
		}
		if ($this->iterate_count > 1000) {
			$this->rehash_queue();
			$this->iterate_count = 0;
		}
		$this->iterate_count++;
		unset($found);
	}
	/**
	 * Выполненние цикла обслуживания очереди и запуска обработчиков запросов
	 *
	 */
	function iterate()
	{
		global $url_types, $proxy;
		for(;;) {
			$this->start_from_queue();
			if (!$this->request_count && !count($this->queue)) {
	    		break;
	    	}
			curl_multi_select($this->mh);
			while(($res = curl_multi_exec($this->mh, $active)) == CURLM_CALL_MULTI_PERFORM) usleep(100);
	    	if($res != CURLM_OK) break;
	    	while($done = curl_multi_info_read($this->mh)) {
	    		$ch = $done['handle'];
	    		//echo "<pre>" . print_r(curl_getinfo($ch),1) . "</pre><br/>";
	    		$done_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
	    		$curl = $this->curls[intval($ch)];
	    		$this->print_debug($done_url);
	        	if(!$done['result']) {
					$done_content = curl_multi_getcontent($ch);
	        		//$this->print_debug($done_content);
	            	//call_user_func_array($url_types[$curl['type']]['handler'], array($done_url, $done_content, $done, $this, $curl['params']));
	            	//call_user_func($url_types[$curl['type']]['handler'], $done_url, $done_content, $done, $this, $curl['params']);
	            	// Сбросим параметры вызова курля. так как они одноразовые.
	            	//unset($curl['params']['curl']);
	            	//unset($curl['params']['no_proxy']);

					//handler($done_url, $done_content, $done, $this, $curl);
					call_user_func_array($curl['handler'], array(&$done_url, &$done_content, &$done, &$this, &$curl['params']));
					//print_r(curl_getinfo($ch, CURLINFO_HEADER_OUT));
					//echo "<br/>";
	            	global $debug;
	            	if ($debug == 2) {
	            		$this->print_debug($done_content);
	            	}
	            	unset($done_content);
	    	    } else {
		            $err = curl_error($ch);
		            print_r($err);
					//exit();
	    	    	$this->print_debug("failed: ".$err."<br>\n", false);
	    	    	// Не распознаваемы глюк, оставим этот запрос до лучших времен.
	    	    	/*if (!strpos($err, 'formpost')) {
			            $this->add_url($done_url, $curl['handler'], $curl['params']);
			            if (isset($curl['proxy_id'])) {
		            		$proxy->report_proxy($curl['proxy_id'], true);
		            	}
	            	}*/
	        	}

	        	unset($curl);
	        	curl_multi_remove_handle($this->mh, $ch);
	        	unset($this->curls[intval($ch)]);
	        	curl_close($ch);
	        	unset($ch);
	        	unset($done_url);
	        	$this->request_count--;
	    	}
	    	unset($done);
		}
		if ($this->cookie_file && file_exists($this->cookie_file)) {
			unlink($this->cookie_file);
		}

	}
	function print_debug($s, $show_info = true)
	{
		global $debug;
		if ($debug) {
			if ($show_info){
				echo '[' .$this->request_count . '|' . count($this->queue). ']' . memory_get_usage() .': ';
			}
			if(!empty($GLOBALS['__in_cmd'])) {
				echo Nertz::convert(print_r($s,1), "UTF-8", "KOI8-R");	
				echo "\n";
			} else {
				print_r($s);	
				echo "<br/>\n";
			}
			
			
			flush();
		}
	}
	function rehash_queue()
	{
		$arr = array();
		foreach ($this->queue as $id => $item) {
			$arr[$id] = $item;
		}
		unset($this->queue);
		$this->queue = $arr;
	}
}

/**
 * Вырезаем кусок из строки
 *
 * @param string $s Препарируемая строка
 * @param string $bs Подстрока предшествующая вырезаемому фрамгенту
 * @param string $es Подстрока находящаяс сразу после фрамгента
 * @param int $n Вход - начальная позиция поиска
 * @return unknown
 */
/*function strbtw($s, $bs, $es, &$n)
{
	$i=strpos($s,$bs,$n);
	$n = strlen($s);
	if ($i===false) {
		unset($i);
		unset($n);
		return "";
	}
	$i1=strpos($s,$es,$i);
	if ($i1===false) {
		return substr($s,$i+strlen($bs));
	} else {
		$n = $i1 + strlen($es);
		return substr($s,$i+strlen($bs),$i1-$i-strlen($bs));
	}
}
*/
function convertBytes( $value ) {
    if ( is_numeric( $value ) ) {
        return $value;
    } else {
        $value_length = strlen( $value );
        $qty = substr( $value, 0, $value_length - 1 );
        $unit = strtolower( substr( $value, $value_length - 1 ) );
        switch ( $unit ) {
            case 'k':
                $qty *= 1024;
                break;
            case 'm':
                $qty *= 1048576;
                break;
            case 'g':
                $qty *= 1073741824;
                break;
        }
        return $qty;
    }
}
