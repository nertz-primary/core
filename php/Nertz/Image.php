<?php
/**
 * Класс для работы с изображениями
 *
 */
class Nertz_Image
{
	function Nertz_Image()
	{
		$this->im          = NULL;
		$this->result_file = "";
		$this->custom_im   = NULL;
	}
	/**
	 * Загрузить файл
	 *
	 * @param string $file_name Имя файла
	 * @return bool Если TRUE - то файл успешно загружен, иначе FALSE
	 */
	function load( $file_name )
	{
		if (!file_exists($file_name) || !is_file($file_name) || !is_readable( $file_name)) {
			return false;
		}
		$image_info = getimagesize( $file_name );
		$this->transparent_color = '';
		//echo $image_info['mime'] . "<br/>";
		switch ($image_info['mime']) {
			case 'image/gif':
				if (imagetypes() & IMG_GIF)	{
					$this->im = imagecreatefromgif($file_name) ;
					$transparent_index = imagecolortransparent($this->im);
					if ($transparent_index != -1) {
						$this->transparent_color = imagecolorsforindex($this->im,$transparent_index);
					}
				}
				break;
			case 'image/jpeg':
				if (imagetypes() & IMG_JPG) {
					$this->im = imagecreatefromjpeg($file_name) ;
				}
				break;
			case 'image/png':
				if (imagetypes() & IMG_PNG)	{
					$this->im = imagecreatefrompng($file_name) ;
				}
				break;
			case 'image/wbmp':
				if (imagetypes() & IMG_WBMP) {
					$this->im = imagecreatefromwbmp($file_name) ;
				}
				break;
			default:
				break;
		}
		if ($this->im) {
			return true;
		}
		return false;
	}
	/**
	 * Загрузить данные файла из памяти
	 *
	 * @param string $s Содержимое файла
	 */
	function load_from_memory($s)
	{
		$this->im = imagecreatefromstring($s) ;
		if ($this->im) {
			$transparent_index = imagecolortransparent($this->im);
			if ($transparent_index != -1) {
				$this->transparent_color = imagecolorsforindex($this->im,$transparent_index);
			}
			return true;
		}
		return false;
	}
	/**
	 * Сохранить файл на диск
	 *
	 * @param string $file_name Имя файла
	 * @param string $extension Расширение - в случае его отсуствия у файла
	 * @return bool Если TRUE то файл удалось записать, иначе FALSE
	 */
	function save( $file_name, $extension="" )
	{
		$path_parts = pathinfo($file_name);
		if (!is_resource($this->im) || !file_exists($path_parts['dirname']) || !is_dir($path_parts['dirname']) || !is_writable($path_parts['dirname'])) {
			return false;
		}
		if (empty($path_parts['extension'])) {
			if ($extension)	{
				$path_parts['extension'] = $extension;
			} else {
				$path_parts['extension'] = "jpg";
			}
			$file_name .= '.' . $path_parts['extension'];
		}
		switch ($path_parts['extension']) {
			case 'gif':
				if (imagetypes() & IMG_GIF) {
					return imagegif($this->custom_im ? $this->custom_im : $this->im, $file_name);
				}
				break;
			case 'jpeg':
				if (imagetypes() & IMG_JPG)	{
					return imagejpeg($this->custom_im ? $this->custom_im : $this->im, $file_name, 100);
				}
				break;
			case 'jpg':
				if (imagetypes() & IMG_JPG)	{
					return imagejpeg($this->custom_im ? $this->custom_im : $this->im, $file_name, 95);
				}
				break;
			case 'png':
				if (imagetypes() & IMG_PNG)	{
					return imagepng($this->custom_im ? $this->custom_im : $this->im, $file_name);
				}
				break;
			case 'wbmp':
				if (imagetypes() & IMG_WBMP) {
					return imagewbmp($this->custom_im ? $this->custom_im : $this->im, $file_name);
				}
				break;
			default:
				return false;
				break;
		}
	}

