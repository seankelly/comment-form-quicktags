<?php
/*
Plugin Name: Comment Form Quicktags
Plugin URI: http://rp.exadge.com/2009/01/08/comment-form-quicktags/
Description: This plugin inserts a quicktag toolbar on the comment form.
Version: 1.3.2
Author: Regen
Author URI: http://rp.exadge.com
*/

/**
 * @author Regen
 * @copyright Copyright (C) 2009 Regen
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link http://rp.exadge.com/2009/01/08/comment-form-quicktags/ Comment Form Quicktags
 * @access public
 */

/**
 * Comment Form Quicktags main class.
 */
class CommentFormQuicktags {

	/**
	 * Gettext domain.
	 * @var string
	 */
	var $domain;

	/**
	 * Plugin name.
	 * @var string
	 */
	var $plugin_name;

	/**
	 * Plugin path.
	 * @var string
	 */
	var $plugin_dir;

	/**
	 * Plugin URL.
	 * @var string
	 */
	var $plugin_url;

	/**
	 * Option ID.
	 * @var string
	 */
	var $option_name;

	/**
	 * Option data.
	 * @var array
	 */
	var $options;

	/**
	 * Option menu ID.
	 * @var string
	 */
	var $option_hook;

	/**
	 * Capability name.
	 * @var string
	 */
	var $cap;

	/**
	 * Initialize CommentFormQuicktags.
	 */
	function CommentFormQuicktags() {
		$this->domain = 'comment-form-quicktags';
		$this->plugin_name = 'comment-form-quicktags';
		$this->plugin_dir = '/' . PLUGINDIR . '/' . $this->plugin_name;
		$this->option_name = $this->plugin_name . '-option';
		$this->option_hook = 'cfq_option_page';
		$this->cap = 'comment_form_quicktags';
		if (defined('WP_PLUGIN_URL')) {
			$this->plugin_url = WP_PLUGIN_URL . '/' . $this->plugin_name;
		} else {
			$this->plugin_url = get_option('siteurl') . '/' . PLUGINDIR . '/' . $this->plugin_name;
		}
		
		load_textdomain($this->domain, dirname(__FILE__) . '/languages/' . get_locale() . '.mo');
		
		$this->get_option();
		$this->set_hooks();
	}

	/**
	 * Get plugin options.
	 */
	function get_option() {
		$this->options = (array)get_option($this->option_name);
		
		$this->options += array(
			'tags' => array(
				'strong' => array(
					'display' => 'b',
					'start' => '<strong>',
					'end' => '</strong>',
					'access' => 'b'
				),
				'em' => array(
					'display' => 'i',
					'start' => '<em>',
					'end' => '</em>',
					'access' => 'i'
				),
				'del' => array(
					'display' => 'del',
					'start' => '<del>',
					'end' => '</del>',
					'access' => 'd'
				),
				'link' => array(
					'display' => 'link',
					'start' => '',
					'end' => '</a>',
					'access' => 'a'
				),
				'block' => array(
					'display' => 'b-quote',
					'start' => '<blockquote>',
					'end' => '</blockquote>',
					'access' => 'q'
				),
				'code' => array(
					'display' => 'code',
					'start' => '<code>',
					'end' => '</code>',
					'access' => 'c'
				),
				'close' => array(
					'display' => 'Close Tags',
					'start' => '',
					'end' => '',
					'access' => ''
				)
			),
			'modified' => filemtime(__FILE__),
			'cap_check' => false
		);
		
		if ($this->options['modified'] < filemtime(__FILE__)) {
			$this->options['modified'] = filemtime(__FILE__);
		}
		if (array_key_exists('sort', $this->options)) {
			$tags = array();
			foreach ($this->options['sort'] as $id) {
				$tags[$id] = $this->options['tags'][$id];
			}
			$this->options['tags'] = $tags;
			unset($this->options['sort']);
			$this->update_option();
		}
	}

