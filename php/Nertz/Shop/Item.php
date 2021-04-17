<?php
define('SMALL_PREVIEW_WIDTH',  72);
define('SMALL_PREVIEW_HEIGHT', 72);
define('MEDIUM_PREVIEW_WIDTH',  100);
define('MEDIUM_PREVIEW_HEIGHT', 90);
define('LARGE_PREVIEW_WIDTH',  218);
define('LARGE_PREVIEW_HEIGHT', 218);
define('SHOP_SMALL_COST', 0.001);
define('SHOP_COUNT_1', 250);
define('SHOP_COUNT_2', 251);
define('SHOP_COUNT_3', 252);
define('SHOP_COUNT_4', 253);

define('DEFAULT_PREVIEW_PATH', 'site/static/img/no_pic.jpg');
include_once(Nertz::class_path('Nertz_Shop'));
/**
 * Класс товаров магазина
 *
 */
class Nertz_Shop_Item
{
	/**
	 * Добавить новый товар
	 *
	 * @param assoc $params Поля добавляемого товара
	 */
	static function add($params)
	{
		global $db;
		$category_ind = $params['category_ind'];
		$name       = $params['name'];
		if ($ind = $db->getOne('SELECT ind FROM ?_shop_item WHERE category_ind = ? AND name = ?', array($category_ind, $name))) {
			$db->save('shop_item', $params, array('ind' => $ind));
		} else {
			$ind = $db->save('shop_item', $params);
			if ($ind) {
				$db->sql_query('UPDATE ?_shop_category SET item_count =  item_count + 1 WHERE ind = ?', array($category_ind));
			}
		}
		return $ind;
	}
	/**
	 * Обновить параметры существующей товара
	 *
	 * @param int $ind Индекс товара
	 * @param assoc $params Массив обновляемых полей товара
	 */
	static function update($ind, $params)
	{
		global $db;
		$db->save('shop_item',$params, array('ind' => $ind));
		return $ind;
	}
	/**
	 * Удалить товар
	 *
	 * @param int $ind Индекс товара
	 */
	static function delete($ind)
	{
		global $db;
		$category_ind = $db->getOne('SELECT category_ind FROM ?_shop_item WHERE ind = ?', array($ind));
		$db->sql_query('DELETE FROM ?_shop_item WHERE ind = ? ', array($ind));
		if ($category_ind) {
			$db->sql_query('UPDATE ?_shop_category SET item_count =  item_count - 1 WHERE ind = ?', array($category_ind));
			$db->sql_query('UPDATE ?_shop_item_link SET item_ind = 0 WHERE item_ind =? ', array($ind));
		}
		return $category_ind;
	}
	/**
	 * Удалить все товары категории
	 *
	 * @param int $category_ind Индекс категории
	 */
	static function delete_by_category($category_ind)
	{
		global $db;
		$inds = $db->getCol('SELECT ind FROM ?_shop_item WHERE category_ind = ? ', array($category_ind));
		if (count($inds)) {
			$db->sql_query('DELETE FROM ?_shop_item WHERE category_ind = ? ', array($category_ind));
			$db->sql_query('UPDATE ?_shop_item_link SET item_ind = 0 WHERE item_ind IN (?a) ', array($inds));
		}
		return;
	}
	/**
	 * Поиск товаров
	 *
	 * @param string $name Поисковая строка
	 */
	static function search($name, $p = 0)
	{
		global $db;
		$oname = $name;
		$names = explode(' ', $name);
		$names = array_unique($names);
		$x = "";
		$name = "";
		foreach ($names as $n) {
			if ($n) {
				$name .=  $x . '+' . $n;
				$x = " ";
			}
		}
		$res = $db->getPaged('SELECT *, MATCH(name) AGAINST (? IN BOOLEAN MODE) as rel FROM ?_shop_item WHERE (MATCH(name) AGAINST (? IN BOOLEAN MODE) OR ind=? OR articul LIKE \'%?#%\') AND category_ind > 0  AND deleted = 0 ORDER BY category_ind, rel DESC', array($name, $name, $oname, $oname), $p, 10, true);
		$items = array();
		$category_ind = 0;
		$id = 0;
		$category_margin = SHOP_MARGIN;
		foreach ($res['data'] as $ind => $item) {
			if ($item['category_ind'] != $category_ind) {
				$category_ind = $item['category_ind'];
				$items[$category_ind]['path'] = Nertz_Shop_Category::get_path($category_ind);
				$items[$category_ind]['items'] = array();
				$category_margin = Nertz_Shop_Category::get_margin($category_ind);
			}
			$item['img'] = Nertz_Shop_Item::image_path($item['image_ind'],'small');
			$margin = ($item['margin'] > SHOP_SMALL_COST) ? $item['margin'] : $category_margin;
			$item['cost'] = Nertz_Shop_Item::convert_cost($item['cost'], $margin);
			$items[$category_ind]['items'][$ind] = $item;
		}
		$res['data'] = $items;
		$res['url']['search'] = $oname;
		return $res;
	}
	/**
	 * Получить информацию об одном товаре
	 *
	 * @param int $ind Индекс товара
	 */
	static function get($ind, $thumb = false)
	{
		global $db;
		if (is_array($ind)) {
			$item = $db->getAssoc('SELECT * FROM ?_shop_item WHERE ind IN (?a)', array($ind));
			if (is_array($item)) {
				foreach($item as $i => $v) {
					$item[$i]['img'] = Nertz_Shop_Item::image_path($v['image_ind'],'small');
					$margin = 0;
					if(!empty($v['category_ind'])) {
						$category_margin = Nertz_Shop_Category::get_margin($v['category_ind']);
						$margin = ($v['margin'] > SHOP_SMALL_COST) ? $v['margin'] : $category_margin;
					}
					$item[$i]['cost'] = Nertz_Shop_Item::convert_cost($v['cost'], $margin);
				}
			}
		} else {
			//$field = is_numeric($ind) ? 'ind' : 'thumb';
			if($thumb || !is_array($item = $db->getRow('SELECT * FROM ?_shop_item WHERE `ind` = ?', array($ind)))) {
				$item = $db->getRow('SELECT * FROM ?_shop_item WHERE `thumb` = ?', array($ind));
			}
			if (is_array($item)) {
				$item['img'] = Nertz_Shop_Item::image_path($item['image_ind'],'large');
				$category_margin = Nertz_Shop_Category::get_margin($item['category_ind']);
				$margin = ($item['margin'] > SHOP_SMALL_COST) ? $item['margin'] : $category_margin;
				$item['cost'] = Nertz_Shop_Item::convert_cost($item['cost'], $margin);
			}
		}
		return $item;
	}
	static function fetch($category_ind, $order = 'ind', $order_by = 'ASC', $p = 0, $brand = '', $pp = 10)
	{
		global $db;
		$s = '';
		$params = array($category_ind);
		if ($brand) {
			$s = ' AND producer = ?';
			$params[] = $brand;
		}
		$params[] =  $order;
		$params[] =  $order_by;
		$items = $db->getPaged('SELECT * FROM ?_shop_item WHERE category_ind = ?  AND name <> ""  AND deleted = 0 ' . $s . ' ORDER BY ?# ?#', $params , $p, $pp, true);
		$category_margin = Nertz_Shop_Category::get_margin($category_ind);
		foreach ($items['data'] as $ind => $item) {
			$items['data'][$ind]['img'] = Nertz_Shop_Item::image_path($item['image_ind'],'small');
			$margin = ($item['margin'] > SHOP_SMALL_COST) ? $item['margin'] : $category_margin;
			$items['data'][$ind]['cost'] = Nertz_Shop_Item::convert_cost($items['data'][$ind]['cost'], $margin);
		}
		return $items;
	}

