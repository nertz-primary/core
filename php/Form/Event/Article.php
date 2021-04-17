<?php
include_once(Nertz::class_path('Nertz_Form_Event'));

class Form_Event_Article extends Nertz_Form_Event
{
	function form_create(&$form)
	{
		global $db, $template, $url;
    	$page = $url->get_page();
    	$n = 0;
    	$this->type = strbtw($url->get_page(), 'admin_', '',$n);
    	
    	$pages = Nertz::config('/pages/');
    	$this->types = array();
    	
    	//echo "<pre>" . htmlspecialchars(print_r($pages,1)) . "</pre>";
    	foreach ($pages as $p => $data) {
    		if (!empty($data['type']) && $data['type'] == 'articles') {
    			$n = 0;
    			$this->types[strbtw($p, 'admin_', '',$n)] =  $data['caption'];
    		}
    	}
    	$form->params['caption'] = $this->types[$this->type];
    	$form->params['sql']['select'] = 'SELECT * FROM ?_article WHERE type = \'' . $db->quote($this->type) . '\' ORDER BY created DESC';
    	$form->params['sql']['table'] = 'article';
    	$form->params['fields']['type']['values'] = $this->types;
		$form->params['fields']['type']['value'] = $this->type;
	}
	
	
}