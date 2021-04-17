<?php
include_once(Nertz::class_path('Nertz_Form_Field'));

class Nertz_Form_Field_Text extends Nertz_Form_Field
{
	function Nertz_Form_Field_Text($name, &$form)
	{
		parent::Nertz_Form_Field($name, $form);
		$this->highlight = isset($this->params['highlight']) ? $this->params['highlight'] : "";
	}
	function field_get_form_html()
	{
		global $template;
		$rows = isset($this->params['rows']) ? " rows='" . $this->params['rows'] . "' " : "";
		$cols = isset($this->params['cols']) ? " cols='" . $this->params['cols'] . "' " : "";
		$highlight = '';
		if ($this->highlight) {
			//$template->add_js('/includes/codepress/codepress.js');
			//$highlight = " class='codepress {$this->highlight}' ";
			$template->add_js('/includes/edit_area/edit_area_full.js');
			$highlight .= '<script language="javascript" type="text/javascript">
editAreaLoader.init({
	id : "' . $this->_get_post_name() . '",
	syntax: "' . $this->highlight . '",
	start_highlight: true
});
</script>';
		}
		
		
		return "<textarea {$rows} {$cols}  id='". $this->_get_post_name() . "' name='". $this->_get_post_name() . "' >" . htmlspecialchars($this->get_value()) . "</textarea>{$highlight}";
	}
	function field_get_table_html($row)
	{
		return htmlspecialchars($row[$this->name]);
	}
	function load_posted_value()
	{
		parent::load_posted_value();
	    $this->set_value(htmlspecialchars_decode($this->get_value()));
	}
}