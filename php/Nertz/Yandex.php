<?php
/**
 * Класс для взаимодействия с Яндекс
 *
 */
include_once(Nertz::class_path('HTTP_Request'));
class Nertz_Yandex
{
	/**
	 * Поиск Картинок
	 *
	 * @param string $s Строка поиска
	 */
	static function search_picture($s) 
	{
		$url = 'http://images.yandex.ru/yandsearch?text=' . urlencode($s);
		$r = &new HTTP_Request($url);
		$r->setMethod(HTTP_REQUEST_METHOD_GET);
		$r->sendRequest();
		// Распарсим что нам выдал Яндекс
		$s = $r->getResponseBody();
		$n = 0;
		$res = array();
		$i = 0;
		while ($s1 = strbtw($s,'<tr class="r1">','</tr>', $n)) {
			$items = array(); // Индексы эллементов текущей строки	
			$n1 = 0;
			while ($s2 = strbtw($s1,'<div class="b-image','</div>', $n1)) {
				$arr = array();
				$n2 = 0;
				$url = str_replace('&amp;','&',strbtw($s2, '<a href="', '" id="', $n2));
				$n3 = 0;	
				$arr['url'] = 'http://' . urldecode(strbtw($url, '&img_url=', '&rpt=', $n3));
				$arr['img']        = strbtw($s2, '<img src="', '" ', $n2);
				$arr['img_width']  = strbtw($s2, 'width="', '" ', $n2);
				$arr['img_height'] = strbtw($s2, 'height="', '" ', $n2);
				$res[$i] = $arr;
				$items[] = $i;
				$i++;
			}
			$s1 = strbtw($s,'<tr class="r2">','</tr>', $n);
			$n1 = 0;
			$z = 0;
			while ($s2 = strbtw($s1,'<ul class="b-image-info">','</ul>', $n1)) {
				$arr = array();
				$n2 = 0;
				if (isset($items[$z])) {
					$res[$items[$z++]]['text']  = strbtw($s2, '<li class="text">', '</li>', $n2);
				}
			}
			
		
		}
		return $res;
	}
	/**
	 * Получить Урль для скачивания картинки
	 *
	 * @param string $url Урль из результатов поиска
	 */
	function get_image_url($url)
	{
		$url = 'http://images.yandex.ru' . $url;
		Nertz::dump($url);
		$r = &new HTTP_Request($url);
		$r->setMethod(HTTP_REQUEST_METHOD_GET);
		$r->sendRequest();
		// Распарсим что нам выдал Яндекс
		$s = $r->getResponseBody();
		$n = 0;
		$s1 = strbtw($s, '<img id="i-main-pic"', '/>', $n);
		$n = 0;
		return strbtw($s, 'src="', '" ', $n);
	}
}