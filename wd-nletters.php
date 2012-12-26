<?php
/*
Plugin Name: WD Newsletter plugin
Plugin URI: http://www.webdesign29.net/
Description: Visually create emails to send with the Mailpress Plugin
Version: 1.5
Author: Benjamin Favre
Author URI: http://www.webdesign29.net/
Copyright 2012  Benjamin Favre  (email : ben@webdesign29.net)
*/
define('WDNLETTER_URL', plugin_dir_url(__FILE__));
define('WDNLETTER_DIR', plugin_dir_path(__FILE__));
define('WDNLETTER_VERSION', '1.5');


add_action('init', 'github_plugin_updater_test_init');
function github_plugin_updater_test_init() {
  include_once (WDNLETTER_DIR . '/_github-updater/updater.php');

  define('WP_GITHUB_FORCE_UPDATE', true);

  if (is_admin()) { // note the use of is_admin() to double check that this is happening in the admin

    $config = array(
      'slug' => plugin_basename(__FILE__),
      'proper_folder_name' => 'wd-mailing',
      'api_url' => 'https://api.github.com/repos/benfavre/wd-mailing',
      'raw_url' => 'https://raw.github.com/benfavre/wd-mailing/master',
      'github_url' => 'https://github.com/benfavre/wd-mailing',
      'zip_url' => 'https://github.com/benfavre/wd-mailing/zipball/master',
      'sslverify' => true,
      'requires' => '3.0',
      'tested' => '3.5',
      'readme' => 'README.md',
      'access_token' => '',
    );
    new WPGitHubUpdater($config);
  }
}

include_once( WDNLETTER_DIR . '/inc/emogrifier.php');
include_once( WDNLETTER_DIR . '/inc/class.html2text.php');
include_once( WDNLETTER_DIR . '/inc/various.php');

/*  Register Single template for CPT */
function get_custom_post_type_template($single_template) {
  global $post;
  if ($post->post_type == 'mailing') {
    wp_register_style('wdnletter-main', WDNLETTER_URL . 'css/wdnletter.css');  
    wp_enqueue_style('wdnletter-main');
    $single_template = WDNLETTER_DIR . '/template/single.php';
  }
  return $single_template;
}
add_filter( "single_template", "get_custom_post_type_template" ) ;


add_action( 'admin_print_scripts-post-new.php', 'mailing_admin_script', 11 );
add_action( 'admin_print_scripts-post.php', 'mailing_admin_script', 11 );
function mailing_admin_script() {
    global $post_type;
    if( ('mailing' == $post_type ) || ('mailing_theme' == $post_type ) || ('mailing_zone' == $post_type ) ) {

      wp_enqueue_script('mailing-jshighlight', WDNLETTER_URL . 'js/codemirror.js'); 
      wp_enqueue_script('mailing-jshighlight-xml', WDNLETTER_URL . 'js/mode/xml/xml.js');
      wp_enqueue_script('mailing-jshighlight-js', WDNLETTER_URL . 'js/mode/javascript/javascript.js');
      wp_enqueue_script('mailing-jshighlight-css', WDNLETTER_URL . 'js/mode/css/css.js');
      wp_enqueue_script('mailing-jshighlight-clike', WDNLETTER_URL . 'js/mode/clike/clike.js'); 
      wp_enqueue_script('mailing-jshighlight-main', WDNLETTER_URL . 'js/mode/php/php.js'); 
      wp_enqueue_script('mailing-admin-script', WDNLETTER_URL . 'js/cptadmin-mailing.js');
      
      wp_register_style('mailing-csshighlight', WDNLETTER_URL . 'js/codemirror.css');  
      wp_enqueue_style('mailing-csshighlight');
      wp_register_style('mailing-admin-style', WDNLETTER_URL . 'css/cptadmin-mailing.css');  
      wp_enqueue_style('mailing-admin-style');
    }
}


