<?php
$GLOBALS['nertz_date_monthes'] = array(
	0,
	31, // Январь
	28, // Февраль
	31,	// Март
	30, // Апрель
	31, // Май
	30, // Июнь
	31, // Июль
	31, // Август
	30, // Сентябрь
	31, // Октябрь
	30, // Ноябрь
	31  // Декабрь
);
class Nertz_Date
{
	var $year = 0;
	var $month = 0;
	var $day = 0;
	var $hour = 0;
	var $minute = 0;
	var $second = 0;
	/**
	 * Установить дату из строки в формате mysql
	 *
	 * @param String $s Дата
	 */
	function set_mysql($s) 
	{
		list ($this->year, $this->month, $this->day, $this->hour, $this->minute, $this->second) = sscanf($s, "%d-%d-%d %d:%d:%d");
	}
	/**
	 * Установить дату из числа timestamp
	 *
	 * @param Int $i Дата
	 */
	function set_timestamp($i) 
	{
		$dt = getdate($i);
		$this->year   = $dt['year'];
		$this->month  = $dt['mon'];
		$this->day    = $dt['mday'];
		$this->hour   = $dt['hours'];
		$this->minute = $dt['minutes'];
		$this->second = $dt['seconds'];
	}
	/**
	 * Установить дату из строки в формате календаря.
	 *
	 * @param String $s Дата
	 */
	function set_calendar($s) 
	{
		list ($this->day, $this->month, $this->year) = sscanf($s, "%d-%d-%d");
		$this->hour   = 0;
		$this->minute = 0;
		$this->second = 0;
	}
	
	function get_mysql()
	{
		//$s = implode('-', array($this->year, $this->month, $this->day));
		$s = $this->year . '-' . lead_zero($this->month) . '-' . lead_zero($this->day);
		if ($this->hour || $this->minute || $this->second) {
			//$s .= ' ' . implode(':', array($this->hour, $this->minute, $this->second));
			$s .= ' ' . $this->hour . ':' . $this->minute . ':' . $this->second;
		}
		return $s;
	}
	function get_timestamp()
	{
		return mktime($this->hour, $this->minute, $this->second, $this->month, $this->day, $this->year);
		//return $this->get_nertz();
	}
	function get_calendar()
	{
		return implode('-', array($this->day, $this->month, $this->year));
	}
	function get_nertz()
	{
		$mult = 1;
		$res = 0;
		$res += $this->second * $mult;
		$mult *= 60;
		$res += $this->minute * $mult;
		$mult *= 60;
		$res += $this->hour * $mult;
		$mult *= 24;
		$res += $this->day * $mult;
		$mult *= 31;
		$res += $this->month * $mult;
		$mult *= 12;
		$res += $this->year * $mult;
		print_r($res);
		echo "<br/>";
		return $res;
	}
	function get_day()
	{
		return $this->day;
	}
	function set_day($i)
	{
		$this->day = $i;
	}
	function get_month()
	{
		return $this->month;
	}
	function set_month($i)
	{
		$this->month = $i;
	}
	function get_year()
	{
		return $this->year;
	}
	function set_year($i)
	{
		$this->year = $i;
	}
	function get_hour()
	{
		return $this->hour;
	}
	function set_hour($i)
	{
		$this->hour = $i;
	}
	function get_minute()
	{
		return $this->minute;
	}
	function set_minute($i)
	{
		$this->minute = $i;
	}
	function get_second()
	{
		return $this->second;
	}
	function set_second($i)
	{
		$this->second = $i;
	}
	function set_date(Nertz_Date $dt)
	{
		$this->set_day($dt->get_day());
		$this->set_month($dt->get_month());
		$this->set_year($dt->get_year());
	}
	/**
	 * Сравненение с другой датой
	 *
	 * @param Nertz_Date $date Другая дата
	 * @return 0 - даты равны, -1 - наша дата меньше другой, 1 другая дата меньше нашей
	 */
	