	/**
	 * Создает превьюшку из указанного файла, при это заполняется вся ширина и высота,
	 * лишние части отбрасываются
	 * @param int $width Ширина
	 * @param int $height Высота
	 * @param string $file_name Имя файла куда сохраним результат
	 */
	function make_smart_preview( $width, $height )
	{
		if (!$this->im || !$width || !$height) {
			return false;
		}
		$ow = imagesx($this->im);
		$oh = imagesy($this->im);
		$im = imagecreatetruecolor($width, $height);

		if ((float)$ow/(float)$oh < (float)$width/(float)$height) {
			// Высота копируемого фрагмена на оригинале
			$bh  = intval(floatval($ow * $height) / floatval($width));
			// Y-координая левого верхнего угла копируемого фрагмена оригинала
			$ltc = intval(floatval($oh - $bh) / floatval(2));
			$res = imagecopyresampled( $im, $this->im , 0, 0, 0, $ltc, $width, $height, $ow, $bh);
		} else {
			// Ширина копируемого фрагмена на оригинале
			$bw  = intval(floatval($oh * $width) / floatval($height));
			// Y-координая левого верхнего угла копируемого фрагмена оригинала
			$ltc = intval(floatval($ow - $bw) / floatval(2));
			$res = imagecopyresampled( $im, $this->im, 0, 0, $ltc, 0, $width, $height, $bw, $oh);
		}
		if ($res) {
			if ($this->result_file) {
				$this->custom_im = & $im;
				$this->save($this->result_file);
				imagedestroy($im);
				$this->custom_im = null;
			} else {
				imagedestroy($this->im);
				$this->im = $im;
			}

			return true;
		}
		return false;
	}
    /**
	 * Создает превьюшку из указанного файла, при это заполняется вся ширина и высота,
	 * недотсающее место заполняется цветом $bg_color
	 * @param int $width Ширина
	 * @param int $height Высота
	 * @param array $bg_color Цвет полей (по умолчанию белый #FFFFFF)
	 */
	function make_normal_preview($width, $height, $bg_color = '#FFFFFF')
	{
		if (!$this->im || !$width || !$height) {
			return false;
		}
		$ow = imagesx($this->im);
		$oh = imagesy($this->im);
		$im = imagecreatetruecolor($width, $height);
		imagefill($im,1,1, $this->hex_alocate($im, $bg_color));
		if ((float)$ow/(float)$oh < (float)$width/(float)$height) {
			// Высота копируемого фрагмена на оригинале
			$bw  = intval(floatval($ow * $height) / floatval($oh));
			// X-координая левого верхнего угла копируемого фрагмена оригинала
			$ltx = intval(floatval($width - $bw) / floatval(2));
			$res = imagecopyresampled( $im, $this->im , $ltx, 0, 0, 0, $bw, $height, $ow, $oh);
		} else {
			// Ширина копируемого фрагмена на оригинале
			$bh  = intval(floatval($oh * $width) / floatval($ow));
			// Y-координая левого верхнего угла копируемого фрагмена оригинала
			$lty = intval(floatval($height - $bh) / floatval(2));
			$res = imagecopyresampled( $im, $this->im, 0, $lty, 0, 0, $width, $bh, $ow, $oh);

		}
		if ($res) {
			if ($this->result_file) {
				$this->custom_im = & $im;
				$this->save($this->result_file);
				imagedestroy($im);
				$this->custom_im = null;
			} else {
				imagedestroy($this->im);
				$this->im = $im;
			}

			return true;
		}
		return false;
	}
	/**
	 * Меняет размер картинки до указанныйх размеров с соблюдением пропорций.
	 * без полей и образения
	 *
	 * @param int $width Ширина
	 * @param int $height Высота
	 */
	function resize($width, $height)
	{
		if (!$this->im || !$width || !$height)
		{
			return false;
		}
		$ow = imagesx($this->im);
		$oh = imagesy($this->im);
		$res = false;
		if ((float)$ow > (float)$width || (float)$oh > (float)$height) {
			if ((float)$ow > (float)$width) {
				$bw = $width;
			}
			else {
				$bw = $ow;
			}
			$bh = intval(((float)$oh / (float)$ow) * (float)$bw);
			if ($bh > $height) {
				$bw = intval((float)$ow / (float)$oh * (float)$height);
				$bh = $height;
			}
			$im = imagecreatetruecolor($bw, $bh);
			$this->set_and_fill_transparent($im);
			$res = imagecopyresampled( $im, $this->im , 0, 0, 0, 0, $bw, $bh, $ow, $oh);
		}
		if ($res) {
			if ($this->result_file) {
				$this->custom_im = & $im;
				$this->save($this->result_file);
				imagedestroy($im);
				$this->custom_im = null;
			} else {
				imagedestroy($this->im);
				$this->im = $im;
			}

			return true;
		}
		return false;
	}
	/**
	 * Создает превьюшку из указанного файла, при это заполняется вся ширина и высота,
	 * лишние части отбрасываются
	 * @param int $width Ширина
	 * @param int $height Высота
	 * @param string $file_name Имя файла куда сохраним результат
	 */
	function make_crop_preview( $width, $height )
	{
		if (!$this->im || !$width || !$height) {
			return false;
		}
		$ow = imagesx($this->im);
		$oh = imagesy($this->im);
		$im = imagecreatetruecolor($width, $height);

		$this->set_and_fill_transparent($im);

		if ((float)$ow/(float)$oh < (float)$width/(float)$height) {
			// Высота копируемого фрагмена на оригинале
			$bh  = intval(floatval($ow * $height) / floatval($width));
			// Y-координая левого верхнего угла копируемого фрагмена оригинала
			$ltc = 0;
			$res = imagecopyresampled( $im, $this->im , 0, 0, 0, $ltc, $width, $height, $ow, $bh);
		} else {
			// Ширина копируемого фрагмена на оригинале
			$bw  = intval(floatval($oh * $width) / floatval($height));
			// Y-координая левого верхнего угла копируемого фрагмена оригинала
			$ltc = 0;
			$res = imagecopyresampled( $im, $this->im, 0, 0, $ltc, 0, $width, $height, $bw, $oh);
		}
		if ($res) {
			if ($this->result_file) {
				$this->custom_im = & $im;
				$this->save($this->result_file);
				imagedestroy($im);
				$this->custom_im = null;
			} else {
				imagedestroy($this->im);
				$this->im = $im;
			}

			return true;
		}
		return false;
	}
	function sign( $text, $size, $color='#FFFFFF')
	{
		if (!$this->im || !$text) {
			return false;
		}
		$ow = imagesx($this->im);
		$oh = imagesy($this->im);
		global $url;
		$font  = $url->gen_static_url('img/tahoma.ttf');
		$color = $this->hex_alocate($this->im, $color);
		$box = imagettfbbox($size, 0, $font, $text);
		$res = imagettftext($this->im,
		$size,
		0,
		$ow - intval(floatval($ow)/20.0) - ($box[2] - $box[0]),
		$oh - intval(floatval($ow)/40.0),
		$color,
		$font,
		$text);
		if ($res) {
			return true;
		}
		return false;
	}

