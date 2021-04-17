<?php
class Nertz_DB
{
	var $query_result;
	var $row = array();
	var $rowset = array();
	var $num_queries = 0;
	var $in_transaction = 0;
	var $db_stat_count;
	var $db_stat;
	var $db_connect_id;
	//
	// Constructor
	//
	function Nertz_DB($params)
	{
		$this->persistency = true;
		$this->user        = $params['user'];
		$this->password    = $params['pass'];
		$this->server      = $params['host'];
		$this->dbname      = $params['name'];
		$this->prefix      = $params['prefix'];
		$this->log         = $params['log'];
		$this->db_connect_id = null;
		$this->db_stat_count = 0;
		$this->db_stat = array();
		// Прибавка к уровню вложения при бактрэйсинге места ошибки
		$this->e_level = 0;
	}
	/**
	 * Хитрая функция подключения к БД при первой необходимости.
	 *
	 * @return unknown
	 */
	function _connect()
	{
		if (!$this->db_connect_id) {
			if($this->persistency) {
				$this->db_connect_id = mysql_pconnect($this->server, $this->user, $this->password);
			} else {
				$this->db_connect_id = mysql_connect($this->server, $this->user, $this->password, true);
			}
			if ($this->db_connect_id) {
				if( $this->dbname != "" ) {
					$dbselect = mysql_select_db($this->dbname, $this->db_connect_id);
					if (!$dbselect) {
						mysql_close($this->db_connect_id);
						$this->db_connect_id = $dbselect;
					} else {
						mysql_set_charset('utf8', $this->db_connect_id);
					}
				}
				return $this->db_connect_id;
			} else {
				global $log;
				$log->write('Нет подключения к БД: ' . $this->sql_error(), LOG_WARN, 3);
				return false;
			}
		} else {
			return true;
		}
	}
	/**
	 * Закрыть соединение с БД
	 *
	 * @return unknown
	 */
	function close()
	{
		if ($this->db_connect_id) {
			return mysql_close($this->db_connect_id);
		} else {
			return false;
		}
	}

	/**
	 * Отправить запрос к БД
	 *
	 * @param string $query Строка запроса
	 * @param array $args Массив параметров для Place Holder-ов
	 * @return unknown
	 */
	function sql_query($query = "", $args="")
	{
		if (!$this->_connect()) {
			return false;
		} else {
			$query = $this->_replace_placeholders( $query, $args );
			$tt=microtime();
			unset($this->query_result);
			if ($query != "") {
				$this->num_queries++;
				$this->query_result = mysql_query($query, $this->db_connect_id);
			}
			$tt=microtime()-$tt;
			$this->db_stat[$this->db_stat_count]['sql']    = $query;
			$this->db_stat[$this->db_stat_count]['time']   = $tt;
			if (!empty($this->query_result)) {
				unset($this->row[$this->query_result]);
				unset($this->rowset[$this->query_result]);
				$this->db_stat[$this->db_stat_count]['result'] = 'OK';
			} else {
				$this->db_stat[$this->db_stat_count]['result'] = $this->sql_error();
				Nertz::log($this->sql_error(), LOG_ERROR, 1 + $this->e_level);
			}
			if ($this->log) {
				Nertz::log($this->db_stat[$this->db_stat_count], LOG_DB, 1 + $this->e_level);
			}
			$this->db_stat_count++;
			// Если слишком много сообщений, то удалим первое.
			if ($this->db_stat_count > 1000) {
				array_shift($this->db_stat);
			}
			$this->e_level = 0;
			return $this->query_result;
		}
	}

	//
	// Other query methods
	//
	function sql_numrows($query_id = 0)
	{
		if (!$query_id) {
			$query_id = $this->query_result;
		}
		return ( $query_id ) ? mysql_num_rows($query_id) : false;
	}
	function sql_affectedrows()
	{
		return ($this->db_connect_id) ? mysql_affected_rows($this->db_connect_id) : false;
	}
	function sql_numfields($query_id = 0)
	{
		if( !$query_id ) {
			$query_id = $this->query_result;
		}
		return ( $query_id ) ? mysql_num_fields($query_id) : false;
	}

