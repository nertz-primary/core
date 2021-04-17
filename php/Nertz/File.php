<?php
/**
 * Класс работы с файлами.
 * Через него будем проводить все операции работы с файлами, 
 * чтобы в дальнейшем можно было более удобно управлять всем потоками
 *
 */
class Nertz_File
{
    /**
     * Конструктор
     *
     * @param string $store Имя хранилища из списка $this->get_store_path()
     * @param string $file_name Имя файла без пути
     * @param string $mode Режим открытия, аналогично fopen
     */
    function Nertz_File($store, $file_name, $mode)
    {
        $this->open($store, $file_name, $mode);
    }
    /**
     * Открыть файл
     *
     * @param string $store Имя хранилища из списка $this->get_store_path()
     * @param string $file_name Имя файла без пути
     * @param string $mode Режим открытия, аналогично fopen
     */
    function open($store, $file_name, $mode)    
    {
        $this->file_name = $this->_get_real_name($store, $file_name);
        if (strpos('r',$mode) !== false && !file_exists($this->file_name)) {
            return false;
        }
        $file_path = dirname($this->file_name);
        // Если мы в режиме записи то создадим все промежуточные папки
    	if (strpos('w',$mode) !== false && !file_exists($file_path)) {
    		mkdir($file_path, 0777, true);
    	}
        $this->handler   = fopen($this->file_name, $mode);
        $this->mode = $mode;
        if (!$this->handler) {
            return false;
        }
        return true;
    }
    /**
     * Получить реальное имя файла
     *
     * @param unknown_type $store
     * @param unknown_type $file_name
     */
    static function _get_real_name($store, $file_name)
    {
    	if (!($sp = Nertz_File::get_store_path($store))) {
            Nertz::log("Неизвестное хранилище \"$store\"",LOG_WARN,3);
        }
        return $sp . ltrim($file_name,'/');
    }
    /**
     * Закрыть зараннее открытый файл
     *
     */
    function close()
    {
        if(isset($this->handler) && $this->handler) {
            fclose($this->handler);
        }
    }
    /**
     * Чтение куска данныйх из файла
     *
     * @param int $length Длина читаемого куска
     */
    function read($length=0)
    {
        return fread($this->handler, $length);
        
    }
    /**
     * Запись данный в файл
     *
     * @param void $data Данные
     * @return void
     */
    function write($data)
    {
        if (!$this->handler) {
        	Nertz::log('Не могу записть данные в файл ' . $this->file_name, LOG_WARNING, 1);
        	return false;
        }
    	$res = fwrite($this->handler, $data);
        return $res;
    }
    /**
     * Считать весь файл разом
     *
     * @return string
     */
    function get_all()
    {
        if (!(isset($this->handler) && $this->handler)) {
            return null;
        }
        if (function_exists('stream_get_contents')) {
            return stream_get_contents($this->handler);
        }
        return file_get_contents($this->file_name);
    }
    /**
     * Получить путь к хранилищу
     *
     * @param string $store Имя хранилища
     * @return unknown
     */
    static function get_store_path($store)
    {
        $nertz_file_stores = array(
        'root'   => $GLOBALS['__base_path'] . '/',
        'store/files'   => 'files/store/',
        );
        if(!isset($nertz_file_stores[$store])) {
            return null;
        }
        return $nertz_file_stores[$store];
    }
    /**
     * Рассовывание файлов по папкам в соотествии с индексом
     *
     * @param int $ind Уникальный индекс, например, 1234567
     * @param string $file_name Имя файла, например, abc.txt
     * @return Результат, например, 12/34/56/7-abc.txt
     */
    static function make_store_name($ind, $file_name)
    {
    	$ind = intval($ind);
    	$arr = array();
    	while ($ind) {
    		$arr[] = strval(intval($ind) % 100);
    		$ind   = intval($ind) / 100;
    	}
    	$ind = array_shift($arr);
    	array_pop($arr);
    	$arr = array_reverse($arr);
    	$fn = "";
    	foreach ($arr as $a) {
    		$fn .= $a . "/";
    	}
    	if (!$ind) {
    		$ind = 0;
    	}
    	return $fn . $ind . '-' . $file_name;
    }
    static function show_size($size, $precision=1)
    {
    	$as = array('б','Кб', 'Мб', 'Гб', 'Тб');
    	$i = 0;
    	while ($size>1000) {
    		$size   = intval($size) / 1000;
    		$i++;
    	}
		return sprintf("%.{$precision}f", $size) . "&nbsp;".$as[$i];
    }
    static function icon_path($file_name) 
    {
    	$pin = pathinfo($file_name);
    	return '/includes/fckeditor/editor/filemanager/browser/default/images/icons/32/' . strtolower($pin['extension']). '.gif';
    }
    static function ls($store, $path)
    {
    	$res = array();
    	$path = Nertz_File::_get_real_name($store, $path);
    	if (is_dir($path)) {
    		if ($dh = opendir($path)) {
    			while (($file = readdir($dh)) !== false) {
    				if ($file[0] != '.') {
    					$res[] = $file;
    				}
    			}
    			closedir($dh);
    		}
    	}
    	return $res;
    }
    static function clear_path($dir)
	{
    	if(!$dh = @opendir($dir)) {
        	return;
    	}
    	while (false !== ($obj = readdir($dh))) {
        	if($obj == '.' || $obj == '..')	{
            	continue;
        	}
	        if (!@unlink($dir . '/' . $obj)) {
            	unlinkRecursive($dir.'/'.$obj);
        	}
    	}
	    closedir($dh);
        @rmdir($dir);
    	return;
	} 
    static function tmp_name($name)
    {
    	$base_path = $GLOBALS['__base_path'];
		return $base_path . '/temp/files/' . time() . getmypid() . '-' . $name;
    }
    static function translit($string) 
    {
    	
    }
}
function unlinkRecursive($path) 
{
	if (is_file($str)) { 
		return @unlink($str);
	} elseif(is_dir($str)) {
		$scan = glob(rtrim($str,'/').'/*');
		foreach($scan as $index=>$path){
			recursiveDelete($path);
		}
		return @rmdir($str);
	}
}
