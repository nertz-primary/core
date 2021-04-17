<?php
include_once(Nertz::class_path('Nertz_Form_Field'));
//include_once('fckeditor/fckeditor.php');
include_once('ckeditor/ckeditor.php');

class Nertz_Form_Field_Html extends Nertz_Form_Field
{
	function Nertz_Form_Field_Html($name, &$form)
	{
		parent::Nertz_Form_Field($name, $form);
	}
	function field_get_form_html()
	{
		global $url, $template;
	/*	$oFCKeditor = new FCKeditor($this->_get_post_name()) ;
		$oFCKeditor->BasePath	= $url->handler->prefix . '/includes/fckeditor/' ;
		$oFCKeditor->Value		= $this->get_value();
		if (!empty($this->params['width'])) {
			$oFCKeditor->Width = $this->params['width'];	
		}
		if (!empty($this->params['height'])) {
			$oFCKeditor->Height	= $this->params['height'];	
		}
		
		
		return $oFCKeditor->CreateHtml();*/
		/*$CKEditor = new CKEditor();
		$CKEditor->basePath = $url->handler->prefix . '/includes/ckeditor/';
		$CKEditor->returnOutput = true;
		$s = $CKEditor->editor($this->_get_post_name(), $this->get_value());
		$template->add_js('/includes/Djenx.Explorer/djenx-explorer.js');
		$s .= "<script type='text/javascript'>DjenxExplorer.init(
			{returnTo: '{$this->_get_post_name()}',
			lang : 'ru',
			path: '{$url->gen_static_url('/includes/Djenx.Explorer/')}',
			});</script>";*/
		$template->add_js('/includes/ckeditor/ckeditor.js');
		$template->add_js('/includes/Djenx.Explorer/djenx-explorer.js');
		$s = "<textarea name='{$this->_get_post_name()}'>" . htmlspecialchars($this->get_value()). "</textarea>";
		$s .= "<script type='text/javascript'>DjenxExplorer.init({
			returnTo: CKEDITOR.replace('{$this->_get_post_name()}', {bodyClass:'econt', contentsCss : '" . $url->gen_static_url('/css/editor.css') . "'}),
			lang : 'ru',
			path: '{$url->gen_static_url('/includes/Djenx.Explorer/')}',
			});</script>";
		return $s;
	}
	function field_get_table_html($row)
	{
	    return $row[$this->name];
	}
}