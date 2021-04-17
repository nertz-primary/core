<?php
include_once(Nertz::class_path('Nertz_Form_Event'));

class Form_Event_User extends Nertz_Form_Event
{
	function form_create(&$form)
	{
		global $db, $url, $auth;
		unset($form->params['fields']['pass1']);
		unset($form->params['buttons']['register']);
	}
}