	static function fetch_random()
	{
		global $db;
		$items = $db->getAssoc('SELECT * FROM ?_shop_item WHERE image_ind > 0 AND category_ind > 0 AND cost > 1 AND count > 0 AND deleted= 0 ORDER BY RAND() LIMIT 9');
		foreach ($items as $ind => $item) {
			$p = Nertz_Shop_Category::get_path($item['category_ind']);
			$items[$ind]['category'] = array_pop($p);
			$items[$ind]['img'] = Nertz_Shop_Item::image_path($item['image_ind'], 'medium');
			$category_margin = Nertz_Shop_Category::get_margin($item['category_ind']);
			$margin = ($item['margin'] > SHOP_SMALL_COST) ? $item['margin'] : $category_margin;
			$items[$ind]['cost'] = Nertz_Shop_Item::convert_cost($items[$ind]['cost'], $margin);
		}
		return $items;
	}

	/**
	 * Получим список ссылок товаров данного поставщика
	 *
	 * @param string $vendor_ind Индекс поставщика
	 * @return array ссылок товаров на прайс поставщика
	 */
	static function fetch_link($vendor_ind)
	{
		global $db;
		return $db->getAssoc('SELECT il.vendor_item_ind, i.name name, i.ind item_ind, il.ind ind, il.cost cost, il.`count` `count`, i.cost icost, i.`count` icount, i.category_ind category_ind FROM ?_shop_item_link il LEFT JOIN ?_shop_item i ON il.item_ind = i.ind WHERE il.vendor_ind = ? ', array($vendor_ind));			
	}
	/**
	 * Получить все ссылки данного товара
	 *
	 * @param int $item_ind Индекс товара
	 */
	static function fetch_item_links($item_ind) 
	{
		global $db;
		return $db->getAssoc('SELECT * FROM ?_shop_item_link il WHERE il.item_ind = ? ', array($item_ind));
	}
	
