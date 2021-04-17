<?php
include_once(Nertz::class_path('Nertz_Forum_User'));
include_once(Nertz::class_path('Nertz_Forum_Topic'));

class Nertz_Forum_Message
{
	function add($params)
	{
		$fu = new Nertz_Forum_User();		
		if (!isset($params['user_ind']))
		{
			$params['user_ind'] = $fu->get_ind();
		}
		
		$t = new Nertz_Forum_Topic();
		$data = $t->get($params['topic_ind']);
		if ((!$params['user_ind'] && $data['a_write']) || $params['user_ind'] || $fu->is_moderator())
		{
			$fu->track_message($params['user_ind'], 1);
			global $db;
			$params['updated'] = time();
			$params['created'] = time();
			$ind = $db->save('forum_message', $params);

			$t = new Nertz_Forum_Topic();
			$t->track_message($ind, 'add', $params['topic_ind'], $params['user_ind']);
			
			$fs = new Nertz_Forum_Subscription();
			$fs->notify_subscribers('message', $params);
			
			return $ind;
		}
		return 0;
	}
	function update($ind, $params)
	{
		$data = $this->get($ind);
		$fu = new Nertz_Forum_User();

		if (($fu->get_ind() && $fu->get_ind() == $data['user_ind']) || $fu->is_moderator())
		{
			$t = new Nertz_Forum_Topic();
			$params['updated'] = time();
			$t->track_message($params['topic_ind'], 'update');

			global $db;
			return $db->save('forum_message', $params, array('ind' => $ind));
		}
		return 0;
	}
	function drop($ind)
	{
		$data = $this->get($ind);
		$fu   = new Nertz_Forum_User();
		if (($fu->get_ind() && $fu->get_ind() == $data['user_ind']) || $fu->is_moderator())
		{
			$user_ind = $fu->get_ind();
			$fu->track_message($user_ind, -1);

			$t = new Nertz_Forum_Topic();
			$t->track_message($ind, 'delete',0,$user_ind);

			global $db;
			return $db->sql_query('DELETE FROM ?_forum_message WHERE ind = ?', array($ind));
		}
		return 0;
	}
	function load_form(&$params, $ind = 0)
	{
		global $db; 
		$keys    = array('topic_ind' => 'INT', 'body' => 'HTML');
		$error   = array();
		$params  = array_copy_checked($keys, $_REQUEST, $error);
		return $error;
	}
	function get_list($topic_ind)
	{
		$t = new Nertz_Forum_Topic();
		$t->track_message(0, 'view', $topic_ind, 1);
		global $db; 
		$res = $db->getAll('SELECT 
		 m.ind ind, 
		 m.created created, 
		 m.body body,
		 u.login user_login,
		 u.message_count user_message_count,
		 m.user_ind user_ind,
		 u.status user_status,
		 u.comment user_comment,
		 u.avatar_ind user_avatar
		FROM ?_forum_message m 
		LEFT JOIN ?_user u ON m.user_ind = u.ind
		WHERE topic_ind = ? ORDER BY m.created', array($topic_ind));
		foreach ($res as $id => $value) {
			$res[$id]['avatar'] = Nertz_Forum_User::get_avatar_path($value['user_avatar']);
		}
		return $res;
	}
	function get($ind)
	{
		global $db; 
		return $db->getRow('SELECT * FROM ?_forum_message WHERE ind = ?', array($ind));
	}
	function drop_topic_messages($topic_ind)
	{
		global $db;
		$umc = $db->getAssoc("SELECT user_ind, COUNT(*) cnt FROM ?_forum_message  WHERE topic_ind = ? GROUP BY user_ind", array($topic_ind));
		if (is_array($umc))
		{
			$fu = new Nertz_Forum_User();
			foreach ($umc as $user_ind => $cnt)
			{
				if ($cnt)
				{
					$fu->track_message($user_ind, $cnt * -1);
				}
			}
		}
		return $db->sql_query('DELETE FROM ?_forum_message WHERE topic_ind = ?', array($topic_ind));
	}
	function get_user_messages($user_ind)
	{
		global $db, $auth; 
		$fu = new Nertz_Forum_User();
		
		return $db->getAll('SELECT 
		m.created me_created, 
		t.title title, 
		m.topic_ind topic_ind, 
		t.created created,
		m.body body, ' .  
		(($fu->is_moderator()) ? '1 a_read, 1 a_write,' : 'SUM(a.r) a_read, SUM(a.w) a_write,')
		. ' t.topicgroup_ind topicgroup_ind, 
		u.login 
		FROM ?_forum_message m 
		LEFT JOIN ?_forum_topic t ON m.topic_ind = t.ind 
		LEFT JOIN ?_user u ON t.user_ind = u.ind' .   
		(($fu->is_moderator()) ? '' : ' LEFT JOIN ?_access a ON t.ind = a.ind AND a.target = ' . Nertz::get_target('forum_topic') . ' AND group_ind IN (' . $auth->get_digest(',') . ') ')
		. ' WHERE m.user_ind = ?' .
		(($fu->is_moderator()) ? '' : ' GROUP BY t.ind 
		HAVING a_read IS NOT NULL AND a_read > 0 ') . 
		' ORDER BY m.created DESC', array($user_ind));
	}
}