	/**
	 * Update plugin options.
	 */
	function update_option() {
		update_option($this->option_name, $this->options);
	}

	/**
	 * Delete plugin options.
	 */
	function delete_option() {
		$this->options = array();
		delete_option($this->option_name);
	}

	/**
	 * Set WP hooks.
	 */
	function set_hooks() {
		wp_register_script('cfq', $this->plugin_url . '/quicktags.php', array(), date('Ymd', $this->options['modified']));
		wp_register_style('cfq', $this->plugin_url . '/style.css', array(), date('Ymd', filemtime(dirname(__FILE__) . '/style.css')));
		add_action('wp_print_scripts', array(&$this, 'add_scripts'));
		add_action('wp_print_styles', array(&$this, 'add_styles'));
		add_action('admin_menu', array(&$this, 'set_admin_hooks'));
		add_filter('comments_template', array(&$this, 'detect_start'));
		
		// for comments-popup.php
		if (isset($_GET['comments_popup'])) {
			wp_enqueue_script('cfq');
			wp_enqueue_style('cfq');
			$this->detect_start();
		}
	}
	
	/**
	 * Check capabilities.
	 */
	function can_quicktag() {
		return !$this->options['cap_check'] || ($this->options['cap_check'] && current_user_can($this->cap));
	}
	
	/**
	 * Add scripts.
	 */
	function add_scripts() {
		if (is_singular()) {
			wp_enqueue_script('cfq');
		}
	}
	
	/**
	 * Add styles.
	 */
	function add_styles() {
		if (is_singular()) {
			wp_enqueue_style('cfq');
		}
	}

	/**
	 * Set WP hooks for admin.
	 */
	function set_admin_hooks() {
		$page = add_options_page(__('Comment Form Quicktags Options', $this->domain), __('Comment Form Quicktags', $this->domain), 8, $this->option_hook, array(&$this, 'options_page'));
		
		add_filter('plugin_action_links', array(&$this, 'add_action_links'), 10, 2);
		add_action('admin_print_scripts-' . $page, array(&$this, 'add_admin_scripts'));
		add_action('admin_print_styles-' . $page, array(&$this, 'add_admin_styles'));
	}

	/**
	 * Add scripts to admin header.
	 */
	function add_admin_scripts() {
		wp_enqueue_script('cfq-admin', $this->plugin_dir . '/admin.js',  array('scriptaculous-dragdrop', 'scriptaculous-effects'));
		wp_localize_script('cfq-admin', 'cfqadminL10n', array(
			'removeConfirm' => __('Are you sure?', $this->domain)
		));
	}

	/**
	 * Add styles to admin header.
	 */
	 
	function add_admin_styles() {
		wp_enqueue_style('cfq');
		wp_enqueue_style('cfq-admin', $this->plugin_url . '/admin.css');
	}

	/**
	 * Add settings link to pluguin menu.
	 * @param array $links
	 * @param string $file
	 * @return array
	 */
	function add_action_links($links, $file){
		if ($file == $this->plugin_name . '/' . basename(__FILE__)) {
			$settings_link = '<a href="options-general.php?page=' . $this->option_hook . '">' . __('Settings', $this->domain) . '</a>';
			$links = array_merge(array($settings_link), $links);
		}
		return $links;
	}

	/**
	 * Start to detect <textarea>.
	 */
	function detect_start($file = null) {
		if ($this->can_quicktag()) {
			ob_start(array(&$this, 'add_tags'));
			$this->ended = false;
			add_action('comment_form', array(&$this, 'detect_end'));
			add_action('wp_footer', array(&$this, 'detect_end'));
		}
		
		return $file;
	}

	/**
	 * End to detect <textarea>.
	 */
	function detect_end() {
		if (!$this->ended) {
			$this->ended = true;
			ob_end_flush();
		}
	}

