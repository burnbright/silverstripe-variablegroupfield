(function($){
	
	jQuery(".VariableGroup").each(function(){
		
		var group = $(this);
		
		$(this).find(".addlink").click(function(){			
			jQuery.ajax({
				type: "GET",
				url: $(this).attr('href'),
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
					group.find('> .CompositeField:last').slideDown();					
				}
			});
			return false;
		});
	
		$(this).find(".removelink").click(function(){
			//if(group.find('.CompositeField').size() > 1){
				jQuery.ajax({
					type: "GET",
					url: $(this).attr('href'),
					dataType: "html",
					beforeSend: function(){
						group.find('div.loadingimage').show();
					},
					success: function(data){
						group.find('> .CompositeField:last').slideUp(600,function(){$(this).remove();});
						group.find('div.loadingimage').hide();
					}
				});
			//}
			return false;
		});
	
	});
	
	
})(jQuery);