	function sql_fieldname($offset, $query_id = 0)
	{
		if (!$query_id) {
			$query_id = $this->query_result;
		}
		return ( $query_id ) ? mysql_field_name($query_id, $offset) : false;
	}

	function sql_fieldtype($offset, $query_id = 0)
	{
		if (!$query_id) {
			$query_id = $this->query_result;
		}
		return ( $query_id ) ? mysql_field_type($query_id, $offset) : false;
	}

	function sql_fetchrow($query_id = 0)
	{
		if (!$query_id) {
			$query_id = $this->query_result;
		}
		if ($query_id) {
			$this->row[intval($query_id)] = mysql_fetch_array($query_id, MYSQL_ASSOC);
			return $this->row[intval($query_id)];
		} else {
			return false;
		}
	}
	function sql_fetchrowset($query_id = 0)
	{
		if (!$query_id) {
			$query_id = $this->query_result;
		}
		if( $query_id ) {
			unset($this->rowset[intval($query_id)]);
			unset($this->row[intval($query_id)]);
			$result = array();
			while($this->rowset[intval($query_id)] = mysql_fetch_array($query_id, MYSQL_ASSOC)) {
				$result[] = $this->rowset[intval($query_id)];
			}
			return $result;
		} else {
			return false;
		}
	}

	function sql_fetchfield($field, $rownum = -1, $query_id = 0)
	{
		if (!$query_id) {
			$query_id = $this->query_result;
		}
		if ($query_id) {
			if ($rownum > -1) {
				$result = mysql_result($query_id, $rownum, $field);
			} else {
				if (empty($this->row[intval($query_id)]) && empty($this->rowset[intval($query_id)])) {
					if( $this->sql_fetchrow() )
					{
						$result = $this->row[intval($query_id)][$field];
					}
				}
				else
				{
					if( $this->rowset[intval($query_id)] )
					{
						$result = $this->rowset[intval($query_id)][$field];
					}
					else if( $this->row[intval($query_id)] )
					{
						$result = $this->row[intval($query_id)][$field];
					}
				}
			}

			return $result;
		}
		else
		{
			return false;
		}
	}

	function sql_rowseek( $rownum, $query_id = 0 )
	{
		if( !$query_id )
		{
			$query_id = $this->query_result;
		}

		return ( $query_id ) ? mysql_data_seek($query_id, $rownum) : false;
	}

	function sql_nextid()
	{
		return ( $this->db_connect_id ) ? mysql_insert_id($this->db_connect_id) : false;
	}

	function sql_freeresult( $query_id = 0 )
	{
		if( !$query_id )
		{
			$query_id = $this->query_result;
		}

		if ( $query_id )
		{
			unset($this->row[intval($query_id)]);
			unset($this->rowset[intval($query_id)]);

			mysql_free_result($query_id);

			return true;
		}
		else
		{
			return false;
		}
	}

	function sql_error()
	{
		return '[' . mysql_errno($this->db_connect_id) . ']' . mysql_error($this->db_connect_id);
	}

	function getRow( $query, $args="" )
	{
		$this->e_level = 1;
		$res = $this->sql_query($query, $args);
		return $this->sql_fetchrow();

	}
	function getAll( $query, $args="", $e_level = 1 )
	{
		$this->e_level = $e_level;
		$res = $this->sql_query($query, $args);
		return $this->sql_fetchrowset($res);
	}

