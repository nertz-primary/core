<?php
/**
 * Базовый класс страниц.
 * От него будем наследовать все оставльные страницы
 * ВНИМАНИЕ: Наследники этого класса создаются не напрямую, поэтому любые действия вне класса
 * как глобальные переменные и обособленные функции работать не будут.
 */
include_once(Nertz::class_path('JsHttpRequest'));

class Nertz_Page
{
    /**
     * @var JsHttpRequest
     */
    private $req;
    /**
     * Конструктор
     * Страницы следует создавать через метод Nertz_Page::create_indirrect($name), где $name - имя страницы
     * @abstract Можно переопределять
     *
     * @param unknown_type $name
     * @return Nertz_Page
     */
	function Nertz_Page($params)
	{
		global $url;
		$this->url      = & $url;
		$this->params   = $params;
		$this->title    = Nertz::config('main/title');
		$this->keywords = Nertz::config('main/keywords');
		$this->description = Nertz::config('main/description');
	}
	/**
	 * Метод показа страницы, его результаты помещаются в
	 *
	 */
	function show()
	{
	    return "Неопределенный вывод";
	}
	function render()
	{
	    global $template;
	    $content = $this->show();
	    if (!isset($this->params['envelope'])) {
	        Nertz::log("Пустое поле \"envelope\" для страницы \"{$this->params['name']}\"", LOG_ERROR, 1);
	        return false;
	    } 
	    if ($this->params['envelope'] == 'Ajax') {
	    	$this->req->RESULT = & $content;
	    } elseif ($this->params['envelope'] == '' || Nertz::in_cmd()){
	    	if (Nertz::in_cmd()) {
	    		$content = $content . "\r\n";
	    	}
	    	return $content;
	    } else {
	    	//$template->add_css('core/css/nertz.css');
	    	$template->set_value('content', $content);
	    	$ga_code = Nertz::config('main/ga_uin', '');
	    	if ($ga_code) {
	    		$ga_code = $template->render('GACode');
	    	}
	    	$counters = Nertz::config('main/counters', '');
	    	$template->set_value('ga_code', $ga_code);
	    	$template->set_value('counters', $counters);
	    	$template->set_value('page_title', $this->title);
	    	$template->set_value('page_keywords', $this->keywords);
	    	$template->set_value('page_description', $this->description);
	    	return $template->render($this->params['envelope']);
	    }
		return false;
	}
	function set_envelope($name)
	{
		if ($name == 'Ajax') {
			$this->req = Nertz::init_ajax();
		}
		$this->params['envelope'] = $name;
		
	}
	/**
	 * Загрузить параметры страницы из конфига
	 *
	 * @param string $name Имя страницы
	 * @return array Массив параметров страницы
	 */
	static function load_params($name)
	{
		if (!$name) {
	    	$name = Nertz::config('main/default_page');
	    }
		$params = Nertz::config('pages/' . $name, '');
	    if (!$params)
	    {
	       $params =  Nertz::config('pages/default');
	    }
	    if(!$params) {
	    	$name = Nertz::config('main/default_page');
	    	$params = Nertz::config('pages/' . $name, '');
	    }
	    return $params;
	}
	static function & create_indirrect($name)
	{
	    $params = Nertz_Page::load_params($name);
        $params['name'] = $name;
	    if (!isset($params['class_name'])) {
	        Nertz::log("Пустое поле \"class_name\" для страницы \"{$name}\"", 'error', 1);
	        return null;
	    }
	    $class_name = $params['class_name'];
	    
	    // Попробуем загрузить имя класса из данных типа страницы
	    if (!$class_name) {
	    	$class_name = Nertz::config('/options/page_types/' . $params['type'] . '/class_name' );
	    }
		include_once(Nertz::class_path($class_name));
		$p = new $class_name($params);
		return $p;
	}
}