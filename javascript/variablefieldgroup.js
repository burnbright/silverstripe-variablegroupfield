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
					if(group.find('.CompositeField:last').size() > 0){
						group.find('.CompositeField:last').after($(data).hide());
					}else{
						group.prepend($(data).hide());
					}
					group.find('div.loadingimage').hide();
					group.find('.CompositeField:last').slideDown();
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
					success: function(data){
						group.find('.CompositeField:last').slideUp(600,function(){$(this).remove();});
					}
				});
			//}
			return false;
		});
	
	});
	
	
})(jQuery);
