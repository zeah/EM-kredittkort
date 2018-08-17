<?php
/*
Plugin Name: EM Kredittkort
Description: Liste for kredittkort
Version: 1.0.6
GitHub Plugin URI: zeah/EM-kredittkort-list
*/

defined('ABSPATH') or die('Blank Space');

// constant for plugin location
define('KREDITTKORT_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once 'inc/kredittkort-posttype.php';
require_once 'inc/kredittkort-shortcode.php';

if (!function_exists('init_kredittkort')) {
	function init_kredittkort() {
		Kredittkort_posttype::get_instance();
		Kredittkort_shortcode::get_instance();
	}
}
add_action('plugins_loaded', 'init_kredittkort');