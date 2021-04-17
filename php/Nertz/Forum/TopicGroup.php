<?php
include_once(Nertz::class_path('Nertz_Forum_User'));
include_once(Nertz::class_path('Nertz_Forum_Topic'));
include_once(Nertz::class_path('Nertz_Access'));

class Nertz_Forum_TopicGroup
{
	function add($params)
	{
		$fu = new Nertz_Forum_User();
		if ($fu->is_moderator())
		{
			$params['updated'] = time();
			unset($params['copy_rights']);
			$rights = $params['rights'];
			unset($params['rights']);

			global $db; 
			$ind =  $db->save('forum_topicgroup', $params);
			// Сохраним права
			Nertz_Access::set('forum_tg', $ind, $rights);
			return $ind;
		}
		
	}
	function update($ind, $params)
	{
		$fu = new Nertz_Forum_User();
		if ($fu->is_moderator())
		{
			// Сохраним права
			Nertz_Access::set('forum_tg',$ind,$params['rights']);
			// Скопируем если нужно права на все дочерние ноды
			if ($params['copy_rights'])
			{
				$t = new  Nertz_Forum_Topic();
				$p = array();
				forum_copy_rights($p, $params);
				$t->inherit_topicgroup_rights($ind, $p['rights']);
			}
			unset($params['copy_rights']);
			unset($params['rights']);			
			
			global $db;
			return $db->save('forum_topicgroup', $params, array('ind' => $ind), true);
		}
	}
	function drop($ind)
	{
		$fu = new Nertz_Forum_User();
		if ($fu->is_moderator())
		{
			global $db;
			return $db->sql_query('DELETE FROM ?_forum_topicgroup WHERE ind = ?', array($ind));
		}
	}
	function load_form(&$params, $ind = 0)
	{
		global $db; 
		$keys    = array('title' => 'TEXT','descr'  => 'TEXT', 'copy_rights' => 'BOOL');
		forum_add_rights_fields($keys);
		$error   = array();
		$params  = array_copy_checked($keys, $_REQUEST, $error);
		if (!isset($error['title']) && 
		$db->getOne('SELECT COUNT(*) FROM ?_forum_topicgroup WHERE title = ? AND ind <> ?', array($params['title'], $ind)))
		{
			$error['title']	= "Раздел с таким именем уже существует";
		}
		$params['rights'] = Nertz_Access::get('forum_tg', 0, $params['rights']);
		return $error;
	}
	function get_list()
	{
		global $db, $auth;
		$fu = new Nertz_Forum_User();
		
		return $db->getAll('SELECT 
		 tg.ind ind,  
		 tg.title title, 
		 tg.descr descr, 
		 tg.message_count message_count,
		 tg.topic_count topic_count,
		 tg.last_topic_ind last_topic_ind,' . 
		 (($fu->is_moderator()) ? '1 a_read, 1 a_write,' :'SUM(a.r) a_read, SUM(a.w) a_write,') 
		 . 't.title last_topic_title,
		 tg.last_user_ind last_user_ind,
		 u.login last_user_login,
		 tg.updated updated
		FROM ?_forum_topicgroup tg 
		LEFT JOIN ?_forum_topic t ON tg.last_topic_ind=t.ind 
		LEFT JOIN ?_user u ON tg.last_user_ind=u.ind ' .
		(($fu->is_moderator()) ? '' : ' LEFT JOIN ?_access a ON tg.ind = a.ind AND a.target = ' . Nertz::get_target('forum_tg') . ' AND group_ind IN (' . $auth->get_digest(',') . ')
		GROUP BY tg.ind
		HAVING a_read IS NOT NULL AND a_read > 0 ')
		. 'ORDER BY tg.ind		
		', array());
	}
	function get($ind)
	{
		global $db; 
		$item = $db->getRow('SELECT * FROM ?_forum_topicgroup WHERE ind = ?', array($ind));
		$item['rights'] = Nertz_Access::get('forum_tg', $ind);
		return $item;
	}
	function track_topic($topic_ind, $act, $ind=0, $user_ind='')
	{
		if ($user_ind === '') {
			$fu = new Nertz_Forum_User();
			$user_ind = $fu->get_ind();
		} 
		if ($ind === 0) {
			$ft = new Nertz_Forum_Topic();
			$data = $ft->get($topic_ind);
			$ind = $data['topicgroup_ind'];
		}
		
		global $db;
		$s = "";
		if ($act == 'add') {
			return $db->sql_query("UPDATE ?_forum_topicgroup SET last_user_ind = ?, last_topic_ind = ?, topic_count = topic_count + 1, updated = UNIX_TIMESTAMP()  WHERE ind =?", array($user_ind, $topic_ind, $ind));
			$s = " ";
		} elseif ($act == 'update') {
			return $db->sql_query("UPDATE ?_forum_topicgroup SET last_user_ind = ?, last_topic_ind = ?, updated = UNIX_TIMESTAMP() WHERE ind =?", array($user_ind, $topic_ind, $ind));
		} elseif ($act == 'delete') {
			return $db->sql_query("UPDATE ?_forum_topicgroup SET topic_count = topic_count - 1, message_count = message_count - ? WHERE ind =?", array(intval($user_ind),$ind));
		} elseif ($act == 'message_add') {
			return $db->sql_query("UPDATE ?_forum_topicgroup SET last_user_ind = ?, last_topic_ind = ?,  message_count = message_count + 1, updated = UNIX_TIMESTAMP() WHERE ind =?", array($user_ind, $topic_ind, $ind));
		} elseif ($act == 'message_delete') {
			return $db->sql_query("UPDATE ?_forum_topicgroup SET message_count = message_count - 1 WHERE ind =?", array($ind));
		}
	}
	
}