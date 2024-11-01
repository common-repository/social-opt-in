<?php
/**
 * Load libraries for admin ui
 */
define( 'SCB_LOAD_MU', true );
foreach ( array(
	'scbUtil', 'scbOptions', 'scbForms', 'scbTable',
	'scbWidget', 'scbAdminPage', 'scbBoxesPage',
	'scbCron', 'scbHooks',
) as $className ) {
	// some other plugins also use scb
	if(!class_exists($className))
		include dirname( __FILE__ ) . '/scb-framework/scb/' . substr( $className, 3 ) . '.php';
}
/**
 * Integrate scb when not integrated by other plugins
 */
if(!function_exists('scb_init'))
	require_once("scb_loader.php");

class wpsoptin extends scbAdminPage {
	/**
	 * Setup optin
	 */
	function setup() {
		if(!is_textdomain_loaded( 'wp-soptin')){
			load_plugin_textdomain( 'wp-soptin', trim( dirname(plugin_basename( __FILE__ )), '/' ). '/languages', dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}
		
		$this->defaults = array(
			'callmethod'       => 'filter',
			'headline'         => __('Share Post','wp-soptin'),
			'description'      => __('To share ths articel via social networks, you\'ve got to enable them - For your privacy.','wp-soptin'),
			'description_link' => '',
			'twitter_pre'      => '',
			'twitter_post'     => '',
			'twitter'          => true,
			'facebook'         => true,
			'google'           => true,
			'mail'             => true,
			'greyscale'        => true,
			'show_description' => true,
			'show_headline' => true,
			'styles'           => $this->default_styles()
		);
		
		$this->options = new scbOptions('wp-soptin', __FILE__, $this->defaults);
		
		$this->args = array(
			'page_title'  => 'Social Opt In',
			'menu_title'  => 'Social Opt In',
			'short_title' => 'Social Opt In',
			'page_slug'   => 'wp-soptin',
			'nonce'       => 'wp-soptin'
		);

		$this->nonce = 'wp-soptin';
	}
	/**
	 *
	 */
	function default_styles(){
		return '
.wpsoptin {padding:7px 0px 7px 0px;margin-bottom:20px;font-family:tahoma,verdana,arial,sans-serif;}
.wpsoptin_headline{text-transform:uppercase;clear:both;font-size:11px;font-weight:bold;}
.wpsoptin_description {border-top:1px solid #bbb;font-size:9px;clear:both;line-height:13px;font-family:tahoma,verdana,arial,sans-serif;}
.wpsoptin ul{margin:0;position:relative;}
.wpsoptin li{list-style:none;border:0;float:left;height:auto;margin:1px 5px 0 0;padding:0;width:auto;}
.wpsoptin li.wpsoptin_mail, .socialprivacy li.print{float:right;margin: 0px 0px 0px 10px;}
.wpsoptin li.wpsoptin_mail{background-position: 0px -145px;cursor:pointer;}
.wpsoptin li.wpsoptin_print{background-position: 0px -95px;}
.wpsoptin li.wpsoptin_twitter{width:140px;overflow:hidden;padding-left:0px;height:30px;}
.wpsoptin li.wpsoptin_facebook{width:147px;overflow:hidden;height:30px;}
.wpsoptin li.wpsoptin_google{width:121px;overflow:hidden;height:30px;}
.wpsoptin li a{padding-left:16px;color:#333;float:left;font-size:11px;text-decoration:none;}
		';
	}
	/**
	 * print admin page
	 */
	function page_content() {
		echo html('p',__('On this page you can configure, how the plugin looks like and which social networks will be shown by this plugin under each page or post. If you choose to add this plugin via code, you\'ve got to add <?php if(function_exists(\'social-opt-in\')) social-opt-in(); ?> to loop.php.','wp-soptin'));
		echo $this->form_table( array(
			array(
				'title' => __('Mode','wp-soptin'),
				'type' => 'radio',
				'name' => 'callmethod',
				'value' => array(
					'filter' => __('<strong>Filtering of posts.</strong> Automatically adds the bar to each post on every page.<br/>','wp-soptin'),
					'function' => __('<strong>Call via PHP.</strong> You\'ve got to add &lt;?php if(function_exists(\'social_opt_in\')) social_opt_in(); ?&gt; to your theme file.','wp-soptin')
				),
				'desc' => '<span class="description">'.__('You have more control over the layout if you choose the PHP code option.<br/>You\'ve got to add <?php if(function_exists(\'social_opt_in\')) social_opt_in(); ?> to loop.php.','wp-soptin').'</span>'
			),
		//));
		//echo html('p',);
		//echo $this->form_table( array(
			array(
				'title' => __('Show headline','wp-soptin'),
				'type' => 'checkbox',
				'name' => 'show_headline',
				'desc' => __('Show headline over the bar','wp-soptin')
			),
			array(
				'title' => __('Headline','wp-soptin'),
				'type' => 'text',
				'name' => 'headline',
				'desc' => '<span class="description">'.__('Headline over <i>Social Opt In</i>.','wp-soptin')."<br/>".__('Share Post','wp-soptin').'</span>'
			),
			array(
				'title' => __('Show description','wp-soptin'),
				'type' => 'checkbox',
				'name' => 'show_description',
				'desc' => __('Show description under the bar','wp-soptin')
			),
			array(
				'title' => __('Description','wp-soptin'),
				'type' => 'text',
				'name' => 'description',
				'desc' => '<span class="description">'.__("Why you've installed <i>Social Opt In</i>.",'wp-soptin')."<br/>".__("To share ths articel via social networks, you've got to enable them - For your privacy.",'wp-soptin').'</span>'
			),
			array(
				'title' => __('Twitter pre','wp-soptin'),
				'type' => 'text',
				'name' => 'twitter_pre',
				'desc' => '<span class="description">'.__("Your Twitter account or some hashtags",'wp-soptin').'</span>'
			),
			array(
				'title' => __('Twitter post','wp-soptin'),
				'type' => 'text',
				'name' => 'twitter_post',
				'desc' => '<span class="description">'.__("Your Twitter account or some hashtags",'wp-soptin').'</span>'
			),
			array(
				'title' => 'Twitter',
				'type' => 'checkbox',
				'name' => 'twitter',
			),
			array(
				'title' => 'Facebook',
				'type' => 'checkbox',
				'name' => 'facebook',
			),
			array(
				'title' => 'Google',
				'type' => 'checkbox',
				'name' => 'google',
			),
			array(
				'title' => __('Mail','wp-soptin'),
				'type' => 'checkbox',
				'name' => 'mail',
			),
			array(
				'title' => __('Greyscale mode','wp-soptin'),
				'type' => 'checkbox',
				'name' => 'greyscale',
			),
			array(
				'title' => __('Styles','wp-soptin'),
				'type' => 'textarea',
				'name' => 'styles',
				'extra' => 'rows="10" cols="80"',
				'desc' => '<span class="description"><br/>'.__("Default CSS-Styles",'wp-soptin').nl2br($this->default_styles()).'</span>'
			)
			
			
			/*
			array(
				'title' => __('Description Link','wp-soptin'),
				'type' => 'text',
				'name' => 'description_link',
				'desc' => __('Url to your privacy policy.','wp-soptin')
			),
		) );
		echo html( 'h3', __('Social Media','wp-soptin') );
		echo $this->form_table( array(*/
		) );
	}
	/**
	 * Update or save options
	 */
	function form_handler() {
		if ( empty($_POST['action']) )
			return false;
		check_admin_referer($this->nonce);
		// Update options
		if ( 'Save Changes' == $_POST['action'] ) {
			foreach(array_keys($this->options->get()) as $key){
				$this->options->set($key, $_POST[$key]);
			}
			$this->options->update($this->options->get());
			$this->admin_msg(__('Settings <strong>saved</strong>.','wp-soptin'));
		}
		// Reset options
		if ( 'Reset' == $_POST['action'] ) {
			$this->options->reset();
			$this->admin_msg(__('Settings <strong>reset</strong>.','wp-soptin'));
		}
	}
	/**
	 * get all options
	 * @return array All options
	 */
	function get_options(){
		return $this->options->get();
	}
	/**
	 * print footer of admin page
	 */
	function page_footer() {
		parent::page_footer();

		// Reset all forms
?>
		<script type="text/javascript">
		(function() {
			var forms = document.getElementsByTagName('form');
			for (var i = 0; i < forms.length; i++) {
				forms[i].reset();
			}
		}());
		</script>
<?php
	}
}
/**
 * init the class
 */
function wpsoptin_init(){
	global $wpsoptin;
	$wpsoptin = new wpsoptin( __FILE__ );
	return $wpsoptin;
}
?>
