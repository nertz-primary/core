<?php

class DB_Query 
{
	/**
	 * Поля запроса
	 *
	 * @var array
	 */
	private $fields = array();
	
	/**
	 * Таблица запроса
	 *
	 * @var string
	 */
	private $table = '';
	
	/**
	 * Джойны запроса
	 *
	 * @var array
	 */
	private $joins = array();
	
	/**
	 * Условия запроса
	 *
	 * @var array
	 */
	private $wheres = array();
	
	/**
	 * Сортировки запроса
	 *
	 * @var array
	 */
	private $orders = array();
	
	/**
	 * Конструктор
	 *
	 * @return DB_Query
	 */
	function DB_Query()
	{
		
	}
	
	/**
	 * Добавить поле в запроса
	 *
	 * @param string $name Имя поля, например, user.login
	 * @param string $alias Алиас поля, например, login
	 */
	function field($name, $alias = '')
	{
		$this->fields[] =  array('name' => $name, 'alias' => $alias);
	}
	
	/**
	 * Задать имя таблицы
	 *
	 * @param string $name Имя таблицы, например, user
	 */
	function table($name) 
	{
		$this->table = $name;
	}
	
	/**
	 * Добавить left join
	 *
	 * @param string $table Имя таблицы
	 * @param unknown_type $alias
	 * @param unknown_type $conditions
	 */
	function left_join($table, $alias, $conditions)
	{
		
	}
	/**
	 * Условия запроса.
	 *
	 * @param string $s Строка запроса, например, 'ind = ?'
	 * @param array  $params Параметры запроса, например array($ind)
	 * @param string $op Операция к предыдущем условияю, например "AND" или "OR"
	 */
	function where($s, $params, $op = 'AND')
	{
		
	}
	
}

