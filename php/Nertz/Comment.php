<?php
/**
 * Прозрачные комментарии для любой части сайта
 *
 */
class Nertz_Comment
{
	function Nertz_Comment($params = array())
	{
		if (empty($params['template'])) {
			$params['template'] = 'Comments';
		}
		$this->params = $params;
	}
	/**
	 * Показать список комменатриев и форму с комментариями
	 *
	 * @param unknown_type $page
	 * @param unknown_type $ind
	 */
	function show($page, $ind) 
	{
		global $template, $auth, $db, $url;
		if ($auth->is_admin() || $auth->is_moderator()) {
			$template->set_value('comment_admin', true);
				if ($comment_ind = $url->get_value('comment_edit', '')) {
					$template->set_value('comment_text', $db->getOne('SELECT body FROM ?_comments WHERE ind = ?', array($comment_ind)));
					$template->set_value('comment_comment_ind', $comment_ind);
				}
		}
		$add_url = isset($this->params['add_url']) ? $this->params['add_url'] : '';
		$template->set_value('logged_in', $auth->logged_in());
		$template->set_value('comment_page', $page);
		$template->set_value('comment_ind', $ind);
		$template->set_value('comment_add_url', $add_url);
		$hide_list = !empty($this->params['hide_list']) ? $this->params['hide_list'] : 0;
		$template->set_value('hide_list', $hide_list);
		$template->set_value('hide_form', !empty($this->params['hide_form']) ? $this->params['hide_form'] : 0);
		if (!$hide_list) {
			$u = $url->get_url();
			if ($add_url) {
				$u['add_url'] = $add_url;
			}
			$comments = $db->getAll('SELECT 
				c.*, 
				u.fio fio, 
				u.login login, 
				u.image_ind 
			FROM ?_comments c
			LEFT JOIN ?_user u ON u.ind = c.user_ind
			WHERE c.page = ? AND c.item_ind = ? AND c.add_url = ? ORDER BY created DESC', array($page, $ind, $add_url));
			foreach ($comments as $i => $comment) {
				unset($u['comment_edit']);
				$u['comment_delete'] = $comment['ind'];
				$comments[$i]['del_url'] = $url->gen_url($u);
				
				unset($u['comment_delete']);
				$u['comment_edit'] = $comment['ind'];
				$comments[$i]['edit_url'] = $url->gen_url($u);
			}
			$template->set_value('comments', $comments);
		}
		return $template->render($this->params['template']);
	}
	function handle_comments()
	{
		global $url, $auth, $db, $session;
		
		if ($url->get_value('send_comment', '')) {
			$params = array();
			$params['body'] = $url->get_value('comment_text', '');
			if ($auth->logged_in()) {
				$params['user_ind'] = $auth->user_ind();
			} else {
				$params['name'] = $url->get_value('comment_name', '');
				$params['email'] = $url->get_value('comment_email', '');
				$code = $url->get_value('comment_capcha', '');
				$capcha = $session->get_value('captcha_keystring', '');
				if (!$code || !$capcha || $code != $capcha ) {
					return false;
				}
			}
			$page = $url->get_value('comment_page', '');
			$ind = $url->get_value('comment_ind', 0);
			if ($page && $ind) {
				$params['page'] = $page;
				$params['item_ind'] = $ind;
				$params['add_url'] = $url->get_value('comment_add_url', '');
				$a = array();
				$comment_ind = $url->get_value('comment_comment_ind', 0);
				if (!$auth->is_admin()) {
					$params['body'] = nl2br(strip_tags($params['body']));
				}
				if ($comment_ind && ($auth->is_admin() || $auth->is_moderator())) {
					$a = array('ind' => $comment_ind);
					$params = array('body' => $params['body']);
					$db->save('comments', $params, $a);
					$u = $url->get_url();
					unset($u['comment_edit']);
					Nertz::redirect($u);
				}
				$db->save('comments', $params, $a);
			}
		}
		if ($auth->is_admin() || $auth->is_moderator()) {
			if ($ind = $url->get_value('comment_delete', '')) {
				$db->sql_query('DELETE FROM ?_comments WHERE ind = ?', array($ind));
			}
		}
	}
	function get_last() 
	{
		global $template, $auth, $db;
		$comments = $db->getAll('SELECT c.*, u.fio fio, u.login login, u.image_ind
		FROM ?_comments c
		LEFT JOIN ?_user u ON u.ind = c.user_ind
		ORDER BY created DESC LIMIT 5');
		foreach ($comments as $i => $item) {
			$url_params = array();
			if (!empty($item['add_url'])) {
				$url_params = $this->parse_add_url($item['add_url']);
			}
			$url_params['page'] = $item['page'];
			$url_params['ind']  = $item['item_ind'];
			
			$comments[$i]['url_params'] = $url_params;
			$s = mb_strip_tags($item['body']);
			$comments[$i]['body'] = mb_str_limit($s, 100);
		}
		return $comments;
	}
	function parse_add_url($s)
	{
		if (is_array($s)) {
			return $s;
		}
		$res = array();
		$arr = explode('&', $s);
		if (is_array($arr) && count($arr)) {
			foreach ($arr as $item) {
				list($k, $v) = explode('=', $item);
				if ($k && $v) {
					$res[$k] = $v;
				}
			}
		}
		return $res;
	}
	
}