// Ajax Handle Program notifications
add_action('wp_ajax_nopriv_wdnl_notifySend', 'mailing_sendNotification');
add_action('wp_ajax_wdnl_notifySend', 'mailing_sendNotification');
function mailing_sendNotification (){
     // now we'll write what the server should do with our request here
  $domain = parse_url(get_bloginfo('wpurl'));
  $domain = ereg_replace('www\.','', $domain['host']);

  $headers = 'From: ' . get_bloginfo('name') . ' <test@' . $domain . '>' . "\r\n";
  $subject = 'Mailing | Site: ' . get_bloginfo('name') . ' | Date: ' . $_POST['date'];
  $message = 'Site : ' . get_bloginfo('name') . "<br>" . 'URL du mailing : ' . get_bloginfo('wpurl') . '/wp-admin/post.php?post=' . $_POST['postid'] . '&action=edit <br>Date : ' . $_POST['date'] . '<br>Heure : ' . $_POST['time'];

  add_filter('wp_mail_content_type',create_function('', 'return "text/html";'));
  $status = wp_mail('ben@webdesign29.net', $subject, $message, $headers);
  echo json_encode($status);
  die; 
}


// Ajax Handle Tests
add_action('wp_ajax_nopriv_wdnl-testSend', 'mailing_sendTest');
add_action('wp_ajax_wdnl-testSend', 'mailing_sendTest');
function mailing_sendTest (){
  global $post; 
  $post->ID = $_POST['postid'];
  $theme = get_field('mailing_theme', $post->ID);
  $subject = get_field('mailing_sujet', $post->ID);
  $css = get_field('theme_css', $theme->ID);
  $html = get_field('theme_html', $theme->ID);

  $html = eval_php(do_shortcode($html));
  $cleanup = new WDNL_Emogrifier(); 
  $cleanup->addUnprocessableHTMLTag('.unsubscribe');
  $cleanup->setHTML($html);
  $cleanup->setCSS($css);
  $mailready = $cleanup->emogrify();

     // now we'll write what the server should do with our request here
  $headers = 'From: ' . get_field('mailing_emetteur', $theme->ID) . ' <' . get_field('mailing_envoi', $theme->ID) . '>' . "\r\n";
  $message = $mailready;

  add_filter('wp_mail_content_type',create_function('', 'return "text/html";'));
  $status = wp_mail($_POST['recipient'], $subject, $message, $headers);
  echo json_encode($status);
  die; 
}

/**
* Retrieve a post given its title.
*
* @uses $wpdb
*
* @param string $post_title Page title
* @param string $post_type post type ('post','page','any custom type')
* @param string $output Optional. Output type. OBJECT, ARRAY_N, or ARRAY_A.
* @return mixed
*/
function get_post_by_title($page_title, $post_type ='post' , $output = OBJECT) {
    global $wpdb;
        $post = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type= %s", $page_title, $post_type));
        if ( $post )
            return get_post($post, $output);

    return null;
}

function eval_php($content)
{
  // to be compatible with older PHP4 installations
  // don't use fancy ob_XXX shortcut functions
  ob_start();
  eval("?>$content<?php ");
  $output = ob_get_contents();
  ob_end_clean();
  return $output;
}


// WDNLCONTENT Shortcode
function process_shortcode($atts){
  global $post; 
  $thePostID = $post->ID;
  if(is_null($thePostID)) {
    return 'no id';
  }
  extract(shortcode_atts(array(
  'zone' => false,
  'content' => false,
  'value' => false
  ), $atts));
  ob_start();

  if ($zone != false) {
    $zone_object =(get_post_by_title($zone, 'mailing_zone'));
    if (!is_null($zone_object->ID)) {
      echo do_shortcode(get_field( "content" , $zone_object->ID));
    } else {
      echo "Zone " . $zone . " introuvable";
    }
  } else if ($content != false) {
    echo $thePostID . "Yo";
  }
  $output = ob_get_clean();
  return $output;
}
add_shortcode('wdnl', 'process_shortcode');


// WDNLDATA Shortcode
function process_field($atts){
  global $post; 
  $thePostID = $post->ID;

  extract(shortcode_atts(array(
  'field' => false
  ), $atts));
  ob_start();
  
  if ($field != false) {
   $theme = get_field('mailing',  $thePostID);
   echo get_field($field, $theme->ID);
  }

  $output = ob_get_clean();
  return $output;
}
add_shortcode('wdnldata', 'process_field');