	function getOne( $query, $args="" )
	{
		$this->e_level = 1;
		$res = $this->sql_query($query, $args);
		$row = $this->sql_fetchrow( $res );
		if( is_array($row)) {
			foreach( $row as $value) {
				return $value;
			}
		}
		return null;
	}
	/**
	 * Получить ассоциавтивный массив
	 *
	 * @example
	 * SELECT ind, value FROM ?_table WHERE ...
	 * Даст в результате массив где ключем будет поле ind, а значениями value.
	 * Если полей больше чем два то значением будет массив содержащий все поля кроме первого
	 *
	 * @param string $query Запрос
	 * @param string $args Параметры
	 * @return assoc Массив результата запроса
	 */
	function getAssoc( $query, $args="", $e_level = 1 )
	{
		$this->e_level = $e_level;
		$r = $this->sql_query($query, $args);
		$row = $this->sql_fetchrow($r);
		$res = array();
		if (count($row) == 2) {
			$res[array_shift($row)] = array_shift($row);
			while ($row = $this->sql_fetchrow($r)) {
				$res[array_shift($row)] = array_shift($row);
			}
		} else if (count($row) > 2) {
			$res[array_shift($row)] = $row;
			while ($row = $this->sql_fetchrow($r)) {
				$res[array_shift($row)] = $row;
			}
		}
		return $res;
	}
	/**
	 * Постранично получить результаты запроса
	 *
	 * @param string $query Запрос
	 * @param array $args Аргументы
	 * @param int $page Номер страницы
	 * @param int $per_page Количество эллементов на страницу
	 * @param bool $assoc Выбирать как ассоциативный массив: первое поле - ключ, остальные - значение
	 */
	function getPaged( $query, $args, $page=0, $per_page=10, $assoc = false, $count_subquery = 'COUNT(*)')
	{
		global $url;
		// Нефиг выполнять левые запросы
		if (strpos($query, 'SELECT') === false) {
			return false;
		}
		
		//Обрежем точку с запятой
		$query   = rtrim($query, ';');

		// Если есть лимит то удалим его
		if (($pos = strrpos($query, ' LIMIT ')) !== false) {
			$query = substr($query, 0, $pos);
		}

		// ORDER нам тоже не нужен для полдсчета количества.
		$cquery = $query;
		//print_r($cquery);
		if (($pos = strrpos($cquery, 'ORDER BY')) !== false) {
			$cquery = substr($cquery, 0, $pos);
		}
		
		// Вырежем все что находится между SELECT и FROM чтобы подсчитать количество
		if (($i = strpos($cquery, 'FROM')) === false ) {
			return false;
		}
		
		$res = array();
		
		if (mb_strpos($count_subquery, 'SELECT') === 0) {
			$count_sql = $count_subquery;
		} else {
			$count_sql = 'SELECT ' . $count_subquery . ' ' . substr($cquery, $i);
		}
		$res['count']  = array_sum($this->getCol($count_sql, $args, 2));
		$res['pcount'] = ceil(floatval($res['count'])/floatval($per_page));
		if ($page == 0) {
			$page = $res['pcount'];
		}
		if ($page > $res['pcount']) {
			$page = $res['pcount'];
		}
		
		
		$first = ($res['pcount'] - $page) * $per_page;
		
		$query  .= ' LIMIT ' . $this->quote($first) . ', ' . $this->quote($per_page);
		
        if ($assoc) {
			$res['data']   = $this->getAssoc($query, $args, 2);
		} else {
			$res['data']   = $this->getAll($query, $args, 2);
		}
        $res['pages']  = gen_page_list($page, $res['pcount']);
        $res['p']      = $page;
        $res['url']    = $url->get_url();
		
        return $res;
	}
	
	/**
	 * Постранично получить результаты запроса (В прямом порядке)
	 *
	 * @param string $query Запрос
	 * @param array $args Аргументы
	 * @param int $page Номер страницы
	 * @param int $per_page Количество эллементов на страницу
	 * @param bool $assoc Выбирать как ассоциативный массив: первое поле - ключ, остальные - значение
	 */
	function getPagedOld( $query, $args, $page=1, $per_page=10, $assoc = false)
	{
		global $url;
		if ($page<1) {
			$page = 1;
		}
		$first = ($page-1) * $per_page;
		//$query   = str_replace('SELECT ', 'SELECT SQL_CALC_FOUND_ROWS ', $query, $iii = 1);
		$meta = 'SELECT ';
		if (($i = strpos($query, $meta)) !== false) {
			$query = substr_replace($query, 'SELECT SQL_CALC_FOUND_ROWS ', $i, strlen($meta)); 
		}
		$query   = rtrim($query, ';');
		// Если есть лимит то удалим его
		if (($pos = strrpos($query, ' LIMIT ')) !== false) {
			$query = substr($query, 0, $pos);
		}
		$query  .= ' LIMIT ' . $this->quote($first) . ', ' . $this->quote($per_page);
		$res = array();
		if ($assoc) {
			$res['data']   = $this->getAssoc($query, $args);
		} else {
			$res['data']   = $this->getAll($query, $args);
		}
        $res['count']  = $this->getOne("SELECT FOUND_ROWS()");
        $res['pcount'] = ceil(floatval($res['count'])/floatval($per_page));
        $res['pages']  = gen_page_list_old($page, $res['pcount']);
        $res['p']      = $page;
        $res['url']    = $url->get_url();
        return $res;
	}
	