	/**
      * Показать файл
      *
      * @param string $file_name Имя файла
      * @return bool Если TRUE - то файл успешно загружен, иначе FALSE
      */
	function show( $file_name = '' )
	{

		$this->load($file_name);
		if ($this->im) {
			if ($time) {
				header("Last-Modified: " . gmdate("D, d M Y H:i:s", $time) . " GMT");
			}
			header('Content-type: image/jpeg');
			imagejpeg($this->im);
			return true;
		}
		return false;
	}
	/**
	 * Генерация превьюхи на основе данных обрезалки
	 *
	 * @param unknown_type $width
	 * @param unknown_type $height
	 * @param unknown_type $crop
	 * @param unknown_type $croper_width
	 * @param unknown_type $croper_height
	 */
	function cropped_preview($width, $height, $crop, $croper_width, $croper_height)
	{

	}
	/**
	 * Указать путь к файлу в который будет сохраненн результат следующей операции
	 *
	 * @param string $file_name
	 */
	function set_result_file($file_name)
	{
		$this->result_file = $file_name;
	}

	function hex_alocate($im, $hex)
	{
		$hex = ltrim($hex, '#');
		$int = hexdec($hex);
	    $color = array(0 => 0xFF & ($int >> 0x10), 1 => 0xFF & ($int >> 0x8), 2 => 0xFF & $int);
		return imagecolorallocate($im, $color[0], $color[1], $color[2]);
	}
	/**
	 * Залить фон прозрачным цветом, который был в изначальном GIF
	 *
	 * @param unknown_type $im
	 */
	function set_and_fill_transparent($im)
	{
		if ($this->transparent_color)  {
			$transparent_new = imagecolorallocate($im, $this->transparent_color['red'], $this->transparent_color['green'], $this->transparent_color['blue'] );
			$transparent_new_index = imagecolortransparent( $im, $transparent_new );
			imagefill($im,1,1, $transparent_new_index);
		}
	}
}