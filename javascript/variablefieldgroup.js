(function($){
	
	$.fn.variablegroupfield = function(options){
		
		var vgf = this;
		var defaults = {};
		
		var options = $.extend(defaults, options);
		
		/**
		 * Sets up all the events for each variable group field
		 */	
		this.initialize = function() {
			
			this.each(function(){
				
				var group = $(this);
				
				//make sure we only ever initialize the group once
				if(group.data('init') == true){
					return;
				}else{
					group.data('init',true);
				}
				
				group.find(".addlink").click(function(){
					addsetto(group);
					return false; //don't let the link execute
				});
				
				group.find(".removelink").click(function(){
					removesetfrom(group);
					return false; //don't let the link execute
				});
				
				//hide remove link if there are no composite sets
				if(group.find('> .CompositeField').size() <= 0){
					group.find(".removelink").hide();
				}
				
			});
			
			return this;
	    };
			
	    /**
	     * Public functions
	     */
	    //add a set to all groups
		this.addset = function(){
			vgf.each(function(){
				addsetto($(this));
			});
		};
		
		//remove a set from groups
		this.removeset = function(){
			vgf.each(function(){
				removesetfrom($(this));
			});
		};
		
		//remove all sets from all groups
		this.removeall = function(){
			vgf.each(function(){
				removesetfrom($(this),-1);
			});
		};
		
		/***
		 * Private functions
		 */
		
		var addsetto = function(group){
			//TODO: trigger event for external manipulation
			jQuery.ajax({
				type: "GET",
				url: group.find('.addlink').attr('href'), //get place to send request
				dataType: "html",
				beforeSend: function(){
					group.find('div.loadingimage').show();
				},
				success: function(data){
					var size = group.find('> .CompositeField').size();
					
					if(group.find('> .CompositeField:last').length){
						
						group.find('> .CompositeField:last').after($(data).hide()); //insert new fields
						
						//copy values over to new field
						group.find('> .CompositeField:eq('+(size-1)+')').find('.duplicateme:input').each(function(){
							var name = $(this).attr('name')
							var newname = name.substring(0,name.indexOf('_'));
							var value = $(this).val();
							
							//duplicate checkbox values
							if($(this).attr('checked')){
								group.find('> .CompositeField:last').find('.'+newname+':input').attr('checked',true);
							}else{
								group.find('> .CompositeField:last').find('.'+newname+':input').val(value);
							}
							//TODO: make this work with radio buttons & dropdowns etc
						});
						
					}else{
						group.prepend($(data).hide());
					}
					group.find('div.loadingimage').hide();
					group.trigger('addedSucccess');
					group.find('> .CompositeField:last').slideDown();
					
					group.find(".removelink").show();
				}
			});
		};
		
		/*
		 * Remove set from group
		 * group - the group to remove from
		 * count - the number of sets to remove
		 * 
		 */
		var removesetfrom = function(group, count){
			//TODO: trigger event for external manipulation
			
			var removeurl = group.find('.removelink').attr('href');
			
			var countstr = ':last';
			
			if(count == -1){
				countstr = '';
				removeurl += 'all'; //modify the url
			}
				
			jQuery.ajax({
				type: "GET",
				url: removeurl,
				dataType: "html",
				beforeSend: function(){
					group.find('div.loadingimage').show();
				},
				success: function(data){
					group.find('> .CompositeField'+countstr).slideUp(600,function(){
						$(this).remove();
						if(group.find('> .CompositeField').size() < 1){
							group.find(".removelink").hide();
						}
					
					});
					group.find('div.loadingimage').hide();
				}
			});
			
		};
		
		return this.initialize();	
	}

	var vgf = $(".VariableGroup").variablegroupfield(); //TODO: replace with generated custom code for specific options
	
})(jQuery);
