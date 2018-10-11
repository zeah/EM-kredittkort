<?php 
defined('ABSPATH') or die('Blank Space');


/*
*/
final class Kredittkort_edit {
	/* singleton */
	private static $instance = null;


	public static function get_instance() {
		if (self::$instance === null) self::$instance = new self();
		return self::$instance;
	}



	private function __construct() {

		add_action('admin_enqueue_scripts', array($this, 'admin_sands'));

		add_action('manage_kredittkort_posts_columns', array($this, 'column_head'));
		add_filter('manage_kredittkort_posts_custom_column', array($this, 'custom_column'));
		add_filter('manage_edit-kredittkort_sortable_columns', array($this, 'sort_column'));
		
		/* metabox, javascript */
		add_action('add_meta_boxes_kredittkort', array($this, 'create_meta'));
		/* hook for page saving/updating */
		add_action('save_post', array($this, 'save'));


		add_filter('emtheme_doc', array($this, 'add_doc'), 99);


	}


	public function admin_sands() {
		wp_enqueue_style('em-kredittkort-admin-style', KREDITTKORT_PLUGIN_URL . 'assets/css/admin/em-kredittkort.css', array(), '1.0.1');
	}

	/**
	 * theme filter for populating documentation
	 * 	
	 * @param [array] $data [array passing through theme filter]
	 */
	public function add_doc($data) {
		$data['kredittkort']['title'] = '<h1 id="kredittkort">Kredittkort (Plugin)</h1>';

		$data['kredittkort']['index'] = '<li><h2><a href="#kredittkort">Kredittkort (Plugin)</a></h2>
											<ul>
												<li><a href="#kredittkort-shortcode">Shortcode</a></li>
												<li><a href="#kredittkort-aldri">Aldri vis</a></li>
												<li><a href="#kredittkort-sort">Sorting order</a></li>
												<li><a href="#kredittkort-overview">Overview</a></li>
											</ul>
										</li>';
		$data['kredittkort']['info'] = '<li id="kredittkort-shortcode"><h2>Shortcodes</h2>
										<ul>
											<li><b>[kredittkort]</b>
											<p>[kredittkort] will show all.</p>
											</li>
											<li><b>[kredittkort name="xx, yy"]</b>
											<p>Shows only the creditcards that is mentioned in the shortcode.
											<br>The name needs to be the slug-name of the creditcard.
											<br>Loans are sorted by the position they have in name=""
											<br>eks.: [kredittkort name="lendo-kort"] will only show the creditcard with slug-name "lendo-kort"
											<br>[kredittkort name="lendo-privatlan, axo-finans"] will show 2 loans: lendo and axo.</p>
											<li><b>[kredittkort kredittkort="xx"]</b>
											<p>kredittkort must match the slug-name of the kredittkort type.
											<br>The loans are sorted by the sort order given in load edit page for that type.
											<br>Eks: [kredittkort kredittkort="frontpage"] shows all loans with the category "frontpage" in the order of lowest number
											<br>of field "Sort frontpage" has in the load editor page.</p>
											</li>
											</li>
											<li><b>[kredittkort-bilde name="xx"]</b>
											<p>Name is required. Will show the creditcard\'s thumbnail with a link.
											<br>[kredittkort-bestill name="xx" source="test"] will append &source=test at the link.</p></li>
											<li><b>[kredittkort-bestill name="xx"]</b>
											<p>Name is required. Will show the creditcard\'s button.
											<br>[kredittkort-bestill name="xx" source="test"] will append &source=test at the link.</p></li>
										</ul>
										</li>
										<li id="kredittkort-aldri"><h2>Aldri vis</h2>
										<p>If tagged, then the creditcard will never appear on the front-end.</p>
										</li>
										</li>
										<li id="kredittkort-sort"><h2>Sorting order</h2>
										<p>The loans will be shown with the lowest "Sort"-value first.
										<br>When only showing a specific category on creditcard page, then the sort order column will reflect 
										<br>that category\'s sort order.</p>
										</li>
										<li id="kredittkort-overview"><h2>Overview</h2>
										<p> The <a target="_blank" href="'.get_site_url().'/wp-admin/edit.php?post_type=kredittkort&page=kredittkort-overview">overview page</a> will show every post and page and whether or not there are
										<br>any lan shortcodes in them.
										<br>You can sort the columns alphabetically</p>
										</li>
										';

		return $data;
	}

