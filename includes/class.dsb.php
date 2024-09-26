<?php
/*
 * The DSB_Seo_Builder singleton is used to store information about url parts, search terms, locations etc so we do not need to call
 * these functions thousands of times
 */
class DSB_Seo_Builder
{
	public static $instance;

	/**
	 * Search Terms
	 *
	 * @var array List of Search Terms
	 */
	public $search_terms = array();

    /**
	 * Locations
	 *
	 * @var array List of Locations
	 */
	public $locations = array();

    /**
	 * SEO pages
	 *
	 * @var array List of SEO pages
	 */
	public $seo_pages = array();

    /**
	 * All unique slug combinations which can be generated for the search terms and locations set in the CPT $post_id page
	 *
	 * @var array Unique slug combinations list
	 */
	public $slugs = array();

    /**
	 * @var int Keeps track of the index of the active slug
	 */
    public $lookup_table_slug_index = 0;

    /**
	 * All url combinations which can be generated for the search terms and locations set in the CPT $post_id page for ALL SEO Pages combined
	 *
	 * @var array Unique seo page urls list
	 */
	public $all_seo_pages_urls = array();

    /**
	 * All url combinations which can be generated for the search terms and locations set in the CPT $post_id page
	 *
	 * @var array Unique seo page urls combinations list
	 */
	public $seo_page_urls = array();

    /**
	 * Maximum number of Search Terms to use
	 *
	 * @var int Maximum number of Search Terms
	 */
	public $max_search_terms = 20;

    /**
	 * Maximum number of Locations to use
	 *
	 * @var int Maximum number of Search Terms
	 */
	public $max_locations = 300;

    /**
	 * Maximum number of entries per sitemap page
	 *
	 * @var int Maximum number of entries per sitemap page
	 */
	public $entries_per_sitemap_page = 1000;

	public $search_term_single_placeholder          = '';
    public $search_term_plural_placeholder          = '';

	public $location_single_placeholder		        = '';
    public $location_plural_placeholder		        = '';

    public $default_search_term_single_placeholder  = '[search_term]';
	public $default_search_term_plural_placeholder  = '[search_terms]';
    
    public $default_location_single_placeholder     = '[location]';
    public $default_location_plural_placeholder     = '[locations]';

