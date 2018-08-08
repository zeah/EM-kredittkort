<?php 

/**
 * WP Shortcodes
 */
final class Kredittkort_shortcode {
	/* singleton */
	private static $instance = null;

	public static function get_instance() {
		if (self::$instance === null) self::$instance = new self();

		return self::$instance;
	}

	private function __construct() {
		$this->wp_hooks();
	}


	/**
	 * hooks for wp
	 */
	private function wp_hooks() {

		// loan list
		if (!shortcode_exists('kredittkort')) add_shortcode('kredittkort', array($this, 'add_shortcode'));
		else add_shortcode('emkredittkort', array($this, 'add_shortcode'));

		// loan thumbnail
		if (!shortcode_exists('kredittkort-bilde')) add_shortcode('kredittkort-bilde', array($this, 'add_shortcode_bilde'));
		else add_shortcode('emkredittkort-bilde', array($this, 'add_shortcode_bilde'));

		// loan button
		if (!shortcode_exists('kredittkort-bestill')) add_shortcode('kredittkort-bestill', array($this, 'add_shortcode_bestill'));
		else add_shortcode('emkredittkort-bestill', array($this, 'add_shortcode_bestill'));


		add_filter('search_first', array($this, 'add_serp'));
	}


	/**
	 * returns a list of loans
	 */
	public function add_shortcode($atts, $content = null) {

		add_action('wp_enqueue_scripts', array($this, 'add_css'));

		if (!is_array($atts)) $atts = [];

		$args = [
			'post_type' 		=> 'kredittkort',
			'posts_per_page' 	=> -1,
			'orderby'			=> [
										'meta_value_num' => 'ASC',
										'title' => 'ASC'
								   ],
			'meta_key'			=> 'kredittkort_sort'.($atts['lan'] ? '_'.sanitize_text_field($atts['lan']) : '')
		];


		$type = false;
		if (isset($atts['lan'])) $type = $atts['lan'];
		if ($type)
			$args['tax_query'] = array(
					array(
						'taxonomy' => 'kredittkorttype',
						'field' => 'slug',
						'terms' => sanitize_text_field($type)
					)
				);


		$names = false;
		if (isset($atts['name'])) $names = explode(',', preg_replace('/ /', '', $atts['name']));
		if ($names) $args['post_name__in'] = $names;
		
		$exclude = get_option('kredittkort_exclude');

		if (is_array($exclude) && !empty($exclude)) $args['post__not_in'] = $exclude;

		$posts = get_posts($args);	

		$sorted_posts = [];
		if ($names) {
			foreach(explode(',', preg_replace('/ /', '', $atts['name'])) as $n)
				foreach($posts as $p) 
					if ($n === $p->post_name) array_push($sorted_posts, $p);
		
			$posts = $sorted_posts;
		}
				

		$html = $this->get_html($posts);

		return $html;
	}


	/**
	 * returns only thumbnail from loan
	 */
	public function add_shortcode_bilde($atts, $content = null) {
		if (!isset($atts['name']) || $atts['name'] == '') return;

		$args = [
			'post_type' 		=> 'kredittkort',
			'posts_per_page'	=> 1,
			'name' 				=> sanitize_text_field($atts['name'])
		];

		$post = get_posts($args);

		if (!is_array($post)) return;

		if (!get_the_post_thumbnail_url($post[0])) return;

		add_action('wp_enqueue_scripts', array($this, 'add_css'));

		$meta = get_post_meta($post[0]->ID, 'kredittkort_data');
		if (isset($meta[0])) $meta = $meta[0];

		// returns with anchor
		if ($meta['bestill']) return '<div class="kredittkort-logo-ls"><a target="_blank" rel=noopener href="'.esc_url($meta['bestill']).'"><img alt="'.esc_attr($post[0]->post_title).'" style="width: 100%; height: auto;" src="'.esc_url(get_the_post_thumbnail_url($post[0], 'full')).'"></a></div>';

		// anchor-less image
		return '<div class="kredittkort-logo-ls"><img alt="'.esc_attr($post[0]->post_title).'" style="width: 100%; height: auto;" src="'.esc_url(get_the_post_thumbnail_url($post[0], 'full')).'"></div>';
	}


