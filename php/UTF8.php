<?php
/**
 * Класс для работы со строками
 * в наший проектах они в UTF8
 *
 */
class UTF8 
{
	/**
	 * Поиск подстроки в строке
	 *
	 * @param string $haystack Где ищем
	 * @param string $needle Что ищем
	 * @param int $offset Начальное смещение
	 * @return int  Позиция где нашли
	 */
	static function strpos($haystack, $needle, $offset = 0)
	{
		return mb_strpos($haystack, $needle, $offset, 'UTF-8');	
	}
	/**
	 * Извлечение подстроки из строки
	 *
	 * @param string $string Строка для поиска
	 * @param int $start Начальная позиция
	 * @param int $length Длина куска
	 * @return string Кусок строки
	 */
	
	static function substr($string, $start, $length = 0 )
	{
		return mb_substr($string, $start, $length, 'UTF-8');	
	}
	/**
	 * Определение длины строки
	 *
	 * @param unknown_type $string
	 * @return unknown
	 */
	static function strlen($string)
	{
		return mb_strlen($string);	
	}
	static function strtolower($search)
	{
	    if (function_exists('mb_strtolower')) {
	        return mb_strtolower($search,'Windows-1251');
	    } else {
	        return strtolower($search);
	    }
	}
	static function number_format($n, $decimals = 0, $dec_point = ',', $thousands_sep = '&#160;') 
	{
	    $b = explode('.', $n);
	    $rn = '';
	    $l = strlen($b[0]);
	    /* Reverse string */
	    for ($i = $l; $i > 3; $i -= 3) { 
	        $rn = $thousands_sep . substr($b[0], $i - 3, 3) . $rn;
	    }
	    /* sprintf() used to correct 0.79 to 0.790 */
	    /* str_replace() used to correct decimals */
	    /* str_repeat() used to correct decimals */
	    return substr($b[0], 0, $i) . $rn . ($decimals 
	            ? $dec_point.(isset($b[1]) 
	                ? str_replace('0.', '', sprintf('%0.'.$decimals.'f', '0.'.$b[1]))
	                : str_repeat(0, $decimals))
	            : '');
	}
	
}

function reduce_html($s, $length)
{
	// cut out <tag/>
	$s = preg_replace(
        '/<(table|form|select|button|textarea|script|style|object)(\s+[^>]*)?\/\s*>/is', 
        '', $s);
	// cut out <tag>...</tag>
	$s = preg_replace(
        '/<(table|form|select|button|textarea|script|style|object)(\s+[^>]*)?>.*?<\/\\1\s*>/is', 
        '', $s);
	// cut out <tag>, </tag>, <tag/> for elements used without closing tags
	$s = preg_replace(
        '/<\/?(img|input)(\s+[^>]*)?\/?\s*>/is', 
        '', $s);
	if (strlen($s) <= $length) {
		return $s;
	}
	// limit string length
	$s = substr($s, 0, $length);
	// cut final unclosed tag
	$s = preg_replace('/<[^>]*$/s', '', $s);
	// cut an opening tag from the end
	$s = preg_replace('/<[^\/][^>]*>$/s', '', $s);
	// cut incomplete word
	$s = preg_replace('/\s*[^\s>]*$/s', '', $s);
	// close open tags
	$a = preg_split('/<(\/?)([a-z0-9]+)[^>\/]*(\/?)\s*>/i', $s, -1, PREG_SPLIT_DELIM_CAPTURE);
	$stack = array();
	$level = 0;
	while (count($a)) {
		$text = array_shift($a);
		if (!count($a)) continue;
		$t1 = array_shift($a);
		$tag = strtolower(array_shift($a));
		$t3 = array_shift($a);
		if ($tag != 'br' && $tag != 'hr') {
			if (!$t1) {
				// opening tag
				if ($tag == 'p' || $tag == 'li' || $tag == 'option') {
					if (isset($stack[count($stack)-1]) && $stack[count($stack)-1] == $tag) {
						array_pop($stack);
						$level--;
					}
				}
				array_push($stack, $tag);
				$level++;
			}
			if ($t1 || $t3) {
				// closing tag
				do {
					$t = array_pop($stack);
					$level--;
				} while ($t && $t != $tag);
			}
		}
	}
	$s .= '...';
	while (count($stack)) {
		$s .= '</' . array_pop($stack) . '>';
	}
	return $s;
}
/**
 * Вырезаем кусок из строки
 *
 * @param string $s Препарируемая строка
 * @param string $bs Подстрока предшествующая вырезаемому фрамгенту
 * @param string $es Подстрока находящаяс сразу после фрамгента
 * @param int $n Вход - начальная позиция поиска
 * @return unknown
 */
