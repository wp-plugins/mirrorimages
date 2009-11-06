<?php
/*
Plugin Name: MirrorImages
Plugin URI: http://www.stegasoft.de/
Description: Bilder eines Posts/Artikels mit Spiegelung darstellen
Version: 0.1
Author: Stephan Gaertner
Author URI: http://www.stegasoft.de
*/

$table_style = "border:solid 1px #606060;border-collapse:collapse;padding:2px;";

$miversion = 0.1;


//============= INCLUDES ==========================================================
@include_once (dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR. "wp-config.php");
@include_once (dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR."wp-includes/wp-db.php");
//@require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR ."var.php");

$version = get_bloginfo('version');

define('MIRRORIMAGE_URLPATH', WP_CONTENT_URL.'/plugins/'.plugin_basename( dirname(__FILE__)) );



$mirrim_options = get_option( "mirrim_options" );

//============= Code für Admin-Kopf erzeugen ============================
function mijs2adminhead() {
  global $mirrim_options;

  $jscript_includes = "\n";
  $jscript_includes .= "<link rel='stylesheet' href='".MIRRORIMAGE_URLPATH."/styles.css' type='text/css' />\n";
  $jscript_includes .= "<script language='JavaScript' src=\"".MIRRORIMAGE_URLPATH."/jscolor/jscolor.js\" type=\"text/javascript\"></script>\n\n";

  echo $jscript_includes;
}
add_action('admin_head', 'mijs2adminhead');



//============= Code für Template-Kopf erzeugen ============================
function mijs2head() {
  global $mirrim_options;

  $jscript_includes = "\n";
  $jscript_includes .= "<link rel='stylesheet' href='".MIRRORIMAGE_URLPATH."/styles.css' type='text/css' />\n\n";

  echo $jscript_includes;
}
//add_action('wp_head', 'imjs2head');



//============= Plugin - Button einbauen =====================================
add_action('admin_menu', 'mirrim_page');
function mirrim_page() {
    add_submenu_page('plugins.php', __('MirrorImage'), __('MirrorImage'), 10, 'mirrimadmin', 'mirrim_options_page');
}


//============= Tabellen/Optionen loeschen ===================================
if($mirrim_options["mi_deinstall"] == "yes")
  register_deactivation_hook(__FILE__, 'mirrim_deinstall');
function mirrim_deinstall() {
  global $wpdb;
  delete_option('mirrim_options');
}


//============ Platzhalter ersetzen =========================================
function mirrim_replace_tag($content) {
  global $mirrim_options;
  if ( stristr( $content, '<img' )) {
    $inhalt = $content;
    $string_stack = "";
    $found_at = false;
    $img_array = Array();

    $img_array = parseTextForImage($inhalt);
    //print_r($img_array);

    for($i=0;$i<count($img_array);$i++) {
      //--- alle gefundenen Bilder bearbeiten ---

      if($mirrim_options["mi_klasse_wahl"]=="yes") {
        if(stristr($img_array[$i],$mirrim_options["mi_klasse"] ) !== FALSE) {
          //--- Bildattribut Höhe finden -----
          $height_apos = strpos($img_array[$i],"height");
          $height_epos = strpos($img_array[$i],"\"",$height_apos+8);
          $height_code = substr($img_array[$i],$height_apos,$height_epos-$height_apos+1);

          $new_src = str_replace("src=\"","src=\"".MIRRORIMAGE_URLPATH."/streamer.php?img=",$img_array[$i]);
          $new_src = str_replace($height_code,"",$new_src);    //Bildattribut Höhe entfernen

          $content = str_replace($img_array[$i],$new_src, $content);
        }
      }
      else {
        //--- Bildattribut Höhe finden -----
        $height_apos = strpos($img_array[$i],"height");
        $height_epos = strpos($img_array[$i],"\"",$height_apos+8);
        $height_code = substr($img_array[$i],$height_apos,$height_epos-$height_apos+1);

        $new_src = str_replace("src=\"","src=\"".MIRRORIMAGE_URLPATH."/streamer.php?img=",$img_array[$i]);
        $new_src = str_replace($height_code,"",$new_src);    //Bildattribut Höhe entfernen

        $content = str_replace($img_array[$i],$new_src, $content);
      }

    }

  }

  return $content;
}
add_filter('the_content', 'mirrim_replace_tag');


//---- Code from http://www.sunilb.com/php/php-script-to-extract-email-address-from-any-text---
function parseTextForImage($text) {
  $images = array();

  $output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $text, $matches);
  $images = $matches;

  //print_r($images);



 return $images[0];

}