add_action( 'init', 'wdnletter_posttype' );
function wdnletter_posttype() {
  $labels = array(
    'name' => _x('Mailings', 'post type general name'),
    'singular_name' => _x('Mailing', 'post type singular name'),
    'add_new' => _x('Ajouter', 'Mailing'),
    'add_new_item' => __('Ajouter un nouveau mailing'),
    'edit_item' => __('Modifier le mailing'),
    'new_item' => __('Nouveau mailing'),
    'all_items' => __('Tous les Mailings'),
    'view_item' => __('Aperçu et Test du mailing'),
    'search_items' => __('Recherche mailings'),
    'not_found' =>  __('Aucun mailing trouvé'),
    'not_found_in_trash' => __('Aucun mailing trouvé dans la corbeille.'), 
    'parent_item_colon' => '',
    'menu_name' => 'Mailings'
  );
  $args = array(
    'labels' => $labels,
    'public' => true,
    'publicly_queryable' => true,
    'show_ui' => true, 
    'show_in_menu' => true, 
    'query_var' => true,
    'rewrite' => true,
    'capability_type' => 'post',
    'has_archive' => true, 
    'hierarchical' => false,
    'menu_position' => null,
    'supports' => array( 'title', 'author' )
  ); 
  register_post_type('mailing',$args);
}


add_action( 'init', 'wdnletter_theme' );
function wdnletter_theme() {
  $labels = array(
    'name' => _x('Thèmes', 'post type general name'),
    'singular_name' => _x('Thème', 'post type singular name'),
    'add_new' => _x('Ajouter un nouveau Thème', 'Mailing'),
    'add_new_item' => __('Ajouter un nouveau Thème'),
    'edit_item' => __('Modifier le Thème'),
    'new_item' => __('Afficher le Thème'),
    'all_items' => __('Thèmes'),
    'view_item' => 'Aperçu du Thème',
    'search_items' => __('Rechercher des Thèmes'),
    'not_found' =>  __('Aucun Thème trouvé'),
    'not_found_in_trash' => __('Aucun Thème trouvé dans la corbeille'), 
    'parent_item_colon' => '',
    'menu_name' => 'Thèmes'
  );
  $args = array(
    'labels' => $labels,
    'public' => true,
    'publicly_queryable' => true,
    'show_ui' => true, 
    'show_in_menu' => 'edit.php?post_type=mailing', 
    'query_var' => true,
    'rewrite' => true,
    'capability_type' => 'post',
    'has_archive' => true, 
    'hierarchical' => false,
    'menu_position' => null,
    'supports' => array( 'title', 'author' )
  ); 
  register_post_type('mailing_theme',$args);
}


add_action( 'init', 'wdnletter_zone' );
function wdnletter_zone() {
  $labels = array(
    'name' => 'Zones',
    'singular_name' => 'Zone',
    'add_new' => 'Ajouter une Zone',
    'add_new_item' => 'Ajouter une Zone',
    'edit_item' => 'Modifier la Zone',
    'new_item' => 'Afficher la Zone',
    'all_items' => 'Zones',
    'view_item' => 'Aperçu de la Zone',
    'search_items' => 'Rechercher des Zones',
    'not_found' =>  'Aucune Zone trouvée',
    'not_found_in_trash' => 'Aucune Zone trouvée dans la corbeille', 
    'parent_item_colon' => '',
    'menu_name' => 'Zones'
  );
  $args = array(
    'labels' => $labels,
    'public' => true,
    'publicly_queryable' => true,
    'show_ui' => true, 
    'show_in_menu' => 'edit.php?post_type=mailing', 
    'query_var' => true,
    'rewrite' => true,
    'capability_type' => 'post',
    'has_archive' => true, 
    'hierarchical' => false,
    'menu_position' => null,
    'supports' => array( 'title', 'author' )
  ); 
  register_post_type('mailing_zone',$args);
}




