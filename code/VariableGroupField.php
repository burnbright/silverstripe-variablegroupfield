<?php
/**
 * Variable Group Field
 * 
 * @author Jeremy Shipman <jeremy@burnbright.co.nz> www.burnbright.co.nz
 * 
 * Splits a group into multiple composite fields & allows saving to has_many relationship.
 */
class VariableGroupField extends CompositeField{
	
	//TODO: load data from dataobjectset capability
	
	//configuration options
	protected $initcount = 1;
	protected $origfieldgroup = null;
	protected $showfieldscount = false;
	protected $loadingimageurl = null;
	protected $fieldstoduplicate = null;
	protected $writeonsave = true;
	
	/**
	 * The constructor will generate $initcount number of field groups.
	 * 
	 */
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
	
	/**
	 * Set the initial count of groups to show
	 */
	function setCount($count = null){
		if($count){
			$this->initcount = $count;
		}
	}
	
	/**
	 * Set the 'loading' spinner image
	 */
	function setLoadingImageURL($i){
		$this->loadingimageurl = $i;
	}
	
	/**
	 * Choose to not write on save
	 */
	function writeOnSave($wos = false){
		$this->writeonsave = $wos;
	}
	
	/**
	 * Name the fields that you want to be duplicated when user clicks 'add'
	 */
	function setFieldsToDuplicate(array $fields){
		$this->fieldstoduplicate = $fields;
	}
	
	//override the CompositeField hasData function
	function hasData() { return true; }
	
	
	/**
	 * Renders the field with the additional controls
	 */
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
		$content .= "<div class=\"bottomcontrols\">";
		if($this->loadingimageurl){
			$content .= "<div class='loadingimage' style='display:none;'><img src='".$this->loadingimageurl."' /></div>";
		}
		$content .= "<a href=\"".$this->AddLink()."\" class=\"addlink\" >+ add</a>\n";
		$content .= "<a href=\"".$this->RemoveLink()."\" class=\"removelink\" />- remove</a>\n";
		$content .= "</div></div>\n";
		return $content;
	}
	
	function AddLink() {
		return $this->Link() . "/add";
	}
	
	function RemoveLink() {
		return $this->Link() . "/remove";
	}
	
	/**
	 * Increase the init count. Returns new field group if ajax.
	 */
	function add(){
		$this->initcount++;
		Session::set($this->name."_initcount",$this->initcount);
		if(Controller::isAjax() || true){		
			return $this->generateFieldGroup($this->initcount)->fieldHolder();
		}
		//TODO: save form data to be loaded again?
		//TODO: populate fields to be duplicated
		Director::redirectBack();
		return 'added';
	}
	
	/**
	 * Decrease the init count.
	 */
	function remove(){
		if($this->initcount > 0){
			$this->initcount--;
			Session::set($this->name."_initcount",$this->initcount);
		}
		Director::redirectBack();
		return 'remove';
	}
	
	/**
	 * Used by constructor to create the new set of fields. (FieldSet of CompositeFields)
	 */
	function generateFields(){
		$returnfields = new FieldSet();		
		for($i = 1; $i <= $this->initcount; $i++){
			$returnfields->push($this->generateFieldGroup($i));
		}
		$this->children = $returnfields;	
	}
		
	/**
	 * Adds the integer $i to the end of each field name.
	 */
	
	function generateFieldGroup($i){
		$newfields = new CompositeField( unserialize(serialize($this->origfieldgroup)));
		foreach($newfields->FieldSet() as $subfield){
			$subfield->addExtraClass($subfield->Name());
			if($this->showfieldscount){
				$subfield->setTitle($subfield->Title()." ".$i);
			}
			$subfield->setName($subfield->Name()."_".$i);
			if($subfield->isComposite()){
				$this->modifyComposite($i,$subfield);
			}
		}
		$newfields->setName($this->name."_group_".$i);
		return $newfields;
	}
	
	
	/**
	 * Helper function for recursively changing field names. This code is probably redundant.
	 */
	function modifyComposite($i,$field = null){

		foreach($field->FieldSet() as $subfield){
			$subfield->addExtraClass($subfield->Name());
			if($this->showfieldscount){
				$subfield->setTitle($subfield->Title()." ".$i);
			}
			$subfield->setName($subfield->Name()."_".$i);
			
			if($subfield->isComposite()){
				$subfield->setChildren($this->modifyFieldGroup($i,$subfield));
			}			
		}
		if($field){
			$field->setName($field->name."_group_".$i);
		}
		return $field;
	}
	
	/**
	 * Allows saving groups into new dataobjects.
	 * The dataobject type is worked out from $this->name and the record's db array.
	 */
	function saveInto(DataObjectInterface $record) {
		if($this->name) {
			//$record->setCastedField($this->name, $this->dataValue()); //origional code

			$class = $record->has_many($this->name);
			foreach($this->FieldSet() as $compositefield){
				$dataobject = new $class();
				foreach($compositefield->FieldSet() as $field){
					$field->setName(substr($field->Name(),0,strpos($field->Name(),'_'))); //assumes underscores aren't used in a DB field name
					if($field->hasData()){
						$field->saveInto($dataobject);
					}elseif($field->isComposite()){
						$this->recursiveSaveInto($field,$dataobject); //save into composite sub-fields
					}
				}
				$dataobject->{$record->class."ID"} = $record->ID;
				if($record->ID && $this->writeonsave){ //writing new dataobjects can be disabled.
					$dataobject->write();
				}
			}
		}
		//TODO: clear session init count
	}
	
	/**
	 * Helper method for saving data that is in child fields of composite fields.
	 */
	function recursiveSaveInto($compositefield,&$dataobject){
		foreach($compositefield->getChildren() as $field){
			$field->setName(substr($field->Name(),0,strpos($field->Name(),'_'))); //assumes underscores aren't used in a DB field name			
			if($field->hasData()){
				$field->saveInto($dataobject);
			}elseif($field->isComposite()){
				$this->recursiveSaveInto($field,$dataobject); //save into composite subfields
			}	
		}
	}

}
?>
