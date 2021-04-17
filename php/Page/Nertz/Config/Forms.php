<?php

/**
 * Страница редактора конфигурационных файлов
 *
 */
include_once(Nertz::class_path('Nertz_Form'));
include_once(Nertz::class_path('Nertz_Config_Forms'));

class Page_Nertz_Config_Forms extends Nertz_Page
{
    /**
     * @var Nertz_Config_Forms $forms;
     * 
     */
	var $forms = null;
	function Page_Nertz_Config_Forms($name)
    {
    	global $url;
        parent::Nertz_Page($name);
        $this->form_name = $url->get_value('form_name', 'main');
        $this->forms = new Nertz_Config_Forms();
        $this->frm = $url->get_value('frm', '');
        $this->level = $url->get_value('list_filter_level', $url->get_value('level', 'project'));
        if (!$this->level) {
        	$this->level = 'project';
        }
        $this->item_name = $url->get_value('item_name', 'main');
        $this->copy = $url->get_value('copy', '');
        //$this->ind = $url->get_value('ind', '');
    }
    /**
     * Генерим Урль для страницы на основе текущих параметров
     */
    function get_page_url()
    {
    	global $url;
    	$arr = array();
    	$arr['page'] = $url->get_page();
    	if ($this->frm) {
    		$arr['frm'] = $this->frm;
    	}
    	if ($this->level) {
    		$arr['level'] = $this->level;
    	}
    	if ($this->form_name) {
    		$arr['form_name'] = $this->form_name;
    	}
    	if ($this->item_name) {
    		$arr['item_name'] = $this->item_name;
    	}
    	if ($this->copy) {
    		$arr['copy'] = $this->copy;
    	}
    	/*if ($this->ind) {
    		$arr['ind'] = $this->ind;
    	}*/
    	return $arr;
    }
    function show()
    {
    	global $template, $url;
    	$form = new Nertz_Form($this->get_list_form());
    	$form->params['filters']['level']['class']->set_value($this->level);
    	$act = $form->get_posted_act();
    	$l = $this->get_levels();
    	$this->levels = $l[$this->level]['levels'];
    	// Если есть потребность, то переходим к нужной подформе, поля, кнопки и т.п.
    	if (!$this->frm) {
	    	if ($act == 'add') {
	    		$this->frm = 'main';
	    	}
	    	if ($act == 'edit') {
	    		$this->form_name = $form->get_posted_index();
	    		$this->frm = 'main';
	    	}
	    	if ($act == 'fields') {
	    		$this->form_name = $form->get_posted_index();
	    		$this->frm = 'fields';
	    	}
	    	if ($act == 'buttons') {
	    		$this->form_name = $form->get_posted_index();
	    		$this->frm = 'buttons';
	    	}
	    	if ($act == 'sql') {
	    		$this->form_name = $form->get_posted_index();
	    		$this->frm = 'sql';
	    	}
	    	if ($act == 'filters') {
	    		$this->form_name = $form->get_posted_index();
	    		$this->frm = 'filters';
	    	}
    		if ($act == 'uniques') {
	    		$this->form_name = $form->get_posted_index();
	    		$this->frm = 'uniques';
	    	}
	    	if ($act == 'back') {
	    		$this->go_back_after_copy();
	    	}
    	}
    	// Обработка событий формы полей
    	if ($this->frm == 'fields') {
    		$form_fields = new Nertz_Form($this->get_fields_form());
    		$this->forms->load_from_file($this->form_name, $this->levels);
    		$act = $form_fields->get_posted_act();
    		if ($act == 'back') {
    			$this->go_back();
    		}
    		if ($act == 'back_fields') {
	    		//$this->go_back_fields();
	    	}
    		if ($act == 'add') {
    			$form_fields->load_posted_vars(); 
    			return $form_fields->show_form('Nertz_Form_Table');
    		}
    		if ($act == 'edit') {
    			$this->forms->load_from_file($this->form_name, $this->levels);
    			$form_fields->set_vars($this->forms->get_field_params($form_fields->get_posted_index()));
    			return $form_fields->show_form('Nertz_Form_Table');
    		}
    		if ($act == 'delete') {
    			$form_fields->load_posted_vars(true);
    			$this->forms->delete_fields($form_fields->get_value('delete'));
				$this->forms->save_to_file();
    		}
    	 	if ($act == 'order') {
            	$form_fields->load_posted_vars();
            	$this->forms->order_fields($form_fields->get_order());
            	$this->forms->save_to_file();
        	}
    		if ($act == 'save') {
   				if ($form_fields->load_posted_vars()) {
   					$data = $form_fields->get_vars();
   					$this->forms->set_field_params($data);
   					$this->forms->save_to_file();
   					$this->item_name = '';
   				} else {
   					return $form_fields->show_form('Nertz_Form_Table');
   				}
   			}
   			if ($act == 'copy') {
   				$this->act_copy($form_fields);
   			}
   			if ($act == 'db') {
   				return $this->act_db();
   			}
    		$form_fields->set_table_values($this->forms->fetch_fields());
    		return $form_fields->show_table('Nertz_Table_Editor');
    	}
    	// Обработка событий формы кнопок    	
    	if ($this->frm == 'buttons') {
    		$form_buttons = new Nertz_Form($this->get_buttons_form());
    		$this->forms->load_from_file($this->form_name, $this->levels);
    		$act = $form_buttons->get_posted_act();
    		if ($act == 'back') {
    			$this->go_back();
    		}
    		if ($act == 'add') {
    			return $form_buttons->show_form('Nertz_Form_Table');
    		}
    		if ($act == 'edit') {
    			$this->forms->load_from_file($this->form_name, $this->levels);
    			$form_buttons->set_vars($this->forms->get_button_params($form_buttons->get_posted_index()));
    			return $form_buttons->show_form('Nertz_Form_Table');
    		}
    		if ($act == 'delete') {
    			$form_buttons->load_posted_vars();
    			$this->forms->delete_buttons($form_buttons->get_value('delete'));
				$this->forms->save_to_file();
    		}
    		if ($act == 'order') {
            	$form_buttons->load_posted_vars();
            	$this->forms->order_buttons($form_buttons->get_order());
            	$this->forms->save_to_file();
        	}
    		if ($act == 'save') {
   				if ($form_buttons->load_posted_vars()) {
   					$data = $form_buttons->get_vars();
   					$this->forms->set_button_params($data);
   					$this->forms->save_to_file();
   					$this->item_name = '';
   				} else {
   					return $form_buttons->show_form('Nertz_Form_Table');
   				}
   			}
   			if ($act == 'copy') {
   				$this->act_copy($form_buttons);
   			}
    		$form_buttons->set_table_values($this->forms->fetch_buttons());
    		return $form_buttons->show_table('Nertz_Table_Editor');
    	}
    	// Обработка событий формы параметров SQL    	
    	if ($this->frm == 'sql') {
    		$form_sql = new Nertz_Form($this->get_sql_form());
    		$this->forms->load_from_file($this->form_name, $this->levels);
    		$act = $form_sql->get_posted_act();
    		if ($act == 'cancel') {
    			Nertz::redirect(array('page' => $url->get_page(), 'level' => $this->level));
    		}
    		if ($act == 'save') {
   				if ($form_sql->load_posted_vars()) {
   					$data = $form_sql->get_vars();
   					$this->forms->set_sql_params($data);
   					$this->forms->save_to_file();
   					$this->item_name = '';
   					Nertz::redirect(array('page' => $url->get_page(), 'level' => $this->level));
   				} else {
   					return $form_buttons->show_form('Nertz_Form_Table');
   				}
   			}
    		$this->forms->load_from_file($this->form_name, $this->levels);
    		$form_sql->set_vars($this->forms->get_sql_params());
    		return $form_sql->show_form('Nertz_Form_Table');
    	}
    	// Обработка событий формы фильтров    	
    	if ($this->frm == 'filters') {
    		$form_filters = new Nertz_Form($this->get_filters_form());
    		$this->forms->load_from_file($this->form_name, $this->levels);
    		$act = $form_filters->get_posted_act();
    		if ($act == 'back') {
    			$this->go_back();
    		}
    		if ($act == 'add') {
    			return $form_filters->show_form('Nertz_Form_Table');
    		}
    		if ($act == 'edit') {
    			$this->forms->load_from_file($this->form_name, $this->levels);
    			$form_filters->set_vars($this->forms->get_filter_params($form_filters->get_posted_index()));
    			return $form_filters->show_form('Nertz_Form_Table');
    		}
    		if ($act == 'delete') {
    			$form_filters->load_posted_vars();
    			$this->forms->delete_filters($form_filters->get_value('delete'));
				$this->forms->save_to_file();
    		}
    	 	if ($act == 'order') {
            	$form_filters->load_posted_vars();
            	$this->forms->order_filters($form_filters->get_order());
            	$this->forms->save_to_file();
        	}
    		if ($act == 'save') {
   				if ($form_filters->load_posted_vars()) {
   					$data = $form_filters->get_vars();
   					$this->forms->set_filter_params($data);
   					$this->forms->save_to_file();
   					$this->item_name = '';
   				} else {
   					return $form_filters->show_form('Nertz_Form_Table');
   				}
   			}
    		if ($act == 'copy') {
   				$this->act_copy($form_filters);
   			}
    		$form_filters->set_table_values($this->forms->fetch_filters());
    		return $form_filters->show_table('Nertz_Table_Editor');
    	}
    	// Обработка событий формы уникальностей    	
   		if ($this->frm == 'uniques') {
    		$form_uniques = new Nertz_Form($this->get_uniques_form());
    		$this->forms->load_from_file($this->form_name, $this->levels);
    		$act = $form_uniques->get_posted_act();
    		if ($act == 'back') {
    			$this->go_back();
    		}
    		if ($act == 'add') {
    			return $form_uniques->show_form('Nertz_Form_Table');
    		}
    		if ($act == 'edit') {
    			$this->forms->load_from_file($this->form_name, $this->levels);
    			$form_uniques->set_vars($this->forms->get_unique_params($form_uniques->get_posted_index()));
    			return $form_uniques->show_form('Nertz_Form_Table');
    		}
    		if ($act == 'delete') {
    			$form_uniques->load_posted_vars();
    			$this->forms->delete_uniques($form_uniques->get_value('delete'));
				$this->forms->save_to_file();
    		}
    	 	if ($act == 'order') {
            	$form_uniques->load_posted_vars();
            	$this->forms->order_uniques($form_uniques->get_order());
            	$this->forms->save_to_file();
        	}
    		if ($act == 'save') {
   				if ($form_uniques->load_posted_vars()) {
   					$data = $form_uniques->get_vars();
   					$this->forms->set_unique_params($data);
   					$this->forms->save_to_file();
   					$this->item_name = '';
   				} else {
   					return $form_uniques->show_form('Nertz_Form_Table');
   				}
   			}
   			if ($act == 'copy') {
   				$this->act_copy($form_uniques);
   			}
    		$form_uniques->set_table_values($this->forms->fetch_uniques());
    		return $form_uniques->show_table('Nertz_Table_Editor');
    	}
    	// Обработка событий формы параметров формы
    	if ($this->frm == 'main') {
    			$form_main = new Nertz_Form($this->get_main_form());
    			$act = $form_main->get_posted_act();
    			if ($act == 'cancel') {
    				$this->frm = '';
    			}
    			if ($act == 'edit') {
    				$this->forms->load_from_file($this->form_name, $this->levels);
    				$form_main->set_vars($this->forms->get_main_params());
    			}
    			if ($act == 'save') {
    				if ($form_main->load_posted_vars()) {
    					$data = $form_main->get_vars();
    					if ($this->form_name) {
    						$this->forms->load_from_file($this->form_name, $this->levels);
    					}
    					$this->forms->set_main_params($data);
    					$this->forms->save_to_file($data['name'], $this->levels);
    					$this->frm = '';
    				}
    			}
    			if ($this->frm == 'main') {
    				return $form_main->show_form('Nertz_Form_Table');
    			}
    	}
    	// Обработка событий формы списка форм    	
    	if (!$this->frm) {
    			unset($form->params['url']['frm']);
    			if ($act == 'delete') {
    				$form->load_posted_vars();
    				$inds = $form->get_value('delete');
    				if (is_array($inds)) {
    					foreach ($inds as $ind) {
    						$this->forms->delete($ind, array($this->level));
    					}
    				}
    			}
    			$arr = $this->forms->fetch($this->levels);
    			$form->set_table_values($arr);
    			return $form->show_table('Nertz_Table_Editor');
    	}
    }
    function get_list_form()
    {
        global $url;
        $u = $this->get_page_url();
        $arr =  array(
            "name"        => "list",
            "caption"     => "Формы",
            "index_field" => "name",
            "url"         => $u,
            "movable_rows" => false,
        	"filters"      => array(
		    	"level" => Array(
			    	"caption"  => "Уровень",
			    	"description"   => "",
			    	"type"          => "Simpleselect",
			    	"values"        => array_extract($this->get_levels(),false, null),
			    	"autosubmit" => true,
        			"value"			=> $this->level,
		    		),
    			),
            "buttons" => array(
    			"back" => array(
                      "form_caption"    => "",
               	      "table_caption"   => "Назад",
               	      "image"           => "core/img/button/back.gif",
               	      "bootstrap_class" => "icon-arrow-left"
                ),
                "add" => array(
                      "form_caption"  => "",
               	      "table_caption" => "Добавить",
               	      "image"         => "core/img/button/add.gif",
               	      "bootstrap_class" => "icon-plus"
                 ),
                 "delete" => array(
                      "form_caption"  => "",
               	      "table_caption" => "Удалить",
               	      "image"         => "core/img/button/delete.gif",
               	      "bootstrap_class" => "icon-trash"
                 ),
            ),
            "fields"      => array(
				"delete" => Array(
                		"form_caption"  => "",
                		"table_caption" => "*",
                		"type"          => "CheckBox",
                		),
            	"name" => Array(
                		"table_caption" => "Имя",
                		"type"          => "String",
                		),
                "edit" => Array(
                		"form_caption"  => "",
                		"table_caption" => "Ред.",
                		"type"          => "Button",
                		"pic_url"       => "core/img/button/edit.gif",
                		"act"           => "edit",
               	  	    "bootstrap_class" => "icon-pencil"
                		),
                "sql" => Array(
                		"form_caption"  => "",
                		"table_caption" => "SQL",
                		"type"          => "Button",
                		"pic_url"       => "core/img/button/database_edit.gif",
                		"act"           => "sql",
                		"bootstrap_class" => "icon-hdd"
                		),
                "fields" => Array(
                		"form_caption"  => "",
                		"table_caption" => "Поля",
                		"type"          => "Button",
                		"pic_url"       => "core/img/button/form_edit.gif",
                		"act"           => "fields",
                		"on_table_show" => array( &$this, 'fields_show'),
               	  	    "bootstrap_class" => "icon-list-alt"
                		),
                "buttons" => Array(
                		"form_caption"  => "",
                		"table_caption" => "Кнопки",
                		"type"          => "Button",
                		"pic_url"       => "core/img/button/button_edit.gif",
                		"act"           => "buttons",
                		"on_table_show" => array( &$this, 'buttons_show'),
                		"bootstrap_class" => "icon-keyboard"
                		),
                "filters" => Array(
                		"form_caption"  => "",
                		"table_caption" => "Фильтры",
                		"type"          => "Button",
                		"pic_url"       => "core/img/button/brick_edit.gif",
                		"act"           => "filters",
                		"on_table_show" => array( &$this, 'filters_show'),
                		"bootstrap_class" => "icon-filter"
                		),
                "uniques" => Array(
                		"form_caption"  => "",
                		"table_caption" => "Уникальности",
                		"type"          => "Button",
                		"pic_url"       => "core/img/button/comment_edit.gif",
                		"act"           => "uniques",
                		"on_table_show" => array( &$this, 'uniques_show'),
                		"bootstrap_class" => "icon-cogs"
                		),
                ),
            );
            $this->fix_copy_form($arr);
            return $arr;
            
    }
    function get_main_form()
    {
    	global $url;
        $u = $this->get_page_url();
        $arr = array(
            "name"        => "main",
        	"add_caption"  => "Параметры новой формы",
            "edit_caption" => "Параметры формы  &laquo;{$this->form_name}&raquo;",
            "index_field" => "name",
            "url"         => $u,
            "buttons" => array(
                  "save" => array(
		          "form_caption"  => "Сохранить",
		   	      "table_caption" => "",
		   	      "image"         => "core/img/button/ok.gif",
               	  "bootstrap_class" => "icon-ok"
			     ),
			     "cancel" => array(
			          "form_caption"  => "Отменить",
			   	      "table_caption" => "",
			   	      "image"         => "core/img/button/cancel.gif",
               	  	  "bootstrap_class" => "icon-remove"
			     ),
            ),
            "fields"      => array(
            	"caption_main" => Array(
                		"form_caption" => "Основные",
                		"type"          => "Caption",
                		),
                "name" => Array(
                		"form_caption" => "Имя Формы",
                		"type"          => "String",
						"reqired"       => 1,
                		),
                "caption" => Array(
                		"form_caption"  => "Заголовок таблицы",
                		"type"          => "String",
                		"reqired"       => 1,
                		),
                "edit_caption" => Array(
                		"form_caption" => "Заголовок редактирования",
                		"type"          => "String",
                		"description"  => "Заголовок формы редактирования существующей записи"
                		),
                "add_caption" => Array(
                		"form_caption" => "Заголовок добавления",
                		"type"          => "String",
                		"description"  => "Заголовок формы добавления новой записи"
                		),
				"index_field" => Array(
                		"form_caption" => "Индекс",
                		"type"         => "String",
                		"description"  => "Имя поля используемое как индекс для работы"
                		),
                "caption_cosmo" => Array(
                		"form_caption" => "Косметика",
                		"type"          => "Caption",
                		),
                "movable_rows" => Array(
                		"form_caption" => "Перестановка строк",
                		"type"         => "Bool",
                		"description"  => "Включить возможность изменения порядка строк в таблице"
                		),
                "items_per_page" => Array(
                		"form_caption" => "Строк на страницу",
                		"type"         => "int",
                		"description"  => "Количество строк на страницу при отображениии таблицы",
                		"default"      => 16,
                		),
                 "seo" => Array(
                		"form_caption" => "Подключить SEO",
                		"type"         => "Bool",
                		"description"  => "Добавить поля для SEO"
                		),		
                "caption_event" => Array(
                		"form_caption" => "Обрабочики событий",
                		"type"          => "Caption",
                		),
                "event_handler_class" => Array(
                		"form_caption" => "Класс обработчика событий",
                		"type"          => "String",
                		"description"  => "Имя класса (наследника Nertz_Form_Event), занимающегося обработкой событий. Все обработчики формы - методы(функции) этого класса.",
                		),
                "on_create" => Array(
                	"form_caption" => "Обрабочик создания",
                	"type"          => "String",
                	"description"  => "Обработчик вызываемый после загрузки параметров, но перед созданием класса формы<br/><b>Параметры:</b><br/> \&\$form Nertz_Form Ссылка на форму<br/><b>Результат:</b><br/> не важен",
                	),
                "on_table_header" => Array(
                	"form_caption" => "Обрабочик заголовка таблицы",
                	"type"          => "String",
                	"description"  => "Обработчик вывода заголовка таблицы<br/><b>Параметры:</b><br/> \&\$form Nertz_Form Ссылка на форму<br/><b>Результат:</b><br/> string HTML текста",
                	),
                "on_table_footer"   => Array(
                	"form_caption"  => "Обрабочик подвала таблицы",
                	"type"          => "String",
                	"description"   => "Обработчик вывода подвала таблицы<br/><b>Параметры:</b><br/> \&\$form Nertz_Form Ссылка на форму<br/><b>Результат:</b><br/> string HTML текста",
                	),
                "on_access"   => Array(
                	"form_caption"  => "Обрабочик доступа",
                	"type"          => "String",
                	"description"   => "Обрабочик доступа к конкретным событиям<br/><b>Параметры:</b><br/> \&\$form Nertz_Form Ссылка на форму<br/> \&\$act string Событие формы<br/><b>Результат:</b><br/> string HTML текста",
                	),
                "on_act" => Array(
                	"form_caption"   => "Обрабочик пользовтаельских событий",
                	"use_key"	     => true,
                	"key_caption"    => 'act',
                	"value_caption"  => 'Обработчик',
                	"type"           => "AssocArray",
                	"description"    => "Укажите массив собственных обработчиков для каждого act.<br/><b>Параметры:</b><br/> \&\$page Nertz_Page_Form Ссылка на класс страницы<br/><b>Результат:</b><br/> Содержимое отображаемой страницы, либо false если надо отобразить таблицу",
                	),

               ),
            );
    	//$this->fix_copy_form($arr);
    	return $arr;
    }
    function get_fields_form()
    {
    	global $url;
        $u = $this->get_page_url();
        $arr = array(
            "name"         => "fields",
        	"caption"      => "Поля формы &laquo;{$this->form_name}&raquo;",
        	"add_caption"  => "Новое поле",
            "edit_caption" => "Редактирование поля",
            "index_field" => "name",
            "url"         => $u,
            "movable_rows" => true,
            "buttons" => array(
                 "save" => array(
			          "form_caption"  => "Сохранить",
			   	      "table_caption" => "",
			   	      "image"         => "core/img/button/ok.gif",
               	  "bootstrap_class" => "icon-ok"
			     	),
			     "cancel" => array(
			          "form_caption"  => "Отменить",
			   	      "table_caption" => "",
			   	      "image"         => "core/img/button/cancel.gif",
               	  	  "bootstrap_class" => "icon-remove"
			     ),
			     "back" => array(
                      "form_caption"  => "",
               	      "table_caption" => "Назад",
               	      "image"         => "core/img/button/back.gif",
               	      "bootstrap_class" => "icon-arrow-left"
                 ),
			     "add" => array(
                      "form_caption"  => "",
               	      "table_caption" => "Добавить",
               	      "image"         => "core/img/button/add.gif",
               	      "bootstrap_class" => "icon-plus"
                 ),
                 "delete" => array(
                      "form_caption"  => "",
               	      "table_caption" => "Удалить",
               	      "image"         => "core/img/button/delete.gif",
               	      "bootstrap_class" => "icon-trash"
                 ),
                 "order" => array(
       				   "form_caption"  => "",
   	      				"table_caption" => "Порядок",
   	      				"image"         => "core/img/button/save.gif",
               	  	    "bootstrap_class" => "icon-save"
     			),
     			"copy" => array(
       				   "form_caption"  => "",
   	      				"table_caption" => "Копировать",
   	      				"image"         => "core/img/button/copy.gif",
               	  	    "bootstrap_class" => "icon-copy"
     			),
     			"db" => array(
       				   "form_caption"  => "",
   	      				"table_caption" => "Из БД",
   	      				"image"         => "core/img/button/database.gif",
               	  	    "bootstrap_class" => "icon-hdd"
     			),
            ),
            "fields"      => array(
               "select" => Array(
                		"form_caption"  => "",
                		"table_caption" => "*",
                		"type"          => "CheckBox",
                		),
               "type" => Array(
			    		"sql_name"      => "type",
			    		"form_caption"  => "Тип поля",
			    		"table_caption" => "Тип",
			    		"description"   => "Тип поля",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "SimpleSelect",
			    		"values"        => Nertz_Form::get_field_types(),
			    		"visibles"		=> array(
            				"Assocarray"    => array("use_key", "values"),
			    			"Simpleselect"  => array("values", "visibles", "ajaxed"),
			    			"String"        => array("length", "max_length", "ajaxed"),
			    			"Text"          => array("rows", "cols", "highlight"),
			    			"Button"        => array("pic_url", "act", "bootstrap_class"),
			    			"Html" 	        => array("width", "height"),
			    			"Date" 		=> array("mysql_format", "combo_view"),
			    			"Dbmultiselect" => array("db_caption",  "db_connect_table_name", "db_connect_item_index", "db_connect_list_index", "db_list_sql", "cols"),
			    			"Multiselect"   => array("cols"),
			    			'Bool'          => array("ajaxed"),
			    			"Tag"           => array("width"),
			    			"Int"           => array("length", "ajaxed"),
						"Lookup"        => array("query_table", "query_ind", "query_text"),
			    			)
			    		),
			    "name" => Array(
			    		"sql_name"      => "name",
			    		"form_caption"  => "Имя поля",
			    		"table_caption" => "Имя",
			    		"description"   => "Уникальное имя поля в форме",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "String",
			    		"length"        => 37,
			    		"max_lenght"    => 32
			    		),
			    "form_caption" => Array(
			    		"sql_name"      => "form_caption",
			    		"form_caption"  => "Заголовок поля в формах",
			    		"table_caption" => "Форм.З.",
			    		"description"   => "Заголов поля используемый в формах, если это поле пустое то данное поле не будет показыаться в форме",
			    		"read_only"     => 0,
			    		"reqired"       => 0,
			    		"type"          => "String",
			    		"length"        => 37,
			    		"max_lenght"    => 64
			    		),
			    "table_caption" => Array(
			    		"sql_name"      => "table_caption",
			    		"form_caption"  => "Заголовок поля в таблицах",
			    		"table_caption" => "Табл.З.",
			    		"description"   => "Заголов поля используемый при отображенни в таблицах, если это поле пустое то данное поле не будет показываться в таблице",
			    		"read_only"     => 0,
			    		"reqired"       => 0,
			    		"type"          => "String",
			    		"length"        => 37,
			    		"max_lenght"    => 64,
			    		),
			    "description" => Array(
			    		"sql_name"      => "table_caption",
			    		"form_caption"  => "Подробное описание поля",
			    		"table_caption" => "",
			    		"description"   => "Полное описание поля дающее пользователю сводную информацию у данном поле",
			    		"read_only"     => 0,
			    		"reqired"       => 0,
			    		"type"          => "Text",
			    		"rows"          => 10,
			    		"cols"          => 28
			    		),
			    "read_only" => Array(
			    		"sql_name"      => "read_only",
			    		"form_caption"  => "Только для чтения",
			    		"table_caption" => "",
			    		"description"   => "Флаг того что поле только для чтения",
			    		"read_only"     => 0,
			    		"type"          => "Bool",
			    		),
			    "reqired"   => Array(
			    		"sql_name"      => "reqired",
			    		"form_caption"  => "Поле является обязательный",
			    		"table_caption" => "",
			    		"description"   => "Флаг того что поле является обязательным для заполнения",
			    		"read_only"     => 0,
			    		"type"          => "Bool",
			    		),
			    "visibles" => Array(
			    		"form_caption"  => "Список видимостей",
			    		"table_caption" => "",
			    		"description"   => "Список значений видимостей вида {Значение SELECT-а}=>{Список полей через запятую}",
			    		"read_only"     => 0,
			    		"type"          => "Assocarray",
			    		),
			    "values" => Array(
			    		"form_caption"  => "Список значений",
			    		"table_caption" => "",
			    		"description"   => "Список значений SELECTA",
			    		"read_only"     => 0,
			    		"type"          => "Assocarray",
			    		"use_key"		=> true,
			    		),
			    "use_key" => Array(
			    		"sql_name"      => "use_key",
			    		"form_caption"  => "Использовать ключи",
			    		"table_caption" => "",
			    		"description"   => "Флаг того что посимо значения вводитсяего ключ, чтобы получить полностью ассоциативный массив",
			    		"read_only"     => 0,
			    		"type"          => "Bool",
			    		),
			    "length" => Array(
			    		"sql_name"      => "length",
			    		"form_caption"  => "Длина поля",
			    		"table_caption" => "Длина",
			    		"description"   => "Длина текстовой части поля",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "Int",
			    		),
			    "max_length" => Array(
			    		"sql_name"      => "max_length",
			    		"form_caption"  => "Максимальная Длина",
			    		"table_caption" => "",
			    		"description"   => "Максимальна длина содержимого поля",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "Int",
			    		),
			    "rows" => Array(
			    		"sql_name"      => "rows",
			    		"form_caption"  => "Количество строк",
			    		"table_caption" => "",
			    		"description"   => "Количество столбцов текстового поля",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "Int",
			    		),
			    "cols" => Array(
			    		"sql_name"      => "cols",
			    		"form_caption"  => "Количество столбцов",
			    		"table_caption" => "",
			    		"description"   => "Количество столбцов текстового поля",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "Int",
			    		),
			    "highlight" => Array(
			    		"sql_name"      => "highlight",
			    		"form_caption"  => "Подсветка кода",
			    		"table_caption" => "",
			    		"description"   => "",
			    		"read_only"     => 0,
			    		"reqired"       => 0,
			    		"type"          => "Simpleselect",
			    		"values" 		=> array(
			    				'php' 		 => 'PHP',
			    				'javascript' => 'JavaScript',
			    				'sql'		 => 'SQL',
			    				'html'		 => 'HTML',
			    				'css'		 => 'CSS'
			    			),
			    		),
			    "width" => Array(
			    		"sql_name"      => "width",
			    		"form_caption"  => "Ширина(в пикселях)",
			    		"table_caption" => "",
			    		"description"   => "Ширина рабочей области в пикселях",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "Int",
			    		),
			    "height" => Array(
			    		"sql_name"      => "height",
			    		"form_caption"  => "Высота(в пикселях)",
			    		"table_caption" => "",
			    		"description"   => "Высота рабочей области в пикселях",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "Int",
			    		),		
			    "pic_url" => Array(
			    		"sql_name"      => "pic_url",
			    		"form_caption"  => "Картинка",
			    		"table_caption" => "",
			    		"description"   => "Укажите урль картинки из папки статических файлов",
			    		"read_only"     => 0,
			    		"reqired"       => 0,
			    		"type"          => "String",
			    		"length"        => 37,
			    		"max_lenght"    => 32
			    		),		
			    "bootstrap_class" => Array(
			    		"sql_name"      => "bootstrap_class",
			    		"form_caption"  => "Класс bootstrap",
			    		"table_caption" => "",
			    		"description"   => "Класс bootstrap для отображения соотвествующей картинки",
			    		"read_only"     => 0,
			    		"reqired"       => 0,
			    		"type"          => "String",
			    		"length"        => 37,
			    		"max_lenght"    => 32
			    		),		
			     "mysql_format"   => Array(
			    		"sql_name"      => "mysql_format",
			    		"form_caption"  => "Формат MySQL",
			    		"table_caption" => "",
			    		"description"   => "",
			    		"read_only"     => 0,
			    		"type"          => "Bool",
			    		),		
			     "combo_view"   => Array(
			    		"sql_name"      => "combo_view",
			    		"form_caption"  => "В виде Комбобоксов",
			    		"table_caption" => "",
			    		"description"   => "",
			    		"read_only"     => 0,
			    		"type"          => "Bool",
			    		),				
			    "ajaxed"   => Array(
			    		"sql_name"      => "ajaxed",
			    		"form_caption"  => "Редактирование прямо в таблице",
			    		"table_caption" => "",
			    		"description"   => "",
			    		"read_only"     => 0,
			    		"type"          => "Bool",
			    		),						
			    "act" => Array(
			    		"sql_name"      => "act",
			    		"form_caption"  => "Действие(act)",
			    		"table_caption" => "Действие(act)",
			    		"description"   => "Действие(act) при нажатии на картинку",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "String",
			    		"length"        => 37,
			    		"max_lenght"    => 32
			    	),
				"query_table" => Array(
			    		"sql_name"      => "query_table",
			    		"form_caption"  => "Талица для поиска",
			    		"table_caption" => "",
			    		"description"   => "",
			    		"read_only"     => 0,
			    		"reqired"       => 0,
			    		"type"          => "String",
			    		"length"        => 37,
			    		"max_lenght"    => 255
			    		),
				"query_ind" => Array(
			    		"sql_name"      => "query_ind",
			    		"form_caption"  => "Поле индекса",
			    		"table_caption" => "",
			    		"description"   => "Поле индекса таблицы поиска",
			    		"read_only"     => 0,
			    		"reqired"       => 0,
			    		"type"          => "String",
			    		"length"        => 37,
			    		"max_lenght"    => 255
			    		),
				"query_text" => Array(
			    		"sql_name"      => "query_text",
			    		"form_caption"  => "Поле заголовка",
			    		"table_caption" => "",
			    		"description"   => "Поле заголовка таблицы поиска",
			    		"read_only"     => 0,
			    		"reqired"       => 0,
			    		"type"          => "String",
			    		"length"        => 37,
			    		"max_lenght"    => 255
			    		),		
		

			    "delete" => Array(
			    		"form_caption"  => "",
			    		"table_caption" => "Удалить",
			    		"description"   => "Удалить галлерею",
			    		"type"          => "CheckBox",
			    		),
			    "edit" => Array(
                		"form_caption"  => "",
                		"table_caption" => "Ред.",
                		"type"          => "Button",
                		"pic_url"       => "core/img/button/edit.gif",
                		"act"           => "edit",
               	  	    "bootstrap_class" => "icon-pencil"
                		),
                "db_caption" => Array(
                		"form_caption" => "Работа с БД",
                		"type"          => "Caption",
                		),		
			    "db_list_sql" => Array(
			    		"form_caption"  => "Запрос на вывод списка",
			    		"table_caption" => "",
			    		"description"   => "Запрос на получение списка, в результате две колонки index=>caption",
			    		"default"		=> "ind",
			    		"type"          => "Text",
			    		"rows"          => 5,
			    		"cols"          => 28
			    	),		
			    "db_connect_table_name" => Array(
			    		"form_caption"  => "Имя таблицы связей",
			    		"table_caption" => "",
			    		"description"   => "В этой таблице содержатся cвязи между конрентыми строками нашей таблицы и эллементами таблицы списков",
			    		"type"          => "String",
			    		"length"        => 37,
			    		"max_lenght"    => 32
			    	),		
			    "db_connect_item_index" => Array(
			    		"form_caption"  => "Поле индексов эллемента",
			    		"table_caption" => "",
			    		"description"   => "Поле содержащее индексы строк нашей таблицы",
			    		"default"		=> "ind",
			    		"type"          => "String",
			    		"length"        => 37,
			    		"max_lenght"    => 32
			    	),	
   			    "db_connect_list_index" => Array(
			    		"form_caption"  => "Поле индексов списка",
			    		"table_caption" => "",
			    		"description"   => "Поле содержащее индексы строк таблицы списка",
			    		"default"		=> "ind",
			    		"type"          => "String",
			    		"length"        => 37,
			    		"max_lenght"    => 32
			    	),	
	
			    	
                		
			    "caption_event" => Array(
                		"form_caption" => "Обрабочики событий",
                		"type"          => "Caption",
                		),
                "on_create"      => Array(
                	"form_caption"  => "После создания поля",
                	"type"          => "String",
                	"description"   => "Обработчик вызываемый после создания поля<br/><b>Параметры:</b><br/> \&\$field Nertz_Form_Field Ссылка на экземпляр поля<br/> <b>Результат:</b><br/> Не важен",
                	),
			    "on_table_show"     => Array(
                	"form_caption"  => "Обрабочик показа поля таблицы",
                	"type"          => "String",
                	"description"   => "Обработчик отображения поля в таблице<br/><b>Параметры:</b><br/> \&\$form Nertz_Form Ссылка на форму<br/> \$row array строка таблицы<br/><b>Результат:</b><br/> string HTML код ячейки",
                	),
                "on_form_show"     => Array(
                	"form_caption"  => "Обрабочик показа поля формы",
                	"type"          => "String",
                	"description"   => "Обработчик отображения поля в формы<br/><b>Параметры:</b><br/> \&\$form Nertz_Form Ссылка на форму<br/><b>Результат:</b><br/> string HTML код поля",
                	),
				"before_save"      => Array(
                	"form_caption"  => "Перед сохранением записи",
                	"type"          => "String",
                	"description"   => "Обработчик вызываемый перед сохранением записи<br/><b>Параметры:</b><br/> \&\$field Nertz_Form_Field Ссылка на экземпляр поля<br/> \&\$params array Массив полей сохраняемой записи<br/> \$act string insert/update действие над записью<br/> \$ind integer Индекс записи<br/><b>Результат:</b><br/> bool true/false  разрешить/запретить добавление/обновление записи",
                	),
 				"after_save"      => Array(
                	"form_caption"  => "После сохранения записи",
                	"type"          => "String",
                	"description"   => "Обработчик вызываемый после сохранения записи<br/><b>Параметры:</b><br/> \&\$field Nertz_Form_Field Ссылка на экземпляр поля<br/> \&\$params array Массив полей сохраняемой записи<br/> \$act string insert/update действие над записью<br/> \$ind integer Индекс записи<br/><b>Результат:</b><br/> Не важен",
                	),
				"before_delete"      => Array(
                	"form_caption"  => "Перед удалением записи",
                	"type"          => "String",
                	"description"   => "Обработчик вызываемый перед удалением записи<br/><b>Параметры:</b><br/> \&\$field Nertz_Form_Field Ссылка на экземпляр поля<br/> \&\$inds array Индексы удаляемой записи<br/><b>Результат:</b><br/> bool true/false  разрешить/запретить удаление записи",
                	),
 				"after_delete"      => Array(
                	"form_caption"  => "После удаления записи",
                	"type"          => "String",
                	"description"   => "Обработчик вызываемый после удаления записи<br/><b>Параметры:</b><br/> \&\$field Nertz_Form_Field Ссылка на экземпляр поля<br/> \$inds array Индексы удаляемой записи<br/><b>Результат:</b><br/> Не важен",
                	),
                "after_fetch"      => Array(
                	"form_caption"  => "После получения списка",
                	"type"          => "String",
                	"description"   => "Обработчик вызываемый после получения списка<br/><b>Параметры:</b><br/> \&\$field Nertz_Form_Field Ссылка на экземпляр поля<br/> \&\$res array Двумерный массив всех выбранных записей<br/><b>Результат:</b><br/> Не важен",
                	),
                "after_get"      => Array(
                	"form_caption"  => "После получения записи",
                	"type"          => "String",
                	"description"   => "Обработчик вызываемый после получения одной записи<br/><b>Параметры:</b><br/> \&\$field Nertz_Form_Field Ссылка на экземпляр поля<br/> \&\$params array Массив полей записи<br/><b>Результат:</b><br/> Не важен",
                	),	
               ),
            );
            $this->fix_copy_form($arr);
            return $arr;
    }
    function get_db_form()
    {
    	global $url;
        $u = $this->get_page_url();
        $arr = array(
            "name"         => "fields",
        	"caption"      => "Импорт полей из БД",
            "index_field" => "name",
            "url"         => $u,
            "buttons" => array(
			     "back_fields" => array(
                      "form_caption"  => "",
               	      "table_caption" => "Назад",
               	      "image"         => "core/img/button/back.gif",
               	      "bootstrap_class" => "icon-arrow-left"
                ),
            ),
            "fields"      => array(
               "type" => Array(
			    		"sql_name"      => "type",
			    		"form_caption"  => "Тип поля",
			    		"table_caption" => "Тип",
			    		"description"   => "Тип поля",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "SimpleSelect",
			    		"values"        => Nertz_Form::get_field_types(),
			    		),
			    "name" => Array(
			    		"sql_name"      => "name",
			    		"form_caption"  => "Имя поля",
			    		"table_caption" => "Имя",
			    		"description"   => "Уникальное имя поля в форме",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "String",
			    		"length"        => 37,
			    		"max_lenght"    => 32
			    		),
			    "length" => Array(
			    		"sql_name"      => "length",
			    		"form_caption"  => "Длина поля",
			    		"table_caption" => "Длина",
			    		"description"   => "Длина текстовой части поля",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "Int",
			    		),
			     "reqired"   => Array(
			    		"sql_name"      => "reqired",
			    		"form_caption"  => "Поле является обязательный",
			    		"table_caption" => "Обяз.",
			    		"description"   => "Флаг того что поле является обязательным для заполнения",
			    		"read_only"     => 0,
			    		"type"          => "Bool",
			    		),		
			    "max_length" => Array(
			    		"sql_name"      => "max_length",
			    		"form_caption"  => "Максимальная Длина",
			    		"table_caption" => "",
			    		"description"   => "Максимальна длина содержимого поля",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "Int",
			    		),
			    "exists" => Array(
			    		"sql_name"      => "exists",
			    		"form_caption"  => "Уже есть",
			    		"table_caption" => "Уже есть",
			    		"description"   => "Максимальна длина содержимого поля",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "Bool",
			    		),		
			    "add" => Array(
                		"form_caption"  => "",
                		"table_caption" => "Добавить",
                		"type"          => "Button",
                		"pic_url"       => "core/img/button/add.gif",
               	         "bootstrap_class" => "icon-plus",
                		"act"           => "add",
                		"on_table_show" => array( &$this, 'db_fields_show')
                		),
                		
                )
            );
            $this->fix_copy_form($arr);
            return $arr;
    }
    function get_buttons_form()
    {
    	global $url;
        $u = $this->get_page_url();
        $arr = array(
            "name"         => "buttons",
        	"caption"      => "Кнопки формы &laquo;{$this->form_name}&raquo;",
        	"add_caption"  => "Новая кнопка",
            "edit_caption" => "Редактирование кнопки",
            "index_field" => "name",
            "url"         => $u,
            "movable_rows" => true,
            "buttons" => array(
                 "save" => array(
			          "form_caption"  => "Сохранить",
			   	      "table_caption" => "",
			   	      "image"         => "core/img/button/ok.gif",
               	  	  "bootstrap_class" => "icon-ok"
			     	),
			     "cancel" => array(
			          "form_caption"  => "Отменить",
			   	      "table_caption" => "",
			   	      "image"         => "core/img/button/cancel.gif",
               	  	  "bootstrap_class" => "icon-remove"
			     ),
			     "back" => array(
                      "form_caption"  => "",
               	      "table_caption" => "Назад",
               	      "image"         => "core/img/button/back.gif",
               	      "bootstrap_class" => "icon-arrow-left"
                 ),
			     "add" => array(
                      "form_caption"  => "",
               	      "table_caption" => "Добавить",
               	      "image"         => "core/img/button/add.gif",
               	      "bootstrap_class" => "icon-plus"
                 ),
                 "delete" => array(
                      "form_caption"  => "",
               	      "table_caption" => "Удалить",
               	      "image"         => "core/img/button/delete.gif",
               	      "bootstrap_class" => "icon-trash"
                 ),
                 "order" => array(
       				  	"form_caption"  => "",
   	      				"table_caption" => "Порядок",
   	      				"image"         => "core/img/button/save.gif",
               	  	    "bootstrap_class" => "icon-save"
     			 ),
     			 "copy" => array(
       				   "form_caption"  => "",
   	      				"table_caption" => "Копировать",
   	      				"image"         => "core/img/button/copy.gif",
               	  	    "bootstrap_class" => "icon-copy"
     			),
            ),
            "fields"      => array(
               "select" => Array(
                		"form_caption"  => "",
                		"table_caption" => "*",
                		"type"          => "CheckBox",
                		),
			    "name" => Array(
			    		"form_caption"  => "Имя нопки",
			    		"table_caption" => "Имя",
			    		"description"   => "Уникальное имя кнопки, оно же и act возникающий по ее нажатии",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "String",
			    		"length"        => 37,
			    		"max_lenght"    => 32
			    		),
			    "form_caption" => Array(
			       		"form_caption"  => "Заголовок кнопки в формах",
			    		"table_caption" => "Форм.З.",
			    		"description"   => "Заголовок кнопки используемый в формах, если это поле пустое то данная кнопка не будет показыаться в форме",
			    		"read_only"     => 0,
			    		"reqired"       => 0,
			    		"type"          => "String",
			    		"length"        => 37,
			    		"max_lenght"    => 64
			    		),
			    "table_caption" => Array(
			    		"sql_name"      => "table_caption",
			    		"form_caption"  => "Заголовок кнопки в таблицах",
			    		"table_caption" => "Табл.З.",
			    		"description"   => "Заголовок кнопки используемый при отображенни в таблицах, если это поле пустое то данное кнопка не будет показываться в таблице",
			    		"read_only"     => 0,
			    		"reqired"       => 0,
			    		"type"          => "String",
			    		"length"        => 37,
			    		"max_lenght"    => 64,
			    		),
			    "image" => Array(
			       		"form_caption"  => "Картинка",
			    		"table_caption" => "",
			    		"description"   => "Укажите урль картинки из папки статических файлов",
			    		"read_only"     => 0,
			    		"reqired"       => 0,
			    		"type"          => "String",
			    		"length"        => 37,
			    		"max_lenght"    => 32
			    		),
			    "bootstrap_class" => Array(
			    		"sql_name"      => "bootstrap_class",
			    		"form_caption"  => "Класс bootstrap",
			    		"table_caption" => "",
			    		"description"   => "Класс bootstrap для отображения соотвествующей картинки",
			    		"read_only"     => 0,
			    		"reqired"       => 0,
			    		"type"          => "String",
			    		"length"        => 37,
			    		"max_lenght"    => 32
			    		),			
			    "delete" => Array(
			    		"form_caption"  => "",
			    		"table_caption" => "Удалить",
			    		"description"   => "Удалить галлерею",
			    		"type"          => "CheckBox",
			    		),
			    "edit" => Array(
                		"form_caption"  => "",
                		"table_caption" => "Ред.",
                		"type"          => "Button",
                		"pic_url"       => "core/img/button/edit.gif",
                		"act"           => "edit",
               	  	    "bootstrap_class" => "icon-pencil"
                		),
               ),
            );
            $this->fix_copy_form($arr);
            return $arr;
    }
    function get_sql_form()
    {
        global $url;
        $u = $this->get_page_url();
        return array(
            "name"        => "sql",
            "caption"     => "SQL формы &laquo;{$this->form_name}&raquo;",
            "edit_caption"  => "Параметры SQL формы &laquo;{$this->form_name}&raquo;",
            "index_field" => "name",
            "url"         => $u,
            "movable_rows" => true,
            "buttons" => array(
                 "save" => array(
			          "form_caption"  => "Сохранить",
			   	      "table_caption" => "",
			   	      "image"         => "core/img/button/ok.gif",
               	      "bootstrap_class" => "icon-ok"
			     	),
			     "cancel" => array(
			          "form_caption"  => "Отменить",
			   	      "table_caption" => "",
			   	      "image"         => "core/img/button/cancel.gif",
               	  	  "bootstrap_class" => "icon-remove"
			     ),
            ),
            "fields"      => array(
	           	"table" => Array(
	           			"table_caption" => "",
                		"form_caption" => "Имя таблицы",
                		"type"          => "String",
                		),
                "select" => Array(
                		"form_caption"  => "Запрос SELECT",
                		"table_caption" => "",
                		"type"          => "Text",
                		"rows"			=> 15,
                		"cols"			=> 80,
                		"highlight"		=> 'sql',
                		),
                "position_field" => Array(
                		"form_caption"  => "Поле порядка",
                		"description"  => "Поле указывающее порядок записей, нужно включить <b>Перестановка строк</b> в параметрах формы.",
                		"table_caption" => "",
                		"type"          => "String",
                		),
                ),
            );
    }
    function get_filters_form()
    {
    	global $url;
        $u = $this->get_page_url();
        $arr = array(
            "name"         => "filters",
        	"caption"      => "Фильтры формы &laquo;{$this->form_name}&raquo;",
        	"add_caption"  => "Новый фильтр",
            "edit_caption" => "Редактирование фильтра",
            "index_field" => "name",
            "url"         => $u,
            "movable_rows" => true,
            "buttons" => array(
                 "save" => array(
			          "form_caption"  => "Сохранить",
			   	      "table_caption" => "",
			   	      "image"         => "core/img/button/ok.gif",
               	      "bootstrap_class" => "icon-ok"
			     	),
			     "cancel" => array(
			          "form_caption"  => "Отменить",
			   	      "table_caption" => "",
			   	      "image"         => "core/img/button/cancel.gif",
               	  	  "bootstrap_class" => "icon-remove"
			     ),
			     "back" => array(
                      "form_caption"  => "",
               	      "table_caption" => "Назад",
               	      "image"         => "core/img/button/back.gif",
               	      "bootstrap_class" => "icon-arrow-left"
                 ),
			     "add" => array(
                      "form_caption"  => "",
               	      "table_caption" => "Добавить",
               	      "image"         => "core/img/button/add.gif",
               	      "bootstrap_class" => "icon-plus"
                 ),
                 "delete" => array(
                      "form_caption"  => "",
               	      "table_caption" => "Удалить",
               	      "image"         => "core/img/button/delete.gif",
               	      "bootstrap_class" => "icon-trash"
                 ),
                 "order" => array(
       				   "form_caption"  => "",
   	      				"table_caption" => "Порядок",
   	      				"image"         => "core/img/button/save.gif",
               	  	    "bootstrap_class" => "icon-save"
     			),
     			"copy" => array(
       				   "form_caption"  => "",
   	      				"table_caption" => "Копировать",
   	      				"image"         => "core/img/button/copy.gif",
               	  	    "bootstrap_class" => "icon-copy"
     			),
            ),
            "fields"      => array(
               "select" => Array(
                		"form_caption"  => "",
                		"table_caption" => "*",
                		"type"          => "CheckBox",
                		),
               "type" => Array(
			    		"form_caption"  => "Тип Фильтра",
			    		"table_caption" => "Тип",
			    		"description"   => "Тип Фильтра",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "SimpleSelect",
			    		"values"        => Nertz_Form::get_filter_types(),
			    		),
			    "name" => Array(
			    		"form_caption"  => "Имя фильтра",
			    		"table_caption" => "Имя",
			    		"description"   => "Уникальное имя поля в фильтра",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "String",
			    		"length"        => 37,
			    		"max_lenght"    => 32
			    		),
			    "caption" => Array(
			    		"form_caption"  => "Заголовок",
			    		"table_caption" => "Заголовок",
			    		"description"   => "Заголов фильтра в таблице",
			    		"read_only"     => 0,
			    		"reqired"       => 0,
			    		"type"          => "String",
			    		"length"        => 37,
			    		"max_lenght"    => 64
			    		),
			    "autosubmit" => Array(
			    		"form_caption"  => "Только для чтения",
			    		"table_caption" => "",
			    		"description"   => "Флаг того что поле только для чтения",
			    		"read_only"     => 0,
			    		"type"          => "Bool",
			    		),
	   		    "caption_event" => Array(
                		"form_caption" => "Обрабочики событий",
                		"type"          => "Caption",
                		),
                "on_create"      => Array(
                		"form_caption"  => "После создания фильтра",
                		"type"          => "String",
                		"description"   => "Обработчик вызываемый после создания фильтраполя<br/><b>Параметры:</b><br/> \&\$filter Nertz_Form_Filter Ссылка на экземпляр фильтра<br/> <b>Результат:</b><br/> Не важен",
                		),
                "on_show"     => Array(
                	"form_caption"  => "Обрабочик показа фильтра",
                	"type"          => "String",
                	"description"   => "Обработчик показа фильтра<br/><b>Параметры:</b><br/> \&\$filter Nertz_Form_Filter Ссылка на фильтр<br/> <b>Результат:</b><br/> string HTML код фильтра",
                	),

			    "delete" => Array(
			    		"form_caption"  => "",
			    		"table_caption" => "Удалить",
			    		"description"   => "Удалить Фильтр",
			    		"type"          => "CheckBox",
			    		),
			    "edit" => Array(
                		"form_caption"  => "",
                		"table_caption" => "Ред.",
                		"type"          => "Button",
                		"pic_url"       => "core/img/button/edit.gif",
                		"act"           => "edit",
               	  	    "bootstrap_class" => "icon-pencil"
                		),

               ),
            );
           	$this->fix_copy_form($arr);
            return $arr;
    }
	function get_uniques_form()
    {
    	global $url;
        $u = $this->get_page_url();
        $arr = array(
            "name"         => "uniques",
        	"caption"      => "Уникальности формы &laquo;{$this->form_name}&raquo;",
        	"add_caption"  => "Новая уникальность",
            "edit_caption" => "Редактирование уникальности",
            "index_field" => "name",
            "url"         => $u,
            "buttons" => array(
                 "save" => array(
			          "form_caption"  => "Сохранить",
			   	      "table_caption" => "",
			   	      "image"         => "core/img/button/ok.gif",
               	      "bootstrap_class" => "icon-ok"
			     	),
			     "cancel" => array(
			          "form_caption"  => "Отменить",
			   	      "table_caption" => "",
			   	      "image"         => "core/img/button/cancel.gif",
               	  	  "bootstrap_class" => "icon-remove"
			     ),
			     "back" => array(
                      "form_caption"  => "",
               	      "table_caption" => "Назад",
               	      "image"         => "core/img/button/back.gif",
               	      "bootstrap_class" => "icon-arrow-left"
                 ),
			     "add" => array(
                      "form_caption"  => "",
               	      "table_caption" => "Добавить",
               	      "image"         => "core/img/button/add.gif",
               	      "bootstrap_class" => "icon-plus"
                 ),
                 "delete" => array(
                      "form_caption"  => "",
               	      "table_caption" => "Удалить",
               	      "image"         => "core/img/button/delete.gif",
               	      "bootstrap_class" => "icon-trash"
                 ),
                 "copy" => array(
       				   "form_caption"  => "",
   	      				"table_caption" => "Копировать",
   	      				"image"         => "core/img/button/copy.gif",
               	  	    "bootstrap_class" => "icon-copy"
     			),
            ),
            "fields"      => array(
               "select" => Array(
                		"form_caption"  => "",
                		"table_caption" => "*",
                		"type"          => "CheckBox",
                		),
			    "name" => Array(
			    		"form_caption"  => "Имя главного поля",
			    		"table_caption" => "Имя поля",
			    		"description"   => "Имя главного поля, для которого будет выводится сообщение об ошибке",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "String",
			    		"length"        => 37,
			    		"max_lenght"    => 32
			    		),
			    "fields" => Array(
			    		"form_caption"  => "Имена полей",
			    		"table_caption" => "Имена полей",
			    		"description"   => "Имена всех полей учавствующих в проверке на уникальность",
			    		"read_only"     => 0,
			    		"reqired"       => 1,
			    		"type"          => "AssocArray",
			    		),
			    "message" => Array(
			    		"form_caption"  => "Сообщение об ошибке",
			    		"table_caption" => "Сообщение об ошибке",
			    		"description"   => "",
			    		"read_only"     => 0,
			    		"reqired"       => 0,
			    		"type"          => "String",
			    		"length"        => 37,
			    		"max_lenght"    => 255
			    		),
			    "delete" => Array(
			    		"form_caption"  => "",
			    		"table_caption" => "Удалить",
			    		"description"   => "Удалить Фильтр",
			    		"type"          => "CheckBox",
			    		),
			    "edit" => Array(
                		"form_caption"  => "",
                		"table_caption" => "Ред.",
                		"type"          => "Button",
                		"pic_url"       => "core/img/button/edit.gif",
                		"act"           => "edit",
               	  	    "bootstrap_class" => "icon-pencil"
                		
                		),

               ),
            );
            $this->fix_copy_form($arr);
           	return $arr;
    }