	/**
	 * Добавление новой ссылки между товарами поставщика и товарами магазина
	 *
	 * @param array $params Параметры ссылки
	 * @return int Индекс свежей ссылки
	 */
	static function add_link($params)
	{
		global $db;
		$ind = $db->save('shop_item_link', $params);
		return $ind;
	}
	/**
	 * Обновление параметров ссылки между товарами поставщика и товарами магазина
	 *
	 * @param int $ind Индекс обновляемой ссылки
	 * @param array $params Параметры ссылки
	 * @return int Индекс свежей ссылки
	 */
	static function update_link($ind, $params)
	{
		global $db;
		$ind = $db->save('shop_item_link', $params, array('ind' =>$ind));
		return $ind;
	}
	/**
	 * Сохранение описания товара
	 *
	 * @param int $ind Индекс товара
	 * @param assoc $options Описние товара вида 'Название' => 'Значение'
	 */
	static function save_options($ind, $options)
	{
		global $db;
		if (!isset($this->options)) {
			$this->options = $db->getAssoc('SELECT name, ind FROM ?_shop_option');
		}
		$options_index = array();
		foreach ($options as $name => $value) {
			if (isset($this->options[$name])) {
				$options_index[$this->options[$name]] = $value;
			} else {
				$oi = $db->save('shop_option', array('name' => $name));
				$this->options[$name] = $oi;
				$options_index[$oi]   = $name;
			}
		}
		$item_options = $db->getAssoc('SELECT option_ind, value FROM ?_shop_item_option WHERE item_ind = ?', array($ind));
		foreach($options_index as $oi => $value) {
			if (!isset($item_options[$oi])) {
				$db->save('shop_item_option', array('item_ind' => $ind, 'option_ind' => $oi, 'value' => $value));
			} else if(strcmp($item_options[$oi],$value) !== 0) {
				$db->save('shop_item_option', array('value' => $value), array('item_ind' => $ind, 'option_ind' => $oi));
			}
			unset($item_options[$oi]);
		}
		if (count($item_options)) {
			$db->sql_query('DELETE FROM ?_shop_item_option WHERE item_ind =? AND option_ind IN(?a)', array($ind, array_keys($item_options)));
		}
	}
	/**
	 * Получение описания товара
	 *
	 * @param int $ind Индекс товара
	 */
	static function get_options($ind)
	{
		global $db;
		$options = $db->getAssoc('SELECT so.name, sio.value FROM ?_shop_option so INNER JOIN ?_shop_item_option sio ON sio.option_ind = so.ind WHERE sio.item_ind = ?', array($ind));
		unset($options['image']);
		return $options;
	}

	/**
	 * Получить путь к картинке товара
	 *
	 * @param int $image_ind Индекс картинки (ссылки)
	 * @param string $type Тип картинки 'full' - оригнинальный размер, 'large' - большая, 'small' - для списка
	 * @return unknown
	 */
	static function image_path($image_ind, $type)
	{
		if (!$image_ind) {
			return '/' . DEFAULT_PREVIEW_PATH;
		}
		$path = '/files/price/img/' . Nertz_File::make_store_name($image_ind, $type .'.jpg');
		return $path;
	}
	/**
	 * Преобразование цены из долларов в рубли
	 *
	 * @param float $cost Цена
	 * @param float $curs Курс обмена. Если 0 то берется текущий от (ЦБРФ + 3%) + 10%. Последнее торговая наценка
	 */
	static function convert_cost($cost, $margin = SHOP_MARGIN, $curs=0)
	{
		if(!$curs) {
			$curs = floatval(Nertz_Shop::get_curs());  
		}
		$cost = floatval($curs) * floatval($cost) * floatval(SHOP_OVERCOST) * ((100.0 + floatval($margin)) / 100.0);	
		
		return sprintf("%01.2f", $cost);
	}
	static function delete_images($link_ind) 
	{
		$base_path = $GLOBALS['__base_path'];
		$arr = array('full', 'large', 'medium', 'small');
		foreach($arr as $a) {
			$f = $base_path . Nertz_Shop_Item::image_path($link_ind, $a);
			if (file_exists($f)) {
				unlink($f);	
			}
		}
	}
	static function fetch_brand($category_ind) 	
	{ 		
		global $db; 		
		return $db->getCol('SELECT DISTINCT(producer) producer FROM ?_shop_item WHERE category_ind = ?  AND deleted = 0 ORDER BY producer', array($category_ind)); 	
	}
}