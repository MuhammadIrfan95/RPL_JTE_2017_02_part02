<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


/**
 * The Rich Snippet WordPress Plugin Class
 *
 * @version 1.6.1
 *
 */
class WPB_Rich_Snippets extends WPB_Plugin {

	/**
	 * _plugin_version
	 * The plugin version
	 *
	 * (default value: '1.0')
	 *
	 * @var string
	 * @access private
	 * @since  1.2
	 */
	public $_plugin_version = '1.6.1';


	/**
	 * _plugin_name
	 *
	 * (default value: google-author)
	 *
	 * @var string
	 * @access protected
	 * @since  1.2
	 */
	protected $_plugin_name = 'rich-snippets-wordpress-plugin';


	/**
	 * _plugin_textdomain
	 *
	 * (default value: google-author )
	 *
	 * @var string
	 * @access protected
	 * @since  2.0
	 */
	protected $_plugin_textdomain = 'rich-snippets-wordpress-plugin';

	/**
	 * _standard_capabilities
	 * This is the array of the standard capabilities
	 * This means if there are no settings set the script will use this array
	 *
	 * (default value: array())
	 *
	 * @var array
	 * @access private
	 * @since  1.0
	 */
	private $_standard_capabilities = array();


	/**
	 * __construct function.
	 * Just do the normal startup stuff (adding actions and so on ...)
	 *
	 * @access public
	 * @since  1.0
	 *
	 * @param null   $file
	 * @param null   $plugin_url
	 * @param string $inclusion
	 *
	 * @param bool   $auto_update
	 *
	 * @return \WPB_Rich_Snippets
	 */
	public function __construct( $file = null, $plugin_url = null, $inclusion = 'plugin', $auto_update = true ) {

		// call the parent constructor first
		parent::__construct( $file, $plugin_url, $inclusion, $auto_update );

		$this->_purchase_code_settings_page_url = admin_url( 'options-general.php?page=rswp-settings' );

		$this->_standard_capabilities = array(
			'administrator' => array(
				"edit_rswp-shortcode"          => true,
				"read_rswp-shortcode"          => true,
				"delete_rswp-shortcode"        => true,
				"edit_rswp-shortcodes"         => true,
				"edit_others_rswp-shortcodes"  => true,
				"publish_rswp-shortcodes"      => true,
				"create_rswp-shortcodes"       => true,
				"read_private_rswp-shortcodes" => true,
			),
			'editor'        => array(
				"edit_rswp-shortcode"          => true,
				"read_rswp-shortcode"          => true,
				"delete_rswp-shortcode"        => true,
				"edit_rswp-shortcodes"         => true,
				"edit_others_rswp-shortcodes"  => true,
				"publish_rswp-shortcodes"      => true,
				"create_rswp-shortcodes"       => true,
				"read_private_rswp-shortcodes" => true,
				"delete_rswp-shortcodes"       => true,
			),
			'author'        => array(
				"edit_rswp-shortcode"          => true,
				"read_rswp-shortcode"          => true,
				"delete_rswp-shortcode"        => true,
				"edit_rswp-shortcodes"         => true,
				"edit_others_rswp-shortcodes"  => false,
				"publish_rswp-shortcodes"      => false,
				"create_rswp-shortcodes"       => false,
				"read_private_rswp-shortcodes" => false,
				"delete_rswp-shortcodes"       => true,
			),
			'contributor'   => array(
				"edit_rswp-shortcode"          => true,
				"read_rswp-shortcode"          => true,
				"delete_rswp-shortcode"        => true,
				"edit_rswp-shortcodes"         => true,
				"edit_others_rswp-shortcodes"  => false,
				"publish_rswp-shortcodes"      => false,
				"create_rswp-shortcodes"       => false,
				"read_private_rswp-shortcodes" => false,
			),
			'subscriber'    => array(
				"edit_rswp-shortcode"          => false,
				"read_rswp-shortcode"          => true,
				"delete_rswp-shortcode"        => false,
				"edit_rswp-shortcodes"         => false,
				"edit_others_rswp-shortcodes"  => false,
				"publish_rswp-shortcodes"      => false,
				"create_rswp-shortcodes"       => false,
				"read_private_rswp-shortcodes" => false,
			),
		);

		// Add custom post type
		add_action( 'init', array( &$this, 'create_custom_post_type' ) );

		// Use shortcodes even for text widgets
		add_filter( 'widget_text', 'do_shortcode' );

		// do the admin stuff
		$this->do_admin();

		// do the non-admin stuff
		$this->do_non_admin();

		// add shortcodes
		$this->add_shortcodes();

	}

	/**
	 * Does some admin stuff
	 *
	 * @since 1.2
	 * @return void
	 */
	private function do_admin() {
		if ( ! is_admin() ) {
			return;
		}

		$options = get_option( 'rswp-settings' );
		if ( ! isset( $options['cap_handling'] ) ) {
			$options['cap_handling'] = 0;
		}

		// Add custom post type messages
		add_filter( 'post_updated_messages', array( &$this, 'post_updated_messages' ) );

		// Edit columns on shortcode post type admin panel
		add_filter( 'manage_rswp-shortcode_posts_columns', array( &$this, 'manage_columns' ) );

		// Add column content to the custom column
		add_action( 'manage_posts_custom_column', array( &$this, 'custom_column' ), 10, 2 );

		// Adding the save_post action
		add_action( 'save_post', array( &$this, 'save_post' ) );

		// Enquie Javascript and CSS
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );

		// Add ajax support
		add_action( 'wp_ajax_rswp_sanitize', array( &$this, 'ajax_sanitize' ) );

		// after the update the plugin will not be automatically deactivated and activated
		// so we simulate this function by setting the activate-option directly
		add_action( 'admin_init', array( &$this, 'do_updates' ), 5 );

		// installs the examples if the plugin was just activated
		add_action( 'admin_init', array( &$this, 'install_examples' ), 10 );

