<?php
include_once(Nertz::class_path('Nertz_Form_Field'));

class Nertz_Form_Field_Button extends Nertz_Form_Field
{
	function Nertz_Form_Field_Button($name, &$form)
	{
		parent::Nertz_Form_Field($name, $form);
	}
	function check()
	{

	}
	function field_get_table_html($row)
	{
	    global $url;
	    $u = $this->form->params['url'];
	    if (isset($this->params['url']) && count($this->params['url'])) {
	    	$u = array_merge($u, $this->params['url']);
	    }
	    $u['act'] = $this->params['act'];
	    if (!isset($this->params['url']['index'])) {
	    	$u['index'] = $row[$this->form->index_name()];	
	    } else {
	    	$u['index'] = $this->params['url']['index']; 
	    }
	    $description = (isset($this->params['description']) ? $this->params['description'] : "" );
	    $number = "";
	    if (isset($this->params['number'])) {
	    	$number = "<span class='n'>" . $this->show_numbers($this->params['number']) . "</span>";
	    }
	    $image = '';
	    if(!empty($this->params['bootstrap_class'])) {
	    	$image = "<i class='{$this->params['bootstrap_class']}'></i> ";
	    } else if(!empty($this->params['pic_url'])) {
	    	$image = "<img alt=\"" . $description . "\" border=\"0\" src=\"" . $url->gen_static_url($this->params['pic_url']) ."\"/>";
	    }
	    
	    return "<a href=\"" . $url->gen_url($u) . "\" title=\"" . $description . "\">{$image}</a>{$number}";
	}
	function show_numbers($s)
    {
    	if (!intval($s)) {
    		return str_repeat("&nbsp;",5);
    	}
    	if (strlen($s)<2) {
    		$s = $s . "&nbsp;";
    	}
    	return "&nbsp;-&nbsp;" . $s;
    }

}