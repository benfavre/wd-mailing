jQuery(document).ready(function($) {

	$('#sendDemande').live("click", triggerDemande);
	$('#progChange').live("click", toggleProg);
	$('div.layout[data-layout="type_horiz_spacer"]').addClass('hidecontent');
	$('div.layout[data-layout="type_space"]').addClass('hidecontent');

	elem = $('#acf_4fcfb3cee6d1a');
	statusval = $('#acf-mailing_status select').val();
	
	status_info = '<div href="#" id="mailingStatus" class="alert alert-info">\
					<strong>Statut : </strong>Programmé\
					</div>';

	send_div = '<div class="postbox-button">\
					<a href="#" class="btn btn-primary" id="sendDemande">Programmer l\'envoi</a>\
				</div>';

	switch(statusval)
    {
        case "1": elem.append(send_div);
        		  break;

        case "2": 
        		  //statusdiv.before('<div id="progDetails">').before(status_info);
        		  statusdiv = $('#acf-mailing_status');
        		  statusdiv.before('<a href="#" id="progChange" class="btn btn-primary"><i class="icon-refresh"></i>Changer la programmation</a>');
        		  $('#acf_4fcfb3cee6d1a .field').wrapAll('<div id="progDetails" />');
        		  $('#progDetails').append(send_div).hide();
        		  break;

        case "3": jQuery('#StatusEnvoye', elem).addClass('active');
        		  break;

        default:  jQuery('#StatusBrouillon', elem).addClass('active');
    }

});

jQuery(window).load(function() {
	jQuery('.field-textarea textarea').each(function() {
		elem = jQuery(this);
		SyntaxHighlight(elem);
	})
      
});


function SyntaxHighlight(elem) {
	CodeMirror.fromTextArea(elem[0], 
		{
		lineNumbers: true,
        matchBrackets: true,
        mode: "application/x-httpd-php",
        indentUnit: 4,
        indentWithTabs: true,
        continuousScanning: 500,
        enterMode: "keep",
        tabMode: "shift"
    	}
	);
}


function toggleProg() {
	btn = jQuery(this);
	if(btn.hasClass('open')){
		btn.removeClass('open');
		jQuery('#progDetails').hide();
		jQuery('#progChange').removeClass('btn-danger').addClass('btn-primary').text('Changer la programmation');
	} else {
		btn.addClass('open');
		jQuery('#progDetails').show();
		jQuery('#progChange').removeClass('btn-primary').addClass('btn-danger').text('Annuler le changement');
	}
}

function triggerDemande() {
	theinput = jQuery('#acf-schedule_date input');
	date = theinput.val();


	if(date != '') {
	  theinput.removeClass('error');
	  programmerEnvoi();
	} else {
	  theinput.addClass('error');
	  alert('Veuillez choisir une date avant de confirmer l\'envoi.');
	}

	return false;
}

function programmerEnvoi() {
	jQuery('.inside', elem).prepend(status_info);
	jQuery.ajax({
	  url: ajaxurl,
	  type: 'POST',
	  data:{
	       'action':'wdnl_notifySend',
	       'postid': acf.post_id,
	       'date': jQuery('#acf-schedule_date input').val(),
	       'time': jQuery('#acf-schedule_time input').val()
	       },
	  dataType: 'JSON',
	  success:function(data){
			// our handler function will go here
			// this part is very important!
			// it's what happens with the JSON data
			// after it is fetched via AJAX!
	        jQuery('#acf-mailing_status select').val('2');
	        alert('Envoi programmé');
	        jQuery('#publishing-action input#publish').trigger('click');
	                     },
	  error: function(errorThrown){
	  	console.log(errorThrown);
	       alert('Erreur Veuillez réessayer');
	  }

	});

}