function strbtw($s, $bs, $es, &$n)
{
	$i = mb_strpos($s,$bs,$n);
	$n = mb_strlen($s);
	if ($i===false) {
		return "";
	}
	$i1 = $es ? mb_strpos($s,$es,$i+mb_strlen($bs)) : false;	
	if ($i1===false) {
		return mb_substr($s,$i + mb_strlen($bs));
	} else {
		$n = $i1 + mb_strlen($es);
		return mb_substr($s,$i+mb_strlen($bs),$i1-$i-mb_strlen($bs));
	}
}
/**
 * Разбиене длинных слов
 *
 * @param unknown_type $str
 * @param unknown_type $maxLength
 * @param unknown_type $char
 * @return unknown
 */
function html_wordwrap($str, $maxLength, $char){
	$wordEndChars = array(" ", "\n", "\r", "\f", "\v", "\0");
	$count = 0;
	$newStr = "";
	$openTag = false;
	for($i=0; $i<strlen($str); $i++){
		$newStr .= $str{$i};
			
		if($str{$i} == "<"){
			$openTag = true;
			continue;
		}
		if(($openTag) && ($str{$i} == ">")){
			$openTag = false;
			continue;
		}
			
		if(!$openTag){
			if(!in_array($str{$i}, $wordEndChars)){//If not word ending char
				$count++;
				if($count==$maxLength){//if current word max length is reached
					$newStr .= $char;//insert word break char
					$count = 0;
				}
			}else{//Else char is word ending, reset word char count
				$count = 0;
			}
		}
			
	}//End for
	return $newStr;
}

function truncate($s, $length, $etc = "...")
{
	$s = strip_tags($s);
	// cut out <tag/>
	$s = preg_replace(
        '/<(table|form|select|button|textarea|script|style|object)(\s+[^>]*)?\/\s*>/is', 
        '', $s);
	// cut out <tag>...</tag>
	$s = preg_replace(
        '/<(table|form|select|button|textarea|script|style|object)(\s+[^>]*)?>.*?<\/\\1\s*>/is', 
        '', $s);
	// cut out <tag>, </tag>, <tag/> for elements used without closing tags
	$s = preg_replace(
        '/<\/?(img|input)(\s+[^>]*)?\/?\s*>/is', 
        '', $s);
	if (strlen($s) <= $length) {
		return $s;
	}
	// limit string length
	$s = substr($s, 0, $length);
	// cut final unclosed tag
	$s = preg_replace('/<[^>]*$/s', '', $s);
	// cut an opening tag from the end
	$s = preg_replace('/<[^\/][^>]*>$/s', '', $s);
	// cut incomplete word
	$s = preg_replace('/\s*[^\s>]*$/s', '', $s);
	// close open tags
	$a = preg_split('/<(\/?)([a-z0-9]+)[^>\/]*(\/?)\s*>/i', $s, -1, PREG_SPLIT_DELIM_CAPTURE);
	$stack = array();
	$level = 0;
	while (count($a)) {
		$text = array_shift($a);
		if (!count($a)) continue;
		$t1 = array_shift($a);
		$tag = strtolower(array_shift($a));
		$t3 = array_shift($a);
		if ($tag != 'br' && $tag != 'hr') {
			if (!$t1) {
				// opening tag
				if ($tag == 'p' || $tag == 'li' || $tag == 'option') {
					if (isset($stack[count($stack)-1]) && $stack[count($stack)-1] == $tag) {
						array_pop($stack);
						$level--;
					}
				}
				array_push($stack, $tag);
				$level++;
			}
			if ($t1 || $t3) {
				// closing tag
				do {
					$t = array_pop($stack);
					$level--;
				} while ($t && $t != $tag);
			}
		}
	}
	$s .= $etc;
	while (count($stack)) {
		$s .= '</' . array_pop($stack) . '>';
	}
	return $s;
}

