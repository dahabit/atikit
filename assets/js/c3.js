/*
		 * New AJAX Handler written for GET and POST methods
		 * Copyright 2012 Core 3 Networks, Inc. 
		 * If you steal this, and you probably will because it's awesome, just mention @core3net or like me on facebook www.facebook.com/core3net
		 * I say this, because it's my first real Javascript routine and I'm proud of it :)
		 * 
		 * Get Example: <a id='3' href='/get/' class='get'> 
		 * 	 
		 * 
		 * POST Example: <a id='{formname}' class='post'> 
		 *  This will post the form with id formname
		 *  
		 *  The result is going to be the bulk of this. Should return the following statuses
		 *  
		 *  status: [success, error]
		 *  gtitle: [growltitle] (if null no growl)
		 *  gbody : [growlbody]
		 *  one   : [one line status text by default this will be gbody] 
		 *  action : 
		 *  			reload : url
		 *  			fade : null // Just fade the modal out, and reset the form if post.
		 *  			prepend : element (which element are we prepending to), content (content to prepend w/ animation)
		 *  			default : error, do nothing but show status message and re-enable submit button
		 *  
		 * 	modal : modalid if it's a modal. 
		 * 	button : need the button class to re-enable if we need to.
		 *  form : need the form id to manipulate on callback
		 *  hide (opt) - Hide an element on callback?
		 */

	$container = $('#notify-container').notify();	
	function create( template, vars, opts )
	{
		return $container.notify('create', template, vars, opts);
	}
			
