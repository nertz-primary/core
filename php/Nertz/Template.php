<?php
include_once("fckeditor/fckeditor.php");
include_once(Nertz::class_path('Nertz_Template_Smarty'));
include_once(Nertz::class_path('Nertz_Store'));
class Nertz_Template
{
	function Nertz_Template()
	{
		global $config;
		$this->smarty = new Nertz_Template_Smarty();
		$this->smarty->template_dir = array($GLOBALS['__base_path']. '/site/templates', $GLOBALS['__base_path'] . '/core/templates');
		$smarty_cache = Nertz::config('path/smarty', 'temp/smarty/');
		$this->smarty->compile_dir  = $smarty_cache . 'cmpl/';
		$this->smarty->cache_dir    = $smarty_cache . 'cache/';
		$this->smarty->register_function('url',        array(&$this, '_function_url'));
		$this->smarty->register_function('url_array',  array(&$this, '_function_url_array'));
		$this->smarty->register_function('static_url', array(&$this, '_function_static_url'));
		$this->smarty->register_function('config',     array(&$this, '_function_config'));
		$this->smarty->register_function('json',       array(&$this, '_function_json'));
		$this->smarty->register_function('post_name',  array(&$this, '_function_post_name'));
		$this->smarty->register_function('add_css',    array(&$this, '_function_add_css'));
		$this->smarty->register_function('add_js',     array(&$this, '_function_add_js'));
		$this->smarty->register_function('button',     array(&$this, '_function_button'));
		$this->smarty->register_block('fckeditor',     array(&$this, '_block_fckeditor'));
		$this->smarty->register_modifier('sdate',      array(&$this, '_modifier_sdate'));
		$this->smarty->register_modifier('money',      array(&$this, '_modifier_money'));
		$this->smarty->register_modifier('htmlwrap',   array(&$this, '_modifier_htmlwrap'));
		$this->smarty->register_function('image_url',  array(&$this, '_function_image_url'));
		$this->smarty->register_function('file_url',   array(&$this, '_function_file_url'));
		$this->smarty->register_function('comments',   array(&$this, '_function_comments'));
		$this->smarty->register_function('fname',      array(&$this, '_function_fname'));
		$this->smarty->register_function('fvalue',     array(&$this, '_function_fvalue'));
		$this->smarty->register_block('fiserror',      array(&$this, '_block_fiserror'));
		$this->smarty->register_function('ferror',     array(&$this, '_function_ferror'));
		$this->smarty->register_function('static',     array(&$this, '_function_static'));
		$this->smarty->register_modifier('number_format', array(&$this, '_modifier_number_format'));
		$this->smarty->register_function('number2word',   array(&$this, '_function_number2word'));
		$this->js_files  = array();
		$this->css_files = array();
	}
	/**
	 * Установка нового значения в шаблон
	 *
	 * @param string $name Имя переменной в шаблоне {$name}
	 * @param * $value Значение переменной в шаблоне
	 */

	function set_value($name, $value)
	{
		$this->smarty->assign($name, $value);
	}
	/**
	 * Установка нового значения в шаблон
	 *
	 * @param $arr Массив значений
	 */