	/**
	 * returns bestill button only from loan
	 */
	public function add_shortcode_bestill($atts, $content = null) {
		if (!isset($atts['name']) || $atts['name'] == '') return;

		$args = [
			'post_type' 		=> 'kredittkort',
			'posts_per_page'	=> 1,
			'name' 				=> sanitize_text_field($atts['name'])
		];

		$post = get_posts($args);

		if (!is_array($post)) return;

		$meta = get_post_meta($post[0]->ID, 'kredittkort_data');

		if (!is_array($meta)) return;

		$meta = $meta[0];

		if (!$meta['bestill']) return;

		add_action('wp_enqueue_scripts', array($this, 'add_css'));
		return '<div class="kredittkort-bestill kredittkort-bestill-mobile"><a target="_blank" rel="noopener" class="kredittkort-link" href="'.esc_url($meta['bestill']).'"><svg class="kredittkort-svg" version="1.1" x="0px" y="0px" width="26px" height="20px" viewBox="0 0 26 20" enable-background="new 0 0 24 24" xml:space="preserve"><path fill="none" d="M0,0h24v24H0V0z"/><path class="kredittkort-thumb" d="M1,21h4V9H1V21z M23,10c0-1.1-0.9-2-2-2h-6.31l0.95-4.57l0.03-0.32c0-0.41-0.17-0.79-0.44-1.06L14.17,1L7.59,7.59C7.22,7.95,7,8.45,7,9v10c0,1.1,0.9,2,2,2h9c0.83,0,1.54-0.5,1.84-1.22l3.02-7.05C22.95,12.5,23,12.26,23,12V10z"/></svg> Ansök här!</a></div>';
	}


	/**
	 * adding sands to head
	 */
	public function add_css() {
        wp_enqueue_style('kredittkort-style', KREDITTKORT_PLUGIN_URL.'assets/css/pub/em-kredittkort.css', array(), '1.0.1', '(min-width: 841px)');
        wp_enqueue_style('kredittkort-mobile', KREDITTKORT_PLUGIN_URL.'assets/css/pub/em-kredittkort-mobile.css', array(), '1.0.1', '(max-width: 840px)');
	}


