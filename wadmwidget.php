<?php
/**
 * @package wadmwidget
 * @version 1.4
 */
/*
Plugin Name: Phototools: wadmwidget
Plugin URI: https://www.funsite.eu/plugins/
Description: Add a link to your work at Werk aan de Muur / Oh my Prints. Just add the Workcode to the post.
Author: Gerhard Hoogterp
Version: 1.4
Author URI: https://www.funsite.eu/
*/

if (!defined('WPINC')) {
	die;
}

class wadmwidget_class {

	const FS_TEXTDOMAIN      = 'wadmwidget';
	const FS_PLUGINID        = 'wadmwidget';

	const userAgent          = 'WadM Widget 1.1';

	const availableLanguages = array(
		'nl'                   => 'Nederlands',
		'de'                   => 'Deutsch',
		'fr'                   => 'français'
	);
	const domains            = array(
		'nl'                   => 'https://werkaandemuur.nl/art/nl/%s/shop',
		'de'                   => 'https://www.ohmyprints.com/art/de/%s',
		'fr'                   => 'https://www.ohmyprints.com/art/fr/%s'
	);
	
	const apidomains            = array(
		'nl'                   => 'https://www.werkaandemuur.nl/api',
		'de'                   => 'https://www.ohmyprints.com/api',
		'fr'                   => 'https://www.ohmyprints.com/api'
	);
	const sites              = array(
		'nl'                   => 'Werk aan de Muur',
		'de'                   => 'Oh my Prints',
		'fr'                   => 'Oh my Prints'
	);
	
	

	public $posttypelist      = array(
		'posts'
	);

	public function __construct() {

		register_activation_hook(__FILE__, array(
			$this,
			'activate'
		));
		register_deactivation_hook(__FILE__, array(
			$this,
			'deactivate'
		));

		add_action('init', array(
			$this,
			'myTextDomain'
		));

		add_action('admin_menu', array(
			$this,
			'add_phototools_menuitem'
		));
		add_action('admin_init', array(
			$this,
			'register_settingspage'
		));

		add_action('admin_menu', array(
			$this,
			'create_workcode_box'
		));
		add_action('save_post', array(
			$this,
			'save_wadmw_workcode'
		) , 10, 2);

		add_action('admin_init', array(
			$this,
			'hook_wadmw_post_column'
		));
		add_action('admin_print_scripts-edit.php', array(
			$this,
			'enqueue_edit_scripts'
		));
		add_filter('post_row_actions', array(
			$this,
			'quickedit_set_data'
		) , 10, 2);
		add_action('quick_edit_custom_box', array(
			$this,
			'add_quick_edit'
		) , 10, 2);

		add_action('restrict_manage_posts', array(
			$this,
			'admin_posts_filter_restrict_manage_posts'
		),10,2);
		add_filter('parse_query', array(
			$this,
			'posts_filter'
		));

		add_shortcode('wadm', array(
			$this,
			'WADM_data_shortcode'
		));

		add_action('widgets_init', function() { return register_widget("wadm_Widget");} );
	}

	/* ****************************************************************************
	 * Basic methodes needed for most plugins
	 ***************************************************************************** */

