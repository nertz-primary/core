<?php
include_once('Mail.php');
class Nertz_Mail
{
	/**
	 * Содержимое письма
	 *
	 * @var string
	 */
	private $mime = '';
	/**
	 * Отправка письма
	 *
	 * @param string|array $emails Мыла
	 * @param string $subject Тема письма
	 * @param string $tmpl_name Шаблон письма
	 * @param assoc  $data Переменны шаблона письма
	 * @param assoc  $files Вложенные файлы
	 */
	function send($emails, $subject, $tmpl_name, $data = array(), $files = array())
	{
		global $template, $config;
		if (!is_array($emails)) {
			$emails = array($emails);
		}
		foreach ($emails as $email_to) {
			 $boundary="=_".md5(uniqid(time()));
			 $headers['MIME-Version'] = "1.0";
			 $headers['Content-Type'] = "multipart/related; boundary=\"$boundary\"";
			 $headers['From']         = Nertz::convert($config->get_value('mail/from'),'UTF-8','CP1251');
			 $headers['Subject']      = Nertz_Mail::bencode(Nertz::convert($subject,'UTF-8','CP1251'));
			 $template->set_value('data', $data);
			 $template->set_value('email', $email_to);
		     $txt = $template->render($tmpl_name);
			 $this->html = Nertz::convert($txt,'UTF-8','CP1251');
			 $this->parts = array();
			 if (!empty($files) && is_array($files)) {
			 	foreach ($files as $file) {
			 		$this->add_attachment(
			 			!empty($file['path'])     ? $file['path']     : '',
			 			!empty($file['name'])     ? $file['name']     : '',
			 			!empty($file['c_type'])   ? $file['c_type']   : 'application/octet-stream',
			 			!empty($file['location']) ? $file['location'] : '',
			 			!empty($file['data'])     ? $file['data']     : ''
			 		);
			 	}
			 }
			 $this->build_message($boundary, 'win'); 
			 $headers['To'] = $email_to;
			 $email_to = trim($email_to);
			 $cron = $config->get_value('mail/cron');
			 if (!$cron) {
			 	$mailer = $this->create_mailer();
				 if (PEAR::isError($e = $mailer->send( $email_to, $headers, $this->mime ))) {
				 		//print_r($e);
				 }
			 } else {
			 	$mail_data = array(
			 		'email'   => $email_to,
			 		'headers' => serialize($headers),
			 		'body'	  => base64_encode($this->mime),
			 	);
			 	global $db;
			 	$db->save('mail', $mail_data);
			 }
		}
	}
	/**
	 * Создать экземмпляр Мэйлера
	 *
	 * @return Mail
	 */
	function create_mailer()
	{
		global $config;
		$type  =  $config->get_value('mail/connection');
		if($type == 'smtp') {
			$params = array(
				'host'	    => $config->get_value('mail/server'),
				'username'	=> $config->get_value('mail/login'),
				'password'	=> $config->get_value('mail/pass'),
				'auth' 		=> true,
			);
			$mailer = &Mail::factory('smtp', $params);
		} else {
			$mailer = &Mail::factory('mail');
		}
		return $mailer;
	}
	/**
	 * Генерим HTML код письма
	 *
	 * @param unknown_type $orig_boundary
	 * @param unknown_type $kod
	 */
	function build_html( $orig_boundary, $kod )
	{
		$this->multipart.="--$orig_boundary\n";
		if ($kod == 'w' || $kod == 'win' || $kod == 'windows-1251')  {
			$kod='windows-1251';
		} else {
			$kod='koi8-r';
		}
		$this->multipart .= "Content-Type: text/html; charset=$kod\n";
		$this->multipart .= "Content-Transfer-Encoding: Quot-Printed\n\n";
		$this->multipart .= "$this->html\n\n";
	}
	function add_attachment($path="", $name = "", $c_type="application/octet-stream", $location = "", $data ='')
	{
		if (!empty($path) && empty($data)) {
			if (!file_exists($path . $name)) {
			  print "File $path.$name dosn't exist.";
			  return;
			}
			$fp=fopen($path.$name,"r");
			if (!$fp) {
			  print "File $path.$name coudn't be read.";
			  return;
			}
			$file=fread($fp, filesize($path . $name));
			fclose($fp);
		} else if(!empty($data)) {
			$file = $data;
		}
		$this->parts[] = array(
			"body"     => $file, 
			"name"     => $name, 
			"c_type"   => $c_type, 
			"location" => $location
		);
	}
	
	function build_part($i) 
	{
		$message_part = "";
		$message_part .= "Content-Type: ".$this->parts[$i]["c_type"];
		if ($this->parts[$i]["name"]!="") {
			$message_part.=";\n name=\"".'=?windows-1251?B?'.base64_encode(Nertz::convert($this->parts[$i]["name"],'UTF-8','CP1251')).'?='."\"\n";
		} else {
			$message_part.="\n";	
		}
		
		$message_part.="Content-transfer-encoding: base64\n";
		if( !empty( $this->parts[$i]["location"] )) {
			$message_part.="Content-Location: ".$this->parts[$i]["location"]."\n";
		}
		$message_part.="Content-Disposition: attachment;\n filename=\"".'=?windows-1251?B?'.base64_encode(Nertz::convert($this->parts[$i]["name"],'UTF-8','CP1251')).'?='."\"; size=".strlen($this->parts[$i]["body"])."\n\n";
		$message_part.=chunk_split(base64_encode($this->parts[$i]["body"]))."";
		return $message_part;
	}

	
	function build_message($boundary, $kod)
	{
		$this->headers="MIME-Version: 1.0\n";
		$this->headers.="Content-Type: multipart/mixed; boundary=\"$boundary\"";
		$this->multipart="";
		//$this->multipart.="This is a MIME encoded message.\n\n";
		$this->build_html($boundary,$kod);
		for ($i=(count($this->parts)-1); $i>=0; $i--) {
			$this->multipart.="--$boundary\n".$this->build_part($i);
		}
		$this->mime = "$this->multipart--$boundary--\n";
	}
	function bencode($s)
	{
		return '=?windows-1251?B?'.base64_encode($s).'?=';
	}
	function send_one_cron()
	{
		global $db;
		$mail_data = $db->getRow('SELECT * FROM ?_mail WHERE send = 0 LIMIT 1');
		if(!$mail_data) {
			return false;
		}
		$db->save('mail', array('send' => time()), array('ind' => $mail_data['ind']));
		$mailer = $this->create_mailer();
		if (PEAR::isError($e = $mailer->send($mail_data['email'], unserialize($mail_data['headers']), base64_decode($mail_data['body'])))) {
		 	$db->save('mail', array('error' => serialize($e)), array('ind' => $mail_data['ind']));
		}
		return true;
	}

}