<?
include_once(Nertz::class_path('Nertz_Forum_TopicGroup'));
include_once(Nertz::class_path('Nertz_Forum_Topic'));
include_once(Nertz::class_path('Nertz_Forum_Message'));
include_once(Nertz::class_path('Nertz_Forum_User'));
include_once(Nertz::class_path('Nertz_Forum_Subscription'));
include_once(Nertz::class_path('Nertz_Forum'));
include_once(Nertz::class_path('Nertz_Form'));
class Page_Nertz_Forum extends Nertz_Page 
{
	function show()
	{
		global $template, $url;
		$this->page =  $url->get_page();
		$fu = new Nertz_Forum_User();
		$template->set_value('user_ind', $fu->get_ind());
		$template->set_value('is_moderator', $fu->is_moderator());
		$form = $url->get_value('form', 'tg_list');
		$template->add_css('css/forum.css');
		$template->set_value('default_avatar', $url->gen_static_url(AVATAR_DEFAULT));
		switch($form) {
			case('tg_list'):
				return $this->renderTG();
				break;
			case('topic_list'):
				return $this->renderTopic();
				break;
			case('message_list'):
				return $this->renderMessage();
				break;
			case('profile'):
				return $this->renderProfile();
				break;
			case('register'):
				return $this->renderRegister();
				break;	
			case('login'):
				return $this->renderLogin();
				break;		
			case('rights'):
				return $this->renderRights();
				break;								
			case('um'):
				return $this->renderUm();
				break;					
			case('docs'):
				return $this->renderDocs();
				break;	
		}
	}
	function renderTG()
	{
		global $url;
		$tg = new Nertz_Forum_TopicGroup();
		$fu = new Nertz_Forum_User();
		$act = $url->get_value('act', 'show');
		$ind  = $url->get_value('ind',  0);
		global $template;
				
		if ($act == 'insert') {
			$params = array();
			$error = $tg->load_form($params, $ind);
			if (!count($error)) {
				$tg->add($params);
				Nertz::redirect(array('page' => $this->page));
			} else {
				$template->set_value('params', $params);
				$template->set_value('error',  $error);
				$template->set_value('forum_act',  'insert');
				return $template->render('Nertz_Forum_Tg_Form');
			}
		}
		if ($act == 'update' && $ind) {
			$params = array();
			$error = $tg->load_form($params, $ind);
			if (!count($error)) {
				$tg->update($ind, $params);
				Nertz::redirect(array('page' => $this->page));
			} else {
				$template->set_value('params', $params);
				$template->set_value('error',  $error);
				$template->set_value('ind',     $ind);
				$template->set_value('forum_act',   'update');
				return $template->render('Nertz_Forum_Tg_Form');
			}
		}
		if ($act == 'edit' && $ind) {
			$params = $tg->get($ind);
			$template->set_value('params', $params);
			$template->set_value('error',  '');
			$template->set_value('ind',     $ind);			
			$template->set_value('forum_act',  'update');
			return $template->render('Nertz_Forum_Tg_Form');
		}
		if ( $act == 'add') {
			$params = array('title' => '', 'descr' => '', 'rights' => Nertz_Access::get('forum_tg', 0));
			$template->set_value('params', $params);
			$template->set_value('error',  0);
			$template->set_value('forum_act',  'insert');
			$template->set_value('ind',  0);	
			return $template->render('Nertz_Forum_Tg_Form');
		}
		if ($act == 'delete' && $ind) {
			$tg->drop($ind);
			Nertz::redirect(array('page' => $this->page));
		}
		$template->set_value('stat', forum_get_stat());
		$template->set_value('top_messagers', $fu->get_top_messagers());
		$template->set_value('list', $tg->get_list());
		global $db_stat;
		return $template->render('Nertz_Forum_Tg_List');
	}

