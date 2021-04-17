<?php
/**
 * Базовый класс для событий Форм
 *
 * От него будем наследовать все обработчики
 * Этот класс должен выступать примером того что и как делать
 * @author Kirill
 *
 */
class Nertz_Form_Event
{
	/**
	 * Форма
	 * @var Nertz_Form
	 */
	var $form;
	/**
	 * Конструктор формы
	 * @param Nertz_From $form Экземпляр формы
	 */
	function Nertz_Form_Even(&$form)
	{

	}

	/**
	 * Пример импользования функции генерации заголовка таблицы
	 * @param Nertz_Form $form
	 */
	function on_table_header(&$form)
	{

	}
	/**
	 * Пример импользования функции генерации подвала таблицы
	 * @param Nertz_Form $form
	 */
	function on_table_footer(&$form)
	{

	}
}