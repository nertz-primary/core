<?php

include_once(Nertz::class_path('Nertz_User_Group'));

class Nertz_Auth
{
	function Nertz_Auth($user_class)
	{
		include_once(Nertz::class_path($user_class));
		$this->user = new $user_class();
		$groups = $this->get_groups();
		$this->anonymous_ind = $groups['Анонимы'];
	}
	function login($login, $pass, $ind = 0)
	{
		global $session;
		$user = $this->user->get_by_login($login);
		if (!(is_array($user) && count($user))) {
			return "Пользователь не найден!";
		}
		if (!$this->check_hash($user['pass'], $login, $pass)) {
			return "Неправильный пароль!";
		}
		$session->set_value('user', $user);
		return true;
	}
	function logout()
	{
		global $session;
		$session->unset_value('user');
	}
	function logged_in()
	{
		global $session;
		$v = $session->get_value('user', "");
		if ($v) {
			return true;
		}
		return false;
	}
	function user_info()
	{
		global $session;
		return $session->get_value('user', array('ind' => 0, 'groups' => array($this->anonymous_ind)));
	}
	function hash($login, $pass)
	{
		//$s = '1' . md5($pass . md5($login));
		//return $s;
		return strval($pass);
	}
	/**
	 * Функция проверки хэша пароля юзера
	 * Собственно сделано для того, если захотим поменять хэши пользовтелей, чтобы старыне не сломались
	 * В данном случае у нас 0-й эллемент строки всегда содержит 1, что дает ему первую версию
	 * @param string $hash Собственно ХЭШ
	 * @param string $login Логин юзера
	 * @param string $pass Пароль юзера
	 * @return unknown
	 */
	function check_hash($hash, $login, $pass)
	{
		if (strcmp($hash, $this->hash($login,$pass)) === 0) {
			return true;
		}
		return false;
	}
	/**
	 * Проверяет текущую страницу на ее наличие и наличие прав доступа к ней
	 *
	 */
	function validate_page()
	{
		// Если скрипт запущен из консоли то забъем на проверки
		if (Nertz::in_cmd()) {
			return true;
		}
		global $url;
		$page = $url->get_page();
		$pages = Nertz::config('/pages');
		$default_page = Nertz::config('/main/default_page','default');
		if (!$page || !isset($pages[$page]) || !isset($pages[$page]['groups']) ||  !is_array($pages[$page]['groups'])) {
			$url->set_page($default_page);
			return;
		}
		$page_groups = $pages[$page]['groups'];
		$has_access = false;
		$user = $this->user_info();
		if (isset($user['groups']) && is_array($user['groups'])) {
			foreach ($page_groups as $grp) {
				if (in_array(intval($grp), $user['groups'])) {

					$has_access = true;
				}
			}
		}
		if (!$has_access) {
			$login_page = Nertz::config('/main/login_page', $default_page);
			if (!isset($pages[$login_page])) {
				$login_page = $default_page;
			}
			global $session;
			$session->set_value('login_url', $url->get_url());
			$url->set_page($login_page);
		}
	}
	/**
	 * Получить уникальный идентификатор прав пользователя
	 *
	 */
	function get_digest($separator='_')
	{
		$ui = $this->user_info();
		settype($ui['groups'], 'array');
		asort($ui['groups']);
		return implode($separator, $ui['groups']);
	}
	function is_moderator()
	{
		$groups = $this->get_groups();
		$ui = $this->user_info();
		return in_array($groups['Модераторы'],$ui['groups']);
	}
	function is_admin()
	{
		$groups = $this->get_groups();
		$ui = $this->user_info();
		if (empty($ui['groups'])) {
			return false;
		}
		return in_array($groups['Администраторы'], $ui['groups']);
	}
	function get_groups() {
		include_once(Nertz::class_path('Nertz_User_Group'));
		return array_flip(Nertz_User_Group::get_all());
	}
	function reload()
	{
		global $session;
		if ($this->logged_in()) {
			$user = $session->get_value('user');
			if (isset($user['ind'])) {
				$user = $this->user->get($user['ind']);
				return $session->set_value('user', $user);
			}
		}
	}
	function user_ind()
	{
		global $session;
		$v = $session->get_value('user', "");
		if ($v && !empty($v['ind'])) {
			return $v['ind'];
		}
		return 0;
	}
	function login_by_ind($ind)
	{
		global $session;
		$user = $this->user->get($ind);
		$session->set_value('user', $user);
		return true;
	}
}