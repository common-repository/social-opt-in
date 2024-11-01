<?php
/*
Plugin Name: Social Opt In
Description: Plugin to add social services to WordPress while protecting the privacy of the visitors. Visitors have to activate each social service. No data is sent to any servers if the visitor doesn't want to. This plugin is totally hosted on your server.
Author: Fritz Mielert
Author URI: http://fritzmielert.de/
Version: 0.3.3
License: GPL2
Text Domain: wp-soptin
Domain Path: /languages

Copyright 2011  Fritz Mielert

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

/**
 * Set version
 */
define( 'WPSOPTIN_VERSION', '0.3.3' );

/**
 * Load WP-Config File If This File Is Called Directly
 * Load stand alone version for mailer
 */
if (!function_exists('add_action')) {
	$wp_root = '../../..';
	if (file_exists($wp_root.'/wp-load.php')) {
		require_once($wp_root.'/wp-load.php');
	} else {
		require_once($wp_root.'/wp-config.php');
	}
	wpsoptin_standalone();
}

/**
 * Demo mode
 */
define('DEMO', false);

$wpsoptin = false;

/**
 * set constants
 */
if ( ! defined( 'WPSOPTIN_PLUGIN_BASENAME' ) )
	define( 'WPSOPTIN_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

if ( ! defined( 'WPSOPTIN_PLUGIN_NAME' ) )
	define( 'WPSOPTIN_PLUGIN_NAME', trim( dirname( WPSOPTIN_PLUGIN_BASENAME ), '/' ) );

if ( ! defined( 'WPSOPTIN_PLUGIN_DIR' ) )
	define( 'WPSOPTIN_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . WPSOPTIN_PLUGIN_NAME );

if ( ! defined( 'WPSOPTIN_PLUGIN_URL' ) )
	define( 'WPSOPTIN_PLUGIN_URL', WP_PLUGIN_URL . '/' . WPSOPTIN_PLUGIN_NAME );

if ( ! defined( 'WPSOPTIN_PLUGIN_MODULES_DIR' ) )
	define( 'WPSOPTIN_PLUGIN_MODULES_DIR', WPSOPTIN_PLUGIN_DIR . '/modules' );

/**
 * Create text domain for translations
 */
load_plugin_textdomain( 'wp-soptin', WPSOPTIN_PLUGIN_URL. '/languages', dirname( plugin_basename( __FILE__ ) ) . '/languages' );

/**
 * Initialize object (mainly used for storage of options)
 */
require_once (dirname(__FILE__) . '/admin.php');
if(!isset($wpsoptin)){
	scb_init( 'wpsoptin_init' );
}

/**
 * Only for the mailer
 */
function wpsoptin_standalone(){
	if(!is_textdomain_loaded( 'wp-soptin')){
		load_plugin_textdomain( 'wp-soptin', trim( dirname(plugin_basename( __FILE__ )), '/' ). '/languages', dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
	if(!array($_POST)||
	   !isset($_POST["soptin_mailer_form_url"])||
	   !isset($_POST["soptin_mailer_form_title"])||
	   !isset($_POST["soptin_mailer_form_email_recipient"])||
	   !isset($_POST["soptin_mailer_form_body"])||
	   !isset($_POST["soptin_mailer_form_email"])||
	   strlen($_POST["soptin_mailer_form_url"])<5||
	   strlen($_POST["soptin_mailer_form_title"])<5||
	   strlen($_POST["soptin_mailer_form_email_recipient"])<6||
	   strlen($_POST["soptin_mailer_form_email"])<6
	   ) {
		echo "500";
		exit;
	}
	foreach(@$_POST as $s){
		$s = strip_tags($s);
	}
	
	$domain = str_replace("www.","",$_SERVER["HTTP_HOST"]);
	//print_r($_POST);
	$subject = sprintf(__('%s Post: %s', 'wp-soptin'),$domain,rawurldecode($_POST["soptin_mailer_form_title"]));
	$message = 
		sprintf(__('Personal message from sender:
%s

The following post was recommended to you by %s:
_______________________________________________________________________________

%s

You can find the complete article at:
%s

_______________________________________________________________________________

%s', 'wp-soptin'),$_POST["soptin_mailer_form_body"],$_POST["soptin_mailer_form_email"],rawurldecode($_POST["soptin_mailer_form_title"]),rawurldecode($_POST["soptin_mailer_form_url"]),$domain);

	$result = wpsoptin_mailer($subject,$message,$_POST["soptin_mailer_form_email_recipient"]);

	echo sprintf(__('We\'ve sent "%s" to %s.', 'wp-soptin'),rawurldecode($_POST["soptin_mailer_form_title"]),$_POST["soptin_mailer_form_email_recipient"]);
}
/**
 * campaign_mailer is a wrapper for wp_mail
 * @param string $subject
 * @param string $message
 * @param string $recipient
 * @param string $sender
 * @param string $sender_name
 * @param string $bcc
 * @return bool Error or success of mailing
 */
function wpsoptin_mailer($subject,$message,$recipient,$sender=false,$sender_name=false,$bcc=false){
	if(wpsoptin_validate_email_address($recipient)){
		if($sender_name === false || $sender_name == "") $sender_name = str_replace("www.","",$_SERVER["HTTP_HOST"]);
		if($sender === false || $sender == "") $sender = "info@".str_replace("www.","",$_SERVER["HTTP_HOST"]);
		//if($bcc === false || $bcc == "") $bcc = "info@".str_replace("www.","",$_SERVER["HTTP_HOST"]);
		//$bcc = "info@".str_replace("www.","",$_SERVER["HTTP_HOST"]);
		$header = "From: $sender_name <$sender> \r\n" .
		'Bcc: ' .$bcc. "\r\n" .
		'Reply-To: ' .$sender. "\r\n" .
		'X-Mailer: Microsoft Office Outlook, Build 11.0.5510 \r\n' .
		'MIME-Version: 1.0 \r\n'.
		'Content-type: text/html; charset=utf-8 \r\n';
		$resmail = wp_mail($recipient,$subject,$message,$header);
	}
	else $resmail = false;
	return $resmail;
}
/**
 * Validate email address
 * @param string $email
 * @return bool True if email address is correct
 */
function wpsoptin_validate_email_address($email){
	return (strpos($email,"@")!==false && strpos($email,".")!==false)?true:false;
}
/**
 * Formats an error message
 * @param string $message
 * @return string The formatted message
 * @deprecated
 */
function wpsoptin_error($message){
	return "<div id=\"soptin_error\">$message</div>";
}
/**
 * Filter for wordpress header
 * Echoes styles and links to stylesheets
 * @todo Optimize (only use one css file) and use Wordpress standards
 */
function wpsoptin_head(){
	global $wpsoptin;
	$options = $wpsoptin->get_options();
	echo '
		<style type="text/css">'.$options["styles"].'</style>
		';
	echo '
		<link rel="stylesheet" href="'.WPSOPTIN_PLUGIN_URL.'/wpsoptin.css" type="text/css" media="all" />
	';
}
/**
 * HTML button
 * @return string Generated HTML
 * @todo This function is not in use yet. Should optimize wpsoptin_content
 */
function wpsoptin_button($medium,$id){

}
/**
 * Filtering the content of posts or pages
 * @param string $content
 * @return string The manipulated content
 */
function wpsoptin_content_filter($content){
	$social_content = wpsoptin_content();
	return $content.$social_content;
}
/**
 * Generate HTML optin bar
 * @return string Generated HTML
 */
function wpsoptin_content(){
	global $post, $wpsoptin;
	$social_url = get_permalink();
	$social_twitter_message = $post->post_title;
	$social_facebook_message = $post->post_title;
	$social_email_message = $post->post_title;
	$options = $wpsoptin->get_options();
	
	$social_content = '
		<!-- [START: wp-soptin] -->
		<!-- This toolbar is generated by the Wordpress plugin "Social Opt In". -->
		<div style="clear:both;"></div>
		<div class="wpsoptin">';
	if($options["show_headline"]!=1)
		$social_content.= '
			<div class="wpsoptin_headline">'.$options["headline"].'</div>';
	$social_content.= '
			<ul>
			';
	$unique = (int)rand(0,100000);
	//$options["imagestyle"] = "color";
	//$options["greyscale"] = ($options["greyscale"])?false:true;
	//$social_content.=print_r($options,true);
	if($options["twitter"]!=1)
		$social_content.= '
				<li class="wpsoptin_twitter" id="wpsoptin_twitter_'.$unique.'">
					<div class="wpsoptin_medium">
						<a class="wpsoptin_sharerlink'.(($options["greyscale"]!=1)?" wpsoptin_grey":"").'" href="javascript:button2iframe(\'wpsoptin_twitter_'.$unique.'\',\'http://platform.twitter.com/widgets/tweet_button.html?url='.rawurlencode($social_url).'&amp;text='.(($options["twitter_pre"]!="")?$options["twitter_pre"]." ":"").rawurlencode($social_twitter_message).(($options["twitter_post"]!="")?" ".$options["twitter_post"]:"").'&amp;url='.rawurlencode($social_url).'&amp;count=horizontal&amp;lang=de\')">'.__('Activate Twitter','wp-soptin').'</a>
						<div class="wpsoptin_sharerend'.(($options["greyscale"]!=1)?" wpsoptin_grey":"").'"></div>
					</div>
				</li>';
	if($options["facebook"]!=1)
		$social_content.= '
				<li class="wpsoptin_facebook" id="wpsoptin_facebook_'.$unique.'">
					<div class="wpsoptin_medium">
						<a class="wpsoptin_sharerlink'.(($options["greyscale"]!=1)?" wpsoptin_grey":"").'" href="javascript:button2iframe(\'wpsoptin_facebook_'.$unique.'\',\'http://www.facebook.com/plugins/like.php?action=recommend&channel_url=http%3A%2F%2Fstatic.ak.fbcdn.net%2Fconnect%2Fxd_proxy.php%3Fversion%3D3%23cb%3Df29ab70a83da7b8%26origin%3D'.rawurlencode($social_url).'%26relation%3Dparent.parent%26transport%3Dpostmessage&font=arial&href='.rawurlencode($social_url).'&layout=button_count&locale=de_DE&node_type=link&sdk=joey&send=false&show_faces=false&width=139\')">'.__('Activate Facebook','wp-soptin').'</a>
						<div class="wpsoptin_sharerend'.(($options["greyscale"]!=1)?" wpsoptin_grey":"").'"></div>
					</div>
				</li>';
	if($options["google"]!=1)
		$social_content.= '
				<li class="wpsoptin_google" id="wpsoptin_google_'.$unique.'">
					<div class="wpsoptin_medium">
						<a class="wpsoptin_sharerlink'.(($options["greyscale"]!=1)?" wpsoptin_grey":"").'" href="javascript:button2iframe(\'wpsoptin_google_'.$unique.'\',\'https://plusone.google.com/u/0/_/+1/fastbutton?hl=de-DE&url='.rawurlencode($social_url).'&size=medium&count=true&_methods=_ready%2C_close%2C_open%2C_resizeMe\')">'.__('Activate Google','wp-soptin').'</a>
						<div class="wpsoptin_sharerend'.(($options["greyscale"]!=1)?" wpsoptin_grey":"").'"></div>
					</div>
				</li>';
	if($options["mail"]!=1)
		$social_content.= '
				<li class="wpsoptin_mail">
					<a onClick="soptin_mailer_form_open(\''.rawurlencode($social_facebook_message).'\',\''.rawurlencode($social_url).'\');"title="'.__('Send post via email','wp-soptin').'">'.__('Email this','wp-soptin').'</a>
				</li>';
	$social_content.= '
			</ul>';
	if($options["show_description"]!=1)
		$social_content.= '
			<p style="clear:both;" class="wpsoptin_description">'.$options["description"].'</p>';
	$social_content.= '
		</div>
		<!-- [END: wp-soptin] -->
	';
	return $social_content;
}
/**
 * Removes all optin bars from content
 * Used to cleanup feeds
 * @param string $content
 * @return string Content without optin bars
 */
function wpsoptin_remove_for_feed($content){
	while(strpos($content,"<!-- [START: wp-soptin] -->")!==false){
		$begin = strpos($content,"<!-- [START: wp-soptin] -->");
		$end = strpos($content,"<!-- [END: wp-soptin] -->");
		$content = substr($content,0,$begin).substr($content,$end+strlen("<!-- [END: wp-soptin] -->"));
	}
	return $content;
}
/**
 * Generate stuff for wordpress footer and echo it
 * @todo Remove direct echo
 */
function wpsoptin_footer(){
	global $wpsoptin;
	$options = $wpsoptin->get_options();
	echo '
<script type="text/javascript">
	function button2iframe(id,link){
		//alert(id);
		var substr = link.split("?");
		var url = substr[0];
		substr.reverse();
		substr.pop();
		substr.reverse();
		var params = substr.join("?");
		params = params.split("&");
		var k;
		var param = "";
		var paramname = "";
		for( var k=0; k<params.length; k++ ) {
			param = params[k].split("=");
			if(param.length>1){
				if(param.length>2){
					paramname = param[0];
					param.reverse();
					param.pop();
					param.reverse();
					param[1] = param.join("=");
					param[0] = paramname;
				}
				param[1] = encodeURIComponent(param[1]);
			}
			params[k] = param.join("=");
		}
		params = params.join("&");
		link = url+"?"+params;
		jQuery(function ($) {
			$("#"+id).html($(\'<iframe allowtransparency="true" frameborder="0" scrolling="no" src="\'+link+\'" id="iframe_\'+id+\'"/>\'));
		});
	}
	jQuery(function ($) { // for speedup: http://stackoverflow.com/questions/2275702/jquery-first-child-of-this
		$(".wpsoptin_twitter").hover(
		  function () {
			$(":first-child", this).css({backgroundPosition: "0px -21px"});
			$(":nth-child(2)", this).css({backgroundPosition: "-162px -21px"});
		  }, 
		  function () {
			$(":first-child", this).css({backgroundPosition: "0px 0px"});
			$(":nth-child(2)", this).css({backgroundPosition: "-162px 0px"});
		  }
		);
		$(".wpsoptin_facebook").hover(
		  function () {
			$(":first-child", this).css({backgroundPosition: "0px -63px"});
			$(":nth-child(2)", this).css({backgroundPosition: "-162px -63px"});
		  }, 
		  function () {
			$(":first-child", this).css({backgroundPosition: "0px -42px"});
			$(":nth-child(2)", this).css({backgroundPosition: "-162px -42px"});
		  }
		);

		$(".wpsoptin_google").hover(
		  function () {
			$(":first-child", this).css({backgroundPosition: "0px -105px"});
			$(":nth-child(2)", this).css({backgroundPosition: "-162px -105px"});
		  }, 
		  function () {
			$(":first-child", this).css({backgroundPosition: "0px -84px"});
			$(":nth-child(2)", this).css({backgroundPosition: "-162px -84px"});
		  }
		);
	});
</script>
';
	if($options["mail"]!=1){
	echo '
<script type="text/javascript">
	function soptin_mailer_form_open(title,url){
		jQuery(function ($) {
			$(".validateTips").text("");
			$("#soptin_mailer_form_url").val(url);
			$("#soptin_mailer_form_title").val(title);
			$("#soptin_mailer_form").dialog("open")
		});
	}
	jQuery(function ($) {
		// hier kann man nun ohne Probleme $ als Referenz auf jQuery nutzen


		$.ui.dialog.defaults.bgiframe = true;
		$(function() {
			// a workaround for a flaw in the demo system (http://dev.jqueryui.com/ticket/4375), ignore!
			//$( "#dialog:ui-dialog" ).dialog( "destroy" );
			
			var name = $( "#soptin_mailer_form_name" ),
				email = $( "#soptin_mailer_form_email" ),
				email_recipient = $( "#soptin_mailer_form_email_recipient" ),
				allFields = $( [] ).add( name ).add( email ).add( email_recipient ),
				tips = $( ".validateTips" ),
				result = $(".soptin_message");
	
			function updateTips( t ) {
				tips
					.text( t )
					.addClass( "ui-state-highlight" );
				setTimeout(function() {
					tips.removeClass( "ui-state-highlight", 1500 );
				}, 500 );
			}
			
			function updateResult( t ) {
				result
					.text( t )
					.addClass( "ui-state-highlight" );
				setTimeout(function() {
					tips.removeClass( "ui-state-highlight", 1500 );
				}, 500 );
			}
	
			function checkLength( o, n, min, max ) {
				//alert(o.val());
				if ( o.val().length > max || o.val().length < min ) {
					o.addClass( "ui-state-error" );
					updateTips('.__('"Length of " + n + " must be between " + min + " and " + max + "."','wp-soptin').');
					return false;
				} else {
					return true;
				}
			}
	
			function checkRegexp( o, regexp, n ) {
				if ( !( regexp.test( o.val() ) ) ) {
					o.addClass( "ui-state-error" );
					updateTips( n );
					return false;
				} else {
					return true;
				}
			}
		
			function soptin_sendmail(){
				$.post("'.WPSOPTIN_PLUGIN_URL.'/social-opt-in.php", $("#soptin_mailer_inner_form").serialize(),
				function(data) {
					//alert("Data Loaded: " + data);
					updateResult(data);
					$("#soptin_mailer_form").dialog("close");
					$("#soptin_mailer_result").dialog("open");
					setTimeout(function() {
					//	$("#soptin_mailer_result").dialog("close");
					},4000);
					//$( "#soptin_mailer_form" ).dialog("option","buttons",{});
				});
			}
			
			$( "#soptin_mailer_result" ).dialog({
				autoOpen: false,
				height: 200,
				width: 350,
				draggable: false,
				resizable: false,
				modal: true,
				buttons: {
					Ok: function() {
						$( this ).dialog( "close" );
					}
				}
			});
			
			$( "#soptin_mailer_form" ).dialog({
				autoOpen: false,
				height: 350,
				width: 350,
				draggable: false,
				resizable: false,
				modal: true,
				buttons: {
					"'. __('Send post via email','wp-soptin') .'": function() {
						
						var bValid = true;
						allFields.removeClass( "ui-state-error" );
	
						bValid = bValid && checkLength( email_recipient, "email", 5, 80 );
						bValid = bValid && checkLength( email, "email", 5, 80 );
						//bValid = bValid && checkLength( password, "password", 5, 16 );
	
						//bValid = bValid && checkRegexp( name, /^[a-z]([0-9a-z_])+$/i, "Username may consist of a-z, 0-9, underscores, begin with a letter." );
						// From jquery.validate.js (by joern), contributed by Scott Gonzalez: http://projects.scottsplayground.com/email_address_validation/'; ?>
						bValid = bValid && checkRegexp( email, /^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i, "<?php echo __('joe@gmail.com','wp-soptin');?>" );
						bValid = bValid && checkRegexp( email_recipient, /^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i, "<?php echo __('joe@gmail.com','wp-soptin');?>" );
						//bValid = bValid && checkRegexp( password, /^([0-9a-zA-Z])+$/, "Password field only allow : a-z 0-9" );
			<?php
			echo '
						if ( bValid ) {
							/*$( "#users tbody" ).append( "<tr>" +
								"<td>" + name.val() + "</td>" + 
								"<td>" + email.val() + "</td>" + 
							"</tr>" );*/
							soptin_sendmail();
							//$( this ).dialog( "close" );
						}
					},
					"'. __('Cancel','wp-soptin') .'": function() {
						$( this ).dialog( "close" );
					}
				},	
				close: function() {
					allFields.val( "" ).removeClass( "ui-state-error" );
				}
			});
	
		});
	});
</script>
<div id="soptin_mailer_form" title="'. __('Share this post via email','wp-soptin').'">
	<p class="validateTips">'. __('All form fields are required.','wp-soptin').'</p>
	<form id="soptin_mailer_inner_form">
		<input type="hidden" id="soptin_mailer_form_url" name="soptin_mailer_form_url" value=""/>
		<input type="hidden" id="soptin_mailer_form_title" name="soptin_mailer_form_title" value=""/>
		<fieldset>
			<label for="soptin_mailer_form_email_recipient">'. __('Recipients email address','wp-soptin') .'</label><br/>
			<input type="text" name="soptin_mailer_form_email_recipient" id="soptin_mailer_form_email_recipient" value="" class="text ui-widget-content ui-corner-all" style="width:98%"/><br/>
			<label for="soptin_mailer_form_body">'. __('Your message','wp-soptin') .'</label><br/>
			<textarea name="soptin_mailer_form_body" id="soptin_mailer_form_body" class="text ui-widget-content ui-corner-all" style="width:98%"></textarea><br/>
			<label for="soptin_mailer_form_email">'. __('Your email address','wp-soptin') .'</label><br/>
			<input type="text" name="soptin_mailer_form_email" id="soptin_mailer_form_email" value="" class="text ui-widget-content ui-corner-all" style="width:98%"/><br/>
		</fieldset>
	</form>
</div>
<div id="soptin_mailer_result" title="'. __('Thanks for sharing','wp-soptin') .'">
	<p class="soptin_message">'. __('All form fields are required.','wp-soptin') .'</p>
</div>';
	}
}
/**
 * Enqueue JavaScripts/CSS
 * @todo Add css used in wpsoptin_head and js used in wpsoptin_footer
 */
function wpsoptin_scripts() {
	global $wpsoptin;
	$options = $wpsoptin->get_options();
	if($options["mail"]!=1)
		wp_enqueue_script('wp-soptin', plugins_url('social-opt-in/jquery-ui.min.js'), array('jquery'), '2.50', 'all');
	else
		wp_enqueue_script("jquery");
}
/**
 * Function to be called directly from theme
 * Echoes the optin bar
 */
function social_opt_in(){
	global $wpsoptin;
	$options = $wpsoptin->get_options();
	if($options["callmethod"]=="function"){
		echo wpsoptin_content();
	}
}

/**
 * Registration of filters and actions
 */
add_action('wp_footer', 'wpsoptin_footer');
add_filter('wp_head', 'wpsoptin_head');
//echo (function_exists("wpsoptin_init"))?"yes":"no";
if(!$wpsoptin) $wpsoptin = wpsoptin_init();
$options = $wpsoptin->get_options();
if($options["callmethod"]=="filter"){
	add_filter('the_content', 'wpsoptin_content_filter');
	add_filter('the_content_feed', 'wpsoptin_remove_for_feed');
}
add_action('wp_enqueue_scripts', 'wpsoptin_scripts');
?>