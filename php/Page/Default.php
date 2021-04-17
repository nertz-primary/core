<?php
/**
 * Страница редактора структуры конфигурационных файлов
 *
 */
class Page_Default extends Nertz_Page 
{
    function Page_Nertz_Config_Structure($name)
    {
        parent::Nertz_Page($name);
    }
    function show()
    {
        global $url;
        $s  = "<p>Эта строница будет главной и на ней следует собрать большую часть информации о сайте в кратком виде, чтобы админ мог видеть где требуется его вмешательство и быстро попадать туда. Джумла как пример - но у нас будет круче :)</p>";
        return $s;
    }
}