	/**
	 * wp filter for adding columns on ctp list page
	 * 
	 * @param  [array] $defaults [array going through wp filter]
	 * @return [array]           [array going through wp filter]
	 */
	public function column_head($defaults) {
		$defaults['kredittkort_sort'] = 'Sorting Order';
		return $defaults;
	}


	/**
	 * filter for populating columns on ctp list page
	 * 
	 * @param  [array] $defaults [array going through wp filter]
	 * @return [array]           [array going through wp filter]
	 */
	public function custom_column($column_name) {
		global $post;
		// echo $_SERVER['QUERY_STRING'];

		// echo parse_url()
		
		// echo print_r($q_out, true);

		if ($column_name == 'kredittkort_sort') {
			$q_out = null;
			parse_str($_SERVER['QUERY_STRING'], $q_out);

			$meta = 'kredittkort_sort';
			if (isset($q_out['kredittkorttype'])) $meta = $meta.'_'.$q_out['kredittkorttype'];

			$meta = get_post_meta($post->ID, $meta);
			
			if (isset($meta[0])) echo $meta[0];
		}
	}


	/**
	 * filter for sorting by columns on ctp list page
	 * 
	 * @param  [array] $defaults [array going through wp filter]
	 * @return [array]           [array going through wp filter]
	 */
	public function sort_column($columns) {
		$columns['kredittkort_sort'] = 'kredittkort_sort';
		return $columns;
	}



	/*
		creates wordpress metabox
		adds javascript
	*/
	public function create_meta() {

		/* lan info meta */
		add_meta_box(
			'kredittkort_meta', // name
			'Kreditt Info', // title 
			array($this,'create_meta_box'), // callback
			'kredittkort' // page
		);

		/* to show or not on front-end */
		add_meta_box(
			'kredittkort_exclude',
			'Aldri vis',
			array($this, 'exclude_meta_box'),
			'kredittkort',
			'side'
		);
		
		/* adding admin css and js */
		// wp_enqueue_style('em-kredittkort-admin-style', KREDITTKORT_PLUGIN_URL . 'assets/css/admin/em-kredittkort.css', array(), '1.0.1');
		wp_enqueue_script('em-kredittkort-admin', KREDITTKORT_PLUGIN_URL . 'assets/js/admin/em-kredittkort.js', array(), '1.0.1', true);
	}


	/*
		creates content in metabox
	*/
	public function create_meta_box($post) {
		wp_nonce_field('em'.basename(__FILE__), 'kredittkort_nonce');

		$meta = get_post_meta($post->ID, 'kredittkort_data');
		$sort = get_post_meta($post->ID, 'kredittkort_sort');

		$tax = wp_get_post_terms($post->ID, 'kredittkorttype');

		$taxes = [];
		if (is_array($tax))
			foreach($tax as $t)
				array_push($taxes, $t->slug);

		$json = [
			'meta' => isset($meta[0]) ? $this->sanitize($meta[0]) : '',
			'kredittkort_sort' => isset($sort[0]) ? floatval($sort[0]) : '',
			'tax'  => $taxes
		];

		$ameta = get_post_meta($post->ID);
		foreach($ameta as $key => $value)
			if (strpos($key, 'kredittkort_sort_') !== false && isset($value[0])) $json[$key] = esc_html($value[0]);


		wp_localize_script('em-kredittkort-admin', 'kredittkort_meta', json_decode(json_encode($json), true));
		echo '<div class="kredittkort-meta-container"></div>';
	}
 

