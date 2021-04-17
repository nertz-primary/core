<?php
class Nertz_Log_Saver_Console
{
    function Nertz_Log_Saver_Console($params)
    {
    	$this->params = $params;
    }
    function save(&$values)
    {
    	global $template;
    	$template->set_value('log_values', $values);
    	echo $template->render('Nertz_Console');
    }
}