function wdnletter_menuicon() {
  echo '
  <style type="text/css" media="screen">
    #menu-posts-mailing .wp-menu-image {background: transparent url(' . WDNLETTER_URL . 'img/mails-stack.png) no-repeat 6px -17px !important;}
    #menu-posts-mailing:hover .wp-menu-image {background: transparent url(' . WDNLETTER_URL . 'img/mails-stack.png) no-repeat 6px 7px !important;}
    .icon32-posts-mailing {background: transparent url(' . WDNLETTER_URL . 'img/news-32.png) no-repeat 0 0 !important;}
  </style>';
}
add_action( 'admin_head', 'wdnletter_menuicon' );


/**
 * Activate Add-ons
 * Here you can enter your activation codes to unlock Add-ons to use in your theme. 
 * Since all activation codes are multi-site licenses, you are allowed to include your key in premium themes. 
 * Use the commented out code to update the database with your activation code. 
 * You may place this code inside an IF statement that only runs on theme activation.
 */ 
if(!get_option('acf_repeater_ac')) update_option('acf_repeater_ac', "QJF7-L4IX-UCNP-RF2W");
// if(!get_option('acf_options_ac')) update_option('acf_options_ac', "xxxx-xxxx-xxxx-xxxx");
if(!get_option('acf_flexible_content_ac')) update_option('acf_flexible_content_ac', "FC9O-H6VN-E4CL-LT33");

/**
 * Register field groups
 * The register_field_group function accepts 1 array which holds the relevant data to register a field group
 * You may edit the array as you see fit. However, this may result in errors if the array is not compatible with ACF
 * This code must run every time the functions.php file is read
 */
