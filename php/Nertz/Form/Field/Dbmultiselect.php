<?php
include_once(Nertz::class_path('Nertz_Form_Field_Multiselect'));

class Nertz_Form_Field_Dbmultiselect extends Nertz_Form_Field_Multiselect
{
	function Nertz_Form_Field_Dbmultiselect($name, &$form)
	{
		parent::Nertz_Form_Field_Multiselect($name, $form); 
		$this->db_list_sql		    = $this->params['db_list_sql']; 
		$this->db_connect_table_name    = $this->params['db_connect_table_name'];
		$this->db_connect_item_index    = $this->params['db_connect_item_index'];
		$this->db_connect_list_index    = $this->params['db_connect_list_index']; 
		
		// Если нет запроса то пользуемся значениями что есть
		if ($this->db_list_sql) {
			global $db;
			$this->params['values'] = $db->getAssoc($this->db_list_sql);	
		}
	}
	
	///////////////////////////////////////////////////////////////////////////////////	
	function before_save(&$params, $act, $ind) {
		$this->items = $params[$this->name];
		unset($params[$this->name]);
		return true;
	}
	function after_save(&$params, $act, $ind) {
		global $db;
		$db->sql_query('DELETE FROM `?_?#` WHERE `?#` = ?', array($this->db_connect_table_name, $this->db_connect_item_index,  $ind));
		if (!empty($this->items) && is_array($this->items) && count($this->items)) {
			$arr = array();
			foreach ($this->items as $i) {
				$arr[] = array($this->db_connect_item_index => $ind, $this->db_connect_list_index => $i);
			}
			$db->mass_insert($this->db_connect_table_name, array($this->db_connect_item_index, $this->db_connect_list_index), $arr);
		}
		return true;
	}
	function after_delete(&$inds) {
		global $db;
		$db->sql_query('DELETE FROM `?_?#` WHERE `?#` IN (?a)', array($this->db_connect_table_name, $this->db_connect_item_index, $inds));
		return true;
	}
	function after_fetch(&$res) {
		global $db;
		if (count($res)) {
			$inds = array();
			$binds = array();
			if (is_array($res) && count($res))  {
				foreach ($res as $i => $row) {
					$inds[$i] = $row[$this->form->index_name()];
					$res[$i][$this->name] = array();
					$binds[$inds[$i]] = $i;
				}
				$items = $db->getAll('SELECT * FROM `?_?#` WHERE `?#` IN (?a)', array($this->db_connect_table_name, $this->db_connect_item_index,  $inds));
				foreach ($items as $item)  {
					$res[$binds[$item[$this->db_connect_item_index]]][$this->name][] =  $item[$this->db_connect_list_index];
				}
			}
		}
		return true;
	}
	function after_get(&$params) {
		global $db;
		$items = $db->getCol('SELECT `?#` FROM `?_?#` WHERE `?#` = ?', array( $this->db_connect_list_index, $this->db_connect_table_name, $this->db_connect_item_index, $params[$this->form->index_name()]));
		$params[$this->name] = $items;
		return true;
	}
	
	
}