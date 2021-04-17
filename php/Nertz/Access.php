<?php

class Nertz_Access
{
	function Nertz_access()
	{
		
	}
	/**
	 * Получить все права на указанную ноду
	 *
	 * @param string/int $target Тип объекта название или индекс
	 * @param int $ind Индекс объекта
	 * @return array Массив прав вида [group_ind] => array('name'=> 'Имя группы', 'r'=> 'права на чтение','w' => 'права на запись', ...)
	 */
	function get($target, $ind, $data='')
	{
		global $db, $auth;
		if (!is_int($target)) {
			$target = Nertz::get_target($target);
		}
		include_once(Nertz::class_path('Nertz_User_Group'));
		$groups = Nertz_User_Group::get_all();
		$targets =  Nertz::get_target();
		if ($ind) {
			$access = $db->getAssoc('SELECT group_ind, r, w, c, m, o FROM ?_access WHERE target = ? AND ind = ?', array($target, $ind));	
		}
		if (is_array($data) && count($data)) {
			$access = $data;
		}
		$res = array();
		foreach ($groups as $ind => $name) {
			$res[$ind] = (isset($access[$ind]) && is_array($access[$ind])) ? $access[$ind] : array();
			$res[$ind]['name'] = $name;
		}
		return $res;
	}
	/**
	 * Enter description here...
	 *
	 * @param string/int $target Тип объекта название или индекс
	 * @param int $ind Индекс объекта
	 * @param bool $no_del Не удалять права перед их добавлением 
	 * @param array $data Массив прав вида [group_ind] => array('r'=> 'права на чтение','w' => 'права на запись', ...)
	 */
	function set($target, $ind, $data, $no_del = false)
	{
		global $db;
		if (!is_int($target)) {
			$target = Nertz::get_target($target);
		}
		if (!$no_del) {
			$db->sql_query('DELETE FROM ?_access WHERE target = ? AND ind = ?', array($target, $ind));
		}
		foreach ($data as $group_ind => $d) {
			$arr = array();
			$ds = array('r','w','c','m','o');
			$all_nulls = 1; // Флаг того что в правах все нули, и эта запись в БД не заносим.
			foreach ($ds as $s) {
				$r = (isset($d[$s]) && $d[$s]) ? 1 : 0;
				$arr[$s] = $r;
				if ($r) {
					$all_nulls = 0;
				}
			}
			if (!$all_nulls) {
				$arr['target'] = $target;
				$arr['ind'] = $ind;
				$arr['group_ind'] = $group_ind;
				$db->save('access',$arr);
			}
		}
	}
}