		// creates a new menu for the settings sections
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );

		// registers the settings
		add_action( 'admin_init', array( &$this, 'register_settings' ) );

		// update capabilities for roles
		if ( ! (bool) $options['cap_handling'] ) {
			add_action( 'admin_init', array( &$this, 'update_capabilities' ) );
		}

		// adds some css to the header
		add_action( 'admin_head-edit.php', array( &$this, 'admin_head' ) );

		// adds admin notices
		add_action( 'admin_notices', array( &$this, 'admin_notices' ) );

		add_action( 'init', array( &$this, 'editor_button' ) );

		add_action( 'admin_footer', array( &$this, 'admin_footer_window' ) );

	}


	/**
	 * Does some non-admin stuff
	 *
	 * @since 1.2
	 * @return void
	 *
	 */
	private function do_non_admin() {
		if ( is_admin() ) {
			return;
		}

		// adds css from the shortcodes
		add_action( 'wp_head', array( &$this, 'add_css' ) );
	}

	/**
	 * Activate the plugin
	 *
	 * @since 1.0
	 */
	public function on_activation() {
		update_option( 'rswp_just_activated', true );
	}

	/**
	 * do_updates function.
	 * Do some things if the plugin has been updated
	 *
	 * @access public
	 * @return void
	 * @since  1.0
	 */
	public function do_updates() {
		$rswp_version = get_option( 'rswp_version', null );
		$plugin_data  = get_plugin_data( __FILE__, false, false );

		// don't do anything when the versions are the same
		if ( $plugin_data['Version'] == $rswp_version ) {
			return;
		}

		// we're updating from version 1.0 to a higher version
		// or we do install the plugin the first time
		// in this case we move the rswp-shortcodes post type to the rswp-shortcode post type (note the s at the end)
		if ( is_null( $rswp_version ) ) {

			global $wpdb; // update the post type						// this is the where-clause
			$wpdb->update( $wpdb->posts, array( 'post_type' => 'rswp-shortcode' ), array( 'post_type' => 'rswp-shortcodes' ) );

		}

		$this->update_plugin_version();

	}


	/**
	 * update_plugin_version function.
	 * Updates the plugin version
	 *
	 * @access private
	 * @return void
	 * @since  1.0
	 */
	private function update_plugin_version() {
		$plugin_data = get_plugin_data( __FILE__, false, false );

		// update to the current version
		update_option( 'rswp_version', $plugin_data['Version'] );
	}


	/**
	 * enqueue_scripts function.
	 * Adds CSS and Javascript but only for the rswp-shortcode post type
	 *
	 * @access public
	 *
	 * @param mixed $hook_suffix
	 *
	 * @return void
	 * @since  1.0
	 */
	public function enqueue_scripts( $hook_suffix ) {
		global $typenow, $pagenow;

		$plugin_data = get_plugin_data( __FILE__, false, false );

		wp_register_style( 'rswp_window', $this->plugins_url( 'assets/css/window.css' ), false, $plugin_data['Version'] );

		// define the style here because it's needed on the edit post pages and the settings pages
		wp_register_style( 'rswp_style', $this->plugins_url( 'assets/css/style.css' ), false, $plugin_data['Version'] );

		// define the javascripts here because it's needed on the edit post pages and the settings pages
		wp_register_script( 'rswp_js', $this->plugins_url( 'assets/js/backend.js' ), array( 'jquery' ), $plugin_data['Version'] );

		if ( 'post.php' == $pagenow OR 'post-new.php' == $pagenow ) {
			wp_enqueue_style( 'rswp_window' );
		}

		// add css and backend.js to the settings page
		if ( 'rswp-shortcode_page_rswp-settings' == $hook_suffix OR 'settings_page_rswp-settings' == $hook_suffix
		     OR ( 'rswp-shortcode' == $typenow && 'edit.php' == $hook_suffix )
		) {
			wp_enqueue_style( 'rswp_style' );
			wp_enqueue_script( 'rswp_js' );

			return;
		}

		if ( ! isset( $typenow ) ) {
			return;
		}

		if ( ! ( ( 'post.php' == $hook_suffix && 'rswp-shortcode' == $typenow )
		         OR 'post-new.php' == $hook_suffix && 'rswp-shortcode' == $typenow
		)
		) {
			return;
		}

		// Adding CSS
		wp_enqueue_style( 'rswp_style' );

		wp_register_style( 'rswp_codemirror_style', $this->plugins_url( 'assets/css/codemirror.css' ) );
		wp_enqueue_style( 'rswp_codemirror_style' );

		// Adding Javascript
		wp_enqueue_script( 'rswp_js' );

		wp_register_script( 'rswp_codemirror_js', $this->plugins_url( 'assets/js/codemirror.js' ) );
		wp_enqueue_script( 'rswp_codemirror_js' );

		wp_register_script( 'rswp_codemirror_xml_js', $this->plugins_url( 'assets/js/codemirror-xml.js' ), array( 'rswp_codemirror_js' ) );
		wp_enqueue_script( 'rswp_codemirror_xml_js' );

		wp_register_script( 'rswp_codemirror_javascript_js', $this->plugins_url( 'assets/js/codemirror-javascript.js' ), array( 'rswp_codemirror_js' ) );
		wp_enqueue_script( 'rswp_codemirror_javascript_js' );

		wp_register_script( 'rswp_codemirror_html_js', $this->plugins_url( 'assets/js/codemirror-htmlmixed.js' ), array( 'rswp_codemirror_js' ) );
		wp_enqueue_script( 'rswp_codemirror_html_js' );

		wp_register_script( 'rswp_codemirror_css_js', $this->plugins_url( 'assets/js/codemirror-css.js' ), array( 'rswp_codemirror_js' ) );
		wp_enqueue_script( 'rswp_codemirror_css_js' );

		wp_register_script( 'rswp_codemirror_clike_js', $this->plugins_url( 'assets/js/codemirror-clike.js' ), array( 'rswp_codemirror_js' ) );
		wp_enqueue_script( 'rswp_codemirror_clike_js' );

		wp_register_script( 'rswp_codemirror_php_js', $this->plugins_url( 'assets/js/codemirror-php.js' ), array( 'rswp_codemirror_js' ) );
		wp_enqueue_script( 'rswp_codemirror_php_js' );
	}


	/**
	 * create_custom_post_type function.
	 * Registers the custom post type for editing shortcodes
	 *
	 * @access public
	 * @return void
	 * @since  1.0
	 */
	public function create_custom_post_type() {

		$options = get_option( 'rswp-settings' );
		if ( ! isset( $options['cap_handling'] ) ) {
			$options['cap_handling'] = 0;
		}
		if ( ! isset( $options['map_meta_cap'] ) ) {
			$options['map_meta_cap'] = 0;
		}
		if ( ! isset( $options['cap_type'] ) ) {
			$options['cap_type'] = 'rswp-shortcode';
		}
		if ( empty( $options['cap_type'] ) ) {
			$options['cap_type'] = 'rswp-shortcode';
		}

		$labels = array(
			'name'               => __( 'Shortcodes', $this->get_textdomain() ),
			'singular_name'      => __( 'Shortcode', $this->get_textdomain() ),
			'edit_item'          => __( 'Edit Shortcode', $this->get_textdomain() ),
			'new_item'           => __( 'New Shortcode', $this->get_textdomain() ),
			'view_item'          => __( 'View Shortcode', $this->get_textdomain() ),
			'search_items'       => __( 'Search Shortcodes', $this->get_textdomain() ),
			'not_found'          => __( 'No Shortcodes found', $this->get_textdomain() ),
			'not_found_in_trash' => __( 'No Shortcodes found in trash', $this->get_textdomain() ),
		);

		$post_type_options = array(
			//'post_type'            => 'rswp-shortcode',
			'labels'               => $labels,
			'description'          => __( 'Allows you to add shortcodes.', $this->get_textdomain() ),
			'public'               => false,
			'exclude_from_search'  => true,
			'publicly_queryable'   => false,
			'show_ui'              => true,
			'show_in_nav_menus'    => false,
			'show_in_menu'         => true,
			'show_in_admin_bar'    => false,
			'menu_icon'            => 'dashicons-migrate',
			'hierarchical'         => false,
			'supports'             => array( 'title', ),
			'register_meta_box_cb' => array( &$this, 'register_metaboxes' ),
			'has_archive'          => false,
			'can_export'           => true,
			'map_meta_cap'         => (bool) $options['map_meta_cap']
		);

		// set the capability_type to "post" if capabilities wont work (and if activated on the settings page)
		if ( ! (bool) $options['cap_handling'] ) {
			$capability_type                      = 'rswp-shortcode';
			$post_type_options['capability_type'] = $capability_type;
			$post_type_options['map_meta_cap']    = false;

			$post_type_options['capabilities'] = array(
				'edit_post'          => "edit_{$capability_type}",
				'read_post'          => "read_{$capability_type}",
				'delete_post'        => "delete_{$capability_type}",
				'delete_posts'       => "delete_{$capability_type}s",
				'edit_posts'         => "edit_{$capability_type}s",
				'edit_others_posts'  => "edit_others_{$capability_type}s",
				'publish_posts'      => "publish_{$capability_type}s",
				'create_posts'       => "create_{$capability_type}s",
				'read_private_posts' => "read_private_{$capability_type}s",
			);

		}

		register_post_type( 'rswp-shortcode', $post_type_options );

	}


	/**
	 * register_metaboxes function.
	 * This is called out of the create_custom_post_type function to register the metaboxes
	 * needed to edit the posts
	 *
	 * @access public
	 * @return void
	 * @since  1.0
	 */
	public function register_metaboxes() {

		// The options
		add_meta_box(
			'rs-options',
			__( 'Options', $this->get_textdomain() ),
			array( &$this, 'metabox_options_html' ),
			'rswp-shortcode',
			'normal',
			'core'
		);

		// The WP Info box
		add_meta_box(
			'rs-infos',
			__( 'Info & Help', $this->get_textdomain() ),
			array( &$this, 'metabox_infos_html' ),
			'rswp-shortcode',
			'side',
			'core'
		);
	}

	/**
	 * @param $post
	 *
	 * @since 1.1.3
	 */
	public function metabox_infos_html( $post ) {
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		// nonce fields are generated by the metabox_options_html field

		echo '<h4>' . __( 'Helpful links', $this->get_textdomain() ) . '</h4>';
		echo '<ul>'
		     . '<li><a href="http://bit.ly/XVnp0J" target="_blank">' . __( 'Installation manual', $this->get_textdomain() ) . '</a></li>'
		     . '<li><a href="http://bit.ly/XiXkdK" target="_blank">' . __( 'Frequently Asked Questions', $this->get_textdomain() ) . '</a></li>'
		     . '<li><a href="http://bit.ly/VIeLIK" target="_blank">' . __( 'Report a bug', $this->get_textdomain() ) . '</a></li>'
		     . '<li><a href="http://bit.ly/XVr4eY" target="_blank">' . __( 'Request a function', $this->get_textdomain() ) . '</a></li>'
		     . '<li><a href="http://bit.ly/YTk8FS" target="_blank">' . __( 'Submit a translation', $this->get_textdomain() ) . '</a></li>'
		     . '<li><a href="http://bit.ly/UlDG4t" target="_blank">' . __( 'More cool stuff by WPBuddy', $this->get_textdomain() ) . '</a></li>'
		     . '</ul>';

		echo '<h4>' . __( 'Like this plugin?', $this->get_textdomain() ) . '</h4>';


		echo '<iframe src="//www.facebook.com/plugins/like.php?href=http%3A%2F%2Fwp-buddy.com%2Fproducts%2Fplugins%2Frich-snippets-wordpress-plugin%2F&amp;send=false&amp;layout=button_count&amp;width=150&amp;show_faces=true&amp;font&amp;colorscheme=light&amp;action=like&amp;height=21" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:150px; height:21px;" allowTransparency="true"></iframe>';

		echo '<a href="https://twitter.com/share" class="twitter-share-button" data-url="http://wp-buddy.com/products/plugins/rich-snippets-wordpress-plugin/" data-text="Rich Snippets WordPress Plugin by WPBuddy">Tweet</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>';

		?>
		<!-- Place this tag where you want the +1 button to render. -->
		<br/>
		<div class="g-plusone" data-size="medium"
		     data-href="http://wp-buddy.com/products/plugins/rich-snippets-wordpress-plugin/"></div>

		<!-- Place this tag after the last +1 button tag. -->
		<script type="text/javascript">
			(function () {
				var po = document.createElement( 'script' );
				po.type = 'text/javascript';
				po.async = true;
				po.src = 'https://apis.google.com/js/plusone.js';
				var s = document.getElementsByTagName( 'script' )[ 0 ];
				s.parentNode.insertBefore( po, s );
			})();
		</script>
		<?php

		echo '<h4>' . __( 'Keep up do date with WPBuddy!', $this->get_textdomain() ) . '</h4>';

		global $current_user;
		get_currentuserinfo();
		$name = $current_user->user_firstname;
		if ( empty( $name ) ) {
			$name = $current_user->display_name;
		}

		echo '<div class="wpbuddy-cr-form" >
			<label for="text1210658">' . __( 'Your first name', $this->get_textdomain() ) . '</label> <input id="text1210658" name="t_209681" type="text" value="' . $name . '"  />
			<label for="text1210692">' . __( 'E-Mail', $this->get_textdomain() ) . '*</label> <input id="text1210692" name="email" value="' . $current_user->user_email . '" type="text"  />
			<a href="https://10955.cleverreach.com/f/54067/wcs/" target="_blank" class="button">' . __( 'Subscribe for free', $this->get_textdomain() ) . '</a>
			</div>';
	}

	/**
	 * metabox_options_html function.
	 * This is the function which displays the Options-Area on Shortcode Backend Pages
	 *
	 * @access public
	 *
	 * @param mixed $post
	 *
	 * @return void
	 * @since  1.0
	 */
	public function metabox_options_html( $post ) {

		if ( ! $post OR ! is_object( $post ) ) {
			return;
		}

		// create nonce for closed post boxes. if we dont do this php will bring up an error
		wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );

		// the same goes for the meta box ordering
		wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );

		// create the ajax none
		echo '<input type="hidden" value="' . wp_create_nonce( 'rswp-sanitize-ajax-nonce' ) . '" name="rswp_sanitize_ajax_nonce" id="rswp_sanitize_ajax_nonce" />';

		// crate the nonce for the actual operation
		wp_nonce_field( 'rswp-nonce', '_rswp_nonce', false );

		// Read the shortcode
		$shortcode = get_post_meta( $post->ID, 'rswp_shortcode', true );

		// Read the attributes from the post meta
		$attributes = get_post_meta( $post->ID, 'rswp_attributes', true );

		echo '<h4 class="rswp-usage-headline">' . __( 'Usage', $this->get_textdomain() ) . '</h4>';

		echo '<div id="rswp_shortcode" data-shortcodename="' . $shortcode . '"></div>';
		echo '<div id="rswp_usage"><textarea>' . $this->usage_code( $shortcode, $attributes ) . '</textarea></div>';


		echo '<h4 class="rswp-headline">' . __( 'Attributes', $this->get_textdomain() ) . '</h4>';

		echo '<div id="rswp-new-attribute-value" data-placeholder="' . __( 'Choose the attribute name', $this->get_textdomain() ) . '"></div>';

		echo '<div id="rswp-attributes">';

		if ( ! is_array( $attributes ) ) {
			$attributes = array();
		}

		// Display the current attributes
		foreach ( $attributes as $attribute ) {
			?>
			<a class="rswp-remove" href="#"><span class="rswp-icon rswp-icon-remove"></a>
			<input type="text" class="rswp-attribute" name="rswp_attributes[]" value="<?php echo $attribute; ?>"/>
			<?php
		}

		echo '</div>';
		echo '<a href="#" class="rswp-add-new"><span class="rswp-icon rswp-icon-add"></span>' . __( 'Add new attribute', $this->get_textdomain() ) . '</a>';
		echo '<h4 class="rswp-headline">' . __( 'HTML / PHP Code', $this->get_textdomain() ) . '</h4>';

		echo '<p>' . __( 'If your current PHP installation supports the eval() function you can use PHP in the textarea below. For this please always start with the &lt;?php tag. You can use the attributes as the name of the variables.', $this->get_textdomain() ) . '</p>';

		echo '<p>' . __( 'You can use the following placeholders:', $this->get_textdomain() ) . ' <span class="rswp-placeholders"><span class="button">[content]</span> '
		     . implode( ' ', array_map( array( &$this, 'callback_brackets' ), $attributes ) )
		     . '</span></p>';

		$code = get_post_meta( $post->ID, 'rswp_code', true );
		$code = esc_textarea( $code );

		echo '<textarea name="rswp_code" id="rswp_code" class="rswp-code">' . $code . '</textarea>';
		?>

		<script type="text/javascript">
			/* <![CDATA[ */

			var editor1 = CodeMirror.fromTextArea( document.getElementById( "rswp_code" ), {
				lineNumbers   : true,
				matchBrackets : true,
				mode          : "application/x-httpd-php",
				indentUnit    : 4,
				indentWithTabs: true,
				enterMode     : "keep",
				tabMode       : "shift"
			} );

			/* ]]> */
		</script>

		<?php

		echo '<h4 class="rswp-headline">' . __( 'CSS Stylesheets', $this->get_textdomain() ) . '</h4>';

		$css = get_post_meta( $post->ID, 'rswp_css', true );
		$css = esc_textarea( $css );

		echo '<textarea name="rswp_css" id="rswp_css" class="rswp-css">' . $css . '</textarea>';
		?>

		<script type="text/javascript">
			/* <![CDATA[ */

			var editor2 = CodeMirror.fromTextArea( document.getElementById( "rswp_css" ), {
				lineNumbers   : true,
				matchBrackets : true,
				mode          : "css",
				indentUnit    : 4,
				indentWithTabs: true,
				enterMode     : "keep",
				tabMode       : "shift"
			} );

			/* ]]> */
		</script>

		<?php
	}


	/**
	 * callback_brackets function.
	 * This is the callback function used in the metabox_options_html function above
	 *
	 * @access public
	 *
	 * @param string $v
	 *
	 * @return string
	 * @since  1.0
	 */
	public function callback_brackets( $v ) {
		return '<span class="button">[' . $v . ']</span>';
	}


	/**
	 * post_updaated_messages function.
	 * Adding the correct messages to the output
	 *
	 * @access public
	 *
	 * @param array $messages
	 *
	 * @return array
	 * @since  1.0
	 */
	public function post_updated_messages( $messages ) {

		$messages['rswp-shortcode'] = array(

			0  => '', // Unused. Messages start at index 1.

			1  => __( 'Shortcode updated.', $this->get_textdomain() ),
			2  => __( 'Custom field updated.', $this->get_textdomain() ),
			3  => __( 'Custom field deleted.', $this->get_textdomain() ),
			4  => __( 'Shortcode updated.', $this->get_textdomain() ),
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Shortcode restored to revision from %s', $this->get_textdomain() ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => __( 'Shortcode can now be used in posts.', $this->get_textdomain() ),
			7  => __( 'Shortcode saved.', $this->get_textdomain() ),
			8  => __( 'Shortcode submitted.', $this->get_textdomain() ),
			9  => __( 'Shortcode scheduled.', $this->get_textdomain() ),
			10 => __( 'Shortcode draft updated.', $this->get_textdomain() ),
		);

		return $messages;
	}


	/**
	 * manage_columns function.
	 * This manages the columns in shortcode post types (backend)
	 *
	 * @access public
	 *
	 * @param array $columns
	 *
	 * @return array
	 * @since  1.0
	 */
	public function manage_columns( $columns ) {
		unset( $columns['date'] );
		$columns['shortcode'] = __( 'Shortcode', $this->get_textdomain() );

		return $columns;
	}


	/**
	 * custom_column function.
	 * Adds the custom column content
	 *
	 * @access public
	 *
	 * @param $column_name
	 * @param $post_id
	 *
	 * @return void
	 * @since  1.0
	 */
	public function custom_column( $column_name, $post_id ) {
		switch ( $column_name ) {
			case 'shortcode':
				// Read the shortcode
				$shortcode = get_post_meta( $post_id, 'rswp_shortcode', true );

				// Read the attributes from the post meta
				$attributes = get_post_meta( $post_id, 'rswp_attributes', true );

				echo '<input class="rswp_column_shortcode" type="text" value="' . $this->usage_code( $shortcode, $attributes ) . '" />';
				break;
		}
	}


	/**
	 * save_post function.
	 * Adding some cool functions to the save_post function to the magic
	 *
	 * @access public
	 *
	 * @param int $post_id
	 *
	 * @return void
	 * @since  1.0
	 */
	public function save_post( $post_id ) {

		// is the current post type the shortcode-type?
		if ( get_post_type( $post_id ) != 'rswp-shortcode' ) {
			return;
		}

		// is the nonce set?
		if ( ! isset( $_REQUEST['_rswp_nonce'] ) ) {
			return;
		}

		// is the nonce valid?
		if ( ! wp_verify_nonce( $_POST['_rswp_nonce'], 'rswp-nonce' ) ) {
			return;
		}

		$options = get_option( 'rswp-settings' );
		if ( ! isset( $options['cap_type'] ) ) {
			$options['cap_type'] = 'rswp-shortcode';
		}

		if ( ! isset( $options['cap_handling'] ) ) {
			$options['cap_handling'] = 0;
		}

		// can current user edit posts?
		if ( ! (bool) $options['cap_handling'] && ! current_user_can( 'edit_' . $options['cap_type'], $post_id ) ) {
			return;
		}

		if ( isset( $_REQUEST['post_title'] ) ) {
			$title = $_REQUEST['post_title'];
		} else {
			$title = __( 'Not named shortcode', $this->get_textdomain() );
		}

		// create a shortcode out of the title
		$shortcode = $this->sanitize_shortcode( $title );

		// save the shortcode name
		update_post_meta( $post_id, 'rswp_shortcode', $shortcode );

		// check if there are attributes
		if ( ! isset( $_REQUEST['rswp_attributes'] ) ) {
			$_REQUEST['rswp_attributes'] = array();
		}

		$attributes = array();
		// loop the attributes to generate shortcodes
		foreach ( $_REQUEST['rswp_attributes'] as $attribute ) {
			if ( __( 'Choose the attribute name', $this->get_textdomain() ) == $attribute ) {
				continue;
			}
			if ( empty( $attribute ) ) {
				continue;
			}
			$attributes[] = $this->sanitize_attribute( $attribute );
		}

		update_post_meta( $post_id, 'rswp_attributes', $attributes );

		update_post_meta( $post_id, 'rswp_code', $_REQUEST['rswp_code'] );

		update_post_meta( $post_id, 'rswp_css', $_REQUEST['rswp_css'] );


	}


	/**
	 * sanitize_shortcode function.
	 *
	 * @access private
	 *
	 * @param string $title
	 *
	 * @return string
	 *
	 * @since  1.0
	 */
	private function sanitize_shortcode( $title ) {
		// crate a good shortcode out of the title
		$shortcode = sanitize_key( $title );

		/* If the shortcode exists already we run through an "endless" while loop
		 * and add numbers at the and as long as the shortcode does not exists
		 */
		if ( $this->shortcode_exists( $shortcode ) ) {
			$i = 1;
			while ( true ) {
				if ( $this->shortcode_exists( $shortcode . '_' . $i ) ) {
					$i ++;
				} else {
					$shortcode .= '_' . $i;
					break;
				}
			}
		}

		return strtolower( $shortcode );
	}


	/**
	 * sanitize_attribute function.
	 * Sanitizes the attribute names
	 *
	 * @access private
	 *
	 * @param string $attr_name
	 *
	 * @return string
	 * @since  1.0
	 */
	private function sanitize_attribute( $attr_name ) {
		$attribute_name = str_replace( '-', '', sanitize_key( $attr_name ) );
		if ( 'content' == $attribute_name ) {
			$attribute_name = 'content2';
		}

		return $attribute_name;
	}

	/**
	 * shortcode_exists function.
	 * Checks whether a shortcode exists already
	 *
	 * @access private
	 *
	 * @param bool $shortcode (default: false)
	 *
	 * @return bool
	 * @since  1.0
	 */
	private function shortcode_exists( $shortcode = false ) {
		global $shortcode_tags;

		if ( ! $shortcode ) {
			return false;
		}

		// is the shortcode in the shortcode_tags global?
		if ( array_key_exists( $shortcode, $shortcode_tags ) ) {

			// if yes, check whether it is a custom shortcode tag
			$shortcode_posts = get_posts( array(
				'numberposts' => - 1,
				'post_type'   => 'rswp-shortcode'
			) );

			foreach ( $shortcode_posts as $shortcode_post ) {
				if ( get_post_meta( $shortcode_post->ID, 'rswp_shortcode', true ) == $shortcode ) {
					return false;
				}
			}

			// it's not a custom shortcode tag so return true because it was found in the global $shortcode_tags
			return true;

		}

		// shortcode tag was not found
		return false;
	}


	/**
	 * ajax_sanitize function.
	 * This will sanitize the attribute value on thy fly
	 *
	 * @access public
	 * @return void
	 * @since  1.0
	 */
	public function ajax_sanitize() {

		// check if there is a legal request
		// if not the script will die
		if ( ! isset( $_REQUEST['security'] ) ) {
			die();
		}

		check_ajax_referer( 'rswp-sanitize-ajax-nonce', 'security' );

		if ( ! isset( $_REQUEST['do'] ) ) {
			die();
		}

		switch ( $_REQUEST['do'] ) {

			case 'sanitize_title':
				if ( ! isset( $_REQUEST['title'] ) ) {
					wp_send_json_error();
				}
				if ( empty( $_REQUEST['title'] ) ) {
					$_REQUEST['title'] = '';
				} else {
					$_REQUEST['title'] = $this->sanitize_shortcode( $_REQUEST['title'] );
				}
				wp_send_json_success( array( 'action' => 'sanitized', 'title' => $_REQUEST['title'] ) );
				break;

			case 'sanitize_attribute':
			default:
				if ( ! isset( $_REQUEST['attribute_name'] ) ) {
					wp_send_json_error();
				}
				wp_send_json_success( array(
					'action'         => 'sanitized',
					'attribute_name' => $this->sanitize_attribute( $_REQUEST['attribute_name'] )
				) );

		}

		wp_send_json_error();
	}


	/**
	 * usage_code function.
	 * Will generate the usage code for the given shortcode
	 *
	 * @access private
	 *
	 * @param string $shortcode_name
	 * @param array  $attributes (default: array())
	 * @param bool   $html       (default: true)
	 *
	 * @return string
	 * @since  1.0
	 */
	private function usage_code( $shortcode_name, $attributes = array(), $html = true ) {

		if ( empty( $shortcode_name ) ) {
			return __( 'No shortcode so far', $this->get_textdomain() );
		}

		$usage_code = '[' . $shortcode_name . ' ' . implode( ' ', array_map( array(
				&$this,
				'callback_attributes'
			), $attributes ) ) . '][/' . $shortcode_name . ']';

		// Convert special characters to HTML entities
		if ( $html ) {
			return esc_textarea( $usage_code );
		}

		return $usage_code;
	}


	/**
	 * callback_attributes function.
	 * this is the callback function used in the usage_code function above
	 *
	 * @access public
	 *
	 * @param string $v
	 *
	 * @return string
	 * @since  1.0
	 */
	public function callback_attributes( $v ) {
		return $v . '=""';
	}


	/**
	 * add_shortcodes function.
	 * Will add the shortcodes globally to wp
	 *
	 * @access private
	 * @return void
	 * @since  1.0
	 */
	private function add_shortcodes() {

		$shortcode_posts = get_posts( array(
			'numberposts' => - 1,
			'post_type'   => 'rswp-shortcode'
		) );


		if ( count( $shortcode_posts ) <= 0 ) {
			return;
		}


		foreach ( $shortcode_posts as $shortcode_post ) {
			// Read the shortcode
			$shortcode = get_post_meta( $shortcode_post->ID, 'rswp_shortcode', true );

			// Read the attributes from the post meta
			$this->_shortcodes[ $shortcode ]['attributes'] = get_post_meta( $shortcode_post->ID, 'rswp_attributes', true );

			// get the code
			$this->_shortcodes[ $shortcode ]['code'] = get_post_meta( $shortcode_post->ID, 'rswp_code', true );

			add_shortcode( $shortcode, array( &$this, 'do_shortcode_' . $shortcode ) );

		}

	}


	/**
	 * __call function.
	 * Calls the shortcode functions that do not really exist
	 *
	 * @access public
	 *
	 * @param string $fname
	 * @param array  $arguments
	 *
	 * @return string
	 * @since  1.0
	 */
	public function __call( $fname, $arguments ) {

		// do not call functions that are not shortcodes
		if ( stripos( $fname, 'do_shortcode_' ) === false ) {
			return;
		}

		$options = get_option( 'rswp-settings' );
		if ( ! isset( $options['feed'] ) ) {
			$options['feed'] = false;
		}

		if ( is_feed() && (bool) $options['feed'] ) {
			return '';
		}

		$atts    = $arguments[0];
		$content = $arguments[1];

		// get the shortcode name
		$shortcode = str_replace( 'do_shortcode_', '', $fname );

		// this is needed for the shortcode_atts function
		$attributes = array();

		// building standard value definitions out of the attributes
		foreach ( $this->_shortcodes[ $shortcode ]['attributes'] as $attribute ) {
			$attributes[ $attribute ] = '';
		}

		// crate variables out of the attributes
		extract( shortcode_atts( array_map( array(
			&$this,
			'callback_empty'
		), array_flip( $this->_shortcodes[ $shortcode ]['attributes'] ) ), $atts ) );

		// get the shortcode HTML code
		$code = $this->_shortcodes[ $shortcode ]['code'];

		// get all php codes
		preg_match_all( "/<\?.*?\?>/s", $code, $matches );

		// do the php stuff
		if ( isset( $matches[0] ) ) {
			foreach ( $matches[0] as $phpcode ) {
				$evalcode = str_replace( '<?php', '', str_replace( '?>', '', $phpcode ) );

				ob_start();
				eval( $evalcode );
				$eval_results = ob_get_contents();
				ob_end_clean();

				$code = str_replace( $phpcode, $eval_results, $code );
			}
		};

		// replace all attributes in brackets
		foreach ( $this->_shortcodes[ $shortcode ]['attributes'] as $attribute_name ) {

			if ( ! isset( $atts[ $attribute_name ] ) ) {
				$code = str_replace( '[' . $attribute_name . ']', '', $code );
				continue;
			}

			$code = str_replace( '[' . $attribute_name . ']', $atts[ $attribute_name ], $code );

		}

		// replace the content
		$code = str_replace( '[content]', do_shortcode( $content ), $code );

		// replace line breaks and white spaces from the output
		$code = str_replace( array( "\r\n", "\r", "\n" ), "", $code );

		return $code;
	}


	/**
	 * callback_empty function.
	 * This is the callback function used in the __call function above
	 *
	 * @access public
	 *
	 * @param string $v
	 *
	 * @return string
	 * @since  1.0
	 */
	public function callback_empty( $v ) {
		return '';
	}

	/**
	 * add_css function.
	 * Adds CSS to the Head of Wordpress Frontend
	 *
	 * @access public
	 * @return void
	 * @since  1.0
	 */
	public function add_css() {

		// get all shortcodes
		$shortcode_posts = get_posts( array(
			'numberposts' => - 1,
			'post_type'   => 'rswp-shortcode'
		) );

		echo '<style type="text/css">';

		foreach ( $shortcode_posts as $shortcode_post ) {
			echo get_post_meta( $shortcode_post->ID, 'rswp_css', true ) . chr( 10 );
		}

		echo '</style>';
	}


	/**
	 * install_examples function.
	 * This installs the examples and moves the old post types (rswp-shortcodes) to the new shortcode-types
	 * (rswp-shortcode) (please notice the "s" at the end)
	 *
	 * @access public
	 * @return void
	 * @since  1.0
	 */
	public function install_examples() {
		$just_activated = (bool) get_option( 'rswp_just_activated', true );
		if ( ! $just_activated ) {
			return;
		}

		// get all posts of post type rswp-shortcode
		$shortcode_posts = get_posts( array(
			'numberposts' => - 1,
			'post_type'   => 'rswp-shortcode'
		) );

		$found = array();
		// check if there are already shortcodes named in examples
		foreach ( $shortcode_posts as $shortcode_post ) {
			// get the shortcode
			$shortcode_post_shortcode = get_post_meta( $shortcode_post->ID, 'rswp_shortcode', true );

			// check if the shortcode exists in the posts
			if ( array_key_exists( $shortcode_post_shortcode, $this->examples() ) ) {
				$found[ $shortcode_post_shortcode ] = true;
			}

		}

		// install event if not found
		if ( count( $found ) < $this->examples() ) {

			foreach ( $this->examples() as $example ) {

				// jump to the next example if it exsits already
				if ( isset( $found[ $example['shortcode'] ] ) ) {
					continue;
				}

				// create the post and fetch the id
				$post_id = wp_insert_post( array(
						'post_title'  => $example['title'],
						'post_type'   => 'rswp-shortcode',
						'post_status' => 'publish',
						'post_author' => get_current_user_id()
					)
				);

				// write the other stuff which is needed
				if ( is_int( $post_id ) ) {

					update_post_meta( $post_id, 'rswp_shortcode', $example['shortcode'] );

					update_post_meta( $post_id, 'rswp_attributes', $example['attributes'] );

					update_post_meta( $post_id, 'rswp_code', $example['code'] );

					update_post_meta( $post_id, 'rswp_css', $example['css'] );
				}
			}

		}

		// for dont doing this again and again deactivate the function
		update_option( 'rswp_just_activated', false );

	}


	/**
	 * update_capabilities function.
	 * This updates the capabilities for the roles
	 *
	 * @access public
	 * @return void
	 * @since  1.0
	 */
	public function update_capabilities() {

		$options = get_option( 'rswp-settings' );

		// this prevents the plugin to add caps again and again
		// we only do this if the shortcode caps have changed
		if ( ( isset( $options['roles_updated'] ) && 1 == $options['roles_updated'] )
		     OR ! isset( $options['roles_updated'] )
		) {

			// Standard capabilities table
			if ( ! isset( $options['capabilities'] ) ) {
				$options['capabilities'] = $this->_standard_capabilities;
			}
			if ( ! is_array( $options['capabilities'] ) ) {
				$options['capabilities'] = $this->_standard_capabilities;
			}

			// this are all the selected capabilities
			$selected = $options['capabilities'];

			global $wp_roles;

			$rswp_shortcode_type = get_post_type_object( 'rswp-shortcode' );

			// run through all roles
			foreach ( $wp_roles->roles as $wp_role_name => $wp_role ) {

				// run through all shortcode capabilities
				foreach ( $rswp_shortcode_type->cap as $cap_name => $real_cap_name ) {

					// check if the current capability was checked by the administrator
					if ( isset( $selected[ $wp_role_name ][ $real_cap_name ] ) ) {
						$grant = true;
					} else {
						$grant = false;
					}

					// add the capability to the database for later use
					$wp_roles->add_cap( $wp_role_name, $real_cap_name, $grant );
				}
			}
		}

		// this prevents the plugin to add caps again and again
		// we only do this if the shortcode caps have changed
		$options['roles_updated'] = 0;
		update_option( 'rswp-settings', $options );
	}

	/**
	 * register_settings function.
	 * Registers all the settings for the plugin
	 *
	 * @access public
	 * @return void
	 * @since  1.0
	 */
	public function register_settings() {

		// register a setting
		register_setting( 'rswp_settings_group', 'rswp-settings' );

		$options = get_option( 'rswp-settings' );
		if ( ! isset( $options['cap_handling'] ) ) {
			$options['cap_handling'] = 0;
		}

		// add section for contentbox 1
		add_settings_section(
			'rswp_settings_section',
			'',
			array( &$this, 'contentbox_1_section_text' ),
			'rswp-settings'
		// rswp-shortcode_page_rswp-shortcode-settings
		);

		add_settings_field(
			'handling',
			__( 'Disable capability handling', $this->get_textdomain() ),
			array( &$this, 'field_handling' ),
			'rswp-settings',
			'rswp_settings_section'
		);

		if ( (bool) $options['cap_handling'] ) {
			add_settings_field(
				'cap_type',
				__( 'Capability type', $this->get_textdomain() ),
				array( &$this, 'field_cap_type' ),
				'rswp-settings',
				'rswp_settings_section'
			);

			add_settings_field(
				'map_meta_cap',
				__( 'Meta Cap Map', $this->get_textdomain() ),
				array( &$this, 'field_map_meta_cap' ),
				'rswp-settings',
				'rswp_settings_section'
			);
		}

		add_settings_field(
			'roles',
			__( 'Match the roles with the capabilities', $this->get_textdomain() ),
			array( &$this, 'field_roles' ),
			'rswp-settings',
			'rswp_settings_section'
		);

		add_settings_field(
			'roles_updated',
			'',
			array( &$this, 'field_roles_updated' ),
			'rswp-settings',
			'rswp_settings_section'
		);

		add_settings_field(
			'feed',
			__( 'Disable shortcodes on feed', $this->get_textdomain() ),
			array( &$this, 'field_feed' ),
			'rswp-settings',
			'rswp_settings_section'
		);

		add_settings_field(
			'purchase_code',
			__( 'Purchase Code', $this->get_textdomain() ),
			array( &$this, 'field_purchase_code' ),
			'rswp-settings',
			'rswp_settings_section',
			array( 'label_for' => 'purchase_code' )
		);
	}


	/**
	 * contentbox_1_section_text function.
	 * Displays some text in front of the contentbox 1
	 * This is not needed at the moment. Unfortunately WordPress does not accept an empty string for the function name
	 *
	 * @access public
	 *
	 * @param array $args
	 *
	 * @return void
	 * @since  1.0
	 */
	public function contentbox_1_section_text( $args ) {
	}


	/**
	 * admin_menu function.
	 * This creates the admin menus.
	 * One is made directly underneath the "Shortcodes" menu
	 * And another one is made directly underneath the Settings menu
	 *
	 * @access public
	 * @return void
	 * @since  1.0
	 */
	public function admin_menu() {

		$this->_pagehook = add_submenu_page(
			'edit.php?post_type=rswp-shortcode',
			__( 'Settings', $this->get_textdomain() ),
			__( 'Settings', $this->get_textdomain() ),
			'manage_options',
			'rswp-settings',
			array( &$this, 'settings_page' )
		);

		$this->_settings_menu_slug = add_options_page(
			__( 'Shortcodes', $this->get_textdomain() ),
			__( 'Shortcodes', $this->get_textdomain() ),
			'manage_options',
			'rswp-settings',
			array( &$this, 'settings_page' )
		);

		$options = get_option( 'rswp-settings' );
		if ( ! isset( $options['cap_handling'] ) ) {
			$options['cap_handling'] = 0;
		}

		if ( (bool) $options['cap_handling'] ) {
			return;
		}

		// remove the "Add new" buttons in the menu for the users which cannot create_posts
		// note: "create_posts" ist not an official wordpress capability
		// but it's discussed here: @url http://core.trac.wordpress.org/ticket/16714
		if ( ! current_user_can( 'create_rswp-shortcodes' ) ) {
			global $submenu;
			if ( isset( $submenu['edit.php?post_type=rswp-shortcode'][10] ) ) {
				unset( $submenu['edit.php?post_type=rswp-shortcode'][10] );
			}
		}

		// redirect the users who cannot "add new"
		$result = stripos( $_SERVER['REQUEST_URI'], 'post-new.php?post_type=rswp-shortcode' );
		if ( $result !== false && ! current_user_can( 'create_rswp-shortcodes' ) ) {
			wp_redirect( 'edit.php?post_type=rswp-shortcode&permissions_error=true' );
		}

	}


	/**
	 * settings_page function.
	 * This displays the settings page for the shortcodes
	 *
	 * @access public
	 * @return void
	 * @since  1.0
	 */
	public function settings_page() {

		add_meta_box( 'rswp-settings-contentbox-1', __( 'General', $this->get_textdomain() ), array(
			&$this,
			'contentbox_1'
		), $this->_settings_menu_slug, 'normal', 'core' );

		// get the columns
		global $screen_layout_columns;

		?>

		<div class="wrap" id="rswp-settings">

			<?php screen_icon( 'options-general' ); ?>
			<h2><?php echo __( 'Shortcode settings', $this->get_textdomain() ); ?></h2><br/>

			<form action="options.php" method="post" class="rswp-settings-form">

				<?php
				wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
				wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );

				settings_fields( 'rswp_settings_group' );
				?>

				<div id="poststuff" class="metabox-holder">

					<div id="post-body">
						<div id="post-body-content">
							<?php do_meta_boxes( $this->_settings_menu_slug, 'normal', array() );
							echo '<p style="clear:both;">';
							submit_button();
							echo '</p>'; ?>
						</div>
					</div>

					<br class="clear"/>

				</div>
				<!-- poststuff -->

			</form>

		</div><!-- wrap -->

		<?php

	}


	/**
	 * Displays the field to deactivate the capability handling
	 *
	 * @since 1.2.2
	 */
	public function field_handling() {
		$options = get_option( 'rswp-settings' );
		if ( ! isset( $options['cap_handling'] ) ) {
			$options['cap_handling'] = 0;
		}
		?>
		<input type="checkbox" value="1" id="cap_handling"
		       name="rswp-settings[cap_handling]" <?php if ( (bool) $options['cap_handling'] ) {
			echo 'checked="checked"';
		} ?> />
		<?php
		echo '<p class="description">' . __( 'Activate this checkbox if you have problems with the capability-functionality (ex. if the menu does not appear).', $this->get_textdomain() ) . '</p>';
	}


	/**
	 * Displays the field to overwrite the cap type
	 *
	 * @since 1.2.2
	 */
	public function field_cap_type() {
		$options = get_option( 'rswp-settings' );
		if ( ! isset( $options['cap_type'] ) ) {
			$options['cap_type'] = 'rswp-shortcode';
		}
		if ( empty( $options['cap_type'] ) ) {
			$options['cap_type'] = 'rswp-shortcode';
		}
		?>
		<input type="text" class="regular-text" value="<?php echo $options['cap_type']; ?>" id="cap_type"
		       name="rswp-settings[cap_type]"/>
		<?php
		echo '<p class="description">' . __( 'Keep this at "rswp-shortcode" if possible', $this->get_textdomain() ) . '</p>';
	}


	/**
	 * Displays the field to deactivate the map_meta_cap
	 *
	 * @since 1.2.2
	 */
	public function field_map_meta_cap() {
		$options = get_option( 'rswp-settings' );
		if ( ! isset( $options['map_meta_cap'] ) ) {
			$options['map_meta_cap'] = 0;
		}
		?>
		<input type="checkbox" value="1" id="map_meta_cap"
		       name="rswp-settings[map_meta_cap]" <?php if ( (bool) $options['map_meta_cap'] ) {
			echo 'checked="checked"';
		} ?> />
		<?php
		echo '<p class="description">' . __( 'Activate to set the "map_meta_cap" value to true.', $this->get_textdomain() ) . '</p>';
	}


	/**
	 * field_roles function.
	 * This displays the area where the roles and capabilities are shown
	 *
	 * @access public
	 * @return void
	 * @since  1.0
	 */
	public function field_roles() {

		$options = get_option( 'rswp-settings' );
		if ( ! isset( $options['cap_handling'] ) ) {
			$options['cap_handling'] = 0;
		}

		// Standard capabilities table
		if ( ! isset( $options['capabilities'] ) ) {
			$options['capabilities'] = $this->_standard_capabilities;
		}
		if ( ! is_array( $options['capabilities'] ) ) {
			$options['capabilities'] = $this->_standard_capabilities;
		}

		$selected = $options['capabilities'];

		$rswp_shortcode_type = get_post_type_object( 'rswp-shortcode' );

		echo '<p class="description" id="cap_handling_hint" style="' . ( ( (bool) $options['cap_handling'] ) ? '' : 'display:none;' ) . '">' . __( 'Deactivate the above checkbox and hit the save button to see this options.', $this->get_textdomain() ) . '</p>';

		echo '<table align="left" border="0" cellpadding="1" cellspacing="1" class="rswp-roles-table" style="' . ( ( (bool) $options['cap_handling'] ) ? 'display:none;' : '' ) . '"><thead><tr>';

		echo '<th scope="col">' . __( 'Role/Capability', $this->get_textdomain() ) . '</th>';

		foreach ( $rswp_shortcode_type->cap as $cap_name => $real_cap_name ) {
			$title = str_replace( '_', ' ', str_replace( '_post', '_shortcode', str_replace( '_posts', '_shortcodes', $cap_name ) ) );
			echo '<th scope="col" class="vertical" title="' . $title . '"><div class="rotated">' . $title . '</div></th>';
		}

		echo '</tr></thead><tbody>';

		foreach ( get_editable_roles() as $role => $role_details ) {
			$role      = esc_attr( $role );
			$role_name = translate_user_role( $role_details['name'] );
			echo '<tr><td>' . $role_name . '</td>';
			foreach ( $rswp_shortcode_type->cap as $cap_name => $real_cap_name ) {
				$s = '';
				if ( isset( $selected[ $role ][ $real_cap_name ] ) && $selected[ $role ][ $real_cap_name ] ) {
					$s = 'checked="checked"';
				}
				echo '<td scope="col"><input class="' . $real_cap_name . '" title="' . str_replace( '_', ' ', str_replace( '_post', '', str_replace( '_posts', '', $cap_name ) ) ) . '" ' . $s . ' name="rswp-settings[capabilities][' . $role . '][' . $real_cap_name . ']" type="checkbox" value="1" /></td>';
			}

			echo '</tr>';
		}


		echo '</tbody></table>';

	}

	/**
	 * Displays the field to deactivate the capability handling
	 *
	 * @since 1.6.0
	 */
	public function field_feed() {
		$options = get_option( 'rswp-settings' );
		if ( ! isset( $options['feed'] ) ) {
			$options['feed'] = 0;
		}
		?>
		<input type="checkbox" value="1" id="feed"
		       name="rswp-settings[feed]" <?php checked( $options['feed'], 1 ); ?> />
		<?php
		echo '<p class="description">' . __( 'Activate this checkbox if you want to deactivate rich snippets shortcodes when the feed is displayed.', $this->get_textdomain() ) . '</p>';
	}


	/**
	 * The purchase code field.
	 *
	 * @since unknown
	 */
	public function field_purchase_code() {
		$options = get_option( 'rswp-settings' );
		if ( ! isset( $options['purchase_code'] ) ) {
			$options['purchase_code'] = '';
		}
		?>
		<input type="text" class="regular-text" value="<?php echo $options['purchase_code']; ?>" id="purchase_code"
		       name="rswp-settings[purchase_code]"/>
		<?php
		echo '<p class="description">' . __( 'Enter your purchase code in order to get automatic updates!', $this->get_textdomain() ) . ' <a target="_blank" href="http://bit.ly/X4d4n6">' . __( 'Click here if you do not know where to find your purchase code.', $this->get_textdomain() ) . '</a></p>';
	}


	/**
	 * field_roles_updated function.
	 * This is a hidden field that checks whether the roles have been updated
	 * This sets a flag to write the new capabilities into the wp_roles global (which saves everything into the db)
	 *
	 * @see    function update_capabilities()
	 *
	 * @access public
	 * @return void
	 * @since  1.0
	 */
	public function field_roles_updated() {
		echo '<input type="hidden" name="rswp-settings[roles_updated]" value="1" />';
	}

	/**
	 * contentbox_1 function.
	 * This calls the contentbox 1 and outputs the settings in this area
	 *
	 * @access public
	 *
	 * @param mixed $data
	 *
	 * @return void
	 * @since  1.0
	 */
	public function contentbox_1( $data ) {
		do_settings_sections( 'rswp-settings' );
	}


	/**
	 * Includes the styles
	 *
	 * @since 1.0
	 */
	public function admin_head() {
		global $typenow;
		if ( 'rswp-shortcode' != $typenow ) {
			return;
		}

		echo '<style>.add-new-h2{display: none;}</style>';
	}


	/**
	 * admin_notices function.
	 * Adds admin notices
	 *
	 * @access public
	 * @return void
	 * @since  1.0
	 */
	public function admin_notices() {

		// add the permission error message if a user wants to add new post types
		if ( isset( $_GET['permissions_error'] ) && $_GET['permissions_error'] ) {
			echo "<div id='permissions-warning' class='error fade'><p><strong>" . __( 'You have been redirected because you do not have permission to access that page.', $this->get_textdomain() ) . "</strong></p></div>";
		}
	}

	/**
	 * examples function.
	 * A list of examples which will be installed after activation
	 *
	 * @access private
	 * @return array
	 * @since  1.0
	 */
	private function examples() {
		return array(
			'event'          => array(
				'shortcode'  => 'event',
				'title'      => 'Event',
				'attributes' => array(
					'name',
					'street',
					'zip',
					'locality',
					'country',
					'url',
					'start_date',
					'end_date',
					'image',
					'latitude',
					'longitude',
					'organization'
				),
				'code'       => file_get_contents( $this->get_plugin_path() . 'assets/examples/event.txt' ),
				'css'        => '',
			),
			'rating'         => array(
				'shortcode'  => 'rating',
				'title'      => 'Rating',
				'attributes' => array( 'itemreviewed', 'rating', 'reviewer', 'dtreviewed', 'best', 'worst' ),
				'code'       => file_get_contents( $this->get_plugin_path() . 'assets/examples/rating.txt' ),
				'css'        => '',
			),
			'address'        => array(
				'shortcode'  => 'address',
				'title'      => 'Address',
				'attributes' => array( 'streetaddress', 'locality', 'region', 'postalcode', 'countryname' ),
				'code'       => file_get_contents( $this->get_plugin_path() . 'assets/examples/address.txt' ),
				'css'        => '',
			),
			'people'         => array(
				'shortcode'  => 'people',
				'title'      => 'People',
				'attributes' => array( 'name', 'photo', 'title', 'role', 'url', 'affiliation' ),
				'code'       => file_get_contents( $this->get_plugin_path() . 'assets/examples/people.txt' ),
				'css'        => '',
			),
			'product'        => array(
				'shortcode'  => 'product',
				'title'      => 'Product',
				'attributes' => array( 'name', 'image', 'description', 'brand', 'identifier', 'price' ),
				'code'       => file_get_contents( $this->get_plugin_path() . 'assets/examples/product.txt' ),
				'css'        => '',
			),
			'musicrecording' => array(
				'shortcode'  => 'musicrecording',
				'title'      => 'MusicRecording',
				'attributes' => array(
					'name',
					'minutes',
					'seconds',
					'userplays',
					'playurl',
					'buyurl',
					'albumurl',
					'albumname',
					'url'
				),
				'code'       => file_get_contents( $this->get_plugin_path() . 'assets/examples/musicrecording.txt' ),
				'css'        => '',
			),
			'musicgroup'     => array(
				'shortcode'  => 'musicgroup',
				'title'      => 'MusicGroup',
				'attributes' => array( 'albumname', 'actor' ),
				'code'       => file_get_contents( $this->get_plugin_path() . 'assets/examples/musicgroup.txt' ),
				'css'        => '',
			),
			'recipe'         => array(
				'shortcode'  => 'recipe',
				'title'      => 'Recipe',
				'attributes' => array(
					'name',
					'recipetype',
					'photo',
					'published',
					'preptime',
					'cooktime',
					'totaltime',
					'nutrition',
					'yield',
					'ingredients',
					'summary',
					'rating',
					'count',
					'author',
					'thumbnail'
				),
				'code'       => file_get_contents( $this->get_plugin_path() . 'assets/examples/recipe.txt' ),
				'css'        => '',
			),
		);
	}

	/**
	 * Returns the purchase code
	 *
	 * @return string
	 * @since 1.3
	 */
	public function get_purchase_code() {
		$options = get_option( 'rswp-settings' );
		if ( ! isset( $options['purchase_code'] ) ) {
			return '';
		}

		return $options['purchase_code'];
	}

	/**
	 * Adds the editor button
	 *
	 * @since 1.4
	 */
	public function editor_button() {
		if ( get_user_option( 'rich_editing' ) == true ) {
			add_filter( "mce_external_plugins", array( &$this, "add_mce_plugin" ) );
			add_filter( 'mce_buttons', array( &$this, 'register_mce_buttons' ) );
		}
	}

	/**
	 * add_mce_plugin function.
	 * Adds the plugin to tinymce
	 *
	 * @access public
	 * @since  1.4
	 *
	 * @param mixed $plugin_array
	 *
	 * @return void
	 *
	 */
	public function add_mce_plugin( $plugin_array ) {
		$plugin_array['wpb_rich_snippets'] = $this->plugins_url( 'assets/js/editor-button.js', $this->_plugin_file );

		return $plugin_array;
	}

	/**
	 * register_mce_buttons function.
	 * Adds the button to the editor
	 *
	 * @access public
	 * @since  1.4
	 *
	 * @param mixed $buttons
	 *
	 * @return void
	 */
	public function register_mce_buttons( $buttons ) {
		array_push( $buttons, "wpb_rich_snippets" );

		return $buttons;
	}


	public function admin_footer_window() {
		global $pagenow;
		if ( ! isset( $pagenow ) ) {
			return;
		}
		if ( 'post.php' == $pagenow OR 'post-new.php' == $pagenow ):

			?>
			<div id="wpb_rich_snippets_window">
				<div class="media-modal wp-core-ui">
					<a title="Close" href="#" class="media-modal-close"><span class="media-modal-icon"></span></a>

					<div class="media-modal-content">
						<div class="media-frame-title">
							<h1><?php _e( 'Add Shortcodes', $this->get_textdomain() ); ?></h1>
						</div>
						<div class="media-frame-content">
							<?php
							/**
							 * @var WP_Post $post
							 */
							$i = 0;
							foreach (
								get_posts(
									array(
										'post_type'      => 'rswp-shortcode',
										'posts_per_page' => - 1
									)
								) as $post
							):
								?>
								<div class="rich-snippets-div"
								     data-shortcode_name="<?php echo get_post_meta( $post->ID, 'rswp_shortcode', true ); ?>">
									<div class="rich-snippets-div-header">
										<a href="#"><?php echo $post->post_title; ?></a>
									</div>
									<div class="rich-snippets-div-content">
										<?php
										$attributes = get_post_meta( $post->ID, 'rswp_attributes', true );
										if ( ! is_array( $attributes ) ) {
											_e( 'There are no attributes for this shortcode', $this->get_textdomain() );
										} else {
											foreach ( $attributes as $attribute ) {
												echo '<div class="rich-snippets-div-content-attribute">'
												     . '<label for="rsdca_' . $i . '">' . $attribute . '</label>'
												     . '<input id="rsdca_' . $i . '" type="text" data-attribute_name="' . $attribute . '" value="" />'
												     . '</div>';
												$i ++;
											}
										}
										?>
										<div class="rich-snippets-div-content-attribute">
											<textarea></textarea>
										</div>
									</div>
								</div>
								<?php
								$i ++;
							endforeach;
							?>
						</div>
						<div class="media-frame-toolbar">
							<div class="media-toolbar">
								<div class="media-toolbar-secondary"></div>
								<div class="media-toolbar-primary">
									<a href="#"
									   class="button media-button button-primary button-large media-button-select"
									   disabled="disabled"><?php _e( 'Insert', $this->get_textdomain() ); ?></a>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="media-modal-backdrop"></div>
			</div>
			<?php
		endif;
	}
}



