# Variable Group Field
    
## Maintainer Contact   

 * Jeremy Shipman (Jedateach, jeremy@burnbright.co.nz)
 
## About

The VariableGroupField allows specifying a group of fields once, and then provides
the means to duplicate that FieldSet for multiple entries (similar to TableField).
It also provides the means to automatically save it's subfields into a new DataObject 
that gets connected via a has_many relationship to the DataObject that was saved into.

When the VariableFieldGroup constructor is called, it duplicates it's child fields X amount of times. 
I have functions for increasing and decreasing X. These can be called via ajax, and return the new extra fields.
The constructor also renames all the fields in the child groups to names like Title_1,Author_1.

The VariableGroupField's save into method searches for a has_many field on the targeted DataObject,
and saves a new instance of the has_many DataObject type, and links that new DataObject to the targeted one.

As an alternative to TableField, the Variable Group Field puts more customisation control in the developer's hands. 
It might however be more visually clunky though when there are many entries involved.

## Future plans

 * make it possible to remove specific groups
 * include checking for the many_many relationship in the saveInto method
 * provide capability to loadDataFrom() a DataObjectSet. (would be nice if it somehow worked seamlessly with Form)
 * ensure the add/remove functionality works without javascript
 * choose which fields are automatically duplicated when 'add' is clicked
 * This idea could be scaled back to encompass has_one relationships, so that a group of fields can be saved into an associated DataObject.
 * Add min / maximum groups count and incorporate this into validation / javascript.

## Known flaws

 * you have to regenerate fields each time you make modifications
 * fields can still clash with other form fields (eg if you have a FirstName field in the rest of the form, and also in the vgf)
 * reliant on javascript, however you can still add groups etc..but they won't retian their data
 * does not work with all field types

This field assumes it is acceptable to modify fields during data loading.