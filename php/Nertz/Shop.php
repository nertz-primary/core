<?php

/**
 * Класс Магазина
 *
 */
include_once(Nertz::class_path('Nertz_Shop_Category'));
include_once(Nertz::class_path('Nertz_Shop_Item'));
include_once(Nertz::class_path('Nertz_Tree'));
class Nertz_Shop
{
	/**
	 * Получить курс валют по отношению к рублю
	 *
	 * @param string $currency Вид валюты - смотрите на сайте ЦБРФ
	 */
	static function get_curs($currency="USD")
	{
		return 1.0;
		if (!isset($GLOBALS['cache_curs_' . $currency])) {
			global $cache;
			$dt = getdate();
			$key = lead_zero($dt['mday']) . '.' . lead_zero($dt['mon']) . '.' . lead_zero($dt['year']);
			$value = $cache->get_value('curs', $key . '.' . $currency);
			if (!$value) {
				include_once('HTTP/Request.php');
				$r = new HTTP_Request('http://cbr.ru/currency_base/D_print.aspx?date_req=' . $key);
				$r->setMethod(HTTP_REQUEST_METHOD_GET);
				$r->sendRequest();
				$n = 0;
				$s = strbtw($r->getResponseBody(), $currency, "</tr>", $n);
				$n1 = 0;
				$s1 = strbtw($s,"<td align=\"right\">", "</td>", $n1);
				$s1 = strbtw($s,"<td align=\"right\">", "</td>", $n1);
				$s1 = str_replace(',', '.', $s1);
				$value = floatval($s1);
				$cache->set_value('curs', $key . '.' . $currency, $value);
			}
			$GLOBALS['cache_curs_' . $currency] = $value;
		}
		return $GLOBALS['cache_curs_' . $currency];
	}
	/**
	 * Получить списко всех статусов заказов
	 *
	 * @return array
	 */
	static function fetch_order_status()
	{
		return array(
		0  => 'Новый',
		10 => 'В работе',
		50 => 'Доставлен',
		60 => 'Отменен',
		);
	}
	/**
	 * Показать дерево категорий
	 *
	 * @param int $ind Индекс раскрытой категорий
	 * @param string $full true - Полное дерево, false - только выбранную ноду, 'main' - список нод для главной
	 * @param func $url_function - Функция генерящая URL для эллементов дерева
	 * @param int $item_ind Индекс выбранного товара
	 * @return unknown
	 */
	static function show_category_tree($ind = 0, $full = 0, $url_function = "", $show_items = 0, $item_ind = 0, $use_thumbs = false)
	{
//		print_r("($ind * $full * $url_function * $show_items * $item_ind)");
		$tree = new Nertz_Tree();
		$tree->set_branch_function(array('Nertz_Shop','tree_branch_function'));
		$tree->set_path_function(array('Nertz_Shop','tree_path_function'));
		$params = array();
		$params['url_function'] = $url_function;
		if ($show_items) {
			$params['show_items'] = $show_items;
		}
		if ($item_ind) {
			$params['item_ind'] = $item_ind;
		}
		$params['use_thumbs'] = $use_thumbs;
		return $tree->render_tree($ind, $full, $params);
	}
	static function tree_path_function($ind)
	{
		//$ind = intval($ind);
		$items = Nertz_Shop_Category::fetch();
		$path = array();		
		$arr = explode(':', $ind);
		$id = $ind;
		if (count($arr) > 1) {
			$id = $arr[0];
			$path[] = $ind;
		}
		$path[] = $id;
		if ($ind) {
			// Сформируем массив роодителей вплоть до корня
			while(isset($items[$id]['parent_ind']) && $items[$id]['parent_ind']) {
				$id = $items[$id]['parent_ind'];
				$path[] = $id;
			}
		}
		$path[] = 0;
		return $path;
	}
	static function tree_branch_function($ind, $params)
	{
		$is_brands = false;
		$use_thumbs = !empty($params['use_thumbs']) ? true : false;
		$items = Nertz_Shop_Category::fetch($use_thumbs);
		//$ind = intval($ind);
		$arr = explode(':', $ind);
		if (count($arr) > 1) {
			return array();
		}
		
		$its = array();
		$show_items = !empty($params['show_items']) && $params['show_items'] != 'brand' ? $params['show_items'] : 0;
		$items[$ind]['show_items'] = $show_items;
		/**
		 * Хитрая переменная включающая показ товаров в категориях
		 */
		$is_items = false;
		if ($show_items) {
			$parent_margin = sprintf("%0.2f",Nertz_Shop_Category::get_margin($ind));
			if (!empty($items[$ind]) && isset($items[$ind]['category_count']) && $items[$ind]['category_count'] == 0 &&  isset($items[$ind]['category_count']) && $items[$ind]['item_count'] > 0) {
				$items = Nertz_Shop_Item::fetch($ind);
				$is_items = true;
			} 
		} 
		foreach ($items as $i => $item) {
			if ($is_items || (isset($item['parent_ind']) && intval($item['parent_ind']) == intval($ind)) ) {
				if ($show_items) {
					if ($is_items) {
						$arr = array('ind' => 'i' . $i);	
					} else {
						$arr = array('ind' => $i);
					}
					$margin = (abs($item['margin']) > 0.0001) ? "<b class='red'>{$item['margin']}</b>" : "<b>{$parent_margin}</b>";   
					$arr['name'] = "{$margin}&nbsp;<span>{$item['name']}</span>";
				} else {
					$arr = array('ind' => $i);
					$arr['name'] = $item['name'];
				}


				if ($is_items || ($item['category_count'] == 0 && !$show_items)) {
					$arr['type'] = 'file';
				} else {
					$arr['type'] = 'directory';
				}
				if($use_thumbs && !empty($item['thumb'])) {
					$arr['thumb'] = $item['thumb'];
				}
				if (!empty($params['url_function']) && is_callable($params['url_function'])) {
					$arr['url'] = call_user_func_array($params['url_function'], array('params'=>$arr));
				}
				$its[] = $arr;
			}
		}
		if (!count($its) && !empty($params['show_items']) && $params['show_items'] == 'brand') {
			$brand = Nertz_Shop_Item::fetch_brand($ind);
			$its = array();
			foreach ($brand as $b) {
				if ($b) {
					$arr = array();
					$arr['name'] = $b;
					$arr['ind'] = $ind . ':' . $b;
					$arr['type'] = 'file';
					$arr['margin'] = 0;
					$arr['brand_thumb'] = $items[$ind]['thumb'] . ':' . $b ;
					if (!empty($params['url_function']) && is_callable($params['url_function'])) {
						$arr['url'] = call_user_func_array($params['url_function'], array('params'=>$arr));
					}
					$its[] = $arr;
				}
			}
		}
		/*if (!$ind && !empty($params['full']) && $params['full'] === 'main') {
			array_unshift($its, array(
						'type' => 'directory',
						'url'  => 'http://www.depocomputers.ru/kitcomp/',
						'name' => '01 СЕРВЕРЫ И КОМПЬЮТЕРЫ DEPO',
						'ind'  => 0,
			));
		}*/
		return $its;
	}
	function upadte_category_count($category_ind) 
	{
		global $db;
		$item_count = $db->getOne('SELECT COUNT(ind) FROM ?_shop_item WHERE category_ind = ?', array($category_ind));
		$category_count = $db->getOne('SELECT COUNT(ind) FROM ?_shop_category WHERE parent_ind = ?', array($category_ind));
		$db->save('shop_category', array('category_count' => $category_count, 'item_count' => $item_count), array('ind'=>$category_ind));
	}
	function fetch_producer()
	{
		global $db;
		return $db->getAssoc('SELECT ind, name FROM ?_shop_producer ORDER BY name');
	}
}
