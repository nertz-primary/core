<?php
/**
 * Класс заказов магазина
 *
 */
include_once(Nertz::class_path('Nertz_Shop'));
include_once(Nertz::class_path('Nertz_Mail'));
include_once(Nertz::class_path('Page_Register'));
class Nertz_Shop_Order
{
	/**
	 * Добавить новую категорию
	 *
	 * @param assoc $items Массив вида 'индекс товара' => array( count => 'количество товара', cost => 'цена товара')
	 */
	static function add($items, $extra = false, $params = array())
	{
		global $db, $auth;
		$user = $auth->user_info();
		$order_ind = 0;
		$summ = 0.0;
		$cnt = 0;
		if (is_array($items) && count($items)) {
			$order = array(
				'user_ind' => !empty($params['user_ind']) ? $params['user_ind'] : $user['ind'],
				'curs'	   => Nertz_Shop::get_curs(), 
			);
			if (!empty($params['comment'])) {
				$order['comment'] = $params['comment'];
			}
			$order_ind = $db->save('shop_order', $order);
			if ($order_ind) {
				foreach($items as $ind => $item) {
					$order_item = array(
						'order_ind' => $order_ind,
						'item_ind'  => $ind,
						'cnt'       => $item['cart_count'],
						'cost'		=> !empty($item['cost_discount']) ? $item['cost_discount'] : $item['cost'],
					);
					if (!empty($params['extra_item_fields']) && is_array($params['extra_item_fields'])) {
						foreach ($params['extra_item_fields'] as $eif ) {
							$order_item[$eif] = $item[$eif];
						}
					}
					if (isset($item['original_cost'])) {
						$order_item['original_cost'] = $item['original_cost'];
					}
					if (isset($item['action'])) {
						$order_item['action'] = $item['action'];
					}
					$summ += !empty($item['sum']) ? $item['sum'] : 0;
					$cnt++;
					$db->save('shop_order_item', $order_item);
				}				
			}
		}
		if (empty($params['no_mail']) && $order_ind) {
			// Разошлем всем модераторам уведомления о новом заказе
			$groups = $auth->get_groups();
			$mail = new Nertz_Mail();
			$emails = $db->getCol('SELECT DISTINCT email FROM ?_user u INNER JOIN ?_user_group ug ON ug.user_ind = u.ind AND ug.group_ind = ?', array($groups['Модераторы']));
			if (!empty($user['email'])) {
				$emails[] = $user['email'];
			}
			$mail->send(
				$emails,
				Nertz::server_name().': Новый заказ',
				'Email_Shop_NewOrder',
				array('items' => $items, 'summ' => $summ, 'cnt' => $cnt, 'extra' => $extra, 'order_ind' => $order_ind));
		}
		return $order_ind;
	}
	/**
	 * Получить информацию о заказе
	 *
	 * @param int $ind Индекс заказа
	 */
	function get($ind) 
	{
		global $db;
		return $db->getRow('SELECT * FROM ?_shop_order WHERE ind = ? ', array($ind));
	}
}