	function set_vars($arr)
	{
		foreach ($arr as $n => $val) {
			$this->set_value($n, $val);
		}
	}
	/**
	 * Рендеринг шаблона
	 *
	 * @param string $template_name Каноническое имя шаблона ( "_" - означает переход в папку "/")
	 * @return string Строка содержащая обработанный шаблон
	 */
	function render($template_name)
	{
		$this->set_value('js_files',  array_unique($this->js_files));
		$this->set_value('css_files', array_unique($this->css_files));
		global $url;
		$this->set_value('page', $url->get_page());
		$this->set_value('act', $url->get_act());
		$template_path = $this->_gen_template_name($template_name);
		if (Nertz::is_debug()) {
			if (!$this->smarty->template_exists($template_path)) {
				Nertz::log("Не найден шаблон \"{$template_name}\"", LOG_ERROR, 1);
				return "Не найден шаблон \"{$template_name}\"";
			}
		}
		global $log;
		$log->catch = false;
		$res = $this->smarty->fetch($template_path);
		$log->catch = true;
		return $res;
	}
	function add_js($name)
	{
		if ($name == 'jquery') {
			$name = '/includes/jquery/jquery-1.6.1.min.js';
		}
		if ($name == 'ui') {
			$name = '/includes/jquery/jquery-ui-1.8.5.slider.min.js';
		}
		if ($name == 'ui-full') {
			$name = '/includes/jquery/jquery-ui-1.8.14.full.min.js';
		}
		if ($name == 'fancybox') {
			$name = '/includes/fancybox/jquery.fancybox.pack.js';
			// Чтобы не заморачиваться с CSS добавим и его
			$this->css_files[] = '/includes/fancybox/jquery.fancybox.css';
		}
		$this->js_files[] = $name;
	}
	function add_css($name)
	{
		$this->css_files[] = $name;
	}
	// Служенбные функции для Smarty
	function _gen_template_name($template_name)
	{
		return str_replace('_', '/', $template_name) . '.tmpl';
	}
	function _function_url($params, &$smarty)
	{
		global $url;
		return $url->gen_url($params);
	}
	function _function_url_array($params, &$smarty)
	{
		global $url;
		$p = array();
		if (is_array($params['params'])) {
			$p = $params['params'];
		}
		foreach ($params as $name => $value) {
			if ($name != 'params') {
				$p[$name] = $value;
			}
		}
		return $url->gen_url($p);
	}
	function _function_static_url($params, &$smarty)
	{
		global $url;
		return $url->gen_static_url($params['path']);
	}
	function _function_config($params, &$smarty)
	{
		global $config;
		return $config->get_value($params['name']);
	}
	function _function_json($params)
	{
		return Nertz::json($params['value']);
	}
	function _function_post_name($params)
	{
		return Nertz_Form::get_post_name($params['name'],false, $params['form']);
	}
	function _function_add_css($params)
	{
		$this->add_css($params['path']);
		return "";
	}
	function _function_add_js($params)
	{
		$this->add_js($params['path']);
		return "";
	}
	/**
	 * Показ кнопок в smarty с помощью тэга {button ...}
	 *
	 */
	function _function_button($params)
	{
		global $url;
		
		$s = "<button type=\"submit\" name=\"{$params['name']}\" class=\"btn btn-custom\" value=\"{$params['caption']}\"";
		if (isset($params['id'])) {
			$s .= " id=\"" . $params['id'] . "\"";
		}
		$s .= ">";
	    if(!empty($params['bootstrap_class'])) {
	    	$s .= "<i class='{$params['bootstrap_class']}'></i> ";
	    } else if(!empty($params['img'])) {
	    	$s .= "<img alt=\"" . $description . "\" src=\"" . $url->gen_static_url($params['img']) ."\"/>";
	    }
		$s .="{$params['caption']}";
		$s .="</button>";
		return "$s";
	}

	function _modifier_sdate($value)
	{
		global $monthes;
		$dt = getdate($value);
		return $dt['mday'] . " " . $monthes[$dt['mon']]. " " . $dt['year'];
	}
	function _modifier_money($value)
	{
		if(abs($value)<1) {
			return "звоните";
		}
		$value = strval(round(floatval($value)));
		$res = "р";
		$i = 0;
		for ($j = strlen($value); $j--; $j>0) {
			if ($i>2) {
				$res = '&nbsp;' . $res;
				$i = 0;
			}
			$res = $value[$j] . $res;
			$i++;
		}
		return $res;
	}
	function _block_fckeditor($params, $content, &$smarty, &$repeat)
	{
		/*$oFCKeditor = new FCKeditor($params['name']) ;
		$oFCKeditor->BasePath	= '/includes/fckeditor/' ;
		$oFCKeditor->ToolbarSet = "Basic";
		$oFCKeditor->Value		= $content;
		$config = array();
		$config['LinkBrowser']  = false;
		$config['ImageBrowser'] = false;
		$config['ImageUpload'] = false;
		$config['LinkUpload'] = false;
		$config['ImageDlgHideAdvanced'] = true;
		$config['LinkDlgHideAdvanced'] = true;
		$oFCKeditor->Config = $config;
		return $oFCKeditor->CreateHtml();*/
		
		$this->add_js('/includes/ckeditor/ckeditor.js');
		//$this->add_js('/includes/Djenx.Explorer/djenx-explorer.js');
		$s = "<textarea name='{$params['name']}'>" . htmlspecialchars($content). "</textarea>";
		$s .= "<script type='text/javascript'>CKEDITOR.replace('{$params['name']}', { toolbar: 'Basic'});</script>";
		return $s;
		
	}
	function _modifier_htmlwrap($value, $length, $delimiter)
	{
		return html_wordwrap($value, $length, $delimiter);
	}
	function _function_image_url($params, &$smarty)
	{
		return Nertz_Store::gen_image_url(
		isset($params['ind']) ? $params['ind'] : 0,
		isset($params['width']) ? $params['width'] : 0,
		isset($params['height']) ? $params['height'] : 0,
		isset($params['type']) ? $params['type'] : 0,
		isset($params['created']) ? $params['created'] : 0,
		isset($params['full']) ? $params['full'] : 0
		);
	}
	function _function_file_url($params, &$smarty)
	{
	    global $url;
	    $prefix = trim($url->handler->prefix,'/') ;
	    if ($prefix) {
			$prefix = '/' . $prefix;
		}
	    if (!empty($params['ind'])) {
			$prefix =  'http://' .  Nertz::server_name() . $prefix;
		}
		return $prefix . '/' . Nertz_Store::gen_path(isset($params['ind']) ? $params['ind'] : 0, ''); 
	}
	function _function_comments($params, &$smarty)
	{
		global $url;
		include_once(Nertz::class_path('Nertz_Comment'));
		$p = !empty($params['page']) ? $params['page'] : $url->get_page();
		$ind = !empty($params['ind']) ? $params['ind'] : $url->get_value('ind', 0);
		$c = new Nertz_Comment($params);
		return $c->show($p, $ind);
	}
	/**
	 * Удалить скрипт из шаблона
	 *
	 * @param string $name Путь к скрипту
	 */
	function remove_js($name)
	{
		foreach ($this->js_files as $i => $path) {
			if ($path == $name) {
				unset($this->js_files[$i]);
			}
		}
	}
	/**
	 * Удалить стиль из шаблона
	 *
	 * @param string $name Путь к стилю
	 */

