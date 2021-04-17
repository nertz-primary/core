<?php
class Nertz_Group
{
	function Nertz_Group()
	{
		$this->system_groups = array('ВСЕ', 'Админы', 'Разработчики', 'Анонимы');
	}
	function add($params)
	{
		
	}
	function delete($params)
	{
		
	}
	function update($ind, $params)
	{
		
	}
	/**
	 * Получает список групп
	 *
	 */
	function get_list()
	{
		global $db;
		$groups = $db->getAll('SELECT * FROM `?_group` ORDER BY `name`');
		$added = false;
		foreach ($this->system_groups as $grp)
		{
			$found = false;
			if (is_array($groups) && count($groups))
			{
				foreach ($groups as $g)
				{
					if ($g['name'] == $grp)
					{
						$found = true;
					}
				}
			}
			if (!$found)
			{
				$db->save('group',array('name' => $grp));
				$added = true;
			}
		}
		if ($added)
		{
			$groups = $db->getAll('SELECT * FROM `?_group` ORDER BY name');
		}
		
		return $groups;
	}
}