	/**
	 * Add quicktags to comment form.
	 * @param string $content
	 * @return string
	 */
	function add_tags($content) {
		// for comments-popup.php
		if (isset($_GET['comments_popup'])) {
			global $wp_scripts, $wp_styles;
			
			$scripts = '';
			
			$wp_scripts->do_concat = true;
			$wp_scripts->do_items();
			$scripts .= $wp_scripts->print_html;
			
			$wp_styles->do_concat = true;
			$wp_styles->do_items();
			$scripts .= $wp_styles->print_html;
			
			$content = preg_replace('%</head>%', $scripts . '\\0', $content);
		}
		
		$toolbar = '<script type="text/javascript">var edInserted; if (!edInserted) {edToolbar(); edInserted = true;}</script>';
		$activate = '<script type="text/javascript">var edCanvas = document.getElementById(\'\\1\');</script>';
		$content = preg_replace(
			'%<textarea.*id="([^"]*)".*>.*</textarea>%U',
			$toolbar . "\n" . '\\0' . "\n" . $activate,
			$content
		);
		
		return $content;
	}

	/**
	 * Print quicktag script.
	 */
	function print_tag_js() {
		foreach($this->options['tags'] as $id => $sets) {
			printf(
				'edButtons[edButtons.length] = new edButton(\'%s\', \'%s\', \'%s\', \'%s\', \'%s\');',
				'ed_' . $id,
				$sets['display'],
				$sets['start'],
				$sets['end'],
				$sets['access']
			);
		}
	}