if(function_exists("register_field_group")) {

  register_field_group(array(
    'title' => 'Configuration du mailing',
    'fields' => 
    array(
      0 => 
      array(
        'key' => 'field_4f69f8a839331',
        'label' => 'Sujet du mailing',
        'name' => 'mailing_sujet',
        'type' => 'text',
        'default_value' => '',
        'formatting' => 'none',
        'instructions' => '',
        'required' => '0',
        'order_no' => '2',
      ),
    ),
    'location' => 
    array(
      'rules' => 
      array(
        0 => 
        array(
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'mailing',
          'order_no' => '0',
        ),
      ),
      'allorany' => 'all',
    ),
    'options' => 
    array(
      'position' => 'normal',
      'layout' => 'default',
      'show_on_page' => 
      array(
      ),
    ),
    'menu_order' => 0,
  ));

  register_field_group(array(
    'id' => '4fcfb332611cf',
    'title' => 'Apparence du mail',
    'fields' => 
    array(
      0 => 
      array(
        'key' => 'field_4fc94ad7adeaf',
        'label' => 'Choisir un thème',
        'name' => 'mailing_theme',
        'type' => 'post_object',
        'instructions' => '',
        'required' => '0',
        'post_type' => 
        array(
          0 => 'mailing_theme',
        ),
        'taxonomy' => 
        array(
          0 => 'all',
        ),
        'allow_null' => '0',
        'multiple' => '0',
        'order_no' => '0',
      ),
    ),
    'location' => 
    array(
      'rules' => 
      array(
        0 => 
        array(
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'mailing',
          'order_no' => '0',
        ),
      ),
      'allorany' => 'all',
    ),
    'options' => 
    array(
      'position' => 'side',
      'layout' => 'default',
      'hide_on_screen' => 
      array(
      ),
    ),
    'menu_order' => 0,
  ));

  register_field_group(array(
    'id' => '4fcfb3c02c081',
    'title' => 'Theme',
    'fields' => 
    array(
      0 => 
      ///get_field('mailing_emetteur') . ' <' . get_field('mailing_envoi')
      array(
        'key' => 'field_4fc947c0e5588',
        'label' => 'Adresse d\'envoi',
        'name' => 'mailing_envoi',
        'type' => 'text',
        'instructions' => '',
        'required' => '0',
        'default_value' => '',
        'formatting' => 'none',
        'order_no' => '0',
      ),
      1 => 
      array(
        'key' => 'field_4fc947c0e5f65',
        'label' => 'Nom d\'envoi',
        'name' => 'mailing_emetteur',
        'type' => 'text',
        'instructions' => '',
        'required' => '0',
        'default_value' => '',
        'formatting' => 'none',
        'order_no' => '0',
      ),
      2 => 
      array(
        'key' => 'field_4fc947c0e5e98',
        'label' => 'CSS',
        'name' => 'theme_css',
        'type' => 'textarea',
        'instructions' => '',
        'required' => '0',
        'default_value' => '',
        'formatting' => 'none',
        'order_no' => '0',
      ),
      3 => 
      array(
        'key' => 'field_4fc947c0e5b25',
        'label' => 'Html',
        'name' => 'theme_html',
        'type' => 'textarea',
        'instructions' => '',
        'required' => '0',
        'default_value' => '',
        'formatting' => 'html',
        'order_no' => '1',
      ),
    ),
    'location' => 
    array(
      'rules' => 
      array(
        0 => 
        array(
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'mailing_theme',
          'order_no' => '0',
        ),
      ),
      'allorany' => 'all',
    ),
    'options' => 
    array(
      'position' => 'normal',
      'layout' => 'default',
      'hide_on_screen' => 
      array(
      ),
    ),
    'menu_order' => 0,
  ));

  register_field_group(array (
    'id' => '4fcfb3cee6d1a',
    'title' => 'Programmer ce mailing',
    'fields' => 
    array(
      0 => 
      array(
        'key' => 'field_4fc9616ac2066',
        'label' => 'Status',
        'name' => 'mailing_status',
        'type' => 'select',
        'instructions' => '',
        'required' => '0',
        'choices' => 
        array (
          1 => 'Brouillon',
          2 => 'Programmé',
          3 => 'En Envoi',
        ),
        'default_value' => '1',
        'allow_null' => '0',
        'multiple' => '0',
        'order_no' => '0',
      ),
      1 => 
      array(
        'key' => 'field_4fcba12c653ca',
        'label' => 'Date d\'envoi :',
        'name' => 'schedule_date',
        'type' => 'date_picker',
        'instructions' => '',
        'required' => '0',
        'date_format' => '',
        'order_no' => '1',
      ),
      2 => 
      array(
        'key' => 'field_4fcba12c657b5',
        'label' => 'Heure d\'envoi :',
        'name' => 'schedule_time',
        'type' => 'text',
        'instructions' => 'Préciser une heure de début d\'envoi',
        'required' => '0',
        'default_value' => '9h00',
        'formatting' => 'none',
        'order_no' => '2',
      ),
      3 => 
      array(
        'key' => 'field_4fcba12c65a9a',
        'label' => 'Commentaire :',
        'name' => 'schedule_comment',
        'type' => 'textarea',
        'instructions' => '',
        'required' => '0',
        'default_value' => '',
        'formatting' => 'none',
        'order_no' => '3',
      ),
    ),
    'location' => 
    array(
      'rules' => 
      array(
        0 => 
        array(
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'mailing',
          'order_no' => '0',
        ),
      ),
      'allorany' => 'all',
    ),
    'options' => 
    array(
      'position' => 'side',
      'layout' => 'prog',
      'hide_on_screen' => 
      array(
      ),
    ),
    'menu_order' => 0,
  ));

  register_field_group(array(
    'id' => '4fcfb3dbe26e7',
    'title' => 'Zone',
    'fields' => 
    array(
      0 => 
      array(
        'key' => 'field_4fcb5e356b158',
        'label' => 'Contenu de la zone',
        'name' => 'content',
        'type' => 'textarea',
        'instructions' => 'Format accepté: Html, Css',
        'required' => '0',
        'default_value' => '',
        'formatting' => 'html',
        'order_no' => '0',
      ),
    ),
    'location' => 
    array(
      'rules' => 
      array(
        0 => 
        array(
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'mailing_zone',
          'order_no' => '0',
        ),
      ),
      'allorany' => 'all',
    ),
    'options' => 
    array(
      'position' => 'normal',
      'layout' => 'default',
      'hide_on_screen' => 
      array(
      ),
    ),
    'menu_order' => 0,
  ));

  register_field_group(array(
    'title' => 'Création du contenu',
    'fields' => 
    array(
      0 => 
      array(
        'key' => 'field_4f69f99a33e0e',
        'label' => 'Sélectionnez, disposez et arrangez différents types de Champs de contenu pour obtenir le résulat souhaité.',
        'name' => 'mailing_modules',
        'type' => 'flexible_content',
        'layouts' => 
        array(
          0 => 
          array(
            'label' => 'Pub Haute',
            'name' => 'type_pub_haute',
            'display' => 'row',
            'sub_fields' => 
            array(
              0 => 
              array(
                'label' => 'Image Pub',
                'name' => 'image_pub',
                'type' => 'image',
                'save_format' => 'url',
                'preview_size' => 'full',
                'key' => 'field_4fc8bcc5d1e5c',
                'order_no' => '0',
              ),
              1 => 
              array(
                'label' => 'Lien Pub',
                'name' => 'lien_pub',
                'type' => 'text',
                'default_value' => '',
                'formatting' => 'none',
                'key' => 'field_4fc8bcc5d1ea0',
                'order_no' => '1',
              ),
            ),
          ),
          1 => 
          array(
            'label' => 'Champ Libre',
            'name' => 'type_libre',
            'display' => 'row',
            'sub_fields' => 
            array(
              0 => 
              array(
                'label' => 'Champ d\'édition libre',
                'name' => 'richtext',
                'type' => 'wysiwyg',
                'toolbar' => 'full',
                'media_upload' => 'yes',
                'key' => 'field_4f6b421ebfddb',
                'order_no' => '0',
              ),
            ),
          ),
          2 => 
          array(
            'label' => 'Champ Image',
            'name' => 'type_image',
            'display' => 'row',
            'sub_fields' => 
            array(
              0 => 
              array(
                'label' => 'Fichier',
                'name' => 'image_image',
                'type' => 'image',
                'save_format' => 'id',
                'preview_size' => 'full',
                'key' => 'field_4f6b421ebfe24',
                'order_no' => '0',
              ),
              1 => 
              array(
                'label' => 'Lien de l\'image',
                'name' => 'image_lien',
                'type' => 'text',
                'default_value' => '',
                'formatting' => 'none',
                'key' => 'field_4f6b421ebfe62',
                'order_no' => '1',
              ),
            ),
          ),
          3 => 
          array(
            'label' => 'Champ Article',
            'name' => 'type_article',
            'display' => 'row',
            'sub_fields' => 
            array(
              0 => 
              array(
                'label' => 'Sélectionnez un ou plusieurs articles',
                'name' => 'article_article',
                'type' => 'relationship',
                'post_type' => 
                array(
                  0 => 'post',
                ),
                'taxonomy' => 
                array(
                  0 => 'all',
                ),
                'max' => '-1',
                'key' => 'field_4f6b421ebfea5',
                'order_no' => '0',
              ),
            ),
          ),
          4 => 
          array(
            'label' => 'Champ Articles avec Titre',
            'name' => 'type_article_titre',
            'display' => 'row',
            'sub_fields' => 
            array(
              0 => 
              array(
                'label' => 'Titre',
                'name' => 'article_title',
                'type' => 'text',
                'default_value' => '',
                'formatting' => 'none',
                'key' => 'field_4987bcc5d1cc5',
                'order_no' => '0',
              ),
              1 => 
              array(
                'label' => 'Sélectionnez un ou plusieurs articles',
                'name' => 'article_article',
                'type' => 'relationship',
                'post_type' => 
                array(
                  0 => 'post',
                ),
                'taxonomy' => 
                array(
                  0 => 'all',
                ),
                'max' => '-1',
                'key' => 'field_4f6b421ebcab2',
                'order_no' => '0',
              ),
            ),
          ),
          5 => 
          array(
            'label' => 'Champ Couv + Text',
            'name' => 'type_vdp_couv',
            'display' => 'table',
            'sub_fields' => 
            array(
              0 => 
              array(
                'label' => 'Image de Couv',
                'name' => 'couv_image',
                'type' => 'image',
                'save_format' => 'url',
                'preview_size' => 'full',
                'key' => 'field_4fc8bcc5d1ee5',
                'order_no' => '0',
              ),
              1 => 
              array(
                'label' => 'Text de Couv',
                'name' => 'couv_text',
                'type' => 'wysiwyg',
                'toolbar' => 'full',
                'media_upload' => 'yes',
                'key' => 'field_4fc8bcc5d1f23',
                'order_no' => '1',
              ),
            ),
          ),
          6 => 
          array(
            'label' => 'Champ Article + Text',
            'name' => 'type_vdp_article',
            'display' => 'row',
            'sub_fields' => 
            array(
              0 => 
              array(
                'label' => 'Titre',
                'name' => 'article_title',
                'type' => 'text',
                'default_value' => '',
                'formatting' => 'none',
                'key' => 'field_4987bcc5d1ea5',
                'order_no' => '0',
              ),
              1 => 
              array(
                'label' => 'Image d\'article',
                'name' => 'article_image',
                'type' => 'image',
                'save_format' => 'url',
                'preview_size' => 'full',
                'key' => 'field_85c8bcc5d1e5c',
                'order_no' => '0',
              ),
              2 => 
              array(
                'label' => 'Text d\'article',
                'name' => 'article_text',
                'type' => 'wysiwyg',
                'toolbar' => 'full',
                'media_upload' => 'yes',
                'key' => 'field_4f45c8bcc5d1ea0',
                'order_no' => '1',
              ),
            ),
          ),
          7 => 
          array(
            'label' => 'Pub Basse',
            'name' => 'type_pub_basse',
            'display' => 'row',
            'sub_fields' => 
            array(
              0 => 
              array(
                'label' => 'Image Pub',
                'name' => 'image_pub',
                'type' => 'image',
                'save_format' => 'url',
                'preview_size' => 'full',
                'key' => 'field_4fc8bcc5d1e5c',
                'order_no' => '0',
              ),
              1 => 
              array(
                'label' => 'Lien Pub',
                'name' => 'lien_pub',
                'type' => 'text',
                'default_value' => '',
                'formatting' => 'none',
                'key' => 'field_4fc8bcc5d1ea0',
                'order_no' => '1',
              ),
            ),
          ),
          8 => 
          array(
            'label' => 'Image d\'espacement',
            'name' => 'type_horiz_spacer',
            'display' => 'row',
            'sub_fields' => 
            array(
              0 => 
              array(
                'label' => 'Ajoute une image d\'espacement',
                'name' => 'horiz_spacer',
                'type' => 'text',
                'default_value' => '',
                'formatting' => 'none',
                'key' => 'field_4fc8bcc5d1ea5',
                'order_no' => '0',
              ),
            ),
          ),
          9 => 
          array(
            'label' => 'Espace',
            'name' => 'type_space',
            'display' => 'row',
            'sub_fields' => 
            array(
              0 => 
              array(
                'label' => 'Ajoute un espace',
                'name' => 'space',
                'type' => 'text',
                'default_value' => '',
                'formatting' => 'none',
                'key' => 'field_4fc8bcc5d1ea9',
                'order_no' => '0',
              ),
            ),
          ),
        ),
        'sub_fields' => 
        array(
          0 => 
          array(
            'key' => 'field_4f6a3fdad4c92',
          ),
          1 => 
          array(
            'key' => 'field_4f6a3fdad4c58',
          ),
        ),
        'instructions' => '',
        'button_label' => '+ Ajouter du Contenu',
        'required' => '0',
        'order_no' => '0',
      ),
    ),
    'location' => 
    array(
      'rules' => 
      array(
        0 => 
        array(
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'mailing',
          'order_no' => '0',
        ),
      ),
      'allorany' => 'all',
    ),
    'options' => 
    array(
      'position' => 'normal',
      'layout' => 'default',
      'show_on_page' => 
      array(
        0 => 'the_content',
        1 => 'custom_fields',
        2 => 'discussion',
        3 => 'comments',
        4 => 'slug',
        5 => 'author',
      ),
    ),
    'menu_order' => 1,
  ));

}