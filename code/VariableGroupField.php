<?php
/**
 * Variable Group Field
 *
 * @author Jeremy Shipman <jeremy@burnbright.co.nz> www.burnbright.co.nz
 *
 * Splits a group into multiple composite fields & allows saving to has_many relationship.
 */
class VariableGroupField extends CompositeField{

	protected $originalchildren = null;

	//configuration options
	protected $groupcount = 1;
	protected $initcount = 1;
	protected $showfieldscount = false;
	protected $loadingimageurl = null;
	protected $fieldstoduplicate = null;
	protected $writeonsave = true;

	protected $singularitem = null;

	protected $addlabel = null;
	protected $removelabel = null;

	protected $clearonsave = true;

	//callback called when DataObjectSet is created
	protected $callbackobj = null; protected $callbackfn = null;

	/**
	 * The constructor will generate $groupcount number of field groups.
	 *
	 */
	public function __construct($name, $groupcount = 1) {

		$args = func_get_args();

		$name = array_shift($args);
		$groupcount = $initcount = array_shift($args);

		if(!is_string($name)) user_error('TabSet::__construct(): $name parameter to a valid string', E_USER_ERROR);
		$this->name = $name;


		if(is_numeric(Session::get($this->name."_groupcount"))){
			 $this->groupcount = Session::get($this->name."_groupcount");
		}
		elseif(is_numeric($groupcount)){
			$this->groupcount = $groupcount;
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
		$this->originalchildren = unserialize(serialize($this->children));
		$this->generateFields();

		Requirements::javascript('variablegroupfield/javascript/variablefieldgroup.js');
	}

	/**
	 * Add a new child field to the end of the set.
	 */
	public function push(FormField $field) {
		$this->originalchildren->push($field);
	}

	//hack
	//removeByName is called by other methods which mess this up
	function removeField($name){
		$this->originalchildren->removeByName($name);
	}
	function removeByName($name){} //overloaded parent function to prevent fields being deleted by onBeforeInsert

	function insertBefore($item,$name){
		$this->originalchildren->insertBefore($item,$name);
	}

	/**
	 * Set the initial count of groups to show
	 */
	function setCount($count = null){
		if(is_numeric($count)){
			if($count <= 0) $count = 0;
			$this->groupcount = $count;
			Session::set($this->name."_groupcount",$this->groupcount);
		}
	}

	function clearCount(){
		Session::clear($this->name."_groupcount");
		$this->groupcount = 1;
	}

	/**
	 * Set the 'loading' spinner image
	 */
	function setLoadingImageURL($i){
		$this->loadingimageurl = $i;
	}

	function setSinglularItem($s){
		$this->singularitem = $s;
	}

	function setAddRemoveLabels($add = null, $remove = null ){
		$this->addlabel = $add;
		$this->removelabel = $remove;
	}

	function disableClearOnSave($clear = false){
		$this->clearonsave = $clear;
	}

	/**
	 * Choose to not write on save. Might be useful if you just want to use the dataobject output.
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

	function setCallback($obj,$fn){
		$this->callbackfn = $fn;
		$this->callbackobj = $obj;
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
		if(!$this->readonly){
			$content .= "<div class=\"bottomcontrols\">";
			if($this->loadingimageurl){
				$content .= "<div class='loadingimage' style='display:none;'><img src='".$this->loadingimageurl."' /></div>";
			}
			$itemname = ($this->singularitem)? " ".$this->singularitem:"";

			$addlabel = ($this->addlabel) ? $this->addlabel:_t("VariableGroupField.ADD","+ add");
			$removelabel = ($this->removelabel) ? $this->removelabel:_t("VariableGroupField.REMOVE","- remove");

			$content .= "<a href=\"".$this->AddLink()."\" class=\"addlink\" >$addlabel</a>\n";
			$content .= "<a href=\"".$this->RemoveLink()."\" class=\"removelink\" />$removelabel</a>\n";
			$content .= "</div>\n";
		}
		$content .= "</div>\n";
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
		$this->groupcount++;
		$this->setCount($this->groupcount);
		if(Controller::isAjax()){
			return $this->generateFieldGroup($this->groupcount)->fieldHolder();
		}
		//TODO: save form data to be loaded again? (for non-javascript implementations)
		//TODO: populate fields to be duplicated (currently done with javascript)
		Director::redirectBack();
		return 'added';
	}

	/**
	 * Decrease the init count.
	 */
	function remove(){
		if($this->groupcount >= $this->initcount){
			$this->groupcount --;
			$this->setCount($this->groupcount);
		}
		if(!Controller::isAjax()){Director::redirectBack();}
		return 'removed';
	}

	function removeall(){
		$this->setCount($this->initcount);
		if(!Controller::isAjax()){Director::redirectBack();}
		return 'removed';
	}

	/**
	 * Used by constructor to create the new set of fields. (FieldSet of CompositeFields)
	 */
	function generateFields(){
		$returnfields = new FieldSet();
		for($i = 1; $i <= $this->groupcount; $i++){
			$returnfields->push($this->generateFieldGroup($i));
		}
		$this->children = $returnfields;
	}

	/**
	 * Adds the integer $i to the end of each field name.
	 */

	function generateFieldGroup($i){
		$newfields = new CompositeField(unserialize(serialize($this->originalchildren)));
		foreach($newfields->FieldSet() as $subfield){
			$subfield->addExtraClass($subfield->Name());
			if($this->showfieldscount){
				$subfield->setTitle($subfield->Title()." ".$i);
			}
			if($this->fieldstoduplicate && in_array($subfield->Name(),$this->fieldstoduplicate)){
				$subfield->addExtraClass('duplicateme');
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
			if($this->fieldstoduplicate && in_array($subfield->Name(),$this->fieldstoduplicate)){
				$subfield->addExtraClass('duplicateme');
			}
			$subfield->setName($subfield->Name()."_".$i);

			if($subfield->isComposite()){
				$this->modifyComposite($i,$subfield);
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
	 * @param $record - the record to relate new dataobjects to.
	 * @param $clear - clear the session group count on save.
	 */
	function saveInto(DataObjectInterface $record, $clear = true) {
		if($record && $this->name && $this->writeonsave){

			//TODO: there is probably a nicer way to get the class name
			//find out the class name
			$has_many = $record->get_static($record->class,'has_many');
			$many_many = $record->get_static($record->class,'many_many');
			$belongs_many_many = $record->get_static($record->class,'belongs_many_many');
			$class = (isset($has_many[$this->name])) ? $has_many[$this->name] : null;
			$class = (isset($belongs_many_many[$this->name])) ? $belongs_many_many[$this->name] : $class;
			$class = (isset($many_many[$this->name])) ? $many_many[$this->name] : $class;

			if($class && $record->ID){
				//$setids = array();
				//create dataobjects
				$set = $this->getDataObjectSet($class,true);

				//Set relationship on record
				$saveDest = $record->{$this->name}();
				if(! $saveDest)
					user_error("No appropriate class could be found for $this->name on $record->class.", E_USER_ERROR);
				$saveDest->addMany($set);

			}else{
				//throwing an error may break saving the filed when we don't want it to break
				//user_error("No appropriate class could be found for $this->name on $record->class.", E_USER_ERROR);
			}

		}

		if($this->clearonsave){//clear session init count
			$this->clearCount();
		}
	}

	/**
	 * Returns data as a DataObjectSet (very similar to saveInto)
	 *
	 * @param $class - the class of the DataObject to create & save into.
	 * @param $write - choose whether to write new dataobjects to database
	 * @param $parent - set the parent of the new dataobjects
	 */
	function getDataObjectSet($class = 'DataObject', $write = false, $parent = null){
		$dos = new DataObjectSet();
		$count = 1;
		foreach($this->FieldSet() as $compositefield){
			$dataobject = new $class();
			if($write) $dataobject->write(); //get an id if necessary
			$dataobject->FieldName = $compositefield->Name();
			$fields = unserialize(serialize($compositefield->getChildren())); //deep copy so we're not changing the origional field
			foreach($fields as $field){
				$field->setName(substr($field->Name(),0,strpos($field->Name(),'_'))); //assumes underscores aren't used in a DB field name
				if($field->hasData()){
					$field->saveInto($dataobject);
					//echo "called saveInto on $dataobject->class $dataobject->ID from $field->class ".$field->Name();
				}elseif($field->isComposite()){
					$this->recursiveSaveInto($field,$dataobject); //save into composite sub-fields
				}
				if($write) $dataobject->write();
			}

			if($parent)
				$dataobject->{$parent->class."ID"} = $parent->ID;

			//call back
			if($this->callbackobj && $this->callbackfn)
				call_user_func(array($this->callbackobj,$this->callbackfn),$dataobject,$fields);

			$dos->push($dataobject);
		}
		return $dos;
	}

	/**
	 * Helper method for saving data that is in child fields of composite fields.
	 */
	function recursiveSaveInto($compositefield,&$dataobject){
		//$fields = unserialize(serialize($compositefield->getChildren())); //deep copy so we're not changing the origional field
		$fields = $compositefield->getChildren();
		foreach($fields as $field){
			$field->setName(substr($field->Name(),0,strpos($field->Name(),'_'))); //assumes underscores aren't used in a DB field name
			if($field->hasData()){
				$field->saveInto($dataobject);
			}elseif($field->isComposite()){
				$this->recursiveSaveInto($field,$dataobject); //save into composite subfields
			}
		}
	}

	//TODO: maybe override the setValue function that is used in form->loadDataFrom ???
	/**
	 *  Load data from dataobjectset capability
	 * **/
	function loadDataFrom(DataObjectSet $dos){

		$iterator = $dos->getIterator();

		if($this->groupcount < $dos->Count()){
			$this->setCount($dos->Count());
		}
		$this->generateFields();
		$count = 0;
		foreach($this->FieldSet() as $fieldgroup){

			if($iterator->current()){
				$count++;
				$data = $iterator->current()->toMap();
				foreach($data as $key => $value){
					$data[$key."_".$count] = $value; //TODO: make this a deep modification?
				}
				$fieldgroup->FieldSet()->setValues($data);
				$iterator->next();
			}
		}
	}

	/**
	 * Return a generated array of required fields base on field names. Eg: name will return name_1, name_2
	 */
	function getRequiredFields(array $fieldnames){
		$returnarray = array();
		for($i = 1; $i <= $this->groupcount ; $i++){
			foreach($fieldnames as $name){
				array_push($returnarray,$name.'_'.$i);
			}
		}
		return $returnarray;
	}

	function setChildren($children){
		parent::setChildren($children);
		$this->originalchildren = unserialize(serialize($this->children));
	}

}