	/**
	 * returns the html of a list of loans
	 * @param  WP_Post $posts a wp post object
	 * @return [html]        html list of loans
	 */
	private function get_html($posts) {
		$html = '<ul class="kredittkort-ul">';

		foreach ($posts as $p) {
			
			$meta = get_post_meta($p->ID, 'kredittkort_data');

			// skip if no meta found
			if (isset($meta[0])) $meta = $meta[0];
			else continue;

			// sanitize meta
			$meta = $this->esc_kses($meta);

			// grid container
			$html .= '<li class="kredittkort-container">';

			// title
			$html .= '<div class="kredittkort-title-container"><a class="kredittkort-title" href="'.$meta['readmore'].'">'.wp_kses_post($p->post_title).'</a></div>';

			// thumbnail
			$html .= '<div class="kredittkort-logo-container"><a target="_blank" rel="noopener" href="'.$meta['bestill'].'"><img class="kredittkort-logo" src="'.wp_kses_post(get_the_post_thumbnail_url($p,'post-thumbnail')).'"></a></div>';

			// lesmer
			$html .= '<div class="kredittkort-lesmer-container"><a class="kredittkort-lesmer-link" href="'.$meta['readmore'].'">Les Mer</a></div>';

			// info container
			$html .= '<div class="kredittkort-info-container">';

			// info 1
			if ($meta['info01']) $html .= '<div class="kredittkort-info kredittkort-info-en">'.$meta['info01'].'</div>';

			// info 2
			if ($meta['info02']) $html .= '<div class="kredittkort-info kredittkort-info-to">'.$meta['info02'].'</div>';

			// info 3
			if ($meta['info03']) $html .= '<div class="kredittkort-info kredittkort-info-tre">'.$meta['info03'].'</div>';


			$html .= '</div>';

			// info list container 
			$html .= '<div class="kredittkort-list-container">';

			// info 5
			if ($meta['info05']) $html .= '<div class="kredittkort-info kredittkort-info-fem">'.$meta['info05'].'</div>';

			// info 6
			if ($meta['info06']) $html .= '<div class="kredittkort-info kredittkort-info-seks">'.$meta['info06'].'</div>';

			// info 7
			if ($meta['info07']) $html .= '<div class="kredittkort-info kredittkort-info-syv">'.$meta['info07'].'</div>';


			$html .= '</div>';

			$html .= '<div class="kredittkort-bunnlinje-container">';

			$html .= '<div class="kredittkort-info-fire">';

			$html .= '<div class="kredittkort-small-logo">test</div>';
			// info 4 (bunnlinje info)
			$html .= '<div class="kredittkort-info kredittkort-info-fire-inner">'.$meta['info04'].'</div>';
			// if ($meta['info04']) $html .= '<div class="kredittkort-info kredittkort-info-fire-inner">'.$meta['info04'].'</div>';


			$html .= '</div>';

			// info 8 (effektiv rente)
			if ($meta['info08']) $html .= '<div class="kredittkort-info kredittkort-info-atte">'.$meta['info08'].'</div>';

			$html .= '</div>';

			$html .= '<div class="kredittkort-end-container">';
			// terning
			if ($meta['terning'] != 'ingen') {
				$html .= '<svg class="kredittkort-terning">
							<defs>
							    <linearGradient id="kredittkort-grad" x1="0%" y1="100%" x2="100%" y2="100%">
							      <stop offset="0%" style="stop-color:rgb(200,0,0);stop-opacity:1" />
							      <stop offset="100%" style="stop-color:rgb(255,0,0);stop-opacity:1" />
							    </linearGradient>
							  </defs>
							<rect class="kredittkort-rect-svg" rx="7" ry="7" fill="url(#kredittkort-grad)"/>';

				switch ($meta['terning']) {

					case 'seks':
					$html .= '<circle class="kredittkort-circle-svg" cx="11" cy="25" r="5"/>';
					$html .= '<circle class="kredittkort-circle-svg" cx="39" cy="25" r="5"/>';

					case 'fire':
					$html .= '<circle class="kredittkort-circle-svg" cx="11" cy="10" r="5"/>';
					$html .= '<circle class="kredittkort-circle-svg" cx="39" cy="40" r="5"/>';

					case 'to':
					$html .= '<circle class="kredittkort-circle-svg" cx="11" cy="40" r="5"/>';
					$html .= '<circle class="kredittkort-circle-svg" cx="39" cy="10" r="5"/>';
					break;

					case 'fem':
					$html .= '<circle class="kredittkort-circle-svg" cx="10" cy="10" r="5"/>';
					$html .= '<circle class="kredittkort-circle-svg" cx="40" cy="40" r="5"/>';

					case 'tre':
					$html .= '<circle class="kredittkort-circle-svg" cx="10" cy="40" r="5"/>';
					$html .= '<circle class="kredittkort-circle-svg" cx="40" cy="10" r="5"/>';

					case 'en':
					$html .= '<circle class="kredittkort-circle-svg" cx="25" cy="25" r="5"/>';
					break;

				}

				$html .= '</svg>';
			}

			// bestill 
			$html .= '<div class="kredittkort-bestill-container">';
			$html .= '<div class="kredittkort-bestill"><a target="_blank" rel="noopener" class="kredittkort-link" href="'.$meta['bestill'].'"><svg class="kredittkort-svg" version="1.1" x="0px" y="0px" width="26px" height="20px" viewBox="0 0 26 20" enable-background="new 0 0 24 24" xml:space="preserve"><path fill="none" d="M0,0h24v24H0V0z"/><path class="kredittkort-thumb" d="M1,21h4V9H1V21z M23,10c0-1.1-0.9-2-2-2h-6.31l0.95-4.57l0.03-0.32c0-0.41-0.17-0.79-0.44-1.06L14.17,1L7.59,7.59C7.22,7.95,7,8.45,7,9v10c0,1.1,0.9,2,2,2h9c0.83,0,1.54-0.5,1.84-1.22l3.02-7.05C22.95,12.5,23,12.26,23,12V10z"/></svg> Ansök här!</a></div>';
			// $html .= '<div class="kredittkort-bestilltext">'.$meta['bestill_text'].'</div>';
			$html .= '</div>';

			$html .= '</div>'; // end-container

			$html .= '</li>';
		}

		$html .= '</ul>';

		return $html;
	}



	/**
	 * wp filter for adding to internal serp
	 * array_push to $data
	 * $data['html'] to be printed
	 * 
	 * @param [Array] $data [filter]
	 */
	public function add_serp($data) {
		global $post;

		if ($post->post_type != 'kredittkort') return $data;

		$exclude = get_option('kredittkort_exclude');

		if (!is_array($exclude)) $exclude = [];

		if (in_array($post->ID, $exclude)) return $data;

		$html['html'] = $this->get_html([$post]);

		array_push($data, $html);
		add_action('wp_enqueue_scripts', array($this, 'add_css'));

		return $data;
	}



	/**
	 * kisses the data
	 * recursive sanitizer
	 * 
	 * @param  Mixed $data Strings or Arrays
	 * @return Mixed       Kissed data
	 */
	private function esc_kses($data) {
		if (!is_array($data)) return wp_kses_post($data);

		$d = [];
		foreach($data as $key => $value)
			$d[$key] = $this->esc_kses($value);

		return $d;
	}
}