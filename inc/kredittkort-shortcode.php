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

		$float = false;
		if ($atts['float']) 
			switch ($atts['float']) {
				case 'left': $float = ' style="float: left; margin-right: 3rem;"'; break;
				case 'right': $float = ' style="float: right; margin-left: 3rem;"'; break;
			}
		

		// returns with anchor
		if ($meta['bestill']) return '<div class="kredittkort-logo-ls"'.($float ? $float : '').'><a target="_blank" rel=noopener href="'.esc_url($meta['bestill']).'"><img alt="'.esc_attr($post[0]->post_title).'" src="'.esc_url(get_the_post_thumbnail_url($post[0], 'full')).'"></a></div>';

		// anchor-less image
		return '<div class="kredittkort-logo-ls"'.($float ? $float : '').'><img alt="'.esc_attr($post[0]->post_title).'" src="'.esc_url(get_the_post_thumbnail_url($post[0], 'full')).'"></div>';
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

		$float = false;
		if ($atts['float']) 
			switch ($atts['float']) {
				case 'left': $float = ' style="float: left; margin-right: 3rem;"'; break;
				case 'right': $float = ' style="float: right; margin-left: 3rem;"'; break;
			}

		add_action('wp_enqueue_scripts', array($this, 'add_css'));
		return '<div class="kredittkort-bestill kredittkort-bestill-mobile"'.($float ? $float : '').'><a target="_blank" rel="noopener" class="kredittkort-link kredittkort-sokna-lenke" href="'.esc_url($meta['bestill']).'"><svg class="kredittkort-svg" version="1.1" x="0px" y="0px" width="26px" height="20px" viewBox="0 0 26 20" enable-background="new 0 0 24 24" xml:space="preserve"><path fill="none" d="M0,0h24v24H0V0z"/><path class="kredittkort-thumb" d="M1,21h4V9H1V21z M23,10c0-1.1-0.9-2-2-2h-6.31l0.95-4.57l0.03-0.32c0-0.41-0.17-0.79-0.44-1.06L14.17,1L7.59,7.59C7.22,7.95,7,8.45,7,9v10c0,1.1,0.9,2,2,2h9c0.83,0,1.54-0.5,1.84-1.22l3.02-7.05C22.95,12.5,23,12.26,23,12V10z"/></svg> Bestill Kortet</a></div>';
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


		foreach ($posts as $p) {
			$meta = get_post_meta($p->ID, 'kredittkort_data');

			// skip if no meta found
			if (isset($meta[0])) $meta = $meta[0];
			else continue;

			// sanitize meta
			$meta = $this->esc_kses($meta);

			$html = '<div class="kredittkort-container">'; // add class here
			
			for ($i = 1; $i <= 6; $i++) 
				$html .= '<div class="kredittkort-sep kredittkort-sep-'.$i.'"></div>';
			
			// title 
			$html .= '<div class="kredittkort-title"><h2 class="kredittkort-title-header"><a class="kredittkort-title-text" href="'.esc_url($meta['readmore']).'">'.esc_html($p->post_title).'</a></h2></div>';
		
			// image
			$html .= '<div class="kredittkort-thumbnail"><img class="kredittkort-thumbnail-image" src="'.get_the_post_thumbnail_url($p).'"></div>';
			
			// info en
			$html .= '<div class="kredittkort-info-0 kredittkort-info">'.$meta['info01'].'</div>';

			// info to
			$html .= '<div class="kredittkort-info-1 kredittkort-info">'.$meta['info02'].'</div>';

			// info tre
			$html .= '<div class="kredittkort-info-2 kredittkort-info">'.$meta['info03'].'</div>';

			// bestill button
			$html .= '<div class="kredittkort-sokna">';

			if ($meta['terning'] != 'ingen') {
				$html .= '<svg class="kredittkort-terning">
							<defs>
							    <linearGradient id="kredittkort-grad" x1="0%" y1="100%" x2="100%" y2="100%">
							      <stop offset="0%" style="stop-color:rgb(200,0,0);stop-opacity:1" />
							      <stop offset="100%" style="stop-color:rgb(255,0,0);stop-opacity:1" />
							    </linearGradient>
							  </defs>
							<rect class="rect-svg" rx="7" ry="7" fill="url(#kredittkort-grad)"/>';

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

			$html .= '<a target="_blank" rel="noopener" class="kredittkort-link kredittkort-sokna-lenke" href="'.esc_url($meta['bestill']).'">Bestill Kortet</a>';
			$html .= '</div>';
			
			// read more button
			$html .= '<div class="kredittkort-lesmer"><a class="kredittkort-link kredittkort-lesmer-lenke" href="'.esc_url($meta['readmore']).'">Les Mer</a></div>';
			
			// aldersgrense 
			$html .= '<div class="kredittkort-aldersgrense">'.$meta['info05'].'</div>';

			// maks rente
			$html .= '<div class="kredittkort-makskreditt">'.$meta['info06'].'</div>';
			
			// rentefri kreditt
			$html .= '<div class="kredittkort-rentefrikreditt">'.$meta['info07'].'</div>';
			
			// effektiv rente
			$html .= '<div class="kredittkort-effrente">'.$meta['info08'].'</div>';

			// blurb and logos
			$html .= '<div class="kredittkort-blurb"><div class="kredittkort-blurb-text">'.$meta['info04'].'</div>';


			// visa/mastercard logo 
			$html .= '<div class="kredittkort-logo-container">';

			$terms = wp_get_post_terms($p->ID, 'kredittkorttype');
			foreach($terms as $term) {
				switch ($term->slug) {
					case 'visa': $html .= '<img class="kredittkort-logo" src="'.KREDITTKORT_PLUGIN_URL.'assets/img/visa-logo.png">'; break;
					case 'mastercard': $html .= '<img class="kredittkort-logo" src="'.KREDITTKORT_PLUGIN_URL.'assets/img/mastercard-logo.png">'; break;
				}
			}

			$html .= '</div>'; // visa/mastercard container

			$html .= '</div>'; // blurb container
			
			$html .= '</div>';

		}
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