<?php
/**
 * Страница редактора структуры конфигурационных файлов
 *
 */

class Page_Article extends Nertz_Page 
{
    function Page_Article($name)
    {
        parent::Nertz_Page($name);
    }
    function show()
    {
        global $template, $url, $db, $auth, $session;
        $this->type = $url->get_page();
        $this->ind = $url->get_value('ind', 0);
    	$this->type_info = Nertz::config('/articles/' .$this->type . '/');
    	$ui = $auth->user_info();
    	$act = $url->get_value('act','');
    	$comment_ind = $url->get_value('comment_ind','');
    	$is_moderator = $auth->is_moderator() | $auth->is_admin();
    	if ($this->ind) {
    		if ($url->get_value('send_comment', '')) {
    			$data = array();
    			$data['body'] = strip_tags($url->get_value('comment',''));
    			if ($auth->logged_in()) {
    				$data['user_ind'] = $ui['ind'];
    				$data['name']     = $ui['login'];
    				$data['www']      = '';
    				$data['email']    = ''; 
    			} else {
    				Nertz::dump($session->get_value('captcha_keystring') . ' == ' . $url->get_value('keystring',''));
    				if ($session->get_value('captcha_keystring') == $url->get_value('keystring','') && $session->get_value('captcha_keystring')) {
    					$data['user_ind'] = 0;
    					$data['name']     = strip_tags($url->get_value('name',''));
    					$data['www']      = strip_tags($url->get_value('www',''));
    					$data['email']    = strip_tags($url->get_value('email',''));
					}
					unset($_SESSION['captcha_keystring']);
    			}
    			if ($data['body'] && $data['name']) {
    				$data['article_ind'] = $this->ind;
    				$db->save('article_comment', $data);	
    			}
    		} else if( $act == 'delete' && $comment_ind && $is_moderator) {
    			$db->sql_query('DELETE FROM ?_article_comment WHERE ind = ?', array($comment_ind));
    		}
    		$item = $db->getRow('SELECT * FROM ?_article WHERE ind = ?', array($this->ind));
    		$comments = $db->getAll('SELECT * FROM ?_article_comment WHERE article_ind = ?', array($this->ind));
    		
    		if ($act == 'edit' && $comment_ind) {
    			$comment_item = $db->getRow('SELECT * FROM ?_article_comment WHERE comment_ind = ?', array($comment_ind));
    			$template->set_value('comment_item',  $comment_item);
    		}
    		$template->set_value('current_user',  $ui);
    		$template->set_value('is_moderator',  $is_moderator);
    		$template->set_value('item',  $item);
    		$template->set_value('ind',  $this->ind);
    		$template->set_value('comments',  $comments);
    		return $template->render('Page_ArticleOne');
    	} else {
    		$items = $db->getPaged('SELECT ind, caption, created, short_body FROM ?_article WHERE type = ? ORDER BY created DESC', array($this->type), $url->get_value('p',0),10);
    		$items['url'] = array('page' => $url->get_page());
    		$template->set_value('title',  $this->type_info['caption']);
    		$template->set_value('items',  $items);
    		return $template->render('Page_Article');
    	}	
    }
}
