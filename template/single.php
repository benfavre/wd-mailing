<!DOCTYPE html>
<html dir="ltr" lang="fr-FR">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<title><?php wp_title(); ?></title>
	<?php wp_head(); ?>

	<?php 
	global $post;
	$url   = get_option('home');
	$title = get_bloginfo('name');
	$theme = get_field('mailing_theme');

	$css = get_field('theme_css', $theme->ID);
	$html = get_field('theme_html', $theme->ID);
	?>
</head>
<body>	
	<div style="display:none">
		<input type="hidden" id="postID" value="<?php echo $post->ID; ?>">
	</div>
	<div class="mailing_info">
		<h1>
			Informations du mailing <span><?php edit_post_link('Modifier le mailing', '- '); ?></span>
		</h1>
		<div class="mailing_config">
			<div class="row">
				<?php 
					$sujet = get_field('mailing_sujet'); 
					if($sujet) {
						?>
						<strong>Sujet : </strong><?php echo $sujet; ?>
						<?php
					} else {
						?>
						<div class="alert" style="margin-bottom:0px;">
							N'oubliez pas de définir un sujet
						</div>
					<?php
					}
				?>
			</div>
			<div class="row testmail">
				<strong>Email de test : </strong>
				<input class="emailfield" id="EmailRecipient" type="text" value="" placeholder="email@domain.com">
				<button class="btn" id="sendTestEmail">Envoyer un mail de test</button>
			</div>
		</div>
		<ul>
			<li><a href="?display=preview" class="<?php if(empty($_GET["display"]) || $_GET["display"] == "preview") { echo "current"; } ?>">Aperçu</a></li>
			<li><a href="?display=html"# class="<?php if(isset($_GET["display"]) && $_GET["display"] == "html") { echo "current"; } ?>">Version HTML</a></li>
			<li><a href="?display=text"# class="<?php if(isset($_GET["display"]) && $_GET["display"] == "text") { echo "current"; } ?>">Version TEXTE</a></li>
		</ul>
	</div>

<?php 
if(isset($_GET["display"]) && (($_GET["display"] == "html") || ($_GET["display"] == "text"))) 
	$display = 'html-text';

if($display == 'html-text') {
	
	echo '<textarea style="margin:10px;padding:10px;border:1px solid #ccc;width:99%;min-height:600px;display:block;overflow:auto;webkit-box-sizing: border-box;-moz-box-sizing: border-box;box-sizing: border-box;">';
	
	$html = eval_php(do_shortcode($html));
	$cleanup = new WDNL_Emogrifier(); 
	$cleanup->addUnprocessableHTMLTag('.unsubscribe');
	$cleanup->setHTML($html);
	$cleanup->setCSS($css);
	$mailready = $cleanup->emogrify();

	if($_GET["display"] == "html") {
		echo $mailready;
	} 
	else if($_GET["display"] == "text") {
		$h2t =& new html2text($mailready);
		echo $h2t->get_text();
	}

	echo '</textarea>';

} else {

	?>
	<style type="text/css">
		<?php echo $css; ?>
	</style>
	<?php

		echo eval_php(do_shortcode($html));
}

?>

<script type="text/javascript">
jQuery(document).ready(function($) {
	$('#sendTestEmail').live("click", triggerSend);
});

function triggerSend() {
	recipient = jQuery('#EmailRecipient').val();
	if(IsEmail(recipient)) {
	  jQuery('#EmailRecipient').removeClass('error');
	  sendEmail(recipient);
	} else {
	  jQuery('#EmailRecipient').addClass('error');
	  alert('Veuillez saisir une adresse email valide.');
	}
}

function sendEmail(recipient) {

	jQuery.ajax({
	  url: '<?php echo get_bloginfo("wpurl"); ?>/wp-admin/admin-ajax.php',
	  type: 'POST',
	  data:{
	       'action':'wdnl-testSend',
	       'postid': jQuery('#postID').val(),
	       'recipient': recipient
	       },
	  dataType: 'JSON',
	  success:function(data){
			// our handler function will go here
			// this part is very important!
			// it's what happens with the JSON data
			// after it is fetched via AJAX!
	        console.log(data);
	        alert('Envoyé à l\'adresse : ' + recipient);
	                     },
	  error: function(errorThrown){
	       alert('Erreur Veuillez réessayer');
	  }

	});
}


function IsEmail(email) {
	var regex = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
	return regex.test(email);
}
</script>
  	
<?php wp_footer(); ?>
</body>
</html>







