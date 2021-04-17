<?php
include_once(Nertz::class_path('Nertz_Forum_TopicGroup'));
include_once(Nertz::class_path('Nertz_Forum_Topic'));
include_once(Nertz::class_path('Nertz_Forum_Message'));
include_once(Nertz::class_path('Nertz_Mail'));
class Nertz_Forum_Subscription
{
	function get_status($type, $ind, $user_ind)
	{
		$status = 0;
		if ($type == 'topicgroup' || $type == 'topic')
		{
			global $db;
			if ($db->getOne("SELECT COUNT(*) FROM ?_forum_subscription WHERE `{$type}_ind` = ? AND user_ind =?", array($ind, $user_ind))>0) {
				$status = 1;
			}
		}
		return $status;
		
	}
	function get_subscribers($type, $ind)
	{
		$res = array();
		if ($type == 'topicgroup' || $type == 'topic') {
			global $db;
			$fu = new Nertz_Forum_User();
			$res = $db->getAll("SELECT u.login login, u.ind ind, u.email email FROM ?_forum_subscription s LEFT JOIN ?_user u ON u.ind = s.user_ind WHERE `{$type}_ind` = ? AND user_ind <> ?", array($ind, $fu->get_ind()));
		}
		return $res;
	}
	function set($type, $ind, $user_ind, $status)
	{
		global $db;
		if ($type == 'topicgroup' || $type == 'topic') {
			if ($status) {
				if (!$this->get_status($type, $ind, $user_ind)) {
					$params = array();
					$params[$type ."_ind"] = $ind;
					$params['user_ind']    = $user_ind;
					return $db->save('forum_subscription', $params, "", true);
				}
			} else {
				return $db->sql_query("DELETE FROM ?_forum_subscription WHERE `{$type}_ind` = ? AND user_ind = ?", array($ind, $user_ind));
			}
		}
	}
	function delete_topic_subscriptions($topic_ind)
	{
		global $db;
		$db->sql_query("DELETE FROM ?_forum_subscription WHERE topic_ind = ? ", array($topic_ind));
	}
	function notify_subscribers($type, $params)
	{
		global $smarty;		
		$mail_params = array();
		if ($type == 'message') {
			$users = $this->get_subscribers('topic', $params['topic_ind']);	
		} elseif ($type == 'topic') {
			$users = $this->get_subscribers('topicgroup', $params['topicgroup_ind']);	
			$mail_params['topic_title'] = $params['title'];
			$tg = new Nertz_Forum_TopicGroup();
			$tg_params = $tg->get($params['topicgroup_ind']);
			$mail_params['topicgroup_title'] = $tg_params['title'];
		}
	
		if (is_array($users) && count($users)>0) {
			$t   = new Nertz_Forum_Topic();
			$p   = $t->get($params['topic_ind']);
			
			$fu   = new Nertz_Forum_User();
			$user = $fu->get_data($p['user_ind']);
			
			$mail_params['topic_title']   = $p['title'];
			$mail_params['now']           = time();
			$mail_params['topicgroup_ind'] = $p['topicgroup_ind'];
			$mail_params['topic_ind']      = $p['ind'];
			$mail_params['message_login'] = (isset($user['ind']) && $user['ind']) ? $user['login'] : "Аноним";
			$mail_users = array();
			foreach ($users as $u) {
				$mail_users[] = array(
				'email' => $u['email'],
				'fio'   => $u['login']
				);
			}
			if ($type == 'message') {
				$this->send_message('Новое сообщение на форуме', 'Email_Forum_NewMessage', $mail_users, $mail_params);
			} else {
				$this->send_message('Новая тема на форуме', 'Email_Forum_NewTopic', $mail_users, $mail_params);
			}
		}
	}
	function send_message($title, $template, $users, $params)
	{
		$mail = new Nertz_Mail();
		$params['server_name'] = Nertz::server_name();
		foreach ($users as $u) {
			$params['fio'] = $u['fio'];
			$email         = $u['email'];
		$mail->send(
			$email,
			Nertz::server_name() . ': ' . $title,
			$template,
			$params);
		}
/*
		global $smarty, $boundary;
		include_once(Nertz::class_path('site_inc_params.php');
		include_once('Mail.php');
		include_once('site/inc/_mail2.php');
		$mail = &Mail::factory('smtp', array( 'host' => GetParam('smtp'), 'auth' => false, 'username' => GetParam('smtp_login'), 'password' => GetParam('smtp_pass')));
		$html_builder = new MailClass();
		$boundary="=_".md5(uniqid(time()));
		$headrs['MIME-Version'] = "1.0";
		$headrs['Content-Type'] = "multipart/mixed; boundary=\"$boundary\"";
		$headrs['From']         = 'Stroysa Forum <'.GetParam('email').'>';
		$headrs['Subject']      = $title;
		foreach ($users as $u)
		{
			$params['fio'] = $u['fio'];
			$email         = $u['email'];
			
			$smarty->assign('params', $params);
			$html_builder->set_html($smarty->fetch($template));
			$html_builder->build_message('win');
			$headrs['To'] = $email;
			$email = trim($email);
			if (PEAR::isError($e = $mail->send( $email, $headrs, $html_builder->mime )))
			{
				mecho( $e->getMessage());
			}
		}
		*/
	}
}	