	/**
	 * Получить столбец данных
	 *
	 * @param string $query Запрос
	 * @param array $args Параметры
	 * @return array Массив значение первого столбца запроса
	 */
	function getCol( $query, $args="", $e_level=1 )
	{
		$this->e_level = $e_level;
		$res = $this->sql_query($query, $args);
		$row = $this->sql_fetchrowset( $res );
		$res = array();
		if (is_array($row)) {
			foreach( $row as $value) {
				$res[] = array_shift($value);
			}
		}
		return $res;
	}
	/**
         * Сохранение записи в БД
         *
         * @param string $table_name Имя таблицы
         * @param array $params Ассоциативный массив вида "Имя поля" => "Значение"
         * @param array $keys Массив ключей. Если не переданн то добавляется нова запись. Иначе обновляется, та запись для которой эти ключи совпадают
         * @param bool $no_time_stamps Если не установлен то в запрос автоматом добавляются два поля  created  и updated
         * @return int Если добавляется новая запись то возвращается ее индекс, иначе количество обновленных записей
         */

	function save( $table_name, $params, $keys="", $no_time_stamps = false )
	{
		$this->e_level = 1;
		$update = (is_array($keys) && count( $keys )>0) ? true : false;
		if( $update ) {
			$sql = "UPDATE `{$this->prefix}{$table_name}` SET ";
		} else {
			$sql = "INSERT INTO `{$this->prefix}{$table_name}` SET ";
		}
		if( !$no_time_stamps ) {
			if (!isset($params['updated'])) {
				$params['updated'] = time();
			}
			if( !$update ) {
				if (!isset($params['created'])) {
					$params['created'] = time();
				}
			}
		}

		$xxx = "";
		foreach( $params as $name => $value ) {
			$sql .= $xxx . " `$name` = '" . $this->quote($value) . "'";
			$xxx = ", ";
		}
		if( $xxx ) {
			if( $update ) {
				$xxx  = "";
				$sql .= " WHERE ";
				foreach( $keys as $name => $value )	{
					$sql .= $xxx . " `$name` = '" . $this->quote($value) . "'";
					$xxx = " AND";
				}
			}
			if( $xxx ) {
				$res = $this->sql_query( $sql );
				if( !$res )	{
					global $log;
					$log->write($this->sql_error(), LOG_WARN, 1);
					return false;
				}
				if( $update ) {
					return $this->sql_affectedrows();
				} else {
					return $this->sql_nextid();
				}
			}
		}
	}
	function getStat()
	{
		return $this->db_stat;
	}
	function _replace_placeholders( $sql, $args )
	{
		$sql 		= str_replace( '?_', $this->prefix, $sql );
		if( !(is_array($args) && count($args))) {
			return $sql;
		}
		$this->args = $args;
		$sql 		= preg_replace_callback(
		'/(\?)([dsafn#]?)/sx',
		array(&$this, '_replace_placeholders_callback'),
		$sql,
		count($args)
		);
		return $sql;
	}



