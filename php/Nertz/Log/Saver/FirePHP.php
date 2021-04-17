<?php
include_once('FirePHPCore/FirePHP.class.php');
class Nertz_Log_Saver_FirePHP
{
    
    function Nertz_Log_Saver_FirePHP($params)
    {
    	$this->params = $params;
    	$this->firephp = FirePHP::getInstance(true);
    }
    function save(&$values)
    {
		if (!isset($_SERVER['HTTP_USER_AGENT'])) {
			return;
		}
    	$cats = array(LOG_ERROR => FirePHP::ERROR, LOG_WARN => FirePHP::WARN, LOG_DUMP => FirePHP::LOG, LOG_NOTICE1 => FirePHP::INFO);
    	foreach ($cats as $cat => $ft) {
    		if (isset($values[$cat]) && is_array($values[$cat])) {
    			foreach ($values[$cat] as $ldb) {
    				if(!$this->firephp->fb($ldb['message'], $ldb['file'] . ':' . $ldb['line'], $ft)) {
    					return false;
    				}
    			}
    		}
    	}
    	if (isset($values[LOG_DB]) && is_array($values[LOG_DB]) && count($values[LOG_DB])) {
    		$farr = array();
    		$farr[] = array('SQL Запрос','Время','Результат', 'Файл');
    		$ftime = 0;
    		foreach ($values[LOG_DB] as $ldb) {
    			$farr[] = array(
    			$ldb['message']['sql'],
    			$ldb['message']['time'],
    			$ldb['message']['result'],
    			$ldb['file'] . ':' . $ldb['line'],
    			);
    			$ftime += $ldb['message']['time'];
    		}
    		if(!$this->firephp->fb(array( 'Выполненно ' .  count($values[LOG_DB]) . ' SQL  запросов за ' . $ftime . ' секунд', $farr),FirePHP::TABLE)) {
    			return false;
    		}
    	}
    	if (!empty($values['screen'])) {
    		if(!$this->firephp->fb($values['screen'], FirePHP::LOG)) {
    			return false;
    		}
    	}
    	return true;
    }

}