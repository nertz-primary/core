<?php
class Nertz_User_Group
{
	function Nertz_User_Group($user_ind)
	{
		$this->user_ind = $user_ind;
		$this->list = false;
	}

	/**
	 * Сохранение всех групп скопом
	 *
	 * @param array $groups Индесы групп назначаемых пользователю
	 */
	function save($groups)
	{
		global $db;
		$db->sql_query('DELETE FROM ?_user_group WHERE user_ind = ?', array($this->user_ind));
		$rows = array();
		foreach ($groups as $grp) {
			$rows[] = array($this->user_ind, $grp);
		}
		$db->mass_insert('user_group', array('user_ind','group_ind'), $rows);
	}
	/**
	 * Получение массива групп пользователя
	 *
	 */
	function fetch()
	{
		if ($this->list === false) {
			global $db;
			$this->list = $db->getCol('SELECT group_ind FROM ?_user_group WHERE user_ind = ?', array($this->user_ind));
			foreach ($this->list as $i => $item) {
				$this->list[$i] = intval($item);
			}
		}
		return $this->list;
	}
	static function get_all( $only_standart = false)
	{
		global $config;
		$groups = $config->get_value('/main/groups');
		$res = array();
		foreach ($groups as $id=> $group) {
			if ($only_standart) {
				if($id < 100) {
					$res[$id] = $group['caption'];
				}
			} else {
				$res[$id] = $group['caption'];
			}


		}
		return $res;
	}
}