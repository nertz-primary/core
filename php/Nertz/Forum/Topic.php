<?php
include_once(Nertz::class_path('Nertz_Forum_User'));
include_once(Nertz::class_path('Nertz_Forum_TopicGroup'));
include_once(Nertz::class_path('Nertz_Forum_Message'));
		
class Nertz_Forum_Topic
{
	function add($params)
	{
		$fu = new Nertz_Forum_User();
		if(!isset($params['user_ind'])) {
			$params['user_ind'] = $fu->get_ind();
		}
		$tg = new Nertz_Forum_TopicGroup();
		$data = $tg->get($params['topicgroup_ind']);
		if ((!$params['user_ind'] && $data['a_write']) || $params['user_ind'] || $fu->is_moderator()) {
			global $db;
			$params['last_user_ind'] = $params['user_ind'];
			$params['updated']  = time();
			
			$rights = $params['rights'];
			unset($params['rights']);
			
			$ind = $db->save('forum_topic', $params);
			if ($fu->is_moderator()) {
				Nertz_Access::set('forum_topic', $ind, $rights);
			} else {
				Nertz_Access::set('forum_topic', $ind, $data['rights']);
			}
			
			
			$tg = new Nertz_Forum_TopicGroup();
			$tg->track_topic($ind, 'add', $params['topicgroup_ind'], $params['user_ind']);

			$fs = new Nertz_Forum_Subscription();
			$params['topic_ind'] = $ind;
			$fs->notify_subscribers('topic', $params);
	
			return $ind;
		}
		return 0;
	}
	function update($ind, $params)
	{
		$data = $this->get($ind);
		$fu = new Nertz_Forum_User();
		
		if (($fu->get_ind() && $fu->get_ind() == $data['user_ind']) || $fu->is_moderator()) {
			$tg = new Nertz_Forum_TopicGroup();
			$tg->track_topic($ind, 'update');
			if ($fu->is_moderator()) {
				Nertz_Access::set('forum_topic', $ind, $params['rights']);
			}
			unset($params['rights']);
			global $db; 
			return $db->save('forum_topic', $params, array('ind' => $ind));
		}
		return 0;
	}
	function drop($ind)
	{
		$data = $this->get($ind);
		$fu = new Nertz_Forum_User();

		if (($fu->get_ind() && $fu->get_ind() == $data['user_ind']) || $fu->is_moderator()) {
			$tg = new Nertz_Forum_TopicGroup();
			$item = $this->get($ind);
			$tg->track_topic($ind, 'delete', $item['topicgroup_ind'], $item['message_count'] );
			$fs = new Nertz_Forum_Subscription();
			$fs->delete_topic_subscriptions($ind);
			$m = new Nertz_Forum_Message();
			$m->drop_topic_messages($ind);
			global $db;
			return $db->sql_query('DELETE FROM ?_forum_topic WHERE ind = ?', array($ind));
		}
		return 0;
	}
	function load_form(&$params, $ind = 0)
	{
		global $db; 
		$keys = array('title' => 'TEXT', 'topicgroup_ind' => 'INT', 'body' => 'HTML');
		$fu = new Nertz_Forum_User();
		if ($fu->is_moderator()) {
			forum_add_rights_fields($keys);
		}
		$error   = array();
		$params  = array_copy_checked($keys, $_REQUEST, $error);
		if (!isset($error['title']) &&
		$db->getOne('SELECT COUNT(*) FROM ?_forum_topic WHERE title = ? AND topicgroup_ind = ? AND ind <> ?', array($params['title'], $params['topicgroup_ind'], $ind))) {
			$error['title']	= "Тема с таким именем уже существует";
		}
		$params['rights'] = Nertz_Access::get('forum_topic', 0, isset($params['rights']) ? $params['rights'] : array());
		return $error;
	}
	function get_list($topicgroup_ind)
	{
		global $db, $auth; 
		$fu = new Nertz_Forum_User();
		return $db->getAll('SELECT 
		 t.ind ind, 
		 t.title title, 
		 t.user_ind user_ind, 
		 t.message_count message_count, 
		 t.view_count view_count, 
		 t.updated updated, 
		 u.login last_user_login,'.  
		 (($fu->is_moderator()) ? '1 a_read, 1 a_write,' : 'SUM(a.r) a_read, SUM(a.w) a_write,')
		 . 't.last_user_ind last_user_ind 
		FROM ?_forum_topic t 
		LEFT JOIN ?_user u ON u.ind = t.last_user_ind' .   
		(($fu->is_moderator()) ? '' : ' LEFT JOIN ?_access a ON t.ind = a.ind AND a.target = ' . Nertz::get_target('forum_topic') . ' AND group_ind IN (' . $auth->get_digest(',') . ') ')
		. ' WHERE topicgroup_ind = ?' .
		(($fu->is_moderator()) ? '' : ' GROUP BY t.ind
		HAVING a_read IS NOT NULL AND a_read > 0 ')
		. 'ORDER BY t.updated DESC', array($topicgroup_ind));
	}
	function get($ind)
	{
		global $db, $auth; 
		$fu = new Nertz_Forum_User();
		$res = $db->getRow('
		SELECT 
		 t.ind ind, 
		 t.topicgroup_ind topicgroup_ind,
		 t.title title,
		 t.message_count message_count,
		 t.created created,
		 t.updated updated,
		 t.user_ind user_ind,
		 t.body body,'.  
		 (($fu->is_moderator()) ? '1 a_read, 1 a_write,' : 'SUM(a.r) a_read, SUM(a.w) a_write,')
		 . 'u.login user_login,
		 u.message_count user_message_count,
		 u.status user_status,
		 u.comment user_comment,
		 u.avatar_ind user_avatar
		FROM ?_forum_topic t 
		LEFT JOIN ?_user u ON t.user_ind = u.ind' .   
		(($fu->is_moderator()) ? '' : ' LEFT JOIN ?_access a ON t.ind = a.ind AND a.target = ' . Nertz::get_target('forum_topic') . ' AND group_ind IN (' . $auth->get_digest(',') . ') ')
		. ' WHERE t.ind = ?' .
		(($fu->is_moderator()) ? '' : ' GROUP BY t.ind')
		, array($ind));
		$res['avatar'] = Nertz_Forum_User::get_avatar_path($res['user_avatar']);
		$res['rights'] = Nertz_Access::get('forum_topic',$ind);
		return $res;
	}
	
	function track_message($message_ind, $act, $ind=0, $user_ind='')
	{
		if ($user_ind === '') {
			$fu = new Nertz_Forum_User();
			$user_ind = $fu->get_ind();
		}
		if ($ind === 0) {
			$fm = new Nertz_Forum_Message();
			$data = $fm->get($message_ind);
			$ind = $data['topic_ind'];
		}
		global $db;
		$s = "";
		if ($act == 'add') {
			$tg = new Nertz_Forum_TopicGroup();
			$tg->track_topic($ind, 'message_add', 0,  $user_ind);
			return $db->sql_query("UPDATE ?_forum_topic SET last_user_ind = ?, last_message_ind = ? , message_count = message_count + 1, updated = UNIX_TIMESTAMP() WHERE ind =?", array($user_ind, $message_ind, $ind));
			$s = " ";
		} elseif ($act == 'update') {
			return $db->sql_query("UPDATE ?_forum_topic SET last_user_ind = ?, last_message_ind = ?, updated = UNIX_TIMESTAMP() WHERE ind = ?", array($user_ind, $message_ind, $ind));
		} elseif ($act == 'delete') {
			$tg = new Nertz_Forum_TopicGroup();
			$tg->track_topic($ind, 'message_delete', 0, $user_ind);
			return $db->sql_query("UPDATE ?_forum_topic SET message_count = message_count - 1 WHERE ind = ?", array($ind));
		} elseif ($act == 'view') {
			return $db->sql_query("UPDATE ?_forum_topic SET view_count = view_count + 1 WHERE ind = ?", array($ind));
		}
	}
	function inherit_topicgroup_rights($topicgroup_ind, $params)
	{
		global $db;
		$tinds = $db->getCol('SELECT ind FROM ?_forum_topic WHERE topicgroup_ind = ?', array($topicgroup_ind));
		$db->sql_query('DELETE FROM ?_access WHERE target = ? AND ind IN(?a)', array(Nertz::get_target('forum_topic'), $tinds));
		foreach ($tinds as $ind) {
			Nertz_Access::set('forum_topic', $ind, $params, true);
		}
	}
	function get_user_topics($user_ind)
	{
		global $db, $auth; 
		$fu = new Nertz_Forum_User();
		
		return $db->getAll('SELECT 
		t.created me_created, 
		t.title title, 
		t.ind topic_ind, 
		t.created, t.body, ' .  
		(($fu->is_moderator()) ? '1 a_read, 1 a_write,' : 'SUM(a.r) a_read, SUM(a.w) a_write,')
		. ' t.topicgroup_ind, 
		u.login FROM ?_forum_topic t 
		LEFT JOIN ?_user u ON u.ind = t.user_ind' .   
		(($fu->is_moderator()) ? '' : ' LEFT JOIN ?_access a ON t.ind = a.ind AND a.target = ' . Nertz::get_target('forum_topic') . ' AND group_ind IN (' . $auth->get_digest(',') . ') ')
		. ' WHERE t.user_ind = ?' .
		(($fu->is_moderator()) ? '' : ' GROUP BY t.ind 
		HAVING a_read IS NOT NULL AND a_read > 0 ') . 
		' ORDER BY t.created DESC', 
		array($user_ind));
	}
}