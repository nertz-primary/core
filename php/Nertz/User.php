<?php

include_once(Nertz::class_path('Nertz_User_Group'));

class Nertz_User
{
	function Nertz_User()
	{
		
	}
	static function add($params)
	{
		global $db;
		return $db->save('user', $params);
	}
	static function delete($id)
	{
		
	}
	static function update( $id, $params)
	{
		global $db;
		if(empty($params['pass'])) {
			unset($params['pass']);
		}
		unset($params['pass1']);
		return $db->save('user', $params,array('ind' => $id));
	}
	static function get($ind) 
	{
		global $auth, $db;
		$user = $db->getRow("SELECT * FROM ?_user WHERE ind = ?", array($ind));
		if (is_array($user) && count($user)) {
			$user_group = new Nertz_User_Group($ind);
			$user['groups']   = $user_group->fetch();
			if (!count($user['groups'])) {
				$user['groups'] = array($auth->anonymous_ind);
			}
		}
		$user = Nertz_Hook::apply_filters('user_after_get', $user);
		return $user;
	}
	/**
	 * Получить даннве пользователя по его логину
	 *
	 * @param string $login Логи пользователя
	 */
	static function get_by_login($login)
	{
		global $db, $auth;
		$user = $db->getRow("SELECT * FROM ?_user WHERE login = ?", array($login));
		if (is_array($user) && count($user)) {
			$user_group = new Nertz_User_Group($user['ind']);
			$user['groups']   = $user_group->fetch();
			if (!count($user['groups'])) {
				$user['groups'] = array($auth->anonymous_ind);
			}
		}
		$user = Nertz_Hook::apply_filters('user_after_get', $user);
		return $user;
		
	}
}