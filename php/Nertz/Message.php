<?php
/**
 * Класс личных сообщений внутри сайта
 * 
 */

class Nertz_Message
{
    /**
     * Индекс пользователя
     *
     * @var unknown_type
     */
	private $user_ind = 0;
	/**
     * 
     * Конструктор
     *
     * @param int $user_ind
     * @return Nertz_Message
     */
	function Nertz_Message($user_ind = 0)
	{
		global $auth;
		if (!$user_ind) {
			$user_ind = $auth->user_ind();
		}
		$this->user_ind = $user_ind;
	}
	function fetch_inbox(&$p)
	{
		global $db;
		$items = $db->getPagedOld('
		SELECT 
			m.ind ind, 
			m.title title, 
			m.`read` `read`, 
			m.created created, 
			u.login login, 
			u.ind user_ind 
		FROM message m 
		LEFT JOIN user u ON m.from_user_ind = u.ind 
		WHERE 
			m.to_user_ind =? 
			AND m.to_deleted = 0 
		ORDER BY created DESC', array($this->user_ind), $p, 10);
		foreach ($items['data'] as $i => $item) {
			if (!$item['title']) {
				$items['data'][$i]['title'] = 'Без темы';
			}
		}
		return $items;
	}
	function fetch_outbox(&$p)
	{
		global $db;
		$items = $db->getPagedOld('
		SELECT 
			m.ind ind, 
			m.title title, 
			m.`read` `read`, 
			m.created created, 
			u.login login, 
			u.ind user_ind 
		FROM message m 
		LEFT JOIN user u ON m.to_user_ind = u.ind 
		WHERE 
			m.from_user_ind =? AND 
			m.from_deleted = 0  
		ORDER BY created DESC', array($this->user_ind), $p, 10);
		foreach ($items['data'] as $i => $item) {
			if (!$item['title']) {
				$items['data'][$i]['title'] = 'Без темы';
			}
		}
		return $items;
		
	}
	function del_inbox($inds) 
	{
		global $db;
		return $db->sql_query('UPDATE ?_message SET to_deleted = 1 WHERE ind IN (?a) AND to_user_ind = ?', array($inds, $this->user_ind));
	}
	function del_outbox($inds) 
	{
		global $db;
		return $db->sql_query('UPDATE ?_message SET from_deleted = 1 WHERE ind IN (?a) AND from_user_ind = ?', array($inds, $this->user_ind));
	}
	function get_fio($user_ind)
	{
		global $db;
		$data = $db->getRow('SELECT first_name, last_name FROM ?_user WHERE ind = ?', array($user_ind));
		return $data['first_name'] . ' ' . $data['last_name'];
		
	}
	function send($item)
	{
		global $db;
		$data = array();
		$data['title'] = $item['title'];
		$data['body']  = strip_tags_except($item['body'], array('strong', 'em', 'p', 'img', 'strike', 'u', 'a','ul','ol','li', 'br', 'div'));
		$data['from_user_ind'] = $this->user_ind;
		$data['to_user_ind'] = $item['user_ind'];
		$db->save('message', $data);
	}
	function get($ind)
	{
		global $db;
		$data = $db->getRow('SELECT * FROM ?_message WHERE ind = ?', array($ind));
		if (!$data['title']) {
			$data['title'] = 'Без темы';
		}
		if ($data['from_user_ind'] == $this->user_ind || $data['to_user_ind'] == $this->user_ind) {
			return $data;
		}
		return false;
	}
	function delete($inds, $dir='from')
	{
		global $db;
		if ($dir!='from')  {
			$dir = 'to';
		}
		return $db->sql_query('UPDATE message SET ?#_deleted = 1 WHERE ind IN (?a) AND ?#_user_ind = ?', array($dir, $inds, $dir, $this->user_ind));
	}
	
}