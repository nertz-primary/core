<?php
include_once(Nertz::class_path('Nertz_Shop_Item'));
/**
 * Класс корзины магазина
 * Все хранится в куках
 *
 */
class Nertz_Shop_Cart
{
	/**
	 * Данные корзины
	 */
	public $data = array();
	/**
	 * Конструктор
	 *
	 * @return Nertz_Shop_Cart
	 */
	function Nertz_Shop_Cart()
	{
		if (isset($_COOKIE['cart'])) {
			$this->data = unserialize(base64_decode($_COOKIE['cart']));
		}
		if (!isset($this->data['i'])) {
			$this->data['i'] = array();
		}
	}
	/**
	 * Добавить товар в корзину
	 *
	 * @param int $ind Индекс товара
	 */
	function add_item($ind)
	{
		if (isset($this->data['i'][$ind])) {
			$this->data['i'][$ind]['count']++;
		} else {
			$this->data['i'][$ind] = array('count' => 1, 'cost' => 0.0);
		}
		$this->__save();
	}
	/**
	 * Удалить товар из корзины
	 *
	 * @param int $ind Индекс товара
	 */
	function delete_item($ind)
	{
		if (isset($this->data['i'][$ind])) {
			unset($this->data['i'][$ind]);
		}
		$this->__save();
	}
	/**
	 * Очистить корзину
	 *
	 */
	function clear()
	{
		$this->data['i'] = array();
		$this->__save();
	}
	/**
	 * Получить список товаров корзины
	 *
	 */
	function fetch()
	{
		if (count($this->data['i'])) {
			$items = Nertz_Shop_Item::get(array_keys($this->data['i']));
			foreach ($this->data['i'] as $ind => $item) {
				$items[$ind]['cart_count'] = $item['count'];
				$items[$ind]['sum'] = $items[$ind]['cost'] * $item['count'];
			}
			return $items;
		}
		return array();
	}
	/**
	 * Обновить количества товаров в корзине
	 *
	 * @param assoc $values Массив количеств товаров вида 'индекс товара' => 'количество товара' 
	 */
	function update_counts($values) 
	{
		if (is_array($values)) {
			foreach($values as $ind => $count) {
				$count = abs(intval($count));
				if (!$count) {
					//unset($this->data['i'][$ind]);
					//$this->data['i'][$ind] = 0;
				} else {
					$this->data['i'][$ind]['count'] = $count;
				}
			}
		}
		$this->__save();
	}
	/**
	 * Получить список товаров для размаещения заказа
	 *
	 * @return assoc Массив вида 'индекс товара' => 'количество товара'
	 */
	function get_items()
	{
		return $this->data['i'];
	}
	/**
	 * Получить статистику по корзине
	 *
	 */
	function get_summary()
	{
		return array('count'=> count($this->data['i']), 'sum' => (isset($this->data['s']) ? $this->data['s'] : 0));
	}
	/**
	 * Сохранить данные корзины (Не использовать, все происходит автоматом)
	 *
	 */
	function __save()
	{
		$this->data['s'] = 0.0;
		if (count($this->data['i'])) {
			$items = Nertz_Shop_Item::get(array_keys($this->data['i']));
			foreach ($this->data['i'] as $ind => $item) {
				if (isset($items[$ind])) {
					$this->data['s'] += $items[$ind]['cost'] * $item['count'];
					$this->data['i'][$ind]['cost'] = $items[$ind]['cost'];
				} else {
					unset($this->data['i'][$ind]);
				}
			}
		}
		setcookie('cart',base64_encode(serialize($this->data)), null, '/');
	}
}