 	/**
 	 * [exclude_meta_box description]
 	 */
	public function exclude_meta_box() {
		global $post;

		$exclude = get_option('kredittkort_exclude');
		if (!is_array($exclude)) $exclude = [];

		$exclude_serp = get_option('kredittkort_exclude_serp');
		if (!is_array($exclude_serp)) $exclude_serp = [];

		echo '<p><input name="kredittkort_exclude" id="kredittkort_exc" type="checkbox"'.(array_search($post->ID, $exclude) !== false ? ' checked' : '').'><label for="kredittkort_exc">Kortet vil aldri bli vist på front-end.</label></p>
		      <p><input name="kredittkort_exclude_serp" id="kredittkort_exc_serp" type="checkbox"'.(array_search($post->ID, $exclude_serp) !== false ? ' checked' : '').'><label for="kredittkort_exc_serp">Vis ikke (kun) på internal search.</label></p>';
	}



	/**
	 * wp action when saving
	 */
	public function save($post_id) {
		// post type is kredittkort
		if (!get_post_type($post_id) == 'kredittkort') return;

		// is on admin screen
		if (!is_admin()) return;

		// user is logged in and has permission
		if (!current_user_can('edit_posts')) return;

		// nonce is sent
		if (!isset($_POST['kredittkort_nonce'])) return;

		// nonce is checked
		if (!wp_verify_nonce($_POST['kredittkort_nonce'], 'em'.basename(__FILE__))) return;

		// saves to wp option instead of post meta
		// when adding
		$this->u_option('kredittkort_exclude', $post_id);
		$this->u_option('kredittkort_exclude_serp', $post_id);
		// if (isset($_POST['kredittkort_exclude'])) {
		// 	$option = get_option('kredittkort_exclude');

		// 	// to avoid php error
		// 	if (!is_array($option)) $option = [];

		// 	// if not already added
		// 	if (array_search($post_id, $option) === false) {

		// 		// if to add to collection
		// 		if (is_array($option)) {
		// 			array_push($option, intval($post_id));

		// 			update_option('kredittkort_exclude', $option);
		// 		}
				
		// 		// if to create collection (of one)
		// 		else update_option('kredittkort_exclude', [$post_id]);
		// 	}
		// }
		// // when removing
		// else {
		// 	$option = get_option('kredittkort_exclude');

		// 	if (array_search($post_id, $option) !== false) {
		// 		unset($option[array_search($post_id, $option)]);
		// 		update_option('kredittkort_exclude', $option);
		// 	}
		// }

		// data is sent, then sanitized and saved
		if (isset($_POST['kredittkort_data'])) update_post_meta($post_id, 'kredittkort_data', $this->sanitize($_POST['kredittkort_data']));
		if (isset($_POST['kredittkort_sort'])) update_post_meta($post_id, 'kredittkort_sort', floatval($_POST['kredittkort_sort']));

		// saving kredittkort_sort_***
		foreach($_POST as $key => $po) {
			if (strpos($key, 'kredittkort_sort_') !== false)
				update_post_meta($post_id, sanitize_text_field(str_replace(' ', '', $key)), floatval($po));
		}

	}

	/**
	 * [updating wp option]
	 * @param  [type] $data  [var to be saved to]
	 * @param  [type] $value [data to be saved]
	 */
	private function u_option($data, $value) {
		$option = get_option($data);
		if (!is_array($option)) $option = []; // to avoid php error
		
		$value = intval($value);

		if (isset($_POST[$data])) {

			// if not already added
			if (array_search($value, $option) === false) {

				// if to add to collection
				if (is_array($option)) {
					array_push($option, $value);
					update_option($data, $option);
				}
				
				// if to create collection (of one)
				else update_option($data, [$value]);
			}
		}
		// when removing
		else {
			// $option = get_option($data);

			if (array_search($value, $option) !== false) {
				unset($option[array_search($value, $option)]);
				update_option($data, $option);
			}
		}
	}

	/*
		recursive sanitizer
	*/
	private function sanitize($data) {
		if (!is_array($data)) return wp_kses_post($data);

		$d = [];
		foreach($data as $key => $value) {
			switch ($key) {
				case 'bestill':
				case 'pixel':
				case 'readmore': $d[$key] = sanitize_text_field($value); break;
				default: $d[$key] = $this->sanitize($value); break;
			}
		}

		return $d;
	}
}