	function remove_css($name)
	{
		foreach ($this->css_files as $i => $path) {
			if ($path == $name) {
				unset($this->css_files[$i]);
			}
		}
	}
	function _function_fname($params, &$smarty)
	{
		if (($form = $params['form']) && ($field = $params['field']) && !empty($form->params['fields'][$field]['class'])) {
			return $form->params['fields'][$field]['class']->_get_post_name();
		}
	}
	function _function_fvalue($params, &$smarty)
	{
		if (($form = $params['form']) && ($field = $params['field']) && !empty($form->params['fields'][$field]['class'])) {
			return $form->get_value($field);
		}
	}
	function _block_fiserror($params, $content, &$smarty, &$repeat)
	{
		if (($form = $params['form']) && ($field = $params['field']) && !empty($form->params['fields'][$field]['error_message'])) {
			return $content;
		}
		
		return "";
	}
	function _function_ferror($params, &$smarty)
	{
		if (($form = $params['form']) && ($field = $params['field']) && !empty($form->params['fields'][$field]['error_message'])) {
			return $form->params['fields'][$field]['error_message'];
		}
		return "";
	}
	function _modifier_number_format($value, $digits = 2, $dec_point = '.', $thousands_sep = ' ') 
	{
		return UTF8::number_format($value, $digits, $dec_point, $thousands_sep);
	}

	function _function_number2word($params, &$smarty)
	{
		if (empty($params['number'])) {
			$params['number'] = '0';
		}
		if (!isset($params['name1']) || !isset($params['name2']) || !isset($params['name5'])) {
			return "numbertoword: Отсутствуют параметры 'name1' или 'name2' или 'name5'";
		}
		$temp = strval($params['number']);
		$temp = $temp[mb_strlen($temp)-1];
		return (($temp>1 and $temp <5 and (intval($params['number']%100)>19 or intval($params['number']%100)<10))?$params['name2']:($temp==1?$params['name1']:$params['name5']));
	}
	function _function_static($params, &$smarty)
	{
		global $db;
		if (!isset($params['name'])) {
			return "static: Отсутствует параметр 'name'";
		}
		$body = $db->getOne('SELECT body FROM ?_static WHERE ind = ?', array($params['name']));
		if (!$body) {
			return "static: Не найдена страница '{$params['name']}'";
		}
		$this->add_css('/css/editor.css');
		return "<div class='econt'>{$body}</div>";
	}
	
	
}

$GLOBALS['monthes'] = array(
1 => "Янв",
2 => "Фев",
3 => "Мар",
4 => "Апр",
5 => "Май",
6 => "Июн",
7 => "Июл",
8 => "Авг",
9 => "Сен",
10 => "Окт",
11 => "Ноя",
12 => "Дек");