	// Internal function to replace placeholders.
	function _replace_placeholders_callback( $m )
	{
		$value = array_shift( $this->args );

		// First process guaranteed non-native placeholders.
		switch( $m[2] )	{
			case 'a':
				// Array or hash.
				if( !is_array( $value ) ) {
					return "ERROR_PLACEHOLDER_VALUE_NOT_ARRAY";
				}
				$parts = array();
				foreach( $value as $k => $v ) {
					$v = $v !== null? $this->quote( $v ) : 'NULL';
					if (!is_int($k)) {
						$k 			= $this->quote( $k );
						$parts[] 	= "$k='$v'";
					} else {
						$parts[] 	= "'$v'";
					}
				}
				return join( ", ", $parts );
			case "#":
				// Identifier.
				return $this->quote( $value );
			case 'n':
				// NULL-based placeholder.
				return empty( $value )? 'NULL' : intval( $value );
		}


		// In non-native mode arguments are quoted.
		if( $value === null ) return 'NULL';
		switch( $m[2] ) {
			case '':
				if( !is_scalar( $value ) )
				{
					return "ERROR_PLACEHOLDER_VALUE_NOT_SCALAR";
				}
				return "'".$this->quote( $value )."'";
			case 'd':
				return intval( $value );
			case 'f':
				return str_replace( ',', '.', floatval( $value ) );
			default:
				return $this->quote( $value );
		}
	}
	function quote( $value )
	{
		if (!$this->_connect()) {
			return false;
		}
		return @mysql_real_escape_string($value, $this->db_connect_id ? $this->db_connect_id : null);
	}
	function mass_insert($table, $names, $arr)
	{
		$this->e_level = 1;
		if (is_array($arr) && count($arr) && is_array($names) && count($names))
		{
			$sql = "INSERT INTO ?_" . $table . "(" . implode(', ', $names) . ") VALUES";
			$xxx = "";
			foreach ($arr as $v)
			{
				$sql .= $xxx . "(";
				$xxx1 = '';
				foreach ($v as $item)
				{
					$sql .= $xxx1 . "'" . $this->quote($item) . "'";
					$xxx1 = ", ";
				}
				$sql .= ")";
				$xxx = ", ";
			}
			return $this->sql_query($sql);
		}
		return null;
	}
	function get_last_query_info()
	{
		return $this->db_stat[$this->db_stat_count-1];
	}
	/**
	 * Получить номер страницы на которой размещен элемент
	 *
	 * @param string $query Запрос
	 * @param array $args Аргументы
	 * @param int $per_page Количество эллементов на страницу
	 * @param int $ind Значение индекса
	 * @param int $ind_field Имя поля индекса
	 */
	function getPageNum( $query, $args, $per_page=10, $ind, $ind_field = 'ind')
	{
		global $url;
		$query   = trim($query, ';');
		//Обрежем точку с запятой
		$query   = rtrim($query, ';');
		// Если есть лимит то удалим его
		if (($pos = strrpos($query, ' LIMIT ')) !== false) {
			$query = substr($query, 0, $pos);
		}

		// Нефиг выполнять левые запросы
		if (strpos($query, 'SELECT') === false) {
			return false;
		}
		// Вырежем все что назодится между SELECT и FROM чтобы подсчитать количество
		if (($i = strpos($query, 'FROM')) === false ) {
			return false;
		}
		$res = array();
		
		array_unshift($args, $ind_field);
		$query = 'SELECT ?# ' . substr($query, $i);
		$inds  = $this->getCol($query, $args);
		$pcount = ceil(floatval(count($inds))/floatval($per_page));
		$n = 1;
		foreach ($inds as $i) {
			if ($i == $ind) {
				break;
			}
			$n++;
		}
		$page = $pcount - ceil(floatval($n)/floatval($per_page))+1;
        return $page;
	}
	/**
	 * Список СТОП-слов для поиска
	 *
	 * @return array
	 */
	function get_stopwords()
    {
    	return array(
			"",
			"а",
			"без",
			"безо",
			"более",
			"больше",
			"буд",
			"будем",
			"будет",
			"будто",
			"буду",
			"будут",
			"бы",
			"быв",
			"был",
			"была",
			"были",
			"было",
			"быть",
			"в",
			"в отношении",
			"в течении",
			"в-восьмых",
			"в-девятых",
			"в-пятых",
			"в-седьмых",
			"в-третьих",
			"в-шестых",
			"вам",
			"вами",
			"вас",
			"ваш",
			"вблизи",
			"вбок",
			"ввосьмером",
			"ввысь",
			"вдали",
			"вдаль",
			"вдвое",
			"вдвоем",
			"вдвойне",
			"вдевятером",
			"вдесятеро",
			"вдогон",
			"вдогонку",
			"вдоль",
			"вдосталь",
			"вдруг",
			"ведь",
			"верх",
			"весь",
			"взамен",
			"вид",
			"видно",
			"вкратце",
			"вкупе",
			"вместо",
			"вне",
			"внешне",
			"вниз",
			"внизу",
			"вновь",
			"внутри",
			"внутрь",
			"во",
			"во время",
			"во-первых",
			"вовне",
			"вовсе",
			"вовсю",
			"воз",
			"возле",
			"воочию",
			"вопреки",
			"вопрос",
			"вот",
			"впредь",
			"впрочем",
			"вровень",
			"врознь",
			"врозь",
			"вряд ли",
			"все",
			"все-таки",
			"всегда",
			"всего",
			"всей",
			"всем",
			"всеми",
			"всему",
			"всех",
			"всею",
			"вслед",
			"всплошную",
			"вспять",
			"всю",
			"всюду",
			"вся",
			"вы",
			"выше",
			"г",
			"где",
			"где-либо",
			"где-нибудь",
			"где-то",
			"главный",
			"говорил",
			"год",
			"да",
			"дабы",
			"даже",
			"далее",
			"два",
			"де",
			"дел",
			"для",
			"до",
			"долж",
			"досвидания",
			"должен",
			"другие",
			"других",
			"другой",
			"его",
			"едва",
			"едва-едва",
			"ее",
			"ей",
			"еле",
			"ему",
			"если",
			"есть",
			"еще",
			"ею",
			"ж",
			"же",
			"жизнь",
			"за",
			"заключается",
			"зато",
			"зачем",
			"зачем-либо",
			"зачем-нибудь",
			"зачем-то",
			"здесь",
			"здравствуйте",
			"здраствуйте",
			"здрасте",
			"здрасти",
			"знать",
			"и",
			"из",
			"из-за",
			"или",
			"им",
			"име",
			"имеющее",
			"имеющие",
			"имеющий",
			"имеющим",
			"ими",
			"иначе",
			"иногда",
			"итого",
			"их",
			"к",
			"каждый",
			"кажется",
			"как",
			"как-либо",
			"как-нибудь",
			"как-то",
			"какая",
			"какой",
			"какой-то",
			"кверху",
			"ко",
			"когда",
			"когда-либо",
			"когда-нибудь",
			"когда-то",
			"кое",
			"кое-где",
			"кое-как",
			"кое-какой",
			"кое-когда",
			"кое-кто",
			"кое-куда",
			"кое-откуда",
			"кое-чей",
			"кое-что",
			"конечно",
			"который",
			"который-либо",
			"который-нибудь",
			"кто",
			"кто-либо",
			"кто-нибудь",
			"кто-то",
			"куда",
			"куда-либо",
			"куда-нибудь",
			"куда-то",
			"лет",
			"ли",
			"либо",
			"лучше",
			"ль",
			"мало",
			"меж",
			"между",
			"меня",
			"мимо",
			"мне",
			"многие",
			"много",
			"мной",
			"мог",
			"мож",
			"может",
			"можн",
			"можно",
			"мой",
			"мочь",
			"моя",
			"мы",
			"на",
			"над",
			"надо",
			"наконец",
			"нам",
			"нами",
			"намного",
			"нас",
			"наш",
			"не",
			"него",
			"нее",
			"ней",
			"некий",
			"некогда",
			"некого",
			"некто",
			"нельзя",
			"нем",
			"немало",
			"немного",
			"нему",
			"несколько",
			"нет",
			"нечего",
			"нею",
			"ни",
			"нибудь",
			"нигде",
			"никакой",
			"никогда",
			"никто",
			"никуда",
			"ним",
			"ними",
			"нисколько",
			"них",
			"ничего",
			"ничей",
			"ничто",
			"но",
			"новый",
			"ну",
			"нужн",
			"нэи",
			"о",
			"об",
			"обе",
			"обо",
			"один",
			"однако",
			"около",
			"он",
			"она",
			"они",
			"оно",
			"опять",
			"особенно",
			"от",
			"откуда",
			"откуда-либо",
			"откуда-нибудь",
			"откуда-то",
			"относится",
			"относятся",
			"отношение",
			"отнюдь",
			"ото",
			"отсюда",
			"оттого",
			"очень",
			"перед",
			"по",
			"по-прежнему",
			"по-своему",
			"под",
			"подле",
			"подо",
			"подчас",
			"позднее",
			"позже",
			"пока",
			"полно",
			"полуЧить",
			"помимо",
			"поначалу",
			"понемногу",
			"порой",
			"поскольку",
			"после",
			"посредине",
			"постольку",
			"потом",
			"потому",
			"почему",
			"почему-либо",
			"почему-нибудь",
			"почему-то",
			"почти",
			"поэтому",
			"пред",
			"предо",
			"представляет",
			"прежде",
			"при",
			"про",
			"проблема",
			"просто",
			"простой",
			"против",
			"прямо",
			"путем",
			"ради",
			"раз",
			"разве",
			"разом",
			"ранее",
			"с",
			"сам",
			"сама",
			"сами",
			"самим",
			"самими",
			"самих",
			"само",
			"самого",
			"самой",
			"самом",
			"самому",
			"самою",
			"саму",
			"самый",
			"свой",
			"свою",
			"себЯ",
			"себе",
			"себя",
			"сегоднЯ",
			"сегодня",
			"сейЧас",
			"сейчас",
			"сказал",
			"сказала",
			"сказать",
			"сквозь",
			"сколь",
			"сколько-нибудь",
			"сколько-то",
			"слово",
			"сложно",
			"служащее",
			"служащие",
			"служащим",
			"служит",
			"сначала",
			"снова",
			"со",
			"собой",
			"собою",
			"совсем",
			"содержащее",
			"содержащий",
			"содержит",
			"сообразно",
			"спасибо",
			"сперва",
			"спереди",
			"сразу",
			"среди",
			"средь",
			"столь",
			"столько",
			"суть",
			"та",
			"так",
			"так как",
			"так что",
			"также",
			"такой",
			"там",
			"те",
			"тебе",
			"тебя",
			"тем",
			"теми",
			"теперь",
			"тех",
			"то",
			"тобой",
			"тобою",
			"тогда",
			"того",
			"тоже",
			"той",
			"только",
			"том",
			"тому",
			"тот",
			"тою",
			"три",
			"ту",
			"тут",
			"ты",
			"у",
			"уж",
			"уже",
			"узнать",
			"уме",
			"хорошо",
			"хот",
			"хотел",
			"хоть",
			"хотя",
			"хоч",
			"чего",
			"чего-то",
			"чей",
			"чей-либо",
			"чей-нибудь",
			"чей-то",
			"человек",
			"чем",
			"через",
			"что",
			"что-либо",
			"что-нибудь",
			"что-то",
			"чтоб",
			"чтобы",
			"чуть",
			"чье",
			"чье-либо",
			"чье-нибудь",
			"чье-то",
			"чья",
			"эи",
			"эта",
			"эти",
			"этим",
			"этими",
			"этих",
			"это",
			"этого",
			"этой",
			"этом",
			"этому",
			"этот",
			"этою",
			"эту",
			"эты",
			"я"
		);
    }
    /**
     * Сделать поисковую строку из запроса
     *
     * @param unknown_type $s
     */
    function lexamize($search)
    {
		$search  = strip_tags($search);
		$sumbols = '.,-=~!@#$%^&*()_+<>}{[]|/\\\'"?;:№\n\r';
		$search  = strtr($search, $sumbols, str_repeat(' ',strlen($sumbols)));
		$words   = explode(' ', UTF8::strtolower($search));    
		$cwords  = explode(' ', $search);
		// Убираем стоп-слова
		$stop_words = $this->get_stopwords();
		$words   = array_diff($words, $stop_words);
		unset($stop_words);
		foreach ($cwords as $i => $word) {
			if (isset($words[$i])) {
			    $words[$i] = $word;
			}
		}
		return implode(' ',$words);	
    }
	
}

if (function_exists('mysql_set_charset') === false) {
	/**
      * Sets the client character set.
      *
      * Note: This function requires MySQL 5.0.7 or later.
      *
      * @see http://www.php.net/mysql-set-charset
      * @param string $charset A valid character set name
      * @param resource $link_identifier The MySQL connection
      * @return TRUE on success or FALSE on failure
      */
	function mysql_set_charset($charset, $link_identifier = null)
	{
		if ($link_identifier == null) {
			return mysql_query('SET NAMES "'.$charset.'"');
		} else {
			return mysql_query('SET NAMES "'.$charset.'"', $link_identifier);
		}
	}
}
