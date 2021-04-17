<?php
include_once(Nertz::class_path("Nertz_Forum_Topic"));
include_once(Nertz::class_path("Nertz_Forum_Message"));
include_once(Nertz::class_path("Nertz_User"));
include_once(Nertz::class_path("Nertz_Store"));
class Nertz_Forum_User
{
	function Nertz_Forum_User()
	{
		$this->data = "";
	}
	function get_ind()
	{
		$data = $this->get_data();
		if (!$data) {
			return 0;
		}
		return $data['ind'];
	}
	function is_moderator()
	{
		global $auth;
		return ($auth->is_moderator() | $auth->is_admin());
	}
	function is_admin()
	{
		global $auth;
		return $auth->is_admin();
	}
	function track_message($user_ind, $cnt)
	{
		global $db;
		return $db->sql_query("UPDATE ?_user SET message_count = message_count + ? WHERE ind = ?", array($cnt, $user_ind));
	}
	function get_data( $user_ind='' )
	{
		global $db;
		global $auth;
		$res = false;
		if ($user_ind === '') {
			$res = $auth->user_info();
		} else {
			if ($user_ind) {
				$res = Nertz_User::get($user_ind);
			} 
		}
		if (isset($res['groups'])) {
			$res['status'] = Nertz_Forum_User::get_status($res['groups']);
		}
		$res['avatar'] = Nertz_Forum_User::get_avatar_path(isset($res['avatar_ind']) ? $res['avatar_ind'] : 0);
		return $res;
	}
	
	function get_top_messagers( $cnt = 4 )
	{
		global $db;
		$res = $db->getAll("SELECT ind, login, avatar_ind, message_count FROM ?_user ORDER BY message_count DESC LIMIT " . intval($cnt));
		foreach ($res as $id => $value) {
			$res[$id]['avatar'] = Nertz_Forum_User::get_avatar_path($value['avatar_ind']);
		}
		return $res;
	}
	function get_messages_and_topics( $user_ind )
	{
		$ft = new Nertz_Forum_Topic();
		$topics = $ft->get_user_topics( $user_ind );
		
		$fm = new Nertz_Forum_Message();
		$messages = $fm->get_user_messages( $user_ind );
		$res = array_merge($messages, $topics);

		function cmp($a, $b)
		{
			return strcmp($b["me_created"], $a["me_created"]);
		}

		uasort($res, "cmp");
		return $res;
	}
	function get_status($groups) 
	{
		global $auth;
		$grp = $auth->get_groups();
		if (in_array($grp['Администраторы'], $groups)) {
			return 'Админиcтратор';
		}
		if (in_array($grp['Модераторы'], $groups)) {
			return 'Модератор';
		}
		if (in_array($grp['Пользователи'], $groups)) {
			return 'Пользователь';
		}
		return 'Аноним';
	}
	function save($params, $ind) 
	{
		global $db, $auth;
		unset($params['pass1']);
		if (!$params['pass']) {
			unset($params['pass']);
		}
		if (!$params['avatar_ind']) {
			unset($params['avatar_ind']);
		}
		$db->save('user', $params, $ind ? array('ind' => $ind) : array());
		if (isset($params['avatar_ind']) && $params['avatar_ind']) {
			$img = new Nertz_Image();
			$img->load(Nertz_Store::gen_path($params['avatar_ind']));
			$img->make_smart_preview(AVATAR_WIDTH, AVATAR_HEIGTH);
			$img->save(Nertz_Store::gen_path($params['avatar_ind'], AVATAR_NAME));
		}
		$curren_user  = $auth->user_info();
		if ($ind == $curren_user['ind']) {
			$auth->reload();
		}
	}
	static function get_avatar_path($avatar_ind)
	{
		global $url;
		if ($avatar_ind) {
			return Nertz_Store::gen_path($avatar_ind, AVATAR_NAME);
		} else {
			return $url->gen_static_url(AVATAR_DEFAULT);
		}
	}
}