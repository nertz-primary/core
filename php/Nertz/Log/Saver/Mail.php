<?php
include_once('FirePHPCore/FirePHP.class.php');
class Nertz_Log_Saver_Mail
{
    
    function Nertz_Log_Saver_Mail($params)
    {
    	$this->params = $params;
    }
    function save(&$values)
    {
    	$emails = array();
    	if (isset($this->params['emails']) && $this->params['emails']) {
    		$emails = explode(';', $this->params['emails']);
    		if (is_array($emails) && count($emails)) {
    			foreach ($emails as $id => $email) {
    				$emails[$id] = trim($emails[$id]);
    			}
    		}
    	}
    	if (!count($emails)) {
    		return;
    	}
    	$cats  = array(LOG_ERROR => FirePHP::ERROR, LOG_WARN => FirePHP::WARN, LOG_DUMP => FirePHP::LOG, LOG_NOTICE1 => FirePHP::INFO);
    	$capts = array(LOG_ERROR => 'Errors', LOG_WARN => 'Warnings', LOG_DUMP => 'Dumps', LOG_NOTICE1 => 'Notices');
    	$res  = "";
    	foreach ($cats as $cat => $ft) {
    		if (isset($values[$cat]) && is_array($values[$cat]) && count($values[$cat])) {
    			$res .= "\n\n***" . $capts[$cat] . "***\n";
    			foreach ($values[$cat] as $id => $val) {
    				$res .= "\n" . $val['file'] . ":". $val['line'] . "\n";
    				$res .= $val['message'] ."\n";
    			}
    		}
    	}
    	if ($res) {
    		$res .= "\n*** DB ***\n";
    		if (!empty($values[LOG_DB]) && is_array($values[LOG_DB])) {
	    		foreach ($values[LOG_DB] as $id => $val) {
	    			$res .= "\n" . $val['file'] . ":" . $val['line'] . "\n";
	    			$res .= $val['message']['sql'] . "\n";
	    			$res .= $val['message']['time'] . ' - ' . $val['message']['result'] . "\n";
	    		}
    		}
    		
    		$res .=  "\n*** REQUEST ***\n";
    		$res .= print_r($_REQUEST, true);
    		
    		$res .= "\n*** SERVER ***\n";
    		$res .= print_r($_SERVER, true);
    		
    		$sname = Nertz::server_name();
    		$res = mb_convert_encoding($res, "CP1251", "UTF-8");
    		foreach ($emails as $email) {
    			mail($email, "ERROR:" . $sname, $res);
    		}
    	}
    }
}