//============= Seite für Plugin-Administration aufbauen ====================
function mirrim_options_page() {
  global $wpdb;

  if (defined('WPLANG')) {
    $lang = WPLANG;
  }
  if (empty($lang)) {
    $lang = 'de_DE';
  }

  if(!@include_once "lang/".$lang.".php")
    include_once "lang/en_EN.php";


  // Read in existing option value from database
  $mirrim_options = get_option( "mirrim_options" );

  $mi_deinstall = $mirrim_options["mi_deinstall"];
  $mi_background = $mirrim_options["mi_background"];
  $mi_gradient = $mirrim_options["mi_gradient"];
  $mi_shadow = $mirrim_options["mi_shadow"];
  $mi_distance = $mirrim_options["mi_distance"];
  $mi_klasse = $mirrim_options["mi_klasse"];
  $mi_klasse_wahl = $mirrim_options["mi_klasse_wahl"];

  // See if the user has posted us some information
  // If they did, this hidden field will be set to 'Y'
  if( $_POST[ 'mi_submit_hidden' ] == "Y" ) {

    // Read their posted value
    $mi_deinstall = $_POST[ 'mi_deinstall' ];
    $mi_background = $_POST["mi_background"];
    if(trim($_POST["mi_gradient"])=="")
      $mi_gradient = 0;
    else
      $mi_gradient = $_POST["mi_gradient"];
    if(trim($_POST["mi_shadow"])=="")
      $mi_shadow = 0;
    else
      $mi_shadow = $_POST["mi_shadow"];
    if(trim($_POST["mi_distance"])=="")
      $mi_distance = 0;
    else
      $mi_distance = $_POST["mi_distance"];

    $mi_klasse = $_POST["mi_klasse"];
    $mi_klasse_wahl = $_POST["mi_klasse_wahl"];

    // Save the posted value in the database
    $mirrim_options["mi_deinstall"] = $mi_deinstall;
    $mirrim_options["mi_background"] = $mi_background;
    $mirrim_options["mi_gradient"] = $mi_gradient;
    $mirrim_options["mi_shadow"] = $mi_shadow;
    $mirrim_options["mi_distance"] = $mi_distance;
    $mirrim_options["mi_klasse"] = $mi_klasse;
    $mirrim_options["mi_klasse_wahl"] = $mi_klasse_wahl;

    update_option( "mirrim_options", $mirrim_options );


    // Put an options updated message on the screen

    ?>
    <div class="updated"><p><strong><?php echo $istgespeichert_w; ?></strong></p></div>
    <?php

  } //bei Formularversand


  if($mi_deinstall=="yes")
    $mi_deinstall_check = " checked";
  else
    $mi_deinstall_check = "";

  if($mi_klasse_wahl=="yes")
    $mi_klasse_wahl_check = " checked";
  else
    $mi_klasse_wahl_check = "";


  //============ Now display the options editing screen ===========================
  echo "<div class=\"wrap\">";

  // header
  echo "<h2>" . __( "MirrorImage Administration", "mi_trans_domain" ) . "</h2>";

  // options form

  ?>

  <form name="form1" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
  <input type="hidden" name="mi_submit_hidden" value="Y" />

  <table border="0" cellpadding="3" cellspacing="0">
   <tr><td colspan="3"><b><?php echo $allgemeines_w; ?></b></td></tr>
   <tr>
    <td style="width:140px;">
    <?php echo $deinstall_w; ?></td>
    <td colspan="2"><input type="checkbox" name="mi_deinstall" value="yes"<?php echo $mi_deinstall_check; ?> />
    <?php echo $deinstall_hinweis_w; ?></td>
   </tr>
   </table>

   <table border="0" cellpadding="3" cellspacing="0">
   <tr>
    <td colspan="2"><br /><b><?php echo $mi_param_w; ?></b></td>
   </tr>
   <tr>
    <td valign="top"><?php echo $mi_bgcolor_w; ?>:</td>
    <td align="left">
     <input type="text" name="colorpicker1" value="" class="color {valueElement:'mi_background'} fe_txt" style="width:20px; height:20px;background:<?php echo $mi_background; ?>;" />
           <input type="text" name="mi_background" id="mi_background" value="<?php echo $mi_background; ?>" class="fe_txt" style="width:60px;" />
    </td>
   </tr>
   <tr>
    <td valign="top"><?php echo $mi_gradient_w; ?>:</td>
    <td align="left">
    <input type="text" name="mi_gradient" value="<?php echo $mi_gradient; ?>" class="fe_txt" style="width:30px;" /> <?php echo $mi_gradient_bsp_w; ?>
    </td>
   </tr>
   <tr>
    <td valign="top"><?php echo $mi_shadow_w; ?>:</td>
    <td align="left">
    <input type="text" name="mi_shadow" value="<?php echo $mi_shadow; ?>" class="fe_txt" style="width:30px;" /> <?php echo $mi_gradient_bsp_w; ?>
    </td>
   </tr>
   <tr>
    <td valign="top"><?php echo $mi_distance_w; ?>:</td>
    <td align="left">
    <input type="text" name="mi_distance" value="<?php echo $mi_distance; ?>" class="fe_txt" style="width:30px;" /> px
    </td>
   </tr>
   <tr>
    <td valign="top"><?php echo $mi_klasse_w; ?>:</td>
    <td align="left">
    <input type="checkbox" name="mi_klasse_wahl" value="yes"<?php echo $mi_klasse_wahl_check; ?> />
    <input type="text" name="mi_klasse" value="<?php echo $mi_klasse; ?>" class="fe_txt" style="width:100px;" /> <?php echo $mi_klasse_bsp_w; ?>
    </td>
   </tr>
  </table>

  <hr />

  <p class="submit">
  <input type="submit" name="Submit" value="<?php echo $speichern_w; ?>" />
  </p>

  </form>

  <br />
  <?php echo $fußnote_w; ?>


  </div>

  <?
}

?>