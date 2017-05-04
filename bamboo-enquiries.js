/************************************************************************************************************/

(function() {
	'use strict';

/************************************************************************************************************/

	jQuery(document).ready(bambooEnquiriesInit);
	jQuery(window).load(bambooEnquiriesCheckSent);

/************************************************************************************************************/

	function bambooEnquiriesInit() {

		if( bambooEnquiriesSent() ) {

			jQuery('.bamboo_enquiry').empty();
			jQuery('.bamboo_enquiry').append('<div class="bamboo_enquiry_confirm"><h4>Thank you for your enquiry</h4><h4>We will get back to you as soon as possible</h4></div>');
			return;

		}

		jQuery('.bamboo_enquiry.auto_labels input[type="text"], .bamboo_enquiry.auto_labels input[type="email"], .bamboo_enquiry.auto_labels input[type="tel"], .bamboo_enquiry.auto_labels input[type="number"], .bamboo_enquiry.auto_labels textarea').each(function(){

			var input  = jQuery(this);
			var label  = jQuery(input.prevAll('label')[0]);
			var prompt = label.html();

			input.val(prompt);
			label.hide();

			input.blur(function(){
				if(input.val()==='') {
					input.val(prompt);
				}
			});

			input.focus(function(){
				if(input.val()===prompt) {
					input.val('');
					input.removeClass('error');
				}
			});

		});

		jQuery('.bamboo_enquiry').each(function(){
			jQuery(this).submit(function(){

				jQuery('.bamboo_enquiry.auto_labels input[type="text"], .bamboo_enquiry.auto_labels input[type="email"], .bamboo_enquiry.auto_labels input[type="tel"], .bamboo_enquiry.auto_labels input[type="number"], .bamboo_enquiry.auto_labels textarea').each(function(){

					var input = jQuery(this);
					var label = input.prev();
					var text = input.val();
					var prompt = label.html();
					if (text===prompt) {
						input.val('');
						text = '';
					}
				});

				jQuery('.bamboo_enquiry input[type="text"], .bamboo_enquiry input[type="email"], .bamboo_enquiry input[type="tel"], .bamboo_enquiry input[type="number"], .bamboo_enquiry textarea').each(function(){
					var input = jQuery(this);
					var label = input.siblings('label[for="' + input.attr('name') + '"]');
					if(0<label.length) {
						var text = input.val();
						var prompt = label.html();
						var promptLastChar = prompt.substr(prompt.length-1);
						if('*'===promptLastChar) {
							if(''===text) {
								input.addClass('error');
							} else {
								input.removeClass('error');
							}
						}
					}
				});

				if(jQuery('.bamboo_enquiry .error').length>0) {
					return false;
				}

				return true;
			});
		});

	}

/************************************************************************************************************/

	function bambooEnquiriesCheckSent() {

		if( bambooEnquiriesSent() ) {
			jQuery.scrollTo( '.bamboo_enquiry', 700, { offset:-100, easing:'easeOutQuart' } );
		}

	}

/************************************************************************************************************/

	function bambooEnquiriesSent() {

		var sent = false;
		var qs = bambooEnquiriesQueryString();
		for (var index = 0; index < qs.length; ++index) {
    		if( 'bamboo_enquiry_sent'===qs[index] ) {
    			sent = true;
    		}
    	}

    	return sent;

	};


/************************************************************************************************************/

     function bambooEnquiriesQueryString() {
         var vars = [], hash;

         var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
         for(var i = 0; i < hashes.length; i++)
         {
             hash = hashes[i].split('=');
             vars.push(hash[0]);
             vars[hash[0]] = hash[1];
         }

         return vars;

     }


/************************************************************************************************************/

})();

/************************************************************************************************************/
