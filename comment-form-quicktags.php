<?php
/*
Plugin Name: Comment Form Quicktags
Plugin URI: http://rp.exadge.com/2009/01/08/comment-form-quicktags/
Description: This plugin inserts a quicktag toolbar on the comment form.
Version: 1.1.1
Author: Regen
Author URI: http://rp.exadge.com
*/

class CommentFormQuicktags {

	var $domain;
	var $plugin_name;
	var $plugin_dir;
	var $plugin_url;
	var $option_name;
	var $options;
	var $option_hook;

	function CommentFormQuicktags() {
		$this->domain = 'comment-form-quicktags';
		$this->plugin_name = 'comment-form-quicktags';
		$this->plugin_dir = '/' . PLUGINDIR . '/' . $this->plugin_name;
		$this->option_name = $this->plugin_name . '-option';
		$this->option_hook = 'cfq_option_page';
		if (defined('WP_PLUGIN_URL')) {
			$this->plugin_url = WP_PLUGIN_URL . '/' . $this->plugin_name;
		} else {
			$this->plugin_url = get_option('siteurl') . '/' . PLUGINDIR . '/'.$this->plugin_name;
		}
		
		load_plugin_textdomain($this->domain, PLUGINDIR . '/' . $this->plugin_name);
		
		$this->get_option();
		$this->set_hooks();
	}

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
			)
		);
		if (!isset($this->options['sort'])) $this->options['sort'] = array_keys($this->options['tags']);
	}

	function update_option() {
		update_option($this->option_name, $this->options);
	}

	function delete_option() {
		$this->options = array();
		delete_option($this->option_name);
	}

	function set_hooks() {
		add_action('wp_head', array(&$this, 'add_head'));
		add_action('comments_template', array(&$this, 'detect_start'));
		add_action('admin_menu', array(&$this, 'set_admin_hooks'));
	}

	function set_admin_hooks() {
		global $wp_version;
		
		$page = add_options_page(__('Comment Form Quicktags Options', $this->domain), __('Comment Form Quicktags', $this->domain), 8, $this->option_hook, array(&$this, 'options_page'));
		$hook_id = version_compare($wp_version, '2.7', '>=') ? $page : $this->option_hook;
		
		add_filter('plugin_action_links', array(&$this, 'add_action_links'), 10, 2);
		add_action('admin_print_scripts-' . $hook_id, array(&$this, 'add_admin_scripts'));
		add_action('admin_print_styles-' . $hook_id, array(&$this, 'add_admin_styles'));
	}

	function add_admin_scripts() {
		wp_enqueue_script('cfq-admin', $this->plugin_dir . '/admin.js',  array('scriptaculous-dragdrop', 'scriptaculous-effects'));
		wp_localize_script('cfq-admin', 'cfqadminL10n', array(
			'removeConfirm' => __('Are you sure?', $this->domain)
		));
	}

	function add_admin_styles() {
		?>

<link rel="stylesheet" href="<?php echo $this->plugin_url; ?>/style.css" type="text/css" media="screen" />
<style type="text/css" >
#ed_toolbar span {
	cursor: move;
}
ol.desc {
	list-style-type: decimal;
	margin-left: 2em;
}
#att th {
	width: 6em;
}
#att label span {
	color: #f00;
	font-weight: bold;
	margin-left: 2px;
}
code.tags {
	font-family: 'Courier New', Courier, monospace, mono !important;
	background-color: transparent;
}
</style>

		<?php
	}

	function add_action_links($links, $file){
		if ($file == $this->plugin_name . '/' . basename(__FILE__)) {
			$settings_link = '<a href="options-general.php?page=cfq_option_page">' . __('Settings', $this->domain) . '</a>';
			$links = array_merge(array($settings_link), $links);
		}
		return $links;
	}

	function add_head() {
		echo '<script src="' . $this->plugin_url . '/quicktags.php' . '" type="text/javascript"></script>';
		echo '<link rel="stylesheet" href="' . $this->plugin_url . '/style.css" type="text/css" media="screen" />';
	}

	function detect_start() {
		ob_start(array(&$this, 'add_tags'));
		$this->ended = false;
		add_action('comment_form', array(&$this, 'detect_end'));
		add_action('wp_footer', array(&$this, 'detect_end'));
	}

	function detect_end() {
		if (!$this->ended) {
			$this->ended = true;
			ob_end_flush();
		}
	}

	function add_tags($content) {
		$toolbar = '<script type="text/javascript">edToolbar();</script>';
		$activate = '<script type="text/javascript">var edCanvas = document.getElementById(\'\\2\');</script>';
		$content = preg_replace(
			'%<textarea(.*)id="([^"]*)"(.*)>(.*)</textarea>%U',
			$toolbar . "\n" . '<textarea\\1id="\\2"\\3>\\4</textarea>' . "\n" . $activate,
			$content
		);
		
		return $content;
	}

	function print_tag_js() {
		foreach($this->options['sort'] as $tag):
			$sets = $this->options['tags'][$tag];
		 	?>

edButtons[edButtons.length] = new edButton('ed_<?php echo $tag; ?>', '<?php echo $sets['display']; ?>', '<?php echo $sets['start']; ?>', '<?php echo $sets['end']; ?>', '<?php echo $sets['access']; ?>');

			<?php
		endforeach;
	}

	function options_page() {
		include 'json.php';
		if (isset($_POST['action'])) {
			switch ($_POST['action']) {
				case 'update':
					parse_str($_POST['sort'], $buf);
					if (!empty($this->options['sort'])) $this->options['sort'] = $buf['ed_toolbar'];
					if (!empty($this->options['tags'])) $this->options['tags'] = json_decode(stripslashes($_POST['tags']), true);
					$this->update_option();
					echo '<div class="updated fade"><p><strong>' . __('Options saved.', $this->domain) . '</strong></p></div>';
					break;
				case 'remove':
					$this->delete_option();
					$this->get_option();
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
	buttons = <?php echo json_encode($this->options['tags']); ?>;
</script>

<div id="ed_toolbar">
	<?php foreach($this->options['sort'] as $tag): ?>
		<span class="ed_button" id="ed_<?php echo $tag; ?>"><?php echo $this->options['tags'][$tag]['display']; ?></span>
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
		<input type="submit" class="button-primary" value="<?php _e('Update Tags', $this->domain) ?>" name="submit"/>
	</p>
</form>


<h3><?php _e('Remove options', $this->domain) ?></h3>
<p><?php _e('You can remove the above options from the database. All the settings return to default.', $this->domain) ?></p>
<form id="rform" action="?page=<?php echo $this->option_hook; ?>" method="post">
<p>
<input type="hidden" name="action" value="remove" />
<input type="submit" class="button" value="<?php _e('Remove options', $this->domain) ?>" name="submit" />
</p>
</form>

</div>

		<?php
	}

}

$comment_form_quicktags = &new CommentFormQuicktags();