    function get_levels($level = null)
    {
    	return $this->forms->get_config_levels($this->form_name);
    }
    function fields_show(&$field, $row)
    {
    	$field->params['number'] = $row['field_count'];
		return $field->field_get_table_html($row);
    }
	function buttons_show(&$field, $row)
    {
    	$field->params['number'] = $row['button_count'];
    	return $field->field_get_table_html($row);
    }
	function uniques_show(&$field, $row)
    {
    	$field->params['number'] = $row['unique_count'];
    	return $field->field_get_table_html($row);
    }
	function filters_show(&$field, $row)
    {
    	$field->params['number'] = $row['filter_count'];
    	return $field->field_get_table_html($row);
    }
    function go_back() 
    {
    	global $url;
		$arr = array('page' => $url->get_page(), 'level' => $this->level);
    	if ($this->copy) {
    		$arr['copy'] = $this->copy; 
    	}
    	Nertz::redirect($arr);    	
    }
    function fix_copy_form(&$arr)
    {
    	if ($this->copy) {
          	$els = array('fields', 'buttons', 'filters', 'uniques', 'edit', 'sql', 'delete');
           	foreach($els as $el) {
           		if ($el != $this->copy) {
           			unset($arr['fields'][$el]);
           		}
           	}	
           	unset($arr['buttons']['add']);
           	unset($arr['buttons']['delete']);
           	unset($arr['buttons']['order']);
           	$arr['movable_rows'] = false;
        } else {
        	unset($arr['fields']['select']);
        	if ($arr['name'] == 'list') {
        		unset($arr['buttons']['back']);
        	}
        }   	
        
    }
    function act_copy(&$form)
    {
    	global $url, $session;
		if (!$this->copy) {
			$session->set_value($url->get_page().'_back_level', $this->level);
			$session->set_value($url->get_page().'_back_frm', $this->form_name);
			$arr = array('page' => $url->get_page(), 'level' => $this->level, 'copy' => $this->frm);
			Nertz::redirect($arr);
		} else {
			$form->load_posted_vars();
			$names = $form->get_value('select', array());
			$level = $session->get_value($url->get_page().'_back_level', $this->level);
			$form_name = $session->get_value($url->get_page().'_back_frm');
			// Хитрый вызов методов
			$get_method = 'get_' . substr($this->copy, 0, strlen($this->copy)-1) .'_params';
			$set_method = 'set_' . substr($this->copy, 0, strlen($this->copy)-1) .'_params';
			
			$target_form = new Nertz_Config_Forms();
			$target_form->load_from_file($form_name, $level);
			foreach($names as $name) {
				// Собственно хитрые методы копирования
				$data = $this->forms->$get_method($name);
				$target_form->$set_method($data);
			}
			$target_form->save_to_file($form_name, $level); 
			$this->go_back_after_copy();
		}    	
    }
    function act_db()
    {
    	global $url, $session, $db;
 		$form_buttons = new Nertz_Form($this->get_buttons_form());
    	$this->forms->load_from_file($this->form_name, $this->levels);
    	$sql_params = $this->forms->get_sql_params();
    	$rows = $this->get_db_fields($sql_params['table']);
		// Проверим существующие поля
		$fields_params = $this->forms->fetch_fields();
		foreach ($fields_params as $field) {
			if (isset($rows[$field['name']])) {
				$rows[$field['name']]['exists'] = isset($rows[$field['name']]) ? true : false;
			}
		}
		// Слепим формочку и выведем ее
		$form_db = new Nertz_Form($this->get_db_form());
		$form_db->set_table_values($rows);
    	return $form_db->show_table('Nertz_Table_Editor');
		
    }
    function get_db_fields($table_name)
    {
    	global $url, $session, $db;
    	$fields = $db->getAll('DESCRIBE `?#`', array($table_name));
		// Размеберемся с типами
		$rows = array();
		foreach ($fields as $field) {
			$arr = array();
			$arr['name'] = $field['Field'];
			$e = explode('(', $field['Type']);
			switch (strtolower($e[0])) {
				case 'varchar'    : $arr['type']    = 'String'; break;
				case 'char'       : $arr['type']    = 'String'; break;
				case 'tinyint'    : $arr['type']    = 'Int';    break;
				case 'smallint'   : $arr['type']    = 'Int';    break;
				case 'mediumint'  : $arr['type']    = 'Int';    break;
				case 'int'        : $arr['type']    = 'Int';    break;
				case 'bigint'     : $arr['type']    = 'Int';    break;
				case 'float'      : $arr['type']    = 'Float';  break;
				case 'double'     : $arr['type']    = 'Float';  break;
				case 'demicial'   : $arr['type']    = 'Float';  break;
				case 'datetime'   : $arr['type']    = 'Date';   break;
				case 'time'       : $arr['type']    = 'Date';   break;
				case 'tinytext'   : $arr['type']    = 'Text';   break;
				case 'mediumtext' : $arr['type']    = 'Text';   break;
				case 'text'       : $arr['type']    = 'Text';   break;
				case 'longtext'   : $arr['type']    = 'Text';   break;
				case 'tinyblob'   : $arr['type']    = 'Text';   break;
				case 'mediumblob' : $arr['type']    = 'Text';   break;
				case 'blob'       : $arr['type']    = 'Text';   break;
				case 'longblob'   : $arr['type']    = 'Text';   break;
				case 'bool'       : $arr['type']    = 'Bool';   break;
				case 'bit'        : $arr['type']    = 'Bool';   break;
			}
			$n = 0;
			$arr['max_length'] = strbtw($field['Type'],'(', ')', $n);
			$arr['length'] =  37;
			if ($arr['type'] == 'String') {
				if ($arr['max_length'] < 37) {
					$arr['length'] =  $arr['max_length'];
				} 
			}
			$arr["reqired"] = '';
			if (strtolower($field['Null']) == 'yes') {
				$arr["reqired"] = 'on';
			}
			$rows[$arr['name']] = $arr;
		}
		return $rows;
    }
    function db_fields_show(&$field, &$row) 
    {
    	$field->params['url']['fields_name']   = $row['name'];
    	$field->params['url']['fields_type']   = $row['type'];
    	$field->params['url']['fields_length'] = $row['length'];
    	$field->params['url']['fields_max_length'] = $row['max_length'];
    	$field->params['url']['fields_reqired'] = $row['reqired'];
    	$field->params['url']['act'] = 'add';
    	return $field->field_get_table_html($row);
    }
    function go_back_after_copy()
    {
    	global $session, $url;
    	
   		$arr = array(
			'page'  => $url->get_page(), 
			'level' => $session->get_value($url->get_page().'_back_level', $this->level),
			'act'   => $this->copy,
   			'index' => $session->get_value($url->get_page().'_back_frm', $this->form_name),
		);
		$session->unset_value($url->get_page().'_back_level');
		$session->unset_value($url->get_page().'_back_frm');
		$this->copy = '';
		Nertz::redirect($arr);
    }
}
