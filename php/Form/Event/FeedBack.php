<?php
include_once(Nertz::class_path('Nertz_Form_Event'));

class Form_Event_FeedBack extends Nertz_Form_Event
{
	function form_create(&$form)
	{
		global $db, $template, $url;
    	$page = $url->get_page();
    	$n = 0;
    	$this->type = strbtw($url->get_page(), 'admin_', '',$n);
    	$this->types = Nertz::config('/feedback/');
    	$form->params['caption'] = $this->types[$this->type]['caption'];
    	$form->params['sql']['select'] = 'SELECT * FROM ?_article WHERE type = \'' . $db->quote($this->type) . '\' ORDER BY created DESC';
	}
	
	
}