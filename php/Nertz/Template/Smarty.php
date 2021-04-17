<?php
include_once('Smarty/Smarty.class.php');
class Nertz_Template_Smarty extends Smarty 
{
	function trigger_error($error_msg, $error_type = E_USER_WARNING)
    {
        //trigger_error("Smarty error: $error_msg", $error_type);
        exit();
        Nertz::log('Smarty error: ' . $error_msg, "error", 6);
    }
}
?>