/**
 * Более продвинутый аналог strip_tags() для корректного вырезания тагов из html кода.
 * Функция strip_tags(), в зависимости от контекста, может работать не корректно.
 * Возможности:
 *   - корректно обрабатываются вхождения типа "a < b > c"
 *   - корректно обрабатывается "грязный" html, когда в значениях атрибутов тагов могут встречаться символы < >
 *   - корректно обрабатывается разбитый html
 *   - вырезаются комментарии, скрипты, стили, PHP, Perl, ASP код, MS Word таги, CDATA
 *   - автоматически форматируется текст, если он содержит html код
 *   - защита от подделок типа: "<<fake>script>alert('hi')</</fake>script>"
 *
 * @param   string  $s
 * @param   array   $allowable_tags     Массив тагов, которые не будут вырезаны
 * @param   bool    $is_format_spaces   Форматировать пробелы и переносы строк?
 *                                      Вид текста на выходе (plain) максимально приближеется виду текста в браузере на входе.
 *                                      Другими словами, грамотно преобразует text/html в text/plain.
 * @param   array   $pair_tags   массив имён парных тагов, которые будут удалены вместе с содержимым
 *                               см. значения по умолчанию
 * @param   array   $para_tags   массив имён парных тагов, которые будут восприниматься как параграфы (если $is_format_spaces = true)
 *                               см. значения по умолчанию
 * @return  string
 *
 * @license  http://creativecommons.org/licenses/by-nc-sa/3.0/
 * @author   Nasibullin Rinat <n a s i b u l l i n  at starlink ru>
 * @charset  ANSI
 * @version  4.0.7
 */