	public function myTextDomain() {
		load_plugin_textdomain(self::FS_TEXTDOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages/');
	}

	function PluginLinks($links, $file) {
		if (strpos($file, self::FS_PLUGINID . '.php') !== false) {
			$links[] = '<a href="' . admin_url() . 'admin.php?page=phototools">' . __('General info', self::FS_TEXTDOMAIN) . '</a>';
			$links[] = '<a href="' . admin_url() . 'admin.php?page=' . self::FS_PLUGINID . '">' . __('Settings', self::FS_TEXTDOMAIN) . '</a>';

		}
		return $links;
	}

	public function activate() {

		$phototools = get_option('phototools_list');
		$phototools[self::FS_PLUGINID]            = plugin_basename(__FILE__);;
		update_option('phototools_list', $phototools);

		$wadmw_options = get_option('wadmw_options');
		if (empty($wadmw_options)):
			$wadmw_options['language']               = 'nl';
			update_option('wadmw_options', $wadmwidget_options);
		endif;
	}

	public function deactivate() {
		$phototools = get_option('phototools_list');
		$self       = self::FS_PLUGINID;
		unset($phototools[$self]);
		if (!empty($phototools)):
			update_option('phototools_list', $phototools);
		else:
			delete_option('phototools_list');
		endif;
	}

	/* ****************************************************************************
	 *  Filter postlist
	 ***************************************************************************** */

	function admin_posts_filter_restrict_manage_posts($post_type,$which) {
                global $pagenow;
             
		$type    = 'post';
		if (isset($_GET['post_type'])) {
			$type    = $_GET['post_type'];
		}
		//only add filter to post type you want
		if ('post' == $type && $pagenow=='edit.php') {
			$values  = array(
				"0"         => __("All works (WadM)",self::FS_TEXTDOMAIN),
				"-1"        => __("No workcode",self::FS_TEXTDOMAIN),
				"1"         => __("Has workcode",self::FS_TEXTDOMAIN)
			);
			$current = $_GET['WadMfilter'];
?>
        <label for="wadmw_filter_id">
        <select  name="WadMfilter" id="wadmw_filter_id">
			<?php
			foreach ($values as $value => $text):
				echo sprintf('<option value="%s" %s>%s</option>', $value, ($value == $current) ? "selected" : "", $text);
			endforeach;
?>
        </select>
        </label>

        <?php
		}
	}

	function posts_filter($query) {
		global $pagenow;
		$type = 'post';
		if (isset($_GET['post_type'])) {
			$type = $_GET['post_type'];
		}
		if ('post' == $type && is_admin() && $pagenow == 'edit.php' && isset($_GET['WadMfilter']) && $_GET['WadMfilter'] != '') {
			switch ($_GET['WadMfilter']):
			case 1:
				$query->query_vars['meta_key']      = 'wadmw_workcode';
				$query->query_vars['meta_compare']      = 'EXISTS';
			break;
			case -1:
				$query->query_vars['meta_key'] = 'wadmw_workcode';
				$query->query_vars['meta_compare'] = 'NOT EXISTS';
			break;
			endswitch;
		}
	}

	function enqueue_edit_scripts() {
		wp_enqueue_script('wadm-admin-edit', plugins_url('/javascript/wadmwidget.js', __FILE__) , array(
			'jquery',
			'inline-edit-post'
		) , '', true);
	}

	/* ****************************************************************************
	 * Settings
	 ***************************************************************************** */

	function add_phototools_menuitem() {
		if (empty($GLOBALS['admin_page_hooks']['phototools'])):
			add_menu_page(__('Phototools', self::FS_TEXTDOMAIN) , __('Phototools', self::FS_TEXTDOMAIN) , 'manage_options', 'phototools', array(
				$this,
				'phototools_info'
			) , 'dashicons-camera', 25);
		endif;
		$this->create_submenu_item();

	}

	public function phototools_info() {
?>
			<div class="wrap">
				<div class="icon32" id="icon-options-general"></div>
				<h1><?php _e('Phototools', self::FS_TEXTDOMAIN); ?></h1>
                                
				<p>
				<?php _e('Phototools is a collection of plugins which add functionality for those
				who use WordPress to run a photoblog or gallery site or something alike.', self::FS_TEXTDOMAIN);
?>
				</p>
                                <p><?php _e('The following plugins in this series are installed:', self::FS_TEXTDOMAIN); ?></p>
                                <?php
		$phototools = get_option('phototools_list');
		foreach ($phototools as $id         => $shortPath):
			$plugin     = get_plugin_data(WP_PLUGIN_DIR . '/' . $shortPath, true);
?>
                                        <div class="card">
                                        <h3><a href="<?php echo $plugin['PluginURI']; ?>" target="_blank" rel="”noopenener noreferrer"><?php echo $plugin['Name'] . ' ' . $plugin['Version']; ?></a></h3>
                                        <p><?php echo $plugin['Description']; ?></p>
                                        </div>
                                        <?php
		endforeach;
?>
                                

			</div>
		<?php
	}

	public function view_settings() {
?>
				 	<style>.full_width input[type=text] {
								width: 30%;
				 	}</style>
				 	<div class="wrap">
								<div class="icon32" id="icon-options-general"></div>
								<h1><?php _e('WadM widget', self::FS_TEXTDOMAIN); ?></h1>

								<form method="POST" action="options.php">
									  	<?php
		settings_fields('wadmwidget_group');
		do_settings_sections('wadmwidget');
		submit_button();
		
?>
                        </form>
                </div>
                <?php
	}

	public function create_submenu_item() {

		add_submenu_page('phototools', __('wadmwidget', self::FS_TEXTDOMAIN) , __('WadM widget', self::FS_TEXTDOMAIN) , 'manage_options', 'wadmwidget', array(
			$this,
			'view_settings'
		));
	}

	public function validate_options($options) {
		$options['error'] = !$this->wadm_authenticationTest($options['userid'], $options['apikey']) ? __('UserID and apikey don\'t seem to match.', self::FS_TEXTDOMAIN) : __('Connection ok', self::FS_TEXTDOMAIN);

		return $options;
	}

	public function register_settingspage() {
		register_setting('wadmwidget_group', 'wadmw_options', array(
			$this,
			'validate_options'
		));

		add_settings_section('wadmwidget_general_settings', __('General features', self::FS_TEXTDOMAIN) , '', 'wadmwidget');
		add_settings_field('userid', __('userid', self::FS_TEXTDOMAIN) , array(
			$this,
			'wadmw_userid'
		) , 'wadmwidget', 'wadmwidget_general_settings', ['label_for' => 'userid']);

		add_settings_field('apikey', __('apikey', self::FS_TEXTDOMAIN) , array(
			$this,
			'wadmw_apikey'
		) , 'wadmwidget', 'wadmwidget_general_settings', ['label_for' => 'apikey']);

		add_settings_field('check', __('check', self::FS_TEXTDOMAIN) , array(
			$this,
			'wadmw_check'
		) , 'wadmwidget', 'wadmwidget_general_settings', ['label_for' => 'check']);

		add_settings_field('language', __('language wadm site', self::FS_TEXTDOMAIN) , array(
			$this,
			'wadmw_language'
		) , 'wadmwidget', 'wadmwidget_general_settings', ['label_for' => 'language']);

	}

	function option($option, $text, $value) {
		printf('<option value="%s"%s>%s</option>', $option, ($option === $value ? ' selected' : '') , $text);
	}

	function wadmw_userid() {
		$wadmw_options = get_option('wadmw_options');
		print '<input type="text" id="userid" name="wadmw_options[userid]" value="' . $wadmw_options['userid'] . '" />';
	}

	function wadmw_apikey() {
		$wadmw_options = get_option('wadmw_options');
		print '<input type="text" id="apikey" name="wadmw_options[apikey]" value="' . $wadmw_options['apikey'] . '" style="width:350px" />';
	}
	function wadmw_check() {
		$wadmw_options = get_option('wadmw_options');
		print $wadmw_options['error'] ? $wadmw_options['error'] : '';
	}
	function wadmw_language() {
		$wadmw_options = get_option('wadmw_options');
		print '<select name="wadmw_options[language]" id="language">';
		foreach (self::availableLanguages as $short => $lang):
			$this->option($short, $lang, $wadmw_options['language']);
		endforeach;
		print '</select>';

	}
	/* ****************************************************************************
	 * WadM Api
	 ***************************************************************************** */
	// https://codex.wordpress.org/Function_Reference/wp_remote_get
	var $wadm_lastError = '';

	function getArgs($args           = array() , $userid         = '', $apikey         = '') {
		$wadmw_options  = get_option('wadmw_options');
		if (!$userid):
			$bLogin         = base64_encode($wadmw_options['userid'] . ':' . $wadmw_options['apikey']);
		else:
			$bLogin         = base64_encode($userid . ':' . $apikey);
		endif;

		$basicArgs = array(
			'user-agent' => self::userAgent,
			'headers' => array(
				'Authorization: Basic ' => $bLogin
			)
		);
		return array_merge($basicArgs, $args);

	}

	function useDomain($lang = '') {
		if (empty($lang)):
			$wadmw_options  = get_option('wadmw_options');
			$lang = $wadmw_options['language'];
		endif;
		return self::apidomains[$lang];
	}
	
	function wadm_connectionTest() {
		$args                 = $this->getArgs();
		$result               = json_decode(wp_remote_get($this->useDomain().'/connectiontest', $args));
		$this->wadm_lastError = $result->message;
		return $result->status === 'success';
	}

	function wadm_authenticationTest($userid               = '', $apikey               = '') {
		$args                 = $this->getArgs(array() , $userid, $apikey);
		$result               = json_decode(wp_remote_retrieve_body(wp_remote_get($this->useDomain().'/authenticationtest', $args)));
		$this->wadm_lastError = $result->message;
		return $result->status === 'success';
	}

	function wadm_getWorkData($workcode) {
		//https://www.werkaandemuur.nl/api/artwork/{artworkId} 4
		$args                 = $this->getArgs();
		$result               = json_decode(wp_remote_retrieve_body(wp_remote_get($this->useDomain().'/artwork/' . $workcode, $args)));
		$this->wadm_lastError = $result->message;
		return $result->status === 'success' ? $result->data->artwork : false;
	}

	/* ****************************************************************************
	 * Body of the plugin
	 ***************************************************************************** */

	function create_workcode_box() {
		add_meta_box('workcode_box', __('WadM Workcode', self::FS_TEXTDOMAIN) , array(
			$this,
			'workcode_box'
		) , ['post'], 'side', 'default');
	}

	function workcode_box($object, $box) {
		$nonce     = wp_create_nonce("wadmw_nonce");
		$workcode  = get_post_meta($object->ID, 'wadmw_workcode', true);
		$wadm_data = $this->wadm_getWorkData($workcode);
		$isMine    = $workcode && $wadm_data;

?>
        <input type="number" value="<?php echo $workcode; ?>" name="workcode" id="workcode" style="width:100%" placeholder="<?php echo __('Oh my Print Workcode', self::FS_TEXTDOMAIN); ?>" />
        <?php if ($isMine): ?>
            <div style="position: relative;top: -29px;background-color: green;color: white;width: 20px;float: right;right: -1px;padding: 4px;" title="<?php echo $wadm_data->title; ?>" /><span class="dashicons dashicons-yes"></span></div>
        <?php
		else: ?>
            <div style="position: relative;top: -29px;background-color: red;color: white;width: 20px;float: right;right: -1px;padding: 4px;" title="<?php echo $this->wadm_lastError; ?>" /><span class="dashicons dashicons-no"></span></div>
        <?php
		endif; ?>
        <input type="hidden" name="wadmw_nonce" value="<?php echo wp_create_nonce(plugin_basename(__FILE__)); ?>" />
        
        <?php
	}

	function save_wadmw_workcode($post_id, $post) {

		if (!wp_verify_nonce($_POST['wadmw_nonce'], plugin_basename(__FILE__))) return $post_id;

		if (!current_user_can('edit_post', $post_id)) return $post_id;

		$old_workcode = get_post_meta($post_id, 'wadmw_workcode', true);
		$new_workcode = $_POST['workcode'];

		if ($new_workcode && empty($old_workcode)):
			add_post_meta($post_id, 'wadmw_workcode', $new_workcode, true);
		elseif (empty($new_workcode) && !empty($old_workcode)):
			delete_post_meta($post_id, 'wadmw_workcode', $old_workcode);
		elseif ($new_workcode != $old_workcode):
			update_post_meta($post_id, 'wadmw_workcode', $new_workcode);
		endif;
	}

	function quickedit_set_data($actions, $post) {
		$found_value   = get_post_meta($post->ID, 'wadmw_workcode', true);
		if ($found_value) {
			if (isset($actions['inline hide-if-no-js'])) {
				$new_attribute = sprintf('data-wadm-workcode="%s"', esc_attr($found_value));
				$actions['inline hide-if-no-js']               = str_replace('class=', "$new_attribute class=", $actions['inline hide-if-no-js']);
			}
		}
		return $actions;
	}

	function add_quick_edit($column_name, $post_type) {
		if ($column_name != 'wadm') return;
		$nonce  = wp_create_nonce("wadmw_nonce");
		$isMine = $workcode && $wadm_data;
?>
		<fieldset class="inline-edit-col-right inline-edit-wadm">
			<div class="inline-edit-col">
				<label for="workcode" class="inline-edit-status alignleft">
					<span class="title"><?php echo __('Workcode', self::FS_TEXTDOMAIN); ?></span>
					<span class="input-text-wrap">
					<input type="number" value="<?php echo $workcode; ?>" name="workcode" id="workcode" style="width:100%" placeholder="<?php echo __('Oh my Print Workcode', self::FS_TEXTDOMAIN); ?>" />
					</span>
				</label>
				<input type="hidden" name="wadmw_nonce" value="<?php echo wp_create_nonce(plugin_basename(__FILE__)); ?>" />
			</div>
		</fieldset>
		<?php
	}

	/* ****************************************************************************
	 * add a marker in the postscreen
	 ***************************************************************************** */

	private function array_insert_after($key, &$array, $new_key, $new_value) {
		if (array_key_exists($key, $array)) {
			$new = array();
			foreach ($array as $k   => $value) {
				$new[$k]     = $value;
				if ($k === $key) {
					$new[$new_key]     = $new_value;
				}
			}
			return $new;
		}
		else {
			return false;
		}
	}

	function wadmIcon_column() {
		echo '<style>
                        
                       .fixed .column-wadm { width: 5.5em; }
                       .fixed .column-wadm .column-wadm { width: 3em; }
                       
                    </style>';
	}

	function hook_wadmw_post_column() {
		
		add_action('admin_print_styles-edit.php', array(
			$this,
			'wadmIcon_column'
		));

		add_filter('manage_posts_columns', array(
			$this,
			'my_columns'
		));
		add_action('manage_posts_custom_column', array(
			$this,
			'my_show_columns'
		));

	}

	function my_columns($columns) {
		$columns = $this->array_insert_after('tags', $columns, 'wadm', 'WadM');
		return $columns;
	}

	function my_show_columns($name) {
		global $post;
		switch ($name) {
			case 'wadm':
				$workcode = get_post_meta($post->ID, 'wadmw_workcode', true);
				echo '<div class="column-wadm" title="Workcode: ' . $workcode . '">';
				echo $workcode ? '<span class="dashicons dashicons-yes"></span>' : '';
				echo '</div>';
		}
	}
	
	/* ****************************************************************************
	 * add a marker in the postscreen
	 ***************************************************************************** */
	function WADM_data_shortcode($atts) {
		$res = '';
		$workcode = get_post_meta($GLOBALS['post']->ID, 'wadmw_workcode', true);
		$workdata = $this->wadm_getWorkData($workcode);

//		print "<!-- ";
//		print_r($workdata);
//		print " -->";
		
		// Attributes
		extract(shortcode_atts(array(
			'data' => 'formatted_link'
		) , $atts));

		$data = strtolower($data);
		switch ($data) {
			case 'id':
				$res  = $workdata->id;
			break;
			case 'ownerid':
				$res = $workdata->owner;
			break;
			case 'file':
				$res = $workdata->file;
			break;
			case 'link':
				$res = $workdata->link.'&utm_medium='.urlencode(self::userAgent);
			break;
			case 'image':
				$key = reset($workdata->images);
				$res = $key;
			break;
			case 'imagehttps':
				$key = reset($workdata->images);
				$res = $key;
			break;
			case 'price':
				$res = $workdata->pricing[0];
			break;
			case 'size':
				$res = $workdata->pricing[1].'x'.$workdata->pricing[2];
			break;
			case 'size-width':
				$res = $workdata->pricing[1];
			break;
			case 'size-height':
				$res = $workdata->pricing[2];
			break;
			case 'aspect':
				$res = $workdata->aspect;
			break;
			case 'title':
				$res = $workdata->title;
			break;
			case 'formatted':
				$res = sprintf('"%s", %sx%s cm for %s.',
							$workdata->title,
							$workdata->pricing[1],
							$workdata->pricing[2],
							$workdata->pricing[0]
							);
			break;
			case 'formatted_link':
				$res = sprintf('<a href="%s" target="_blank" class="wadm_link">%s", %sx%s cm for %s.</a>',
							$workdata->link.'&utm_medium='.urlencode(self::userAgent),
							$workdata->title,
							$workdata->pricing[1],
							$workdata->pricing[2],
							$workdata->pricing[0]
							);
			break;
		}

		return $res;
	}
}

	/* ****************************************************************************
	 * The widget
	 ***************************************************************************** */

class wadm_Widget extends WP_Widget {

	const FS_TEXTDOMAIN = 'wadmwidget';

	public function __construct() {
		parent::__construct(false, 
						$name = __('wadm widget', self::FS_TEXTDOMAIN) , 
						array(
							'description' => __('wadm', self::FS_TEXTDOMAIN)
						));
	}

	// widget form creation
	function form($instance) {
		// Check values
		if ($instance) {
			$title        = esc_attr($instance['title']);
			$style	      = $instance['style'];
			$wadmtext     = esc_attr($instance['wadmtext']);

		}
		else {
			$title        = __('WadM', self::FS_TEXTDOMAIN);
			$style	      = 'thumb';
			$wadmtext     = '';
		}
?>
	    <p>
	    	<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Title', self::FS_TEXTDOMAIN); ?></label>
	    	<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
	    </p>
	    
	    <p>
	    	<label for="<?php echo $this->get_field_id('style'); ?>"><?php _e('Widget style', self::FS_TEXTDOMAIN); ?></label>
	    	<select class="widefat" id="<?php echo $this->get_field_id('style'); ?>" name="<?php echo $this->get_field_name('style'); ?>">
	    		<option value="simple"<?php echo $style=='simple'?' selected':''; ?>>Simple text and icon</option>
	    		<option value="thumb"<?php echo $style=='thumb'?' selected':''; ?>>With thumbnail</option>
	    		<option value="text"<?php echo $style=='text'?' selected':''; ?>>With text</option>
	    	</select>
	    </p>
	    
	    <p>
	    	<label for="<?php echo $this->get_field_id('wadmtext'); ?>"><?php _e('WadM text', self::FS_TEXTDOMAIN); ?></label>
	    	<textarea class="widefat" id="<?php echo $this->get_field_id('wadmtext'); ?>" name="<?php echo $this->get_field_name('wadmtext'); ?>"><?php echo $wadmtext; ?></textarea>
	    </p>
	    
	    <?php
	}

	// widget update
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		// Fields
		$instance['title']	= strip_tags($new_instance['title']);
		$instance['style']	= $new_instance['style'];
		$instance['wadmtext']   = $new_instance['wadmtext'];
		return $instance;
	}

	function displaySimple($workcode) {
		$wadmw_options = get_option('wadmw_options');
		$site          = wadmwidget_class::sites[$wadmw_options['language']];
		$domain        = wadmwidget_class::domains[$wadmw_options['language']].'?utm_source=worpress&utm_medium='.urlencode(wadmwidget_class::userAgent);
		?>
		<style>
				  .wadm_widget_class {
						display: flex;
						background-color: #47a1f8;
						background-image: url('<?php echo plugin_dir_url(__FILE__); ?>WadM-icon.svg');
						background-size: 90px 90px;
						background-repeat: no-repeat;
						background-position:left -15px;
						color: white;
						padding: 10px;
						height: 70px;
						font-size: 1.2em;
						font-weight: bold;
						align-items: center;
						justify-content: center;
				  }
				  .wadm_widget_class:visited { color: white }
				  .wadm_widget_class:hover { color: white; text-decoration:underline; }
				</style>
	<?php
	echo '<a href="' . sprintf($domain, $workcode) . '" target="_blank" rel="”noopenener noreferrer" title="' . __('Image for sale', self::FS_TEXTDOMAIN) . '" class="widget-text wp_widget_plugin_box wadm_widget_class">';
	echo $site . ', code: ' . $workcode;
	echo "</a>";	
	}
	
	function displayWithThumbnail($workcode) {
		global $wadmwidget;
		?>
		<style>
				.wadm_thumb_class { display: block; margin-bottom: -70px;}
				.wadm_thumb_image {
						position: relative;
						display: inline-block;
						background-image: url('<?php echo plugin_dir_url(__FILE__);?>WadM-icon.svg');
						background-size: 90px 90px;
						background-repeat: no-repeat;
						background-position:left -15px;
						background-color: #47a1f8;
						color: white;
						padding-left: 70px;
						line-height: 70px;
						width: 100%;
						height: 70px;
						top:  -70px;
				  }
				  .wadm_thumb_class:visited { color: white }
				  .wadm_thumb_class:hover { color: white; text-decoration:underline; }
				</style>
		<?php
		$wadmw_options = get_option('wadmw_options');
		$site          = wadmwidget_class::sites[$wadmw_options['language']];
		$domain        = wadmwidget_class::domains[$wadmw_options['language']].'?utm_source=worpress&utm_medium='.urlencode(wadmwidget_class::userAgent);
		$workdata 		= $wadmwidget->wadm_getWorkData($workcode);
		
		echo '<a href="' . sprintf($domain, $workcode) . '" target="_blank" rel="”noopenener noreferrer" title="' . __('Image for sale', self::FS_TEXTDOMAIN) . '" class="widget-text wp_widget_plugin_box wadm_thumb_class">';
		$thumb = reset($workdata->images);
		echo '<img src="'.$thumb.'" style="width:100%">';
		echo '<div class="wadm_thumb_image">'.$site . ', code: ' . $workcode.'</div>';
		echo "</a>";
	}
	
	function displayWithText($workcode,$wadmtext) {
		global $wadmwidget;
		?>
		<style>
				.wadm_thumb_class { display: block; margin-bottom: -70px;}
				.wadm_thumb_image {
						position: relative;
						display: inline-block;
						background-image: url('<?php echo plugin_dir_url(__FILE__);?>WadM-icon.svg');
						background-size: 90px 90px;
						background-repeat: no-repeat;
						background-position:left -15px;
						background-color: #47a1f8;
						color: white;
						padding-left: 70px;
						line-height: 70px;
						width: 100%;
						height: 70px;
						top:  -70px;
				  }
				  .wadm_thumb_class:visited { color: white }
				  .wadm_thumb_class:hover { color: white; text-decoration:underline; }
				</style>
		<?php
		$wadmw_options = get_option('wadmw_options');
		$site          = wadmwidget_class::sites[$wadmw_options['language']];
		$domain        = wadmwidget_class::domains[$wadmw_options['language']].'?utm_source=worpress&utm_medium='.urlencode(wadmwidget_class::userAgent);
		$workdata      = $wadmwidget->wadm_getWorkData($workcode);
		
		echo '<a href="' . sprintf($domain, $workcode) . '" target="_blank" rel="”noopenener noreferrer" title="' . __('Image for sale', self::FS_TEXTDOMAIN) . '" class="widget-text wp_widget_plugin_box wadm_thumb_class">';
		echo '<div class="wadm_thumb_image">'.$site . ', code: ' . $workcode.'</div>';
		echo "<div>".nl2br($wadmtext).'</div>';
		echo "</a>";

	}
	
	
	// widget display
	function widget($args, $instance) {
		global $post;

		extract($args);
		// these are the widget options
		$title    = apply_filters('widget_title', $instance['title']);
		$workcode = get_post_meta($post->ID, 'wadmw_workcode', true);
		if ($workcode):
			echo $before_widget;
			switch ($instance['style']) {
				case 'thumb':	$this->displayWithThumbnail($workcode); break;
				case 'text':    $this->displayWithText($workcode,$instance['wadmtext']); break;
				default: 	$this->displaySimple($workcode); break;
			}
			echo $after_widget;
		endif;
	}
}
/*
[id] => 376692
    [owner] => 1125
    [file] => d4c8d9d2b4db80d11c5409d1e4f95f9b
    [aspect] => 1496
    [link] => https://www.werkaandemuur.nl/nl/shopwerk/Bloem-in-Zwart-Wit-2-2/376692?utm_source=wordpress
    [images] => stdClass Object
        (
            [950x600] => https://thumbs.werkaandemuur.nl/d4c8d9d2b4db80d11c5409d1e4f95f9b_950x600_fit.jpg
        )

    [imagesHttps] => stdClass Object
        (
            [950x600] => https://thumbs.werkaandemuur.nl/d4c8d9d2b4db80d11c5409d1e4f95f9b_950x600_fit.jpg
        )

    [pricing] => Array
        (
            [0] => <span>&euro;</span> 79
            [1] => 75
            [2] => 50
        )

    [title] => Bloem in Zwart Wit #2/2
)
*/

$wadmwidget = new wadmwidget_class();
?>
