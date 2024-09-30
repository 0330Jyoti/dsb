<?php
class DSB_Seo_Builder {
	public static $instance;
	public $search_terms = array();
	public $locations = array();
	public $seo_pages = array();
	public $slugs = array();
    public $lookup_table_slug_index = 0;
	public $all_seo_pages_urls = array();
	public $seo_page_urls = array();
	public $max_search_terms = 20;
	public $max_locations = 300;
	public $entries_per_sitemap_page = 1000;
	public $search_term_single_placeholder          = '';
    public $search_term_plural_placeholder          = '';
	public $location_single_placeholder		        = '';
    public $location_plural_placeholder		        = '';
    public $default_search_term_single_placeholder  = '[search_term]';
	public $default_search_term_plural_placeholder  = '[search_terms]';
    public $default_location_single_placeholder     = '[location]';
    public $default_location_plural_placeholder     = '[locations]';

	function __construct(){
        $this->search_term_single_placeholder  = get_option('dsb-search-term-placeholder', '[search_term]');
        $this->search_term_plural_placeholder  = get_option('dsb-search-terms-placeholder', '[search_terms]');

        $this->location_single_placeholder     = get_option('dsb-location-placeholder', '[location]');
        $this->location_plural_placeholder     = get_option('dsb-locations-placeholder', '[locations]');

        if (empty($this->search_term_single_placeholder))
        {
            $this->search_term_single_placeholder = $this->default_search_term_single_placeholder;
        }

        if (empty($this->location_single_placeholder))
        {
            $this->location_single_placeholder = $this->default_location_single_placeholder;
        }
	}

