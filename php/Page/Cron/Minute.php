<?php
/**
 * Страница редактора структуры конфигурационных файлов
 *
 */
include_once(Nertz::class_path('Nertz_Mail'));

class Page_Cron_Minute extends Nertz_Page 
{
    function Page_Cron_Minute($name)
    {
        parent::Nertz_Page($name);
    }
    function show()
    {
        // Отправим почту
        $mail = new Nertz_Mail();
        while ($mail->send_one_cron()) {
        	
        }
        exit();
    }
}