	/**
	 * Admin page function.
	 */
	function options_page() {
		global $wp_roles;
		include 'json.php';
		
		if (isset($_POST['action'])) {
			switch ($_POST['action']) {
				case 'update':
					parse_str($_POST['sort'], $buf);
					$sort = $buf['ed_toolbar'];
					
					$tags = json_decode(stripslashes($_POST['tags']));
					$this->options['tags'] = array();
					foreach ($sort as $id) {
						$this->options['tags'][$id] = (array)$tags->$id;
					}
					
					$this->options['modified'] = time();
					$this->update_option();
					echo '<div class="updated fade"><p><strong>' . __('Options saved.', $this->domain) . '</strong></p></div>';
					break;
				case 'rolelimit':
					$this->options['cap_check'] = isset($_POST['cap_check']);
					$this->update_option();
					if ($this->options['cap_check']) {
						foreach ($wp_roles->get_names() as $role => $name) {
							$wp_roles->add_cap($role, $this->cap, in_array($role, $_POST['role']));
						}
					}
					echo '<div class="updated fade"><p><strong>' . __('Options saved.', $this->domain) . '</strong></p></div>';
					break;
				case 'remove':
					$this->delete_option();
					$this->get_option();
					foreach ($wp_roles->get_names() as $role => $name) {
						$wp_roles->remove_cap($role, $this->cap);
					}
					echo '<div class="updated fade"><p><strong>' . __('Options removed.', $this->domain) . '</strong></p></div>';
					break;
			}
		}
		?>

<div class="wrap">
<h2><?php _e('Comment Form Quicktags Options', $this->domain) ?></h2>
<noscript><div class="error"><p><?php _e('Javascript is needed! ', $this->domain) ?></p></div></noscript>

<h3><?php _e('Tag Options', $this->domain) ?></h3>
<ol class="desc">
	<li><?php _e('Click a tag which you want to edit or input ID which you want to add.', $this->domain) ?></li>
	<li><?php _e('Edit other field.', $this->domain) ?></li>
	<li><?php _e('Click Edit/Add button.', $this->domain) ?></li>
	<li><?php _e('Sort tags by dragging.', $this->domain) ?></li>
	<li><?php _e('After that, click Update Tags button to save.', $this->domain) ?></li>
</ol>
<p><?php printf(__('<strong>[Info]</strong> Allowed tags in comments: <code class="tags">%s</code>.', $this->domain), trim(allowed_tags())) ?></p>
<p><?php _e('<strong>[Note]</strong> There are special IDs: ed_link, ed_img and ed_close.', $this->domain) ?></p>

<script type="text/javascript">
//<![CDATA[
	var buttons = <?php echo json_encode($this->options['tags']); ?>;
//]]>
</script>

<div id="ed_toolbar">
	<?php foreach($this->options['tags'] as $id => $sets): ?>
		<span class="ed_button" id="ed_<?php echo $id; ?>"><?php echo $sets['display']; ?></span>
	<?php endforeach; ?>
</div>

<div id="att">
	<table class="form-table">
		<tbody>
			<tr>
				<th><label for="edit_id"><?php _e('ID<span>*</span>', $this->domain) ?></label></th><td>ed_<input type="text" name="id" id="edit_id" value="" /></td>
			</tr>
			<tr>
				<th><label for="edit_display"><?php _e('Display<span>*</span>', $this->domain) ?></label></th><td><input type="text" name="display" id="edit_display" value="" /></td>
			</tr>
			<tr>
				<th><label for="edit_start"><?php _e('Start tag', $this->domain) ?></label></th><td><input type="text" name="start" id="edit_start" value="" /></td>
			</tr>
			<tr>
				<th><label for="edit_end"><?php _e('End tag', $this->domain) ?></label></th><td><input type="text" name="end" id="edit_end" value="" /></td>
			</tr>
			<tr>
				<th><label for="edit_access"><?php _e('Access key', $this->domain) ?></label></th><td><input type="text" name="access" id="edit_access" value="" /></td>
			</tr>
		</tbody>
	</table>
	<p>
		<input type="button" class="button" value="<?php _e('Edit/Add', $this->domain) ?>" id="save_btn" />
		<input type="button" class="button" value="<?php _e('Delete', $this->domain) ?>" id="del_btn" />
		<input type="button" class="button" value="<?php _e('Clear fields', $this->domain) ?>" id="clear_btn" />
	</p>
</div>

<form id="sform" method="post" action="?page=<?php echo $this->option_hook; ?>">
	<p class="submit">
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="sort" id="sort" value="" />
		<input type="hidden" name="tags" id="tags" value="" />
		<input type="submit" class="button-primary" value="<?php _e('Update Tags', $this->domain) ?>" />
	</p>
</form>

<h3><?php _e('Role limitation', $this->domain) ?></h3>
<form id="rolelimit" method="post" action="?page=<?php echo $this->option_hook ?>">
	<p>
		<label><input type="checkbox" id="cap_check" name="cap_check" <?php echo $this->options['cap_check'] ? 'checked="checked"' : '' ?> /> <?php _e('Use role limitation', $this->domain) ?></label>
	</p>
	<p id="roles">
		<?php _e('Select the roles that can use quicktags.', $this->domain) ?><br />
		<?php
			foreach ($wp_roles->roles as $role => $data) {
				$checked = isset($data['capabilities'][$this->cap]) && $data['capabilities'][$this->cap] ? 'checked="checked"' : '';
				$disabled = $this->options['cap_check'] ? '' : 'disabled="disabled"';
				printf('<label><input type="checkbox" name="role[]" value="%s" %s %s /> %s</label><br />', $role, $checked, $disabled, translate_user_role($data['name']));
			}
		?>
	</p>
	
	<p class="submit">
		<input type="hidden" name="action" value="rolelimit" />
		<input type="submit" class="button-primary" value="<?php _e('Update roles', $this->domain) ?>" />
	</p>
</form>

<h3><?php _e('Remove options', $this->domain) ?></h3>
<p><?php _e('You can remove the above options from the database. All the settings return to default.', $this->domain) ?></p>
<form id="rform" action="?page=<?php echo $this->option_hook; ?>" method="post">
<p>
<input type="hidden" name="action" value="remove" />
<input type="submit" class="button" value="<?php _e('Remove options', $this->domain) ?>" />
</p>
</form>

</div>

		<?php
	}

}

/**
 * CommentFormQuicktags class instance.
 */
$comment_form_quicktags = &new CommentFormQuicktags();
