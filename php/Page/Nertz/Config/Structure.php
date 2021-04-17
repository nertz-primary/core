<?php
/**
 * Страница редактора структуры конфигурационных файлов
 *
 */
class Page_Nertz_Config_Structure extends Nertz_Page
{
    function Page_Nertz_Config_Structure($params)
    {
        $this->config_name = 'general';
    	parent::Nertz_Page($params);
    }
    function show()
    {
        global $url, $template;
        global $nertz_config_structure_form;
        //$template->add_css('core/css/admin.css');
	    include_once(Nertz::class_path('Nertz_Config_Structure'));
        $struct = new Nertz_Config_Structure();
        $levels = $struct->get_config_levels($this->config_name);
        $template->set_value('levels', $levels);
        $struct_level = $url->get_value('level', array_first_key($levels));
        $template->set_value('struct_level', $struct_level);
        $struct->load_from_file($this->config_name, $levels[$struct_level]['levels']);
		
        include_once(Nertz::class_path('Nertz_Form'));
        $path = $url->get_value('path', '/');
        $node = $struct->get_node($path);
        if (!empty($node['special']))
        {
        	// Для полей со "special" организуем собственную форму
        	$class_name = 'Nertz_Config_Special_' . $node['special'];
        	include_once(Nertz::class_path($class_name));
        	$special = new $class_name;
        	$form = new Nertz_Form($special->get_form_params());
        } 
        else 
        {
        	$form = new Nertz_Form($nertz_config_structure_form);	
        }
        $form_act = $form->get_posted_act();
        $index    = $form->get_posted_index();
        
        $show_table = true;
        $form->params['url']['path'] = $path;
        if ($form_act == 'edit' && $index )
        {
            $form->set_vars($struct->get_node($path . '/' . $index));
            $form->params['url']['index'] = $index;
            return $form->show_form('Nertz_Form_Table');
            $show_table = false;
        }
        else if ($form_act == 'add')
        {
            return $form->show_form('Nertz_Form_Table');
            $show_table = false;
        }
        else if ($form_act == 'delete')
        {
            $form->load_posted_vars();
            $struct->delete_node($path, $form->get_value('delete'));
            $struct->save_to_file();
        }
        else if ($form_act == 'order')
        {
            $form->load_posted_vars();
            $struct->set_order($path, $form->get_order());
            $struct->save_to_file();
        }
        else
        {
        	if ($form_act == 'save')
            {
                if(!$form->load_posted_vars())
                {
                    if ($index)
                    {
                        $form->params['url']['index'] = $index;
                    }
                    return  $form->show_form('Nertz_Form_Table');
                    $show_table = false;
                }
                else if ($index)
                {
                	$struct->update_node($path . '/' . $index, $form->get_vars());
                    $struct->save_to_file();
                }
                else
                {
                    $struct->add_node($path, $form->get_vars());
                    $struct->save_to_file();
                }
            }
        }
        if ($show_table)
        {
         	if (isset($node['type']) && $node['type'] == 'Select') 
        	{
        		// У типа поля Select набор полей бкдет фиксированным, поэтому 
        		unset($form->params['fields']['delete']);
        		unset($form->params['fields']['edit']);
        		unset($form->params['buttons']['add']);
        		unset($form->params['buttons']['delete']);
        	}
			// Покажем табличку с полями
            $form->set_table_values($struct->get_node_list($url->get_value('path','/')));
            return $form->show_table('Nertz_Table_Editor');
        }
    }
    function get_select_params_form()
    {
    	
    }
    
}