	function renderTopic()
	{
		global $url;
		$t  = new Nertz_Forum_Topic();
		$tg = new Nertz_Forum_TopicGroup();
		$fu = new Nertz_Forum_User();
		$fs = new Nertz_Forum_Subscription();
		
		$act = $url->get_value('act', 'show');
		$ind  = $url->get_value('ind',  0);
		$topicgroup_ind  = $url->get_value('topicgroup_ind',  0);
		
		global $template;
		$template->set_value('topicgroup_ind', $topicgroup_ind);
		
		$can_post = $fu->is_moderator() || $fu->get_ind();

		$tg_data  = $tg->get($topicgroup_ind);
		if (!$can_post && !$fu->get_ind()) {
			if (isset($tg_data['a_write']) && $tg_data['a_write']) {
				$can_post = true;
			}
		}
		$template->set_value('can_post', $can_post);
		
		if ($act == 'insert') {
			$params = array();
			$error = $t->load_form($params, $ind);
			if (!count($error)) {
				$t->add($params);
				Nertz::redirect(array('page' => $this->page, 'form' => 'topic_list', 'topicgroup_ind' => $topicgroup_ind));
			} else {
				$template->set_value('params', $params);
				$template->set_value('error',  $error);
				$template->set_value('forum_act',  'insert');
				return $template->render('Nertz_Forum_Topic_Form');
			}
		}
		if ($act == 'update' && $ind) {
			$params = array();
			$error = $t->load_form($params, $ind);
			if (!count($error)) {
				$t->update($ind, $params);
				Nertz::redirect(array('page' => $this->page, 'form' => 'topic_list', 'topicgroup_ind' => $topicgroup_ind));
			} else {
				$template->set_value('params', $params);
				$template->set_value('error',  $error);
				$template->set_value('ind',     $ind);
				$template->set_value('forum_act',   'update');
				return $template->render('Nertz_Forum_Topic_Form');
			}
		}
		if ($act == 'edit' && $ind) {
			$params = $t->get($ind);
			$template->set_value('params', $params);
			$template->set_value('error',  '');
			$template->set_value('ind',     $ind);			
			$template->set_value('forum_act',  'update');
			return $template->render('Nertz_Forum_Topic_Form');
		}
		if ( $act == 'add') {
			$params = array('title' => '', 'body' => '', 'rights' => Nertz_Access::get('forum_tg', $topicgroup_ind));
			$tg_data = $tg->get($topicgroup_ind);
			forum_copy_rights($params, $tg_data);
			$template->set_value('params', $params);
			$template->set_value('error',  '');
			$template->set_value('forum_act',  'insert');
			$template->set_value('ind',  0);
			return $template->render('Nertz_Forum_Topic_Form');
		}
		
		if ($act == 'delete' && $ind) {
			$t->drop($ind);
			Nertz::redirect(array('page' => $this->page, 'form' => 'topic_list', 'topicgroup_ind' => $topicgroup_ind));
		}
		
		if($fu->get_ind()) {
			if ($act == 'subscribe') {
				$st = (isset($_REQUEST['subscribe']) && $_REQUEST['subscribe']) ? 1 : 0;
				$fs->set('topicgroup', $topicgroup_ind, $fu->get_ind(), $st);
				Nertz::redirect(array('page' => $this->page, 'form' => 'topic_list', 'topicgroup_ind' => $topicgroup_ind));
			}
			$template->set_value('subscription', $fs->get_status('topicgroup', $topicgroup_ind, $fu->get_ind()));
		}
		$template->set_value('list', $t->get_list($topicgroup_ind));
		$tg_list = $tg->get_list();
		$template->set_value('tg_list', $tg_list);
		// Получим имя topic_group для хлебных крошек
		$tg_title = "";
		foreach ($tg_list as $tg) {
			if ($tg['ind'] == $topicgroup_ind) {
				$tg_title = $tg['title'];
			}
		}
		$template->set_value('tg_title', $tg_title);
		return $template->render('Nertz_Forum_Topic_List');
	}

