<?php
class Nertz_Cache
{
	function Nertz_Cache($params)
	{
		$this->cache_path = Nertz::config('/path/cache','temp/cache/');
	}
	function set_value($category, $name, $value, $serialize=true)
	{
		$category_path =  $this->cache_path . "/" . $category;
		if (!file_exists($category_path))
		{
			mkdir($category_path,0777);
		}
		$fn = $category_path . "/" . $name . ".txt";
		if (!file_exists($category_path) || !$f=fopen($fn,"wb"))
		{
			return false;
		}
		if ($serialize)
		{
			if (!fwrite($f,serialize($value)))
			{
				return false;
			}
		}
		else
		{
			if (!fwrite($f,$value))
			{
				return false;
			}
		}
		fclose($f);
	}
	/**
	 * Получение записи из кэша
	 *
	 * @param string $category Категория
	 * @param string $name Имя записи
	 * @param bool $serialize Флаг необходимости сериализации данных
	 * @param int $life_time Время жизни, если 0, то запись живет вечно
	 * @return unknown
	 */
	function & get_value($category, $name, $serialize=true, $life_time=0)
	{
		$tt = microtime();
		$category_path =  $this->cache_path . "/" . $category;
		$fn = $category_path . "/" . $name . ".txt";
		if (!file_exists($fn)) {
			$res = $serialize ? array() : NULL;
			return $res;
		}
		// Если кэш устарел то вернем - пусто
		if ($life_time) {
			if (filemtime($fn) < time() - $life_time) {
				$res = $serialize ? array() : NULL;
				return $res;
			}
		}
		$s = @file_get_contents($fn);
		if (empty($s)) {
			$res = $serialize ? array() : NULL;
			return $res;
		}
		if ($serialize) {
			$ret = unserialize($s);
		} else {
			$ret = &$s;
		}
		$tt = microtime()-$tt;
		global $db_stat_count, $db_stat;
		$db_stat[$db_stat_count]['sql'] = "[$category]$name";
		$db_stat[$db_stat_count]['time'] = $tt;
		$db_stat[$db_stat_count]['result'] = 'OK';
		Nertz::log($db_stat[$db_stat_count], LOG_DB, 1 );		
		$db_stat_count++;
		return $ret;
	}
	function drop_value($category, $name)
	{
		$fn = $this->cache_path . "/" . $category . "/" . $name . ".txt";
		if (file_exists($fn))
		{
			return unlink($fn);
		}
		return false;
	}
	function drop_category($category)
	{
		$category_path =  $this->cache_path . "/" . $category;
		if (is_dir( $category_path ))
		{
			if ($dh = opendir($category_path))
			{
				while (($file = readdir($dh)) !== false)
				{
					$fn = $category_path . "/" . $file;
					if (is_file($fn))
					{
						unlink($fn);
					}
				}
				closedir($dh);
			}
		}
	}
	function drop_all()
	{
		if (is_dir($this->cache_path))
		{
			if ($dh = opendir($this->cache_path))
			{
				while (($directory = readdir($dh)) !== false)
				{
					$fn = $this->cache_path."/".$directory;
					if (is_dir($fn) && $directory!="." && $directory!="..")
					{
						$this->drop_category($directory);
					}
				}
				closedir($dh);
			}
		}
	}
	
}