jQuery(document).ready(function ($) {
	
	 $("[rel=popover-hover]")
     .popover({
     html: true,
     trigger: 'hover',
     delay: { show: 100, hide: 1500 }
});
	
	 $("[rel=tooltip]").tooltip({
	     trigger: 'hover',
	     animation: true  });
	
	
function c3Responder(data)
			{
	 if (data.action != 'reload')
       {
		 create('default', { title: data.gtitle, text: data.gbody});	 
		     
        } // I mean.. don't show a growl for like 0.5 seconds for no reason. 
              switch (data.action)
              {
                   case 'reload' : 
                                    if (data.modal) 
                                    	$(data.modal).modal('hide');
                                    	window.location.assign(data.url);
                   break;
                   
                   case 'waitload' : 
                	   if (data.modal) 
                       	$(data.modal).modal('hide');
	   					var microseconds = 400000;
	   					var start = new Date().getTime();  
	   					while (new Date() < (start + microseconds/1000));  
	   					window.location.assign(data.url);
	   				break;
  
                   
                   case 'waitreload' : 
				                	   if (data.modal) 
				                       	$(data.modal).modal('hide');
                	   					var microseconds = 400000;
                	   					var start = new Date().getTime();  
                	   					while (new Date() < (start + microseconds/1000));  
                	   					window.location.reload();
                	   				break;
                  
                   case 'inline' : $(data.element).html(data.content).fadeIn(500);
				                   if (data.modal && !data.nofade) 
				                   	$(data.modal).modal('hide');
				                   if (data.restore)
				                	   $(data.button).html(data.oldval);
                   break;
                   
                   case 'fade' :   
                	   				
                	   				$(data.modal).find('.modal-body').html(data.oldcontent);             
                	   				$(data.button).html(data.oldval);
                                    $(data.button).removeAttr('disabled');
	                                if (data.form)
	                                	$(data.form)[0].reset();
	                                if (data.modal) 
                   						$(data.modal).modal('hide');
                   
	               break;
                   
                   case 'fadesource' : // This fades the source element calling the action. Like a delete button that you don't want pressed again.
                	   				$(data.button).fadeOut('slow');
	               break;
	               
                   case 'reassignsource' : //This fades back in the result you want to see.
               						$(data.button).fadeOut('slow', function () 
               						{
               							$(data.button).html(data.elementval);
               							$(data.button).fadeIn('slow');
               							
               						});
               						
               						
               					
                	   break;
	               
                   case 'prepend' :   if (data.modal) 
  										{
					                	   $(data.button).html(data.oldval);
					  						$(data.button).removeAttr('disabled');
					  						$(data.modal).find('.modal-body').html(data.oldcontent);
						   	   				$(data.modal).modal('hide');
					  					}
                   
                                       $(data.content).hide().prependTo(data.element).slideDown('slow');
                                        
                   break;
                   
                   case 'append' :   if (data.modal) 
										{
                	   						$(data.button).html(data.oldval);
                	   						$(data.button).removeAttr('disabled');
                	   						$(data.modal).find('.modal-body').html(data.oldcontent);
						   	   				$(data.modal).modal('hide');
										}
						              $(data.content).hide().appendTo(data.element).slideDown('slow');
                      break;
                   
                   default : 
                	   	var fid = '.' + data.fid + '_msg';
                   if (data.modal) 
					{
 						$(data.button).html(data.oldval);
 						$(data.button).removeAttr('disabled');
 						$(data.button).fadeIn();
 						$(data.modal).find('.modal-body').html(data.oldcontent);
					}
                  			
                   
                           $(fid).html("<p><div class='alert alert-" + data.status + "'> <a class='close' data-dismiss='alert' href='#'>&times;</a><h4 class='alert-heading'>" + data.gtitle + "</h4>" + data.gbody + "</div></p>");
                           $(data.button).html(data.oldval);
                           $(data.button).removeAttr('disabled');
    					   $(data.button).fadeIn();

                           break;
              } // switch
	
	            if (data.hide)
	                $(data.hide).hide();

	            if (data.ev)
	            	{
	            	eval(data.ev);
	            	
	            	}
	            $(data.button).html(data.oldval);
                $(data.button).removeAttr('disabled');
				$(data.button).fadeIn();   
				
			} //c3responder


			$('.get').livequery('click', function(event) { 
					var xid = $(this).attr('id');
					var href = $(this).attr('href');
					var button = $(this);
					event.preventDefault();
					var stext = button.attr('data-title');
					var oldval = $(button).html();
					if (stext == null)
						stext = 'Processing...';
					$(button).html('<i class="icon-time"></i> ' + stext);
				$.ajax({url: href, datatype: 'json', success: function (data)
    	    	{
					data.button = button;
					data.oldval = oldval;
    	        	c3Responder(data);
				}}); // success
			}); // lq
			

			
			
			$('.post').livequery('click', function(event) 
					{ 
						event.preventDefault();
						var button = $(this);
						var postvar = button.attr('rel');
						var stext = button.attr('data-title');
						if (button.attr('data-content'))
							{
								var form = button.attr('data-content');
								form = $(form);
							}
							else
								var form = $(this).parent().parent().parent();
						var id = form.attr('id');
						var action = form.attr('action');
						if (stext == null)
							stext = 'Processing...';
						var bid = button.attr('id'); 
						$(button).attr('disabled', 'disabled');
				    	var oldval = $(button).html();
						$(button).html('<i class="icon-time"></i> ' + stext);
				    	$(button).fadeOut();
						$("#" + id).append("<input type='hidden' name='" + postvar + "' value='" + bid + "'>");
				    	$.ajax({type: 'post', url: action, data: $(form).serialize(), datatype: 'json', success: function (data)
			    	    {
				    		data.button = button;
			    	        data.oldval = oldval;
			    	        data.fid = id;
			    	        c3Responder(data);
			    	    }}); // success
						}); // lq


			
			
			
			
			$('.mpost').livequery('click', function(event) 
			{ 
				// For Modal forms we have to delve into the modal from the button pressed.
				event.preventDefault();
				var button = $(this);
				
				if (button.attr('data-content'))
				{
					var form = button.attr('data-content');
					form = $(form);
				}
				else
					var form = $(this).parent().parent().parent().find('.modal-body').find('form');
				var modal = $(this).parent().parent().parent();
				
				var id = form.attr('id');
				var postvar = button.attr('rel');
				var bid = button.attr('id');
				$("#" + id).append("<input type='hidden' name='" + postvar + "' value='" + bid + "'>");
				fdata = $(form).serialize();
				var action = form.attr('action');
				var stext = button.attr('data-title');
				if (stext == null)
					stext = 'Processing...';
				$(button).attr('disabled', 'disabled');
		    	var oldval = $(button).html();
		    	var oldcontent = $(modal).find('.modal-body').html();
		    	$(modal).find('.modal-body').html("<br/><br/><div class='well'><center><img src='/assets/img/loadsmall.gif'> " + stext + "</center>");
				$(button).html('<i class="icon-time"></i> ' + stext);
		    	$(button).fadeOut();
		    	
				$.ajax({type: 'post', url: action, data: fdata, datatype: 'json', success: function (data)
	    	    {
					
		    		data.modal = modal;
	    	        data.button = button;
	    	        data.oldval = oldval;
	    	        data.oldcontent = oldcontent;
	    	        data.fid = id;
	    	        c3Responder(data);
	    	    }}); // success
				}); // lq

			
			$('.mget').livequery('click', function(event) 
					{ 
						// For Modal forms we have to delve into the modal from the button pressed.
						event.preventDefault();
						var button = $(this);
						var modal = $(this).parent().parent();
						var xid = $(button).attr('id');
						var url = $(button).attr('href');
						if (xid)
							var xurl = url + xid + "/";
						else
							var xurl = url;
						
						$(button).attr('disabled', 'disabled');
						
						var stext = button.attr('data-title');
						if (stext == null)
							stext = 'Processing...';
						
				    	var oldval = $(button).html();
						$(button).html('<i class="icon-time"></i> ' + stext);
				    	$(button).fadeOut();

						$.ajax({url: xurl, data: {}, datatype: 'json', success: function (data)
			    	    {
			    	        data.modal = modal;
			    	        data.button = button;
			    	        data.oldval = oldval;
			    	        c3Responder(data);
			    	    }}); // success
						}); // lq

			// Create confirm with 

			$(".mjax").click(function(e)
			{
				e.preventDefault();
				target = $(this).attr('data-target');
				url = 	$(this).attr('href');
				$(target).html("<center><br/><br/><br/><br/><img src='/assets/img/loader.gif'><br/><br/><br/><br/></center>");
				$(target).load(url);
				$(target).modal({show: true , backdrop : true , keyboard: true});
				
			});
	
			$('.msgtoggle').click(function(e)
					{
				$.get('/notify/clear/', function(data) {});
					});
			
			
			$(".liveload").livequery('click', function (event)
					{
						event.preventDefault();
						var button = $(this);
						$(button).hide();
						var url = $(button).attr('href');
						var target = $(button).attr('data-target');
						$(target).html("<center><img src='/assets/img/loader.gif'></center>");
						$(target).load(url);
					});
			
			
			function checkMessages()
			{
				// Every 10 seconds check re-populate the notifications.
				$.ajax({url: '/notify/' , datatype: 'json', success: function (data)
	    	    	{
						$('.notify-count').html(data.count);
						$('.msg-list').html(data.content);
						if (data.gtitle)
							create('default', { title: data.gtitle, text: data.gbody});	 
						
	    	        	
	    	    	}}); // success from initial poll
			}
			checkMessages();
			setInterval(function(){checkMessages();}, 10000);
										
			
			
			
});

			
			
			