	function renderMessage()
	{
		global $url;
		$t  = new Nertz_Forum_Topic();
		$m  = new Nertz_Forum_Message();
		$tg = new Nertz_Forum_TopicGroup();
		$fu = new Nertz_Forum_User();
		$fs = new Nertz_Forum_Subscription();
		
		$act = $url->get_value('act', 'show');
		$ind  = $url->get_value('ind',  0);
		
		$topicgroup_ind  = $url->get_value('topicgroup_ind',  0);
		$topic_ind       = $url->get_value('topic_ind',  0);
		$t_data          = $t->get($topic_ind);
		global $template;
		$template->set_value('topicgroup_ind', $topicgroup_ind);
		$template->set_value('topic_ind', $topic_ind);
		$template->set_value('topic', $t_data);
			
		$can_post = $fu->is_moderator();
		if (!$can_post) {
			if (isset($t_data['a_write']) && $t_data['a_write']) {
				$can_post = true;
			}
		}
		$template->set_value('can_post', $can_post);
		
		if ($act == 'insert' && $can_post) {
			$params = array();
			$error = $m->load_form($params, $ind);
			if (!count($error)) {
				$m->add($params);
				Nertz::redirect(array('page' => $this->page, 'form' => 'message_list', 'topicgroup_ind' => $topicgroup_ind, 'topic_ind' => $topic_ind));
			} else {
				$template->set_value('params', $params);
				$template->set_value('error',  $error);
				$template->set_value('forum_act',  'insert');
				return $template->render('Nertz_Forum_Message_Form');
			}
		}
		if ($act == 'update' && $ind) {
			$params = array();
			$error = $m->load_form($params, $ind);
			if (!count($error)) {
				$m->update($ind, $params);
				Nertz::redirect(array('page' => $this->page, 'form' => 'message_list', 'topicgroup_ind' => $topicgroup_ind, 'topic_ind' => $topic_ind));
			} else {
				$template->set_value('params', $params);
				$template->set_value('error',  $error);
				$template->set_value('ind',     $ind);
				$template->set_value('forum_act',   'update');
				return $template->render('Nertz_Forum_Message_Form');
			}
		}
		if ($act == 'edit' && $ind) {
			$params = $m->get($ind);
			$template->set_value('params', $params);
			$template->set_value('error',  '');
			$template->set_value('ind',     $ind);			
			$template->set_value('forum_act',  'update');
			return $template->render('Nertz_Forum_Message_Form');
		}
		if ( $act == 'add') {
			$template->set_value('params', array('body' =>''));
			$template->set_value('error',  '');
			$template->set_value('forum_act',  'insert');
			$template->set_value('ind', 0);
			return $template->render('Nertz_Forum_Message_Form');
		}
		if ( $act == 'delete' && $ind) {
			$m->drop($ind);
			Nertz::redirect(array('page' => $this->page, 'form' => 'message_list', 'topicgroup_ind' => $topicgroup_ind, 'topic_ind' => $topic_ind));
		}
		if($fu->get_ind()) {
			if ($act == 'subscribe') {
				$st = (isset($_REQUEST['subscribe']) && $_REQUEST['subscribe']) ? 1 : 0;
				$fs->set('topic', $topic_ind, $fu->get_ind(), $st);
				Nertz::redirect(array('page' => $this->page, 'form' => 'message_list', 'topicgroup_ind' => $topicgroup_ind, 'topic_ind' => $topic_ind));
			}
			$template->set_value('subscription', $fs->get_status('topic', $topic_ind, $fu->get_ind()));
		}
		$template->set_value('list', $m->get_list($topic_ind));
		$tg_list = $tg->get_list();
		$template->set_value('tg_list', $tg_list);
		// Получим имя topic_group для хлебных крошек
		$tg_title = "";
		foreach ($tg_list as $tg) {
			if ($tg['ind'] == $topicgroup_ind) {
				$tg_title = $tg['title'];
			}
		}
		$template->set_value('tg_title', $tg_title);
		return $template->render('Nertz_Forum_Message_List');
	}	
	function renderProfile()
	{
		$fu = new Nertz_Forum_User();
		$user = $fu->get_data( $_REQUEST['ind']);
		global $template, $auth;
		$groups = $auth->get_groups();
		$template->set_value('user',$user);
		return $template->render('Nertz_Forum_Profile');
	}		
	