function mb_strip_tags(
    /*string*/ $s,
    array $allowable_tags = null,
    /*boolean*/ $is_format_spaces = true,
    array $pair_tags = array('script', 'style', 'map', 'iframe', 'frameset', 'object', 'applet', 'comment', 'button'),
    array $para_tags = array('p', 'td', 'th', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'form', 'title', 'pre', 'textarea')
)
{
    static $_callback_type  = false;
    static $_allowable_tags = array();
    static $_para_tags      = array();
    #регулярное выражение для атрибутов тагов
    #корректно обрабатывает грязный и битый HTML в однобайтовой или UTF-8 кодировке!
    static $re_attrs_fast_safe =  '(?![a-zA-Z\d])  #утверждение, которое следует сразу после тага
                                   #правильные атрибуты
                                   (?>
                                       [^>"\']+
                                     | (?<=[\=\x20\r\n\t]|\xc2\xa0) "[^"]*"
                                     | (?<=[\=\x20\r\n\t]|\xc2\xa0) \'[^\']*\'
                                   )*
                                   #разбитые атрибуты
                                   [^>]*';

    if (is_array($s))
    {
        if ($_callback_type === 'strip_tags')
        {
            $tag = strtolower($s[1]);
            if ($_allowable_tags &&
                (array_key_exists($tag, $_allowable_tags) || array_key_exists('<' . trim(strtolower($s[0]), '< />') . '>', $_allowable_tags))
                ) return $s[0];
            if ($tag == 'br') return "\r\n";
            if ($_para_tags && array_key_exists($tag, $_para_tags)) return "\r\n\r\n";
            return '';
        }
        if ($_callback_type === 'strip_spaces')
        {
            if (substr($s[0], 0, 1) === '<') return $s[0];
            return ' ';
        }
        trigger_error('Unknown callback type "' . $_callback_type . '"!', E_USER_ERROR);
    }

    if (($pos = strpos($s, '<')) === false || strpos($s, '>', $pos) === false)  #оптимизация скорости
    {
        #таги не найдены
        return $s;
    }

    #непарные таги (открывающие, закрывающие, !DOCTYPE, MS Word namespace)
    $re_tags = '/<[\/\!]? ([a-zA-Z][a-zA-Z\d]* (?>\:[a-zA-Z][a-zA-Z\d]*)?)' . $re_attrs_fast_safe . '>/sx';

    $patterns = array(
        '/<([\?\%]) .*? \\1>/sx',     #встроенный PHP, Perl, ASP код
        '/<\!\[CDATA\[ .*? \]\]>/sx', #блоки CDATA
        #'/<\!\[  [\x20\r\n\t]* [a-zA-Z] .*?  \]>/sx',  #:DEPRECATED: MS Word таги типа <![if! vml]>...<![endif]>

        '/<\!--.*?-->/s', #комментарии

        #MS Word таги типа "<![if! vml]>...<![endif]>",
        #условное выполнение кода для IE типа "<!--[if expression]> HTML <![endif]-->"
        #условное выполнение кода для IE типа "<![if expression]> HTML <![endif]>"
        #см. http://www.tigir.com/comments.htm
        '/<\! (?:--)?
              \[
              (?> [^\]"\']+ | "[^"]*" | \'[^\']*\' )*
              \]
              (?:--)?
         >/sx',
    );
    if ($pair_tags)
    {
        #парные таги вместе с содержимым:
        foreach ($pair_tags as $k => $v) $pair_tags[$k] = preg_quote($v, '/');
        $patterns[] = '/<((?i:' . implode('|', $pair_tags) . '))' . $re_attrs_fast_safe . '> .*? <\/(?i:\\1)' . $re_attrs_fast_safe . '>/sx';
    }
    #d($patterns);

    $i = 0; #защита от зацикливания
    $max = 99;
    while ($i < $max)
    {
        $s2 = preg_replace($patterns, '', $s);
        if ($i == 0)
        {
            $is_html = ($s2 != $s || preg_match($re_tags, $s2));
            if ($is_html)
            {
                if ($is_format_spaces)
                {
                    #В библиотеке PCRE для PHP \s - это любой пробельный символ, а именно класс символов [\x09\x0a\x0c\x0d\x20\xa0] или, по другому, [\t\n\f\r \xa0]
                    #Если \s используется с модификатором /u, то \s трактуется как [\x09\x0a\x0c\x0d\x20]
                    #Браузер не делает различия между пробельными символами,
                    #друг за другом подряд идущие символы воспринимаются как один
                    #$s2 = str_replace(array("\r", "\n", "\t"), ' ', $s2);
                    #$s2 = strtr($s2, "\x09\x0a\x0c\x0d", '    ');
                    $_callback_type = 'strip_spaces';
                    $s2 = preg_replace_callback('/  [\x09\x0a\x0c\x0d]+
                                                  | <((?i:pre|textarea))' . $re_attrs_fast_safe . '>
                                                    .+?
                                                    <\/(?i:\\1)' . $re_attrs_fast_safe . '>
                                                 /sx', __FUNCTION__, $s2);
                    $_callback_type = false;
                }

                #массив тагов, которые не будут вырезаны
                if ($allowable_tags) $_allowable_tags = array_flip($allowable_tags);

                #парные таги, которые будут восприниматься как параграфы
                if ($para_tags) $_para_tags = array_flip($para_tags);
            }
        }#if

        #обработка тагов
        if ($is_html)
        {
            $_callback_type = 'strip_tags';
            $s2 = preg_replace_callback($re_tags, __FUNCTION__, $s2);
            $_callback_type = false;
        }

        if ($s === $s2) break;
        $s = $s2; $i++;
    }#while
    if ($i >= $max) $s = strip_tags($s); #too many cycles for replace...

    if ($is_format_spaces /*|| $is_html*/)
    {
        #вырезаем дублирующие пробелы
        $s = preg_replace('/\x20\x20+/s', ' ', trim($s));
        #вырезаем пробелы в начале и в конце строк
        $s = str_replace(array("\r\n\x20", "\x20\r\n"), "\r\n", $s);
        #заменяем 2 и более переносов строк на 2 переноса строк
        $s = preg_replace('/\r\n[\r\n]+/s', "\r\n\r\n", $s);
    }
    return $s;
}


/**
 * Обрезает текст в кодировке UTF-8 до заданной длины,
 * причём последнее слово показывается целиком, а не обрывается на середине.
 * Html сущности корректно обрабатываются.
 *
 * @param    string   $s           текст в кодировке UTF-8
 * @param    int      $maxlength   ограничение длины текста
 * @param    string   $continue    завершающая строка, которая будет вставлена после текста, если он обрежется
 * @param    string   &$is_cutted  текст был обрезан?
 * @return   string
 *
 * @license  http://creativecommons.org/licenses/by-nc-sa/3.0/
 * @author   Nasibullin Rinat <n a s i b u l l i n  at starlink ru>
 * @charset  ANSI
 * @version  3.3.3
 */
function mb_str_limit($s, $maxlength = 256, $continue = "\xe2\x80\xa6", &$is_cutted = null) #"\xe2\x80\xa6" = "&hellip;"
{
    $is_cutted = false;
    if ($continue === null) $continue = "\xe2\x80\xa6";

    #оптимизация скорости:
    #{{{
    if (strlen($s) <= $maxlength) return $s;
    $s2 = str_replace("\r\n", '?', $s);
    $s2 = preg_replace('/&(?> [a-zA-Z][a-zA-Z\d]+
                            | \#(?> \d{1,4}
                                  | x[\da-fA-F]{2,4}
                                )
                          );  # html сущности (&lt; &gt; &amp; &quot;)
                        /sx', '?', $s2);
    #utf8_decode() converts characters that are not in ISO-8859-1 to '?', which, for the purpose of counting, is quite alright.
    if (strlen($s2) <= $maxlength || strlen(utf8_decode($s2)) <= $maxlength) return $s;
    #}}}

    preg_match_all('/(?> \r\n   # переносы строк
                       | &(?> [a-zA-Z][a-zA-Z\d]+
                            | \#(?> \d{1,4}
                                  | x[\da-fA-F]{2,4}
                                )
                          );  # html сущности (&lt; &gt; &amp; &quot;)
                       | [\x09\x0A\x0D\x20-\x7E]           # ASCII
                       | [\xC2-\xDF][\x80-\xBF]            # non-overlong 2-byte
                       |  \xE0[\xA0-\xBF][\x80-\xBF]       # excluding overlongs
                       | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
                       |  \xED[\x80-\x9F][\x80-\xBF]       # excluding surrogates
                       |  \xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
                       | [\xF1-\xF3][\x80-\xBF]{3}         # planes 4-15
                       |  \xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
                     )
                    /sx', $s, $m);
    #d($m);
    if (count($m[0]) <= $maxlength) return $s;
    $is_cutted = true;
    $left = implode('', array_slice($m[0], 0, $maxlength));
    #из диапазона ASCII исключаем буквы, цифры, закрывающие парные символы [a-zA-Z\d)}\];]
    #нельзя вырезать в конце строки символ ";", т.к. он используются в сущностях &xxx;
    $left2 = rtrim($left, "\x00..\x28\x2A..\x2F\x3A\x3C\x40\x5C\x5E..\x60\x7C\x7E\x7F");
    if (strlen($left) !== strlen($left2)) return $left2 . $continue;

    #добавляем остаток к обрезанному слову
    $right = implode('', array_slice($m[0], $maxlength));
    preg_match('/^(?: [a-zA-Z\d\)\]\}\-\.]  #английские буквы или цифры, закрывающие парные символы, дефис для составных слов, дата, IP-адреса, URL типа www.ya.ru!
                    | \xe2\x80[\x9d\x99]|\xc2\xbb|\xe2\x80\x9c  #закрывающие кавычки
                    | \xc3[\xa4\xa7\xb1\xb6\xbc\x84\x87\x91\x96\x9c]|\xc4[\x9f\xb1\x9e\xb0]|\xc5[\x9f\x9e]  #турецкие
                    | \xd0[\x90-\xbf\x81]|\xd1[\x80-\x8f\x91]   #русские буквы
                    | \xd2[\x96\x97\xa2\xa3\xae\xaf\xba\xbb]|\xd3[\x98\x99\xa8\xa9]  #татарские
                  )+
                /sx', $right, $m);
    #d($m);
    $right = isset($m[0]) ? rtrim($m[0], '.-') : '';
    $s2 = $left . $right;
    if (strlen($s2) !== strlen($s)) $s2 .= $continue;
    return $s2;
}
function strip_tags_except($text, $allowed_tags, $strip=TRUE) {
  if (!is_array($allowed_tags))
    return $text;

  if (!count($allowed_tags))
    return $text;

  $open = $strip ? '' : '&lt;';
  $close = $strip ? '' : '&gt;';

  preg_match_all('!<\s*(/)?\s*([a-zA-Z]+)[^>]*>!',
    $text, $all_tags);
  array_shift($all_tags);
  $slashes = $all_tags[0];
  $all_tags = $all_tags[1];
  foreach ($all_tags as $i => $tag) {
    if (in_array($tag, $allowed_tags))
      continue;
    $text =
      preg_replace('!<(\s*' . $slashes[$i] . '\s*' .
        $tag . '[^>]*)>!', $open . '$1' . $close,
        $text);
  }

  return $text;
}
function rand_str($length = 32, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890')
{
    $chars_length = (strlen($chars) - 1);
    $string = $chars{rand(0, $chars_length)};
    for ($i = 1; $i < $length; $i = strlen($string)) {
        $r = $chars{rand(0, $chars_length)};
        if ($r != $string{$i - 1}) $string .=  $r;
    }
    return $string;
}