	public static function get_instance(){
        if (self::$instance === null)
		{
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function dsb_get_search_terms($post_id){
		if ($post_id !== false && empty($this->search_terms[$post_id]))
        {
            $value                          = get_post_meta($post_id, 'dsb-search-terms', true);
			$this->search_terms[$post_id]   = dsb_textarea_value_to_array($value);
        }

        return $this->search_terms[$post_id];
    }

    public function dsb_get_ucfirst_placeholder($placeholder){
        
        $placeholder[1]  = strtoupper($placeholder[1]);

        return $placeholder;
    }

    public function dsb_get_locations($post_id){
        if ($post_id !== false && empty($this->locations[$post_id]))
        {
            $value                  = get_post_meta($post_id, 'dsb-locations', true);
			$this->locations[$post_id] = dsb_textarea_value_to_array($value);
        }

        return $this->locations[$post_id];
    }

    public function dsb_get_seo_pages($post_status = 'publish'){
        if (empty($this->seo_pages))
        {
            $this->seo_pages = false;
            $args = array(
                'post_type'         => 'dsb_seo_page',
                'posts_per_page'    => -1,
                'post_status'       => $post_status
            );

            $query      = new WP_Query($args);

            if ($query->have_posts())
            {
                $this->seo_pages = $query->posts;
            }
        }

        return $this->seo_pages;
    }

    public function dsb_get_slug_placeholder($post_id){
        $slug_placeholder = get_post_meta($post_id, 'dsb-slug-placeholder', true);
        if (empty($slug_placeholder))
        {
            $dsb                        = DSB_Seo_Builder::get_instance();
            $search_term_placeholder	= $dsb->get_search_term_single_placeholder();
			$location_placeholder		= $dsb->get_location_single_placeholder();

            $slug_placeholder           = "{$search_term_placeholder}-in-{$location_placeholder}";
        }
    
        return strtolower($slug_placeholder);
    }

    public function dsb_get_search_word_and_location_from_slug($post_id = false){
        $search_term_and_slug   = false;
        $lookup_table           = dsb_get_search_terms_and_locations_lookup_table($post_id);
        if (get_post_status($post_id) !== 'publish')
        {
            $search_terms   = $this->dsb_get_search_terms($post_id);
            $locations      = $this->dsb_get_locations($post_id);

            if (is_array($search_terms) && count($search_terms) > 0 && is_array($locations) && count($locations) > 0)
            {
                $search_term_and_slug[] = current($search_terms);
                $search_term_and_slug[] = current($locations);
            }
        }
        else
        {
            $slug   = get_query_var('dsb_seo_page');

            if ($slug && isset($lookup_table[$slug]))
            {
                $search_term_and_slug = $lookup_table[$slug];
            }
        }

        return $search_term_and_slug;
    }

    public function dsb_store_search_word_and_location_for_slugs($post_id = false){
        // Old lookup table
        $lookup_table               = dsb_get_search_terms_and_locations_lookup_table($post_id);
        $search_term_and_slug       = array();
        
        if (get_post_type($post_id) === 'dsb_seo_page')
        {
            if ($post_id === false)
            {
                $post_id                    = get_the_ID();
            }

            $post = get_post($post_id);

            if ($post)
            {
                $slug_placeholder           = $this->dsb_get_slug_placeholder($post_id);
                $seo_page_base              = $post->post_name;
                $search_term                = false;
                $location                   = false;
                $search_term_placeholder	= $this->get_search_term_single_placeholder();
                $location_placeholder		= $this->get_location_single_placeholder();
                $search_terms               = $this->dsb_get_search_terms($post_id);
                $locations                  = $this->dsb_get_locations($post_id);

                if (is_array($search_terms) && count($search_terms) > 0 && is_array($locations) && count($locations) > 0 && !empty($seo_page_base))
                {
                    $index      = 0;

                    foreach ($search_terms as $search_term)
                    {
                        foreach ($locations as $location)
                        {
                            $search_term_single = $location_single = $search_term_plural = $location_plural = '';
                            
                            if (strstr($search_term, "|"))
                            {
                                $my_search_terms = explode("|", $search_term);

                                if (is_array($my_search_terms) && count($my_search_terms) === 2)
                                {
                                    $search_term_single = trim($my_search_terms[0]);
                                    $search_term_plural = trim($my_search_terms[1]);
                                }
                            }
                            else
                            {
                                $search_term_single = $search_term;
                            }

                            if (strstr($location, "|"))
                            {
                                $my_locations = explode("|", $location);

                                if (is_array($my_locations) && count($my_locations) === 2)
                                {
                                    $location_single = trim($my_locations[0]);
                                    $location_plural = trim($my_locations[1]);
                                }
                            }
                            else
                            {
                                $location_single = $location;
                            }

                            $search_term_lowercase  = strtolower(sanitize_title($search_term_single));
                            $location_lowercase     = strtolower(sanitize_title($location_single));
                            $slug              = str_replace($search_term_placeholder, $search_term_lowercase, $slug_placeholder);
                            $slug              = str_replace($location_placeholder, $location_lowercase, $slug);

                            $search_term_and_slug[$slug][0]  = $search_term_single;
                            $search_term_and_slug[$slug][1]  = $location_single;
                            $search_term_and_slug[$slug][2]  = $search_term_plural;
                            $search_term_and_slug[$slug][3]  = $location_plural;

                            if (isset($lookup_table[$slug]) && isset($lookup_table[$slug][4]) && substr( $lookup_table[$slug][4], 0, 4 ) !== "-001")
                            {
                                $date = $lookup_table[$slug][4];
                            }
                            else
                            {
                                $offset_minutes     = $index * 5;       // 5 minutes
                                $offset_seconds     = ($offset_minutes * 60) + mt_rand(5, 295);  
                                $post               = get_post($post_id);
                                $post_modified_gmt  = $post->post_modified_gmt;
                                $date               = dsb_format_timestamp($post_modified_gmt, "-{$offset_seconds} seconds");
                            }

                            $search_term_and_slug[$slug][4]  = $date;
                            $search_term_and_slug[$slug][5]  = $seo_page_base;

                            $index++;
                        }
                    }
                }

                update_post_meta($post_id, 'dsb-search-word-and-location-for-slugs', $search_term_and_slug);
            }
        }

        return $search_term_and_slug;
    }

    public function dsb_get_seo_page_slugs($post_id, $max_results = false){
        $slugs        = false;
        $lookup_table = dsb_get_search_terms_and_locations_lookup_table($post_id);

        if (empty($lookup_table))
        {
            $lookup_table = $this->dsb_store_search_word_and_location_for_slugs($post_id);
        }

        if (is_array($lookup_table) && count($lookup_table) > 0)
        {
            $slugs = array_keys($lookup_table);
        }

        if ($max_results !== false && is_array($slugs) && count($slugs) > $max_results)
        {
            $slugs = array_slice($slugs, 0, $max_results);
        }

        return $slugs;
    }

    public function dsb_get_lookup_table_slug_index(){
        if ($this->lookup_table_slug_index === 0)
        {
            $lookup_table   = dsb_get_search_terms_and_locations_lookup_table(get_the_ID());
            $slug           = get_query_var('dsb_seo_page');
            $keys           = array_keys($lookup_table);
            $this->lookup_table_slug_index = (int)array_search($slug, $keys);

            if ($this->lookup_table_slug_index === false)
            {
                $this->lookup_table_slug_index = -1;
            }
        }

        return $this->lookup_table_slug_index;
    }

    public function dsb_get_all_seo_pages_urls(){
        if (count($this->all_seo_pages_urls) < 1)
        {
            $seo_pages 	        = $this->dsb_get_seo_pages();

            foreach ($seo_pages as $seo_page)
            {
                $this->dsb_get_seo_page_urls($seo_page->post_name, $seo_page->ID);
            }

            $this->all_seo_pages_urls = call_user_func_array('array_merge', $this->seo_page_urls);
        }

        return $this->all_seo_pages_urls;
    }

    public function dsb_get_seo_pages_urls($offset, $length){
        $seo_pages_urls = array();

        if (count($this->all_seo_pages_urls) < 1)
        {
            $this->dsb_get_all_seo_pages_urls();

            if ($offset < count($this->all_seo_pages_urls))
            {
                $seo_pages_urls = array_slice($this->all_seo_pages_urls, $offset, $length);
            }
        }

        return $seo_pages_urls;
    }

    public function dsb_get_seo_page_urls($post_name, $post_id, $max_results = false){
        if (empty($this->seo_page_urls[$post_id]))
        {
            $this->seo_page_urls[$post_id]  = array();
            $slugs                          = $this->dsb_get_seo_page_slugs($post_id, $max_results);

            if (is_array($slugs) && count($slugs) > 0)
            {
                foreach ($slugs as $slug)
                {
                    // Escaping: Securing Output
                    $this->seo_page_urls[$post_id][]     = esc_url(home_url($post_name . '/' . strtolower(sanitize_title($slug))));
                }
            }
        }

        return $this->seo_page_urls[$post_id];
    }

    public function dsb_get_max_search_terms(){
        return $this->max_search_terms;
    }

    public function dsb_get_max_locations(){
        return $this->max_locations;
    }

    public function dsb_get_entries_per_sitemap_page(){
        return $this->entries_per_sitemap_page;
    }

    public function get_search_term_single_placeholder(){
        return $this->search_term_single_placeholder;
    }

    public function get_search_term_plural_placeholder(){
        return $this->search_term_plural_placeholder;
    }

    public function get_location_single_placeholder(){
        return $this->location_single_placeholder;
    }

    public function get_location_plural_placeholder(){
        return $this->location_plural_placeholder;
    }

	public function has_any_placeholder($string, $check_both = false){
        $result = true;

        if ($check_both)
        {
            $result = stristr($string, $this->get_search_term_single_placeholder()) && stristr($string, $this->get_location_single_placeholder());
        }
        else
        {
            $result = stristr($string, $this->get_search_term_single_placeholder()) || stristr($string, $this->get_location_single_placeholder());
        }
		
		return $result;
	}
}
