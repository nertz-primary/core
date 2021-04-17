<?php
/**
 * Страница редактора структуры конфигурационных файлов
 *
 */
include_once(Nertz::class_path('Nertz_Form'));
class Page_Nertz_Login extends Nertz_Page 
{
    function Page_Nertz_Login($name)
    {
        parent::Nertz_Page($name);
    }
    function show()
    {
        global $template, $url, $session, $auth;
        $template->add_css('/core/css/admin.css');
        $form = new Nertz_Form($this->get_login_form());
        $act = $form->get_posted_act();
        if ($url->get_value('login', '')) {
        	$form->load_posted_vars();
        	$err = $auth->login($url->get_value('login', ''), $url->get_value('pass', ''));
        	if ($err === true) {
        		$lurl = $session->get_value('login_url', '');
        		if (!$lurl  || $lurl['page'] == Nertz::config('/main/login_page')) {
        			$lurl = array('page' => Nertz::config('/main/default_page'));
        		}
        		Nertz::redirect($lurl);
        	}
        	$template->set_value('error_message', $err);
        }
        if ($act == 'user') {
        	$err = $auth->login($url->get_value('login',''), $url->get_value('pass',''));
        	$lurl = $url->get_value('login_url', '');
        	if(!$lurl) {
        		$lurl = array('page' => $url->get_value('login_page', Nertz::config('/main/default_page'))); 
        	}
        	if ($err !== true) {
       				$lurl['login_error'] = $err;
        	}
        	Nertz::redirect($lurl);
        }
        if ($act == 'logout') {
        	$auth->logout();
        	Nertz::redirect(array('page' => Nertz::config('/main/default_page')));
        }
        return $template->render('Nertz_Admin_Login');
    }
    function get_login_form()
    {
    	return array(
			"name"        => "main",
			"caption"     => "Список Занятий",
			"index_field" => "ind",
			"url"         => array('page' => 'login'),
			"buttons" => array(
				"login" => array(
				"form_caption"  => "Войти",
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
}