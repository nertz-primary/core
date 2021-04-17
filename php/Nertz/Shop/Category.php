<?php
/**
 * Класс древовидных категорий магазина
 *
 */
class Nertz_Shop_Category
{
	/**
	 * Добавить новую категорию
	 *
	 * @param assoc $params Поля добавляемой категории
	 */
	static function add($params)
	{
		global $db;
		$parent_ind = $params['parent_ind'];
		$name       = $params['name'];
		if ($shop_ind = $db->getOne('SELECT ind FROM ?_shop_category WHERE parent_ind = ? AND name = ?', array($parent_ind, $name))) {
		} else {
			$vars = array ('name' => $name, 'parent_ind' => $parent_ind );
			$shop_ind = $db->save('shop_category', $vars);
			if ($shop_ind) {
				$db->sql_query('UPDATE ?_shop_category SET category_count =  category_count + 1 WHERE ind = ?', array($parent_ind));
			}
		}
		$this->_clear_cache();
		return $shop_ind;
	}
	/**
	 * Обновить параметры существующей категории
	 *
	 * @param int $ind Индекс категории
	 * @param assoc $params Массив обновляемых полей категории
	 */
	static function update($ind, $params)
	{
		global $db;
		$db->save('shop_category',$params, array('ind' => $ind));
		self::_clear_cache();
		return $ind;

	}
	/**
	 * Получить данные категории
	 *
	 * @param int $ind Индекс Категории
	 */
	static function get($ind) 
	{
		global $db;
		return $db->getRow('
			SELECT 
				c.ind ind,
				c.parent_ind parent_ind,
				c.name name,
				c.margin margin,
				COUNT(cc.ind) category_count,
				COUNT(i.ind) item_count
			FROM ?_shop_category c
			LEFT JOIN ?_shop_item i ON i.category_ind = c.ind
			LEFT JOIN ?_shop_category cc ON cc.parent_ind = c.ind
			WHERE c.ind = ?
			GROUP BY c.ind
			', array($ind)); 
	}
	/**
	 * Получить данные категории
	 *
	 * @param int $ind Индекс Категории
	 */
	static function get_thumb($thumb) 
	{
		global $db;
		return $db->getRow('
			SELECT 
				c.ind ind,
				c.thumb thumb,
				c.parent_ind parent_ind,
				c.name name,
				c.margin margin,
				COUNT(cc.ind) category_count,
				COUNT(i.ind) item_count
			FROM ?_shop_category c
			LEFT JOIN ?_shop_item i ON i.category_ind = c.ind
			LEFT JOIN ?_shop_category cc ON cc.parent_ind = c.ind
			WHERE c.thumb = ? OR c.ind= ?
			GROUP BY c.ind
			', array($thumb, $thumb)); 
	}
	/**
	 * Получить список дочерних категорий
	 *
	 * @param int $ind Индекс Категории
	 * @return assoc Массив вида 'Индекс категории' => 'Имя категории'
	 */
	static function fetch_childs($ind, $use_thumbs = false) 
	{
		global $db;
		$s = $use_thumbs ? ', thumb' : '';
		return $db->getAll('SELECT ind, name' . $s . ' FROM ?_shop_category WHERE parent_ind = ? AND name <> "" ORDER BY pos', array($ind));
	}	
	/**
	 * Удалить категорию
	 *
	 * @param unknown_type $ind
	 */
	static function delete($ind)
	{
		global $db;
		$parent_ind = $db->getOne('SELECT parent_ind FROM ?_shop_category WHERE ind = ?', array($ind));
		$db->sql_query('DELETE FROM ?_shop_category WHERE ind = ? ', array($ind));
		if ($parent_ind) {
			$db->sql_query('UPDATE ?_shop_category SET category_count =  category_count - 1 WHERE ind = ?', array($parent_ind));
		}
		include_once(Nertz::class_path('Nertz_Shop_Item'));
		Nertz_Shop_Item::delete_by_category($ind);
		$this->_clear_cache();
		return $parent_ind;
	}
	static function lookup($parent_ind, $name)
	{
		if (!isset($GLOBALS['cache_nsc_names']) || !isset($GLOBALS['cache_nsc_names'][$parent_ind])) {
			global $db;
			$arr = $db->getAssoc('SELECT ind, name FROM ?_shop_category WHERE parent_ind = ?', array($parent_ind));
			$res = array();
			foreach($arr as $ind => $n) {
				$res[strtolower($n)] = $ind;
			}
			$GLOBALS['cache_nsc_names'][$parent_ind] = $res;
		}
		if (isset($GLOBALS['cache_nsc_names'][$parent_ind][strtolower($name)])) {
			return $GLOBALS['cache_nsc_names'][$parent_ind][strtolower($name)];
		} else {
			$ind = $this->add(array('parent_ind' => $parent_ind, 'name' => $name));
			$GLOBALS['cache_nsc_names'][$parent_ind][strtolower($name)] = $ind;
			return $ind;
		}
	}
	static function fetch($use_thumbs = false)
	{
		 global $db;
		 if (!isset($GLOBALS['cache_nsc_all_' . intval($use_thumbs)])) {
		 	$s = $use_thumbs ? 'c.thumb thumb,' : '';
		 	$GLOBALS['cache_nsc_all_' . intval($use_thumbs)] = $db->getAssoc("
		 		SELECT 
		 			c.ind ind, 
		 			{$s}
		 			c.parent_ind parent_ind,  
		 			c.name name, 
		 			COUNT(c1.ind) category_count, 
		 			c.item_count item_count, 
		 			c.margin margin,
		 			c.pos pos 
		 		FROM ?_shop_category c 
		 		LEFT JOIN ?_shop_category c1 ON c1.parent_ind = c.ind
		 		GROUP BY c.ind
		 		ORDER BY parent_ind, pos");
		 };
		 return $GLOBALS['cache_nsc_all_' . intval($use_thumbs)];
	}
	static function get_path($category_ind, $use_thumbs = false)
	{
		$items = Nertz_Shop_Category::fetch($use_thumbs);
		$category_path = array();
		$id = $category_ind;
		if ($id) {
			while(isset($items[$id])) {
				if($use_thumbs) {
					$category_path[$items[$id]['thumb']] = $items[$id]['name']; 
				} else {
					$category_path[$id] = $items[$id]['name']; 
				}
				$id = $items[$id]['parent_ind'];
			}
		}
		$category_path = array_reverse($category_path,1);
		return $category_path;
	}
	static function fetch_branch($category_ind = 0)
	{

	}
	static function fetch_link($vendor_ind)
	{
		global $db;
		return $db->getAssoc('SELECT vendor_category_ind, category_ind, by_supl FROM ?_shop_category_link WHERE vendor_ind = ? ', array($vendor_ind));
	}
	/**
	 * Привязать категорию поставщика к категории магазина
	 *
	 * @param int $vendor_ind Индекс поставщика
	 * @param string $vendor_category_ind Индекс категории поставщика
	 * @param int $shop_category_ind Индекс категории магазина
	 * @param bool $bu_supl Хитрая связь по поставщику для ланита
	 */
	function link( $vendor_ind, $vendor_category_ind, $shop_category_ind, $by_supl=0)
	{
		if (!$vendor_ind || !$vendor_category_ind || !$shop_category_ind) {
			return;
		}
		global $db;
		$this->unlink($vendor_ind, $vendor_category_ind);
		return $db->save('shop_category_link', array('category_ind' => $shop_category_ind, 'vendor_category_ind' => $vendor_category_ind, 'vendor_ind' => $vendor_ind, 'by_supl' => ($by_supl ? 1 : 0)));
	}
	/**
	 * Отвязать категорию поставщика от категории магазина
	 *
	 * @param int $vendor_ind Индекс поставщика
	 * @param string $vendor_category_ind Индекс категории поставщика
	 * @param int $shop_category_ind Индекс категории магазина
	 */
	static function unlink( $vendor_ind, $vendor_category_ind)
	{
		if (!$vendor_ind || !$vendor_category_ind) {
			return;
		}
		global $db;
		return $db->sql_query('DELETE FROM ?_shop_category_link WHERE vendor_category_ind = ? AND vendor_ind = ?' , array($vendor_category_ind, $vendor_ind));
	}
	static function _clear_cache()
	{
		unset($GLOBALS['cache_nsc_all']);
		unset($GLOBALS['cache_nsc_names']);
	}
	/**
	 * Вычислить наценку для категории
	 *
	 * @param int $ind Индекс категории
	 */
	static function get_margin($ind)
	{
		$items = self::fetch();
		while ($ind && isset($items[$ind]) && isset($items[$ind]['parent_ind']) && abs($items[$ind]['margin']) < 0.0001) {
			 $ind = $items[$ind]['parent_ind'];
		}
		if ($ind && isset($items[$ind])) {
			return $items[$ind]['margin'];
		}
		return SHOP_MARGIN;
	}
}