	function compare(Nertz_Date $date)
	{
		if ($this->year < $date->year ||( $this->year == $date->year && ($this->month < $date->month || ($this->month == $date->month && $this->day < $date->day)))) {
			return -1;
		}
		if ($date->get_day() == $this->day && $date->get_month() == $this->month && $date->get_year() == $this->year) {
			return 0;
		}
		return 1;
	}
	/**
	 * Преход к следующему дню
	 *
	 */
	function next_day() 
	{
		global $nertz_date_monthes;
		$this->day++;
		if ($this->month == 2 && ($this->year % 4 == 0) || ($this->year % 100 == 0)) {
			if ($this->day > 29) {
				$this->day = 1;
				$this->month++;
			}
		} else if ($this->day > $nertz_date_monthes[$this->month]) {
			$this->day = 1;
			$this->month++;
		}
		if ($this->month > 12) {
			$this->month = 1;
			$this->year++;
		}
	}
	/**
	 * Преход к предыдущему дню
	 *
	 */
	function prev_day() 
	{
		global $nertz_date_monthes;
		$this->day--;
		if ($this->day == 0) {
			$this->month--;
			if ($this->month == 2 && ($this->year % 4 == 0) || ($this->year % 100 == 0)) {
				$this->day = 29;
			} else {
				$this->day = $nertz_date_monthes[$this->month];
			}
		}
		if ($this->month == 0) {
			$this->year--;
			$this->month = 12; 
		}
	}
	/**
	 * Переход к следующему месяцу
	 *
	 */
	function next_month() 
	{
		global $nertz_date_monthes;
		$this->month++;
		if ($this->month > 12) {
			$this->month = 1;
			$this->year++;
		}
		$this->_fix_day();
	}
	/**
	 * Переход к предыдущему месяцу
	 *
	 */
	function prev_month() 
	{
		global $nertz_date_monthes;
		$this->month--;
		if ($this->month < 1) {
			$this->month = 12;
			$this->year--;
		}
		$this->_fix_day();
	}
	/**
	 * Поправим дни после сдвига месяца
	 *
	 */
	function _fix_day() 
	{
		global $nertz_date_monthes;
		if ($this->month == 2 && ($this->year % 4 == 0) || ($this->year % 100 == 0)) {
			if ($this->day > 29) {
				$this->day = 29;
			}
		} else if ($this->day > $nertz_date_monthes[$this->month]) {
			$this->day = $nertz_date_monthes[$this->month];
		}
		if ($this->day < 1 ){
			$this->day = 1;
		}
	}
	/**
	 * Сдвиг даты на определенное число месяцев
	 *
	 * @param Int $n Количество месяцев для сдвига даты
	 */
	function add_monthes($n) 
	{
		if ($n>0) {
			while ($n-->0) {
				$this->next_month();	
			}
			return;
		}
		if ($n<0) {
			while ($n++<0) {
				$this->prev_month();	
			}
		}
	}
	
	/**
	 * Сдвиг на час вперед
	 *
	 */
	function next_hour()
	{
		$this->hour++;
		while ($this->hour >= 24) {
			$this->hour -= 24;
			$this->next_day();
		}
	}
	