	function __construct()
	{
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

	/**
     * Return instance of DSB_Seo_Builder
     * 
     * @return DSB_Seo_Builder
     */
	public static function get_instance()
	{
        if (self::$instance === null)
		{
            self::$instance = new self();
        }

        return self::$instance;
    }

    /*
	 * Return all Search Terms entered in the Textarea field of the given $post_id SEO Page
	 *
	 * @param $post_id	int The post id
	 *
	 * @return $search_terms	Array The list of search terms
	 */
    public function dsb_get_search_terms($post_id)
    {
		if ($post_id !== false && empty($this->search_terms[$post_id]))
        {
            $value                          = get_post_meta($post_id, 'dsb-search-terms', true);
			$this->search_terms[$post_id]   = dsb_textarea_value_to_array($value);
        }

        return $this->search_terms[$post_id];
    }

    /*
	 * Return all Locations entered in the Textarea field of the given $post_id SEO Page
	 *
	 * @param string $placeholder Placeholder, something like [search_term] or [location]
	 *
	 * @return array $locations	The list of locations
	 */
    public function dsb_get_ucfirst_placeholder($placeholder)
    {
        // ucfirst() does not work, as first character is the square bracket [search_term]
        // So let's change the second letter to uppercase
        
        $placeholder[1]  = strtoupper($placeholder[1]);

        return $placeholder;
    }

    /*
	 * Return all Locations entered in the Textarea field of the given $post_id SEO Page
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array $locations	The list of locations
	 */
    public function dsb_get_locations($post_id)
    {
        if ($post_id !== false && empty($this->locations[$post_id]))
        {
            $value                  = get_post_meta($post_id, 'dsb-locations', true);
			$this->locations[$post_id] = dsb_textarea_value_to_array($value);
        }

        return $this->locations[$post_id];
    }

    /*
	 * Return all CPT dsb_seo_page posts from the CMS
	 *
	 * @return $search_terms	Array The list of locations
	 */
    public function dsb_get_seo_pages($post_status = 'publish')
    {
        if (empty($this->seo_pages))
        {
            $this->seo_pages = false;

            // Fetch all pages from the CPT dsb_seo_page
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

    public function dsb_get_slug_placeholder($post_id)
    {
        $slug_placeholder = get_post_meta($post_id, 'dsb-slug-placeholder', true);
    
        // We need something... so use a default as fallback
        if (empty($slug_placeholder))
        {
            $dsb                        = DSB_Seo_Builder::get_instance();
            $search_term_placeholder	= $dsb->get_search_term_single_placeholder();
			$location_placeholder		= $dsb->get_location_single_placeholder();

            $slug_placeholder           = "{$search_term_placeholder}-in-{$location_placeholder}";
        }
    
        return strtolower($slug_placeholder);
    }

    public function dsb_get_search_word_and_location_from_slug($post_id = false)
    {
        $search_term_and_slug   = false;
        $lookup_table           = dsb_get_search_terms_and_locations_lookup_table($post_id);

        // We cannot fetch the search_word and / or location from the url
        if (get_post_status($post_id) !== 'publish')
        {
            // Just get the first search_word and location we can find
            $search_terms   = $this->dsb_get_search_terms($post_id);
            $locations      = $this->dsb_get_locations($post_id);

            // Only fetch 1st search_word and location if BOTH are filled in CMS.
            // Otherwise replacing one puts less emphasize on the other missing and the user might not notice
            if (is_array($search_terms) && count($search_terms) > 0 && is_array($locations) && count($locations) > 0)
            {
                $search_term_and_slug[] = current($search_terms);
                $search_term_and_slug[] = current($locations);
            }
        }
        else
        {
            // SEO Page is published, we should have a query var
            $slug   = get_query_var('dsb_seo_page');

            if ($slug && isset($lookup_table[$slug]))
            {
                $search_term_and_slug = $lookup_table[$slug];
            }
        }

        return $search_term_and_slug;
    }

    public function dsb_store_search_word_and_location_for_slugs($post_id = false)
    {
        // Old lookup table
        $lookup_table               = dsb_get_search_terms_and_locations_lookup_table($post_id);

        // Build a new lookup table with latest data
        $search_term_and_slug       = array();
        
        if (get_post_type($post_id) === 'dsb_seo_page')// && empty($search_term_and_slug) || $post_id !== false)
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
                
                // We cannot simply explode the slug on dashes. [search_term] and [place] can have dashes and spaces like 'free website' and 'new york'
                $search_terms               = $this->dsb_get_search_terms($post_id);
                $locations                  = $this->dsb_get_locations($post_id);

                if (is_array($search_terms) && count($search_terms) > 0 && is_array($locations) && count($locations) > 0 && !empty($seo_page_base))
                {
                    // $num_urls   = count($search_terms) * count($locations);
                    $index      = 0;

                    foreach ($search_terms as $search_term)
                    {
                        foreach ($locations as $location)
                        {
                            // Reset for this loop item
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

                            // $search_term and $location have already been escaped with esc_html() in dsb_textarea_value_to_array()
                            $slug              = str_replace($search_term_placeholder, $search_term_lowercase, $slug_placeholder);
                            $slug              = str_replace($location_placeholder, $location_lowercase, $slug);

                            $search_term_and_slug[$slug][0]  = $search_term_single;
                            $search_term_and_slug[$slug][1]  = $location_single;
                            $search_term_and_slug[$slug][2]  = $search_term_plural;
                            $search_term_and_slug[$slug][3]  = $location_plural;

                            if (isset($lookup_table[$slug]) && isset($lookup_table[$slug][4]) && substr( $lookup_table[$slug][4], 0, 4 ) !== "-001")
                            {
                                // If we already set a date and told search engines, we can't change the date suddenly every time we save the dsb_seo_page
                                $date = $lookup_table[$slug][4];
                            }
                            else
                            {
                                $offset_minutes     = $index * 5;       // 5 minutes
                                $offset_seconds     = ($offset_minutes * 60) + mt_rand(5, 295);  // + between 5 and 295 seconds (so almost 5 minutes)
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

    public function dsb_get_seo_page_slugs($post_id, $max_results = false)
    {
        $slugs        = false;//get_post_meta($post_id, 'dsb-slugs', true);
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

    public function dsb_get_lookup_table_slug_index()
    {
        // This means we did not try to find the index yet
        if ($this->lookup_table_slug_index === 0)
        {
            $lookup_table   = dsb_get_search_terms_and_locations_lookup_table(get_the_ID());
            $slug           = get_query_var('dsb_seo_page');

            // Get all keys of the associative array
            $keys           = array_keys($lookup_table);

            // Find the index of the key
            $this->lookup_table_slug_index = (int)array_search($slug, $keys);

            // $slug not found as index in the $lookup_table
            // Avoid trying to lookup again
            if ($this->lookup_table_slug_index === false)
            {
                $this->lookup_table_slug_index = -1;
            }
        }

        return $this->lookup_table_slug_index;
    }

    public function dsb_get_all_seo_pages_urls()
    {
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

    // Return urls from all seo pages combined, defined by $length and $offset in the total list
    public function dsb_get_seo_pages_urls($offset, $length)
    {
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

    // Return urls from a single seo page
    public function dsb_get_seo_page_urls($post_name, $post_id, $max_results = false)
    {
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

    /*
	 * Return max search terms
	 *
	 * @return $max_search_terms	int The max search terms
	 */
    public function dsb_get_max_search_terms()
    {
        return $this->max_search_terms;
    }

    /*
	 * Return max locations
	 *
	 * @return $max_locations	int The max locations
	 */
    public function dsb_get_max_locations()
    {
        return $this->max_locations;
    }

    /*
	 * Return entries per sitemap page
	 *
	 * @return $max_locations	int The entries per sitemap page
	 */
    public function dsb_get_entries_per_sitemap_page()
    {
        return $this->entries_per_sitemap_page;
    }

	/*
	 * Return search_term_single_placeholder
	 *
	 * @return string The search term single placeholder
	 */
    public function get_search_term_single_placeholder()
    {
        return $this->search_term_single_placeholder;
    }

    /*
	 * Return search_term_plural_placeholder
	 *
	 * @return string The search term plural placeholder
	 */
    public function get_search_term_plural_placeholder()
    {
        return $this->search_term_plural_placeholder;
    }

	/*
	 * Return location_single_placeholder
	 *
	 * @return string The location single placeholder
	 */
    public function get_location_single_placeholder()
    {
        return $this->location_single_placeholder;
    }

    /*
	 * Return location_plural_placeholder
	 *
	 * @return string The location plural placeholder
	 */
    public function get_location_plural_placeholder()
    {
        return $this->location_plural_placeholder;
    }

	/*
	 * Does the given string contain any of the placeholders?
	 *
	 * @param string    $string     The string to test if it has any placeholders
     * @param bool      $check_both Check for both placeholders, not just one
	 *
	 * @return $result	bool If the string contains at least one or both placeholders
	 */
	public function has_any_placeholder($string, $check_both = false)
	{
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
