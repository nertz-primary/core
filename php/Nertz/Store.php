<?php
/**
 * Хранилище
 *
 */
define('PREVIEW_NAME', '__preview.jpg');

include_once(Nertz::class_path('Nertz_File'));
include_once(Nertz::class_path('Nertz_Image'));
class Nertz_Store
{
	function Nertz_Store()
	{

	}
	/**
	 * Добавить или обновить файл в хранилище
	 *
	 * @param array $file Эллемент из $_FILE
	 * @param int $ind Индекс, если 0 то файл добавляется, иначе файл обновляется
	 * @return int Индекс добавляемого или обновляемого файла
	 */
	static function upload($file, $ind=0, $params = array())
	{
		global $db;
		$ind = intval($ind);
		if(isset($file['tmp_name'])) {
			$md5 = md5_file($file['tmp_name']);
		} 
		if(isset($file['data'])) {
			$md5 = md5($file['data']);
		} 
		$old_file = Nertz_Store::get($ind);
		if (!isset($old_file['ind'])) {
			$ind = 0;
		} else {
			if (file_exists($old_file['path'])) {
				unlink($old_file['path']);
			}
			// Удаляем все известные превьюхи
			$pname = Nertz_Store::gen_image_path($old_file['ind'], 10, 10, 1);
			Nertz_File::clear_path(dirname($pname));
		}
		$file_name = Nertz_Store::ru_2_translit($file['name']);
		if ($ind) {
			$db->save('store_file', array('name' => $file_name, 'size' => $file['size'], 'md5' => $md5), array('ind' => $ind));
		} else {
			$ind = $db->save('store_file', array('name' => $file_name, 'size' => $file['size'], 'md5' => $md5));
		}
		if ($ind) {
			$file_name = Nertz_Store::gen_path($ind, $file_name,true, $params);
			if (!empty($file['data'])) {
				file_put_contents($file_name, $file['data']);
			} else {
	    		if (is_uploaded_file($file['tmp_name'])) {
					move_uploaded_file($file['tmp_name'], $file_name);    			
	    		} else {
	    			rename($file['tmp_name'], $file_name);
	    		}
			}
			chmod($file_name, 0777);
		}
		return $ind;
	}
	/**
	 * Сгенерировать превьюху для файла
	 *
	 * @param int $ind Индекс файла
	 * @param int $width Ширина будущей превьюхи
	 * @param int $height Высота будущей превьюхи
	 * @param string $bg_color цвет фона
	 * @param string $name Имя файла в хранилище, если оставить пустым то оно будет запрошено из БД
	 */
	static function gen_preview($ind, $width, $height, $bg_color, $name="")
	{
   		$file_name = Nertz_Store::gen_path($ind, $name);
   		$img = new Nertz_Image();
   		$img->load($file_name);
   		$img->make_normal_preview($width, $height, $bg_color);
   		$img->save(Nertz_Store::gen_path($ind, PREVIEW_NAME));
	}
	/**
	 * Придумаем Имя для файла
	 *
	 * @param int $ind Индекс файла
	 * @param string $name Имя файла
	 * @param bool $create Флаг необходимости создать путь
	 * @param assoc $params Параметры
	 * 			path - путь к хранилищу
	 * @return unknown
	 */
	static function gen_path($ind, $name = "", $create = false, $params = array())
	{
		if (!$name) {
   			$f = Nertz_Store::get($ind);
   			$name = $f['name'];
   		}
   		if(!empty($params['path'])) {
   			$store_path = $params['path'];
   		} else {
			$store_path = Nertz_File::get_store_path('store/files');
   		}
    	$file_name = $store_path . Nertz_File::make_store_name($ind, $name);
    	if ($create) {
    		$file_path = dirname($file_name);
    		if (!file_exists($file_path)) {
    			mkdir($file_path, 0777, true);
    		}
    	}
    	return $file_name;
	}
	static function gen_url($ind, $name, $full = false)
	{
		return Nertz_Store::gen_path($ind, $name);
	}
	/**
	 * Получить список файлов
	 *
	 * @param int/array $inds Индекс или массив Индеков
	 * @return Строки файлов
	 */
	static function get($inds, $params = array())
	{
		global $db;
		//  Много файлов
		if (!empty($inds) && is_array($inds) && count($inds)) {
			$res = $db->getAll('SELECT * FROM ?_store_file WHERE ind IN (?a)', array($inds));
			if (is_array($res)) {
				foreach ($res as $id => $r) {
					$res[$id]['url']  = '/' . Nertz_Store::gen_url($r['ind'],  $r['name']);
					$res[$id]['path'] = Nertz_Store::gen_path($r['ind'], $r['name'], $params);
				}
			}
		//  Один файл
		} else if(true) {
			$res = $db->getRow('SELECT * FROM ?_store_file WHERE ind =?', array($inds));
			if (is_array($res)) {
				$res['url']  = '/' . Nertz_Store::gen_url($res['ind'],$res['name']);
				$res['path'] = Nertz_Store::gen_path($res['ind'],$res['name'], $params);
			}
		} else {
			$res = false;
		}

		return $res;
	}
	/**
	 * Удалить файлы из хранилища
	 *
	 * @param array $inds  Индексы удаляемых файлов
	 * @param array $names Имена связанных файлов, которые тоже должны быть удалены(Превьюшки, ресайзы картинок и прочая муть)
	 */
	static function delete($inds, $names = null, $params = array())
	{
		global $db;
		if (!$inds) {
			return false;
		}
		if (!is_array($inds)) {
			$inds = array($inds);
		}
		$files = Nertz_Store::get($inds, $params);
		if (is_array($files)) {
			foreach ($files as $file) {
				if (file_exists($file['path'])) {
					unlink($file['path']);
				}
				// Удаляем все известные превьюхи
				$pname = Nertz_Store::gen_image_path($file['ind'],10,10, 1);
				Nertz_File::clear_path(dirname($pname));
				
				if (is_array($names)) {
					foreach ($names as $name) {
						$fname = Nertz_Store::gen_path($file['ind'], $name, $params);
						if (file_exists($fname)) {
							unlink($fname);
						}
					}
				}
			}
			$db->sql_query('DELETE FROM ?_store_file WHERE ind IN (?a)', array($inds));
		}
	}
	static function gen_image_path($ind, $width, $height, $type)
	{
		$name = intval($width) . '_' . intval($height) . '_' . $type . '.jpg';
		$store_path = Nertz_File::get_store_path('store/files');
    	$file_name = $store_path . Nertz_File::make_store_name($ind, 'previews');
    	return $file_name . '/' . $name;
	}
	static function gen_image_url($ind, $width, $height, $type=1, $created = 0, $full = 0)
	{
		global $url;
		$full = true;
		$prefix = trim($url->handler->prefix,'/') ;
		if ($prefix) {
			$prefix = '/' . $prefix;
		}
		//$sep = $full ? '&' : '&amp;';
		$sep = '&amp;';
		if ($full) {
			$prefix =  'http://' .  Nertz::server_name() . $prefix ;
		}
		return $prefix.'/image.php?ind=' . $ind . $sep . 'width=' . $width . $sep . 'height=' . $height . $sep . 'type=' . $type. ($created ? $sep . 'created=' . $created : '');
	}
	static function ru_2_translit($filename) {
	    $changes = array(
	        "Є"=>"EH", "І"=>"I", "і"=>"i", "№"=>"#", "є"=>"eh",
	        "А"=>"A", "Б"=>"B", "В"=>"V", "Г"=>"G", "Д"=>"D",
	        "Е"=>"E", "Ё"=>"E", "Ж"=>"ZH", "З"=>"Z", "И"=>"I",
	        "Й"=>"J", "К"=>"K", "Л"=>"L", "М"=>"M", "Н"=>"N",
	        "О"=>"O", "П"=>"P", "Р"=>"R", "С"=>"S", "Т"=>"T",
	        "У"=>"U", "Ф"=>"F", "Х"=>"H", "Ц"=>"C", "Ч"=>"CH",
	        "Ш"=>"SH", "Щ"=>"SCH", "Ъ"=>"", "Ы"=>"Y", "Ь"=>"",
	        "Э"=>"E", "Ю"=>"YU", "Я"=>"YA", "Ē"=>"E", "Ū"=>"U",
	        "Ī"=>"I", "Ā"=>"A", "Š"=>"S", "Ģ"=>"G", "Ķ"=>"K",
	        "Ļ"=>"L", "Ž"=>"Z", "Č"=>"C", "Ņ"=>"N", "ē"=>"e",
	        "ū"=>"u", "ī"=>"i", "ā"=>"a", "š"=>"s", "ģ"=>"g",
	        "ķ"=>"k", "ļ"=>"l", "ž"=>"z", "č"=>"c", "ņ"=>"n",
	        "а"=>"a", "б"=>"b", "в"=>"v", "г"=>"g", "д"=>"d",
	        "е"=>"e", "ё"=>"e", "ж"=>"zh", "з"=>"z", "и"=>"i",
	        "й"=>"j", "к"=>"k", "л"=>"l", "м"=>"m", "н"=>"n",
	        "о"=>"o", "п"=>"p", "р"=>"r", "с"=>"s", "т"=>"t",
	        "у"=>"u", "ф"=>"f", "х"=>"h", "ц"=>"c", "ч"=>"ch",
	        "ш"=>"sh", "щ"=>"sch", "ъ"=>"", "ы"=>"y", "ь"=>"",
	        "э"=>"e", "ю"=>"yu", "я"=>"ya", "Ą"=>"A", "Ę"=>"E",
	        "Ė"=>"E", "Į"=>"I", "Ų"=>"U", "ą"=>"a", "ę"=>"e",
	        "ė"=>"e", "į"=>"i", "ų"=>"u", "ö"=>"o", "Ö"=>"O",
	        "ü"=>"u", "Ü"=>"U", "ä"=>"a", "Ä"=>"A", "õ"=>"o",
	        "Õ"=>"O", "є"=>"e", "Є"=>"e", "ї"=>"yi","Ї"=>"yi", 
	        "і"=>"i", "І"=>"i", "ґ"=>"g", "Ґ"=>"g");
	    $alias=strtr($filename, $changes);
	    $alias = strtolower( $alias );
	    $alias = preg_replace('/&.+?;/', '', $alias); // kill entities
	    $alias = str_replace( '_', '-', $alias );
	    $alias = preg_replace('/[^a-z0-9\s-.]/', '', $alias);
	    $alias = preg_replace('/\s+/', '-', $alias);
	    $alias = preg_replace('|-+|', '-', $alias);
	    $alias = trim($alias, '-');
	    return $alias;
	}
}