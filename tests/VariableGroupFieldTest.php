<?php

class VariableGroupFieldTest extends FunctionalTest{

	//make sure all fields are included when adding
	//use various types of fields

	/**
	 * Call every function to see if anything breaks.
	 */
	function testAllFunctions(){

		$_REQUEST['ajax'] = true; //pretend this is an ajax request, so redirects aren't attempted

		//$controller = new FormTest_ControllerWithSecurityToken();

		//$form = new Form($controller,"Form",new FieldSet( //form needed for link functions
			$vgf = $this->createGroup();
		//),new FieldSet());

		//$vgf->AddLink(); // requires form to work
		$vgf->clearCount();
		$vgf->disableClearOnSave();
		//$vgf->FieldHolder(); //requires form to work
		$vgf->generateFieldGroup(1);
		$vgf->generateFields();
		$vgf->getDataObjectSet();
		$vgf->getRequiredFields(array("Name","Email"));
		$vgf->hasData();
		$vgf->insertBefore(new TextField("Gender"),"Occupation");
		$vgf->loadDataFrom(new DataObjectSet());
		//$vgf->modifyComposite($i); //internal helper method
		$vgf->push(new CheckboxField("Checked"));
		//$vgf->recursiveSaveInto($compositefield, $dataobject) //internal helper method
		$vgf->remove();
		$vgf->removeAll();
		$vgf->removeByName("Name");
		$vgf->removeField("Occupation");
		//$vgf->RemoveLink(); // requires form to work
		$vgf->saveInto(new DataObject());
		$vgf->setAddRemoveLabels("ADD","REMOVE");
		$vgf->setCallback(new DataObject(),"getCMSActions");
		$vgf->setCount(5);
		$vgf->setFieldsToDuplicate(array("Name"));
		$vgf->setLoadingImageURL("");
		$vgf->setSinglularItem("DataObject");
		$vgf->writeOnSave(true);

		$vgf->generateFields();
	}

	function testAddingRecord(){
		$vgf = $this->createGroup();
		$_REQUEST['ajax'] = true;
		$vgf->add();
		$vgf->generateFields(); //required after add
		$this->assertEquals($vgf->FieldSet()->Count(),3);
	}

	function testRemovingRecord(){
		$vgf = $this->createGroup();
		$_REQUEST['ajax'] = true;
		$vgf->remove();
		$vgf->generateFields(); //required after remove
		$this->assertEquals($vgf->FieldSet()->Count(),1);
	}

	function testRemoveAllRecords(){
		$vgf = $this->createGroup();
		$_REQUEST['ajax'] = true;
		$vgf->removeall();
		$vgf->generateFields(); //required after remove
		$this->assertEquals($vgf->FieldSet()->Count(),1); //count returns to init count, which is currently 1
	}

	function testModifyParentFields(){
		//add and remove fields
		//add a field with the same name to a different part of the form
		$fieldset = new FieldSet(
			$vgf = $this->createGroup(),
			new TextField('Occupation')
		);

		$fieldset->insertBefore(new TextField("Name"),"Records"); //field with same name as one of the fields in the variable group
		$fieldset->removeByName('Email'); //attempting to remove field from parent fieldset //TODO: should this be allowed??

		$fields = $vgf->FieldSet();
		$this->assertEquals($fields->Count(),2); //2 groups of fields, as defined below
		foreach($fields as $field){
			$this->assertEquals($field->FieldSet()->Count(),4); //4 fields per group
		}
	}
/*
	function testModifyGroupFields(){
		//TODO
	}

	function testSaveInto(){
		//TODO: finish me
	}

	function testFieldTypes(){

	}
*/
	function testDataLoading(){

		$fields = new FieldSet(
			$this->createGroup()
		);

		$form = new Form(new Controller(),'Form',$fields,new FieldSet());

		$data = array(
			'Name_1' => 'Joe Bloggs',
			'Email_1' => 'joe.bloggs@example.net',
			'Occupation_1' => 'developer',
			'Age_1' => 20
		);

		$form->loadDataFrom($data);

		$vgf = $form->Fields()->fieldByName("Records");
		$record = $vgf->FieldSet()->First();
		$this->assertEquals($record->fieldByName('Name_1')->Value(),'Joe Bloggs');
		$this->assertEquals($record->fieldByName('Email_1')->Value(),'joe.bloggs@example.net');
		$this->assertEquals($record->fieldByName('Occupation_1')->Value(),'developer');
		$this->assertEquals($record->fieldByName('Age_1')->Value(),20);

	}

	protected function createGroup(){
		$vgf = new VariableGroupField("Records",2,
				new TextField("Name"),
				new EmailField("Email"),
				new TextField("Occupation")
		);
		$vgf->push(new NumericField("Age"));

		//TODO: is it possible that this is not needed?
		$vgf->generateFields(); //needed to properly introduce Age

		return $vgf;
	}

}