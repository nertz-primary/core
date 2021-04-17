<?php
class Nertz_Tree
{
	/**
	 * Функция получения эллементов ветви дерева
	 */
	private $_branch_function = "";
	/**
	 * Функция получения пути к эллементу
	 */
	private $_path_function = "";
	function set_branch_function($func)
	{
		$this->_branch_function = $func;
	}
	function set_path_function($func)
	{
		$this->_path_function = $func;
	}
	function render_tree($ind, $full, $params = array())
	{
		global $template, $db, $url;
		$params['full'] = $full;
		if ($full && $ind) { // отображем целую раскрытую ветвь
			$path = Nertz::call($this->_path_function, array('ind' => $ind));
			
			$prev_ind = -1;
			$content = "";
			// Городим само дерево
			foreach ($path as $id) {
				$its = Nertz::call($this->_branch_function, array('ind' => $id, 'params' => $params));
				if (count($its)) {
					foreach ($its as $i => $item) {
							if ($item['ind'] == $prev_ind) {
								$its[$i]['content'] = $content; 
							}
							if (strcmp($item['ind'],$ind) === 0) {
								$its[$i]['active'] = 1;
							}
						if (!$item['name']) {
							unset($its[$i]);
						}
					}
					$template->set_value('items', $its);
					$template->set_value('parent_ind', $id);
					$content = $template->render('Tree');
				} else {
					$content = "";
				}
				$prev_ind = $id;
			}
		} else { // Или отображаем только одноуровневый список подкатегорий
			$items = Nertz::call($this->_branch_function, array('ind' => $ind, 'params' => $params));
			$template->set_value('items', $items);
			$template->set_value('parent_ind', $ind);
			$content =  $template->render('Tree');
		}
		// Если необходим полный ввыод то подклеим корень
		if ($full && $full !== 'main') {
			
			$template->set_value('items', array(array('ind' => 0, 'name'=> 'Магазин', 'content' => $content, 'type' => 'root')));
			$template->set_value('parent_ind', -1);
			$content = $template->render('Tree');
		}
		return $content;
	}
}