<?php
define('AVATAR_WIDTH',   50);
define('AVATAR_HEIGTH',  50);
define('AVATAR_NAME',    '__avatar.jpg');
define('AVATAR_DEFAULT', 'img/forum/avatar.gif');


function forum_copy_rights(&$dest, $src)
{
	//$dest['a_read']  = $src['a_read'];
	//$dest['a_write'] = $src['a_write'];
	$dest['rights'] = $src['rights'];
}
function forum_unset_rights(&$dest)
{
	unset($dest['rights']);
}
function forum_add_rights_fields(&$dest)
{
	//$dest['a_read']  = "BOOL";
	//$dest['a_write'] = "BOOL";
	$dest['rights'] = "ANY";
}

function forum_get_stat()
{
	global $db;
	$ret = array();
	$ret['topic_count']       = $db->getOne('SELECT COUNT(*) FROM ?_forum_topic');
	$ret['message_count']     = $db->getOne('SELECT COUNT(*) FROM ?_forum_message');
	$ret['user_count']        = $db->getOne('SELECT COUNT(*) FROM ?_user');
	$ret['day_message_count'] = $db->getOne('SELECT COUNT(*) FROM ?_forum_message WHERE created > UNIX_TIMESTAMP()-86400');
	return $ret;
}

function array_copy_checked($keys, $arr, &$error)
{
	if (! is_array($error)) {
		$error = array();
	}
	$default   = null;
	$res       = array();
	foreach ($keys as $key => $type) {
		if (isset($arr[$key])) {
			$res[$key] = &$arr[$key];
			if (get_magic_quotes_gpc()) {
		    	$res[$key] = stripslashes_arr($res[$key]);
			} 
		} else {
			$res[$key] = $default;
		}
		$type = strtoupper($type);
		if ($type == 'INT' || $type == 'INTEGER' || $type == 'NUMBER' || $type == 'NUM') {
			if (strval(intval($res[$key])) !== $res[$key]) {
				$error[$key] = 'В этом поле должно быть число';
			}
		}
		else if ($type == 'FLOAT' || $type == 'REAL' || $type == 'DOUBLE') {
			$res[$key] = str_replace(",", ".", $res[$key]);
			if (strpos($res[$key], ".") !== false) {
				$res[$key] = rtrim($res[$key], "0 ");
			}
			if (strval(floatval($res[$key])) !== $res[$key]) {
				$error[$key] = 'В этом поле должно быть число с точкой';
			}
		} else if ($type == 'NULL' || $type == 'EMPTY' || $type == 'NOCHECK'  || $type == 'BOOL' || $type == '' || $type == 'ANY' ) {
			
		} else {
			if (empty($res[$key])) {
				$error[$key] = 'Это поле не может быть пустым';
			}
		}
		if ($type == 'HTML') {
			$res[$key] = strip_tags_except($res[$key], array('strong', 'em', 'p', 'img', 'strike', 'u', 'a','ul','ol','li', 'br'));
		} elseif ($type == 'BOOL') {
			$res[$key] = ($res[$key]) ? 1 : 0;
		} elseif ($type == 'ANY') {
			// Ничего не делаем
		} else {
			$res[$key] = htmlspecialchars($res[$key]);
		}
	}
	return $res;
}

function url_to_link($text) {
  $text =
    preg_replace('!(^|([^\'"]\s*))' .
      '([hf][tps]{2,4}:\/\/[^\s<>"\'()]{4,})!mi',
      '$2<a href="$3">$3</a>', $text);
  $text =
    preg_replace('!<a href="([^"]+)[\.:,\]]">!',
    '<a href="$1">', $text);
  $text = preg_replace('!([\.:,\]])</a>!', '</a>$1',
    $text);
  return $text;
}
