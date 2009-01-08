<?php
/*
Plugin Name: Comment Form Quicktags
Plugin URI: http://rp.exadge.com/
Description: This plugin inserts a quicktag toolbar on the comment form.
Version: 1.0
Author: Regen
Author URI: http://rp.exadge.com
*/

class CommentFormQuicktags {

	var $domain;
	var $plugin_name;
	var $plugin_path;

	function CommentFormQuicktags() {
		$this->domain = 'comment-form-quicktags';
		$this->plugin_name = 'comment-form-quicktags';
		if (defined('WP_PLUGIN_URL')) {
			$this->plugin_path = WP_PLUGIN_URL . '/' . $this->plugin_name;
			load_plugin_textdomain($this->domain, str_replace(ABSPATH, '', WP_PLUGIN_DIR) . '/' . $this->plugin_name);
		} else {
			$this->plugin_path = get_option('siteurl') . '/' . PLUGINDIR . '/'.$this->plugin_name;
			load_plugin_textdomain($this->domain, PLUGINDIR . '/' . $this->plugin_name);
		}
		
		$this->set_hooks();
	}

	function set_hooks() {
		add_action('wp_head', array(&$this, 'add_head'));
		add_action('comments_template', array(&$this, 'detect_start'));
	}

	function add_head() {
		echo '<script src="' . $this->plugin_path . '/quicktags.js' . '" type="text/javascript"></script>';
		echo '<link rel="stylesheet" href="' . $this->plugin_path . '/style.css" type="text/css" media="screen" />';
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

}

$comment_form_quicktags = &new CommentFormQuicktags();