	function renderRights()
	{
		global $template;
		return $template->render('Nertz_Forum_Rights');
	}
	function renderLogin()
	{
	    global $template, $url, $session;
        $form = new Nertz_Form($this->get_login_form());
        $act = $form->get_posted_act();
        if ($act == 'login') {
        	global $auth;
        	$form->load_posted_vars();
        	$err = $auth->login($form->get_value('login'), $form->get_value('pass'));
        	if ($err === true) {
        		Nertz::redirect(array('page' => $this->page));
        	}
        	$form->params['fields']['login']['error_message'] = $err;
         }
        if ($act == 'register') { 
        		Nertz::redirect(array('page' => $this->page, 'form' => 'register'));
        }
        
        $template->set_value('login_box', $form->show_form('Nertz_Form_Table'));
		return $template->render('Nertz_Forum_Login');
	}
	function renderRegister()
	{
		global $template, $url, $session, $auth;
		$fu = new Nertz_Forum_User();
		$form = new Nertz_Form($this->get_register_form());
		if ($fu->get_ind()) {
			$form->params['buttons']['register']['form_caption'] = 'Сохранить';
			$form->params['fields']['pass']['reqired']  = 0;
			$form->params['fields']['pass1']['reqired'] = 0;
		}
		$act = $form->get_posted_act();
		if ($act == 'register') {
			global $auth;
			$form->load_posted_vars();
			$error = false;
			if ($form->check_vars()) {
				if ($form->get_value('pass') != $form->get_value('pass1')){
					$form->params['fields']['pass1']['error_message'] = "Пароли не совпадают";
					$error = true;
				}
				$usr = Nertz_User::get_by_login($form->get_value('login'));
				if (is_array($usr) && count($usr)) {
					if ($fu->get_ind() != $usr['ind']) {
						$form->params['fields']['login']['error_message'] = "Такой пользователь уже существует";
						$error = true;
					}
				}
				if (!$error) {
					$vars = $form->get_vars();
					if ($fu->get_ind()) {
						$form->params['fields']['avatar_ind']['class']->before_save($vars, 'update', $fu->get_ind());
					} else {
						$form->params['fields']['avatar_ind']['class']->before_save($vars, 'insert');
					}

					$fu->save($vars, $fu->get_ind());
					if ($fu->get_ind()) {
						$template->set_value('form', "<div style='float:left; clear:both;'>Ваши данные успешно обновлены.<br/> Теперь вы можете вернуться <a href='" . $url->gen_url(array('page' => $this->page)) .  "'>на форум</a>.</div>");
						Nertz_User::get($fu->get_ind());
					} else {
						$template->set_value('form', "<div style='float:left; clear:both;''>Вы успешно зарегистрировались.<br/> Теперь вы можете <a href='" . $url->gen_url(array('page' => $this->page, 'form' => 'login')) .  "'>авторизоваться</a> на форуме.</div>");
					}
					return $template->render('Nertz_Forum_Register');
				}
			}
		}
		if ($fu->get_ind()) {
			$data = $fu->get_data();
			unset($data['pass']);
			$form->set_vars($data);
		}
		$template->set_value('form', $form->show_form('Nertz_Form_Table'));
		$template->set_value('user_ind', $fu->get_ind());
		return $template->render('Nertz_Forum_Register');
	}
	function renderUm()
	{
		$fu = new Nertz_Forum_User();
		$um = $fu->get_messages_and_topics( $_REQUEST['ind']);
		$count = 0;
		if(is_array($um)) {
			$count = count($um);
		}
		global $template;
		$template->set_value('count',$count);
		if($count) {
			$template->set_value('last_um',$um[0]);
			$template->set_value('um',$um);
		}
		return $template->render('Nertz_Forum_Um');
	}
	function renderDocs()
	{
		global $template;
		return $template->render('Nertz_Forum_Docs');
	}
	function get_login_form()
	{
		return array(
		"name"        => "main",
		"caption"     => "Список Занятий",
		"index_field" => "ind",
		"url"         => array('page' => $this->page, 'form' => 'login'),
		"buttons" => array(
			"login" => array(
				"form_caption"  => "Войти",
			),
			"register" => array(
				"form_caption"  => "Регистрация",
			),
		),
		"fields"      => array(
			"login" => Array(
				"form_caption"  => "Логин",
				"table_caption" => "",
				"description"   => "",
				"read_only"     => 0,
				"reqired"       => 1,
				"type"          => "String",
				),
			"pass" => Array(
				"form_caption"  => "Пароль",
				"table_caption" => "",
				"description"   => "",
				"read_only"     => 0,
				"reqired"       => 1,
				"type"          => "Password",
				),
			),
		);
	}
	function get_register_form()
	{
		global $url;
		return array(
		"name"        => "main",
		"caption"     => "Регистрация",
		"index_field" => "ind",
		"url"         => array('page' => $this->page, 'form' => 'register'),
		"buttons" => array(
		"register" => array(
		"form_caption"  => "Регистрация",
		),
		),
		"fields"      => array(
			"login" => Array(
				"form_caption"  => "Логин",
				"table_caption" => "",
				"description"   => "",
				"read_only"     => 0,
				"reqired"       => 1,
				"type"          => "String",
			),
			"email" => Array(
				"form_caption"  => "E-mail",
				"table_caption" => "",
				"description"   => "",
				"read_only"     => 0,
				"reqired"       => 1,
				"type"          => "String",
			),
			"pass" => Array(
				"form_caption"  => "Пароль",
				"table_caption" => "",
				"description"   => "",
				"read_only"     => 0,
				"reqired"       => 1,
				"type"          => "Password",
			),
			"pass1" => Array(
				"form_caption"  => "Повтор пароля",
				"table_caption" => "",
				"description"   => "",
				"read_only"     => 0,
				"reqired"       => 1,
				"type"          => "Password",
			),
			"avatar_ind" => Array(
					    "form_caption"  => "Картинка",
					    "table_caption" => "",
					    "description"   => "",
					    "read_only"     => 0,
					    "reqired"       => 0,
					    "type"          => "Storefile",
					    "extensions"	=> array('jpg', 'gif', 'png'),
				    ),
			"comment" => Array(
				"form_caption"  => "Подпись",
				"table_caption" => "",
				"description"   => "",
				"read_only"     => 0,
				"reqired"       => 0,
				"type"          => "TEXT",
				"rows"			=> "7",
			),
		),
		);
	}
}