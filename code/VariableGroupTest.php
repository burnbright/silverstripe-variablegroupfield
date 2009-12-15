<?php
class VariableGroupTest extends Page_Controller{
	
	function init(){
		parent::init();
		
	}
	
	function Title(){
		return "Variable Group Field Test";		
	}
	
	function Link(){
		return 'VariableGroupTest';
	}
	
	function Form(){
		
		$fields = new FieldSet(
			$vgf = new VariableGroupField('MyData',3,
				new TextField('Name','Name'),
				new NumericField('Score','Score')
			)
		);
		
		$vgf->writeOnSave(false);
		$vgf->setLoadingImageUrl('variablegroupfield/images/ajax-loader.gif');
		
		$actions = new FieldSet(
			new FormAction('result','Submit')
		);
		
		$form = new Form($this,'Form',$fields,$actions);
		
		return $form; 
	}
	
	function Content(){
		return "<p>This is the Variable Group Field in action. Click '+ add' to add another group of fields. Clicking '- remove' will remove the last group.</p>";
	}
	
	function result($data,$form){
		$vgf = $form->Fields()->fieldByName('MyData');
		$data = $vgf->getDataObjectSet();
		return array(
			'Data' => $data,
			'Content' => 'Here is what you entered:',
			'Form' => ''
		);
	}
	
}
?>
