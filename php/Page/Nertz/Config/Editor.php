<?php

/**
 * Страница редактора конфигурационных файлов
 *
 */
include_once(Nertz::class_path('Nertz_Form'));

class Page_Nertz_Config_Editor extends Nertz_Page
{
    function Page_Nertz_Config_Editor($name)
    {
        parent::Nertz_Page($name);
        $this->config_name = 'general';
    }
    function show()
    {
        // Глобальные переменные, они необходимы для инициализации Классов стуруктры и конфигов
        global $url, $template;
        include_once(Nertz::class_path('Nertz_Config_Structure'));
        include_once(Nertz::class_path('Nertz_Config'));
        // Получить спиоск всех конфигов
        $config = new Nertz_Config();
        $levels = $config->get_config_levels($this->config_name);
        $template->set_value('levels', $levels);
        $els = $config->get_site_levels();
        $config_level = $url->get_value('level', array_pop($els));
        $template->set_value('level', $config_level);
        // Загрузим данные конфига
        $config->load_from_file($this->config_name, $levels[$config_level]['levels']);
		// Загрузим структуру конфига
        $struct = new Nertz_Config_Structure();
        $struct->load_from_file($this->config_name, $levels[$config_level]['levels']);        
        // Базовый урль для этой страницы
        $this->page_url = array('page' => $url->get_page(), 'level' => $config_level);
        // Путь к текущей ноде
        $path     = $url->get_value('path', '/');
        // *** Подготовим класс формы для правой колонки ***
        $u = $this->page_url;
        $u['path'] = $path;
        $form_config = array(
            "name"        => "main",
            "caption"     => "",
            "index_field" => "name",
            "url"         => $u,
            "buttons" => array(
                "update" => array(
                    "form_caption"  => "Сохранить",
                    "table_caption" => "",
                    "image"         => "core/img/button/ok.gif"
                    ),
                "insert" => array(
                    "form_caption"  => "Сохранить",
                    "table_caption" => "",
                    "image"         => "core/img/button/ok.gif"
                    ),    
                "cancel" => array(
                    "form_caption"  => "Отменить",
       	            "table_caption" => "",
       	            "image"         => "core/img/button/cancel.gif"    
   	                ),
                "add" => array(
                      "form_caption"  => "",
               	      "table_caption" => "Добавить",
               	      "image"         => "core/img/button/add.gif"
                 ),
                "delete" => array(
                     "form_caption"  => "",
              	      "table_caption" => "Удалить",
                    "image"         => "core/img/button/delete.gif"
                ),
            ),
            "fields"      => array(),
        );
        // Список всех нод по данному пути
        $nodes       = $struct->get_node_list($path, true); 
        // Параметры родительской ноды
        $parent_node = $struct->get_node($path, true);      
        // Получим путь для родительского уровня
        $arr  = Nertz_Config_Structure::_parse_path($path);
        $name = array_pop($arr);
        $pn_path = "/" . implode('/', $arr);
        
        // Небольшой хук для Массивов у которых всего одно поле - они будут ассоциативными
        $first_node = false;
        if (count($nodes) == 1 && $parent_node['type'] == 'Array')
        {
        	foreach ($nodes as $node)
        	{
        		$first_node = $node;
        	}
        }
        
        // Значения переменных ветки
        $values      = $config->_get_value($path, array());
         // Если редактируем эллемент массива, то добавим поле значения ключа
        if (!empty($parent_node['type']) && $parent_node['type'] == 'Array') {
            $form_config['fields']['_key'] = array(
                "form_caption"  => (!empty($parent_node['a_title'])?  $parent_node['a_title'] :   'Название эллемента'),
                "table_caption" => "",
                "description"   => "",
                "read_only"     => 0,
                "reqired"       => 1,
                "type"          => "String",
                "length"        => 79,
                "value"         => !empty($parent_node['a_title']) ? $name : '',
                );
        } else {
            unset($form_config['buttons']['insert']);
        }
        foreach ($nodes as $node) {
            // Добавим все дочерние поля
            if ($node['type'] != 'Folder' && $node['type'] != 'Array') {
            	$arr = array(
                "form_caption"  => $node['caption'],
                "table_caption" => "",
                "description"   => isset($node['descr']) ? $node['descr'] : "",
                "read_only"     => 0,
                "reqired"       => 0,
                "type"          => $node['type'],
                "length"        => 32,
                );
               	// Костыль для поля типа Select
				if ($arr['type'] == 'Select') {
					$arr['type'] = $node['stype'];
					// Данные берутся из ветки конфига
					if ($node['ssource'] == 'config') {
						$vals = $config->get_value($node['source_path']);
						if (empty($node['source_capt'])) {
							$arr['values'] = array_combine(array_keys($vals), array_keys($vals));
						} else {
							$arr['values'] = array();
							foreach ($vals as $id => $value) {
								$arr['values'][$id] = $value[$node['source_capt']];
							}
						} 
						if (!empty($node['e_value']))	{
							$arr['reqired'] = 0;
						} else {
							$arr['reqired'] = 1;
						}
					}else {
						// Данные берутся из заранее определенного перечня
						$s    = $path . '/' . $node['name'] . '/values';
						$vals = $struct->get_node_list($s, true);
						$arr['values'] = array_extract($vals,'caption','name');
					}
				}
				if ($arr['type'] == 'Text') {
           			$arr['cols'] = 60;
           			$arr['rows'] = 8;
           		}
           		if ($arr['type'] == 'String') {
           			$arr['length'] = 79;
           		}
				$form_config['fields'][$node['name']] = $arr;
            }
        }
        $form     = new Nertz_Form($form_config);
        $form_act = $form->get_posted_act();
        // Хак для сортировки полей.
        if (!$form_act && $url->get_value('main_button_order', '')){
        	$form_act = 'order';
        }
        
        // *** Обрабатываем события формы ***
        if ($form_act == 'update') {
            if ($form->load_posted_vars())
            {
                $form_values = $form->get_vars();
                foreach ($form_values as $name => $value)
                {
                    $node_path = $path . '/' . $name;
                    if ($name !='_key')
                    {
                        $config->set_value($node_path, $value );
                    } 
                }
                if (!empty($form_values['_key']))
                {
                    $config->rename_value($path, $form_values['_key']);
                }
                $config->save_to_file();
                //$config->load_from_file($this->config_name, $levels[$config_level]['levels']);
            }
        } else if ($form_act == 'delete') {
        	$form1 = new Nertz_Form($this->get_array_form());
        	$form1->load_posted_vars();
            $config->delete_value($path, $form1->get_value('delete'));
            $config->save_to_file();
            //$config->load_from_file($this->config_name, $levels[$config_level]['levels']);
        } else if ($form_act == 'insert') {
            if ($form->load_posted_vars()) {
                $form_values = $form->get_vars();
                $values = array();
                foreach ($form_values as $name => $value)
                {
                    if ($name !='_key')
                    {
                        $values[$name] = $value;
                    } 
                }
                if (!empty($form_values['_key']))
                {
                    $config->set_value($path . '/' . $form_values['_key'], $values);
                }
                $config->save_to_file();
                //$config->load_from_file($this->config_name, $levels[$config_level]['levels']);
            }
        } else if ($form_act == 'order') {
        	$form1 = new Nertz_Form($this->get_array_form());
            $form1->load_posted_vars();
            $config->set_order($path, $form1->get_order());
            $config->save_to_file();
            //$config->load_from_file($this->config_name, $levels[$config_level]['levels']);
        } else {
            $form->set_vars($values);
        }

        // *** Сформируем хлебные крошки ***
        $arr = Nertz_Config_Structure::_parse_path($path);
        $u   = $this->page_url;
        $u['path'] = '/';
        $breadcrumbs[] = array( 'caption' => 'Корень', 'url' => $url->gen_url($u), 'sub' => 0);
        $pp = "";
        $fix_next = false;
        foreach ($arr as $p) {
            $pp .= "/$p";
            $n = $struct->get_node($pp, true);
            $u['path'] = $pp;
            $breadcrumbs[] = array( 'caption' => (isset($n['caption']) && !$fix_next) ? $n['caption'] : $p, 'url' => $url->gen_url($u), 'sub' => $fix_next ? 1 : 0);
            if (!$fix_next && $n['type'] == 'Array') 
            {
            	$fix_next = true;
            }
            else 
            {
            	$fix_next = false;
            }
        }
        $in_array = $fix_next;
        $template->set_value('breadcrumbs',  $breadcrumbs);

        // *** Сформируем левую колонку ***
        $u = $this->page_url;
        if ($path != '/') {
        	// Для корневой ноды не показываем путь наверх
            $u['path'] = $pn_path;
            $folders[] = array(
            'caption' => $breadcrumbs[count($breadcrumbs)-2]['caption'],
            'type' => 'Up',
            'url' => $url->gen_url($u));
        } else {
        	$path = ""; // Избегаем двойного слэша в генеримых путях
        }
        // Загрузим список нод для левой колонки
        foreach ($nodes as $node)
        {
        	if ($node['type'] == 'Folder' || $node['type'] == 'Array')
        	{
        		$u['path'] = $path . '/' . $node['name'];
        		$folders[] = array(
        		'caption' => $node['caption'],
        		'type' => $node['type'],
        		'url' => $url->gen_url($u)
        		);
        	}
        }
        $template->set_value('folders',  $folders);
		
        // *** Сформируем правую колонку ***
        if (!count($form->params['fields'])) {
            $template->set_value('form', '');
        } else if (!empty($parent_node['type']) && $parent_node['type'] == 'Array' && $in_array && $form_act != 'add' ) {
            $f_params = $this->get_array_form();
            $c = "";
            if ($first_node)
            {
           		$f_params['fields']['value']['table_caption'] = $first_node['caption'];
           		$f_params['fields']['value']['type'] = $first_node['type'];
           		$c = $first_node['name'];
            }
            $f_params['fields']['name']['table_caption'] = $parent_node['a_title'];
            // Покажем только таблицу
            $form1 = new Nertz_Form($f_params);
            $values = $config->_get_value($path, array());
        	$v      = array();
            foreach ($values as $name => $vals)  {
            	if($name !== ORDER_FIELD) {
	                $a = array('name' => $name);
	                if ($c) {
	                	$a['value'] = $vals[$c];
	                }
	                $v[] = $a;
            	}
            }
            $form1->set_table_values($v);
            $template->set_value('table', $form1->show_table('Nertz_Table_Editor'));
        } else if ($form_act == 'add')  {
			unset($form->params['buttons']['update']);
        	$template->set_value('table', $form->show_form('Nertz_Form_Div'));
        } else {
        	unset($form->params['buttons']['insert']);
        	$template->set_value('table', "");
            $template->set_value('form', $form->show_form('Nertz_Form_Div'));
        }
        $template->add_css('core/css/nertz/config.css');
        //$template->add_css('core/css/admin.css');
        return $template->render('Nertz_Config_Editor');
    }
    function get_array_form()
    {
        global $url;
        $u = $this->page_url;
        $u['path'] =$url->get_value('path','/');
        return array(
            "name"        => "main",
            "caption"     => " ",
            "index_field" => "name",
            "url"         => $u,
            "movable_rows" => true,
            "buttons" => array(
                "add" => array(
                      "form_caption"  => "",
               	      "table_caption" => "Добавить",
               	      "image"         => "core/img/button/add.gif"
                 ),
                 "delete" => array(
                      "form_caption"  => "",
               	      "table_caption" => "Удалить",
               	      "image"         => "core/img/button/delete.gif"
                 ),
                 "order" => array(
          				"form_caption"  => "",
   	      				"table_caption" => "Порядок",
   	      				"image"         => "core/img/button/save.gif" 
     			),		
            ),
            "fields"      => array(
                "name" => Array(
                		"table_caption" => "Имя",
                		"type"          => "String",
                		),
                "value" => Array(
                		"table_caption" => "",
                		"type"          => "String",
                		),		
                "delete" => Array(
                		"form_caption"  => "",
                		"table_caption" => "Удалить",
                		"type"          => "CheckBox",
                		),
                "edit" => Array(
                		"form_caption"  => "",
                		"table_caption" => "Редактор",
                		"type"          => "Button",
                		"pic_url"       => "core/img/button/edit.gif",
                		"act"           => "edit",
                		"on_table_show" => array(&$this, "form_edit_onshow"),
                		),	
                ),
            );
    }
    // Немного модифицируем поведение кнопки "Редактировать"
    function form_edit_onshow($field, $row)
    {
    	global $url;
    	$u = $field->form->params['url'];
    	$u['path'] .=  '/' . $row[$field->form->index_name()];
    	return "<a href=\"" . $url->gen_url($u) . "\" title=\"" . "" . "\"><img border=\"0\" src=\"" . $url->gen_static_url($field->params['pic_url']) ."\"/></a>";
    }
}