	/**
	 * Поиск максимальной даты среди всех переданых дат
	 * @params Nertz_Date Любое количество
	 * @return Nertz_Date
	 * 
	 */
	function max() 
	{
		$num = func_num_args();
		if (!$num) {
			return $this;
		}
		$args = func_get_args();
		$max = $this->dup();
		for ($i = 0; $i < $num; $i++) {
			if ($max->compare($args[$i]) < 0) {
				$max->set_date($args[$i]);
			}
		}
		return $max;
		
	}
	/**
	 * Поиск минимальной даты среди всех переданых дат
	 * @params Nertz_Date Любое количество
	 * @return Nertz_Date
	 * 
	 */
	function min() 
	{
		$num = func_num_args();
		if (!$num) {
			return $this;
		}
		$args = func_get_args();
		$min = $this->dup();
		for ($i = 0; $i < $num; $i++) {
			if ($min->compare($args[$i]) > 0) {
				$min->set_date($args[$i]);
			}
		}
		return $min;
	}
	/**
	 * Скопировать объект
	 *
	 * @return Nertz_Date Новый объект с тем же содержимым
	 */
	function dup()
	{
		$res = new Nertz_Date();
		$res->set_date($this);
		return $res;
	}
	/**
	 * Сдвиг даты на определенное число дней
	 *
	 * @param Int $n Количество дней для сдвига даты
	 */
	function add_days($n) 
	{
		if ($n>0) {
			while ($n>0) {
				$this->next_day();	
				$n--;
			}
		}
		if ($n<0) {
			while ($n<0) {
				$this->prev_day();	
				$n++;
			}
		}
	}
	static function get_monthes()
	{
		return array(
		1 => 'январь',
		2 => 'февраль',
		3 => 'март',
		4 => 'апрель',
		5 => 'май',
		6 => 'июнь',
		7 => 'июль',
		8 => 'август',
		9 => 'сентябрь',
		10 => 'октябрь',
		11 => 'ноябрь',
		12 => 'декабрь'
		);
	}
	static function get_monthes1()
	{
		return array(
		1 => 'января',
		2 => 'февраля',
		3 => 'марта',
		4 => 'апреля',
		5 => 'мая',
		6 => 'июня',
		7 => 'июля',
		8 => 'августа',
		9 => 'сентября',
		10 => 'октября',
		11 => 'ноября',
		12 => 'декабря'
		);
	}
	function get_month_literal()
	{
		$ms = $this->get_monthes();
		
		return !empty($ms[$this->month]) ? $ms[$this->month] : '';
	}
	function get_month_literal1()
	{
		$ms = $this->get_monthes1();
		return !empty($ms[$this->month]) ? $ms[$this->month] : '';
	}
	
	function get_age() 
	{
		$dt = getdate();
		$div = $dt['year'] - $this->year - 1;
		if ($div > 0) {
			if ($dt['mon'] > $this->month) {
				$div++;
			} elseif ($dt['mon'] == $this->month && $dt['mday'] >= $this->day) {
				$div++;
			}
		}
		return $div;		
	}
	function get_american()
	{
		$s = implode('/', array($this->month, $this->day, $this->year));
		if ($this->hour || $this->minute || $this->second) {
			$s .= ' ' . implode(':', array($this->hour, $this->minute, $this->second));
		}
		return $s;
	}
	function set_american($s)
	{
		list ($this->month, $this->day, $this->year, $this->hour, $this->minute, $this->second) = sscanf($s, "%d/%d/%d %d:%d:%d");
	}
	/**
	 * Возвращет день недели в виде числа
	 * 0 - Воскресенье
	 * 1 - Понедельник
	 * 2 - Вторник
	 * ...
	 * 6 - Суббота
	 *
	 * @return unknown
	 */
	function get_week_day()
	{
		$a = floor((14-intval($this->month)) / 12);
		$y = intval($this->year) - $a;
		$m = intval($this->month) + 12*$a -2;
		return  (7000 + (intval($this->day) + $y + floor($y / 4) - floor($y / 100) + floor($y / 400) + (31 * $m) / 12)) % 7;
	}
	function get_week_days()
	{
		return array(
			0 => 'Воскресенье',
			1 => 'Понедельник',
			2 => 'Вторник',
			3 => 'Среда',
			4 => 'Четверг',
			5 => 'Пятница',
			6 => 'Суббота',
		);
	}
	function get_week_days1()
	{
		return array(
			0 => 'Вс',
			1 => 'Пн',
			2 => 'Вт',
			3 => 'Ср',
			4 => 'Чт',
			5 => 'Пт',
			6 => 'Сб',
		);
	}
	/**
	 * Сбросить время на 00:00:00
	 *
	 */
	function reset_time()
	{
		$this->set_hour(0);
		$this->set_minute(0);
		$this->set_second(0);
	}
}