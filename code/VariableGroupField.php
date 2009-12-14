<?php
/**
 * Splits a group into multiple composite fields & allows saving to has_many relationship.
 */
class VariableGroupField extends CompositeField{
	
	protected $initcount = 1;
	protected $origfieldgroup = null;
	protected $showfieldscount = false;
	protected $loadingimageurl = null;
	
	public function __construct($name, $initcount = 1) {
		
		$args = func_get_args();
		
		$name = array_shift($args);
		$initcount = array_shift($args);
		
		if(!is_string($name)) user_error('TabSet::__construct(): $name parameter to a valid string', E_USER_ERROR);
		$this->name = $name;
	
		
		if(is_numeric(Session::get($this->name."_initcount"))){
			 $this->initcount = Session::get($this->name."_initcount");
		}
		elseif(is_numeric($initcount)){
			$this->initcount = $initcount;
		}
			
		$this->id = preg_replace('/[^0-9A-Za-z]+/', '', $name);
		
		// Legacy handling: only assume second parameter as title if its a string,
		// otherwise it might be a formfield instance
		if(isset($args[0]) && is_string($args[0])) {
			$title = array_shift($args);
		}
		$this->title = (isset($title)) ? $title : FormField::name_to_label($name);
		
		$this->brokenOnConstruct = false;
		
		parent::__construct($args);
		
		//duplicate child fields as necessary
		$this->origfieldgroup = unserialize(serialize($this->children));
		$this->generateFields();
		
		Requirements::javascript('variablegroupfield/javascript/variablefieldgroup.js');
	}
	
	function FieldHolder() {
		$subfields = $this->FieldSet();
		
		$idAtt = isset($this->id) ? " id=\"{$this->id}\"" : '';
		$className = ($this->columnCount) ? "field VariableGroup {$this->extraClass()} multicolumn" : "field VariableGroup {$this->extraClass()}";
		$content = "<div class=\"$className\"$idAtt>\n";
				
		foreach($subfields as $subfield) {
			if($this->columnCount) {
				$className = "column{$this->columnCount}";
				if(!next($subfields)) $className .= " lastcolumn";
				$content .= "\n<div class=\"{$className}\">\n" . $subfield->FieldHolder() . "\n</div>\n";
			} else if($subfield){
				$content .= "\n" . $subfield->FieldHolder() . "\n";
			}
		}
		if($this->loadingimageurl){
			$content .= "<div class='loadingimage' style='display:none;'><img src='".$this->loadingimageurl."' /></div>";
		}
		$content .= "<br/><a href=\"".$this->AddLink()."\" class=\"addlink\" >+ add</a>\n";
		$content .= "<a href=\"".$this->RemoveLink()."\" class=\"removelink\" />- remove</a>\n";
		$content .= "</div>\n";
		return $content;
	}
	
	function saveInto(DataObjectInterface $record) {
		if($this->name) {
			//$record->setCastedField($this->name, $this->dataValue()); //origional code

			$class = $record->has_many($this->name);
			foreach($this->FieldSet() as $compositefield){
				$dataobject = new $class();
				foreach($compositefield->FieldSet() as $field){
					$field->setName(substr($field->Name(),0,strpos($field->Name(),'_')));
					$field->saveInto($dataobject);
				}
				$dataobject->{$record->class."ID"} = $record->ID;
				if($record->ID){
					$dataobject->write();
				}
			}
		}

	}
	
	function hasData() { return true; }
	
	function AddLink() {
		return $this->Link() . "/add";
	}
	
	function RemoveLink() {
		return $this->Link() . "/remove";
	}
	
	function add(){
		$this->initcount++;
		Session::set($this->name."_initcount",$this->initcount);
		if(Controller::isAjax() || true){		
			return $this->generateFieldGroup($this->initcount)->fieldHolder();
		}
		return 'added';
	}
	
	function remove(){
		if($this->initcount > 0){
			$this->initcount--;
			Session::set($this->name."_initcount",$this->initcount);
		}
		return 'remove';
	}
	
	function setCount($count = null){
		if($count){
			$this->initcount = $count;
		}
	}
	
	function setLoadingImageURL($i){
		$this->loadingimageurl = $i;
	}
	
	function generateFields(){
		$returnfields = new FieldSet();		
		for($i = 1; $i <= $this->initcount; $i++){
			$returnfields->push($this->generateFieldGroup($i));
		}
		$this->children = $returnfields;	
	}
	
	function generateFieldGroup($i){
		$newfields = new CompositeField( unserialize(serialize($this->origfieldgroup)));
		foreach($newfields->FieldSet() as $subfield){
			$subfield->addExtraClass($subfield->Name());
			if($this->showfieldscount){
				$subfield->setTitle($subfield->Title()." ".$i);
			}
			$subfield->setName($subfield->Name()."_".$i);
			
		}
		$newfields->setName($this->name."_group_".$i);
		return $newfields;
	}

}
?>
