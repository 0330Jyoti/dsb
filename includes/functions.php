<?php

function dsb_regenerate_search_terms_and_locations_lookup_tables()
{
	$dsb        = DSB_Seo_Builder::get_instance();
    $seo_pages  = $dsb->dsb_get_seo_pages();

    // Regenerate lookup tables
    if (is_array($seo_pages) && count($seo_pages) > 0)
    {
        foreach ($seo_pages as $seo_page)
        {
            $dsb->dsb_store_search_word_and_location_for_slugs($seo_page->ID);
        }
    }
}

function dsb_get_search_terms_and_locations_lookup_tables()
{
	$meta_key = 'dsb-search-word-and-location-for-slugs';

    $posts = get_posts(
        array(
            'post_type' 		=> 'dsb_seo_page',
            'meta_key' 			=> $meta_key,
            'posts_per_page' 	=> -1,
			'post_status'		=> 'publish'
        )
    );

    $meta_values = array();
    foreach( $posts as $post)
	{
		$post_meta = get_post_meta($post->ID, $meta_key, true);

		$meta_values = array_merge($meta_values, $post_meta);
    }

    return $meta_values;
}

function dsb_get_search_terms_and_locations_lookup_table($post_id)
{
	$lookup_table = array();
	if ($post_id > 0)
	{
		$lookup_table = get_post_meta($post_id, 'dsb-search-word-and-location-for-slugs', true);
	}

    return $lookup_table;
}

/**
 * Returns a value from the post_meta table with the search term and location placeholders already replaced
 * 
 * @param string 	$key     		The meta key to retrieve
 * @param int    	$post_id 		Post ID.
 * @param string	$default_value 	The default value to return if value is empty / not found
 * @param boolean	$do_replace		Replace the placeholders or not
 * 
 * @return string	$value		The value with the search term and location placeholders already replaced
 */
function dsb_get_field($key, $post_id = false, $default_value = false, $do_replace = true)
{
	$post_id	= dsb_get_valid_post_id($post_id);
	$value		= get_post_meta($post_id, $key, true);
    
	if ($do_replace)
	{
		$value		= dsb_get_seo_pages_replace_search_terms_and_locations($value);
	}

	if ($default_value !== false && empty($value))
	{
		$value = $default_value;
	}

	return $value;
}

/**
 *  This function will return a valid post_id based on the current screen / parameter
 * 
 *  @param	$post_id (mixed)
 * 
 *  @return	$post_id (mixed)
 */
function dsb_get_valid_post_id($post_id = 0)
{
	// if not $post_id, load queried object
	if( !$post_id )
	{
		// try for global post (needed for setup_postdata)
		$post_id = (int) get_the_ID();

		// try for current screen
		if( !$post_id )
		{
			$post_id = get_queried_object();
		}
	}

	if( is_object($post_id) )
	{
		// post
		if( isset($post_id->post_type, $post_id->ID) )
		{
			$post_id = $post_id->ID;

		// user
		} elseif( isset($post_id->roles, $post_id->ID) ) {

			$post_id = 'user_' . $post_id->ID;

		// term
		} elseif( isset($post_id->taxonomy, $post_id->term_id) ) {

			$post_id = 'term_' . $post_id->term_id;

		// comment
		} elseif( isset($post_id->comment_ID) ) {

			$post_id = 'comment_' . $post_id->comment_ID;

		// default
		} else {

			$post_id = 0;
		}
	}

	// And add DSB logic:
	if (is_admin())
	{
		global $pagenow;

		if ($pagenow === 'post.php' && isset($_GET['action']) && $_GET['action'] === 'edit')
		{
			$post_id = (int)$_GET['post'];
		}

		if (!$post_id && !empty($_POST)
			&& isset($_POST['action']) && $_POST['action'] === 'editpost'
			&& isset($_POST['post_type']) && $_POST['post_type'] === 'dsb_seo_page'
		)
		{
			$post_id = (int)$_POST['post_ID'];
		}
	}

	// return
	return $post_id;
}

// Return the single value if the string contains single | plural in 1 line
function dsb_get_single_value($value)
{
	if (strstr($value, "|"))
	{
		$values = explode("|", $value);

		if (is_array($values) && count($values) === 2)
		{
			$value = trim($values[0]);
		}
	}

	return $value;
}

// Return the plural value if the string contains "single | plural" in 1 line
function dsb_get_plural_value($value)
{
	if (strstr($value, "|"))
	{
		$values = explode("|", $value);

		if (is_array($values) && count($values) === 2)
		{
			$value = trim($values[1]);
		}
	}

	return $value;
}

/**
 * Flush rewrite rules when SEO page is saved
 * 
 * Workaround to automatically flush rewrite rules when needed
 */
function dsb_late_init_flush_rewrite_rules()
{
    if (!$option = get_option( 'dsb-flush-rewrite-rules'))
    {
        return false;
    }

    if ( $option == 1 )
    {
        flush_rewrite_rules();
        update_option( 'dsb-flush-rewrite-rules', 0 );
    }

    return true;
}
add_action('init', 'dsb_late_init_flush_rewrite_rules', 999999);

/**
 * Limits max number of allowed lines by stripping the lines over the limit
 * 
 * @param string	$string		The multiline string to trim
 * @param int		$max_lines	The max number of lines allowed
 * 
 * @return string	The multiline string maximum set lines
 */
function dsb_limit_max_lines($string, $max_lines)
{
	
    if (!empty($string))
	{
		$lines  = dsb_textarea_value_to_array($string);
		$lines  = array_slice($lines, 0, $max_lines);
		$string = implode(PHP_EOL, $lines);
	}
    return $string;
}

/**
 * Explodes textarea lines to php arrray
 * 
 * @param string 	$textarea_value The multiline string
 * 
 * @return array	$array			The multiline string where each line is added to an array
 */
function dsb_textarea_value_to_array($textarea_value)
{
	$array = false;
    if (!empty($textarea_value))
    {
        $array                 = array_values(array_filter(explode(PHP_EOL, $textarea_value))); // transform textarea lines to array
        $array                 = array_map('trim', $array);                                     // trim spaces from each line
        $array                 = array_unique($array);                                          // Remove duplicates
        $array                 = array_map('esc_html', $array);                                 // Escaping: Securing Output
    }

    return $array;
}

/**
 * Recursive find and replace
 * 
 * @param string		$find
 * @param string 		$replace
 * @param array|string 	$array
 * 
 * @return array
 */
function dsb_recursive_array_replace ($find, $replace, $array)
{
    if (! is_array($array)) {
        return str_replace($find, $replace, $array);
    }

    $newArray = array();

    foreach ($array as $key => $value) {
        $newArray[$key] = dsb_recursive_array_replace($find, $replace, $value);
    }

    return $newArray;
}

/*
 * dsb_is_empty
 *
 * Returns true if the value provided is considered "empty". Allows numbers such as 0.
 *
 * @param	mixed $var The value to check.
 * 
 * @return	bool
 */
function dsb_is_empty( $var )
{
	return ( !$var && !is_numeric($var) );
}

/**
 * Filters the robots.txt output.
 * 
 * Adds Sitemap to robots.txt
 *
 * @param string $output The robots.txt output.
 * @param bool   $public Whether the site is considered "public".
 * 
 * @return string Robots.txt content with sitemap added
 */
function dsb_add_sitemap_to_robots($output, $public)
{
    $output .= "\r\nSitemap: " . dsb_get_sitemap_url();

	return $output;
}
add_filter('robots_txt', 'dsb_add_sitemap_to_robots', 10, 2);

/**
 * Array helper function to easily insert a value in an associative array after given needle
 * 
 * @see https://stackoverflow.com/questions/21335852/move-an-associative-array-key-within-an-array
 * 
 * @param string[] 	$array  		Associative array
 * @param string 	$insert_after 	Key (needle) to inser new key / value pair after
 * @param string 	$key 			New key to insert
 * @param string 	$value 			New value for given key
 * 
 * @return array Array with value inserted after given position
 */
function dsb_array_insert_after($array, $insert_after, $key, $new)
{
    $pos = (int) array_search($insert_after, array_keys($array)) + 1;
    return array_merge(
        array_slice($array, 0, $pos),
        array($key => $new),
        array_slice($array, $pos)
    );
}

/**
 * Checks if given option exists in options table
 * 
 * Will return true if the option exists, but the value is empty, 0, null, false
 * 
 * @global $wpdb 
 * 
 * @param string $option The option to check
 * 
 * @return bool Whether the option exists or not
 */
function dsb_option_exists($option)
{
    global $wpdb;

	$table	= $wpdb->prefix . "options";
	$sql	= $wpdb->prepare("SELECT * FROM {$table} WHERE option_name LIKE %s", $option);
	$result = (bool)$wpdb->query($sql);

	return $result;
}

add_filter( "previous_post_link", "dsb_get_adjacent_post_link", 10, 5);
add_filter( "next_post_link", "dsb_get_adjacent_post_link", 10, 5);
function dsb_get_adjacent_post_link($output, $format, $link, $post, $adjacent)
{
	if (!empty($output) && get_post_type($post) === 'dsb_seo_page')
	{
		libxml_use_internal_errors(true);	// disable warnings about imperfect HTML markup
		$html = new DOMDocument();
		$html->loadHTML($output);

		foreach($html->getElementsByTagName('a') as $link)
		{
			$dsb 		= DSB_Seo_Builder::get_instance();

			// Fetch new link
			$new_link 	= $dsb->dsb_get_seo_page_urls($post->post_name, $post->ID, 1);

			// Replace first search term and first location in the title of given post
			$search_terms    = $dsb->dsb_get_search_terms($post->ID);

			if (is_array($search_terms) && count($search_terms) > 0)
			{
				$search_term_single = current($search_terms);
			}

			$locations      = $dsb->dsb_get_locations($post->ID);

			if (is_array($locations) && count($locations) > 0)
			{
				$location_single = current($locations);
			}
			
			$title	= dsb_get_seo_pages_replace_search_terms_and_locations($post->post_title, $post->ID, $search_term_single, $location_single);

			// Change the <a href>-tag:
			$link->setAttribute('href', $new_link[0]);			
			$link->nodeValue = $title;
		}

		$output = $html->saveHtml();
	}

	 return $output;
}

/**
 * Formats the given timestamp to the needed format.
 *
 * @param string $date 		The date to use for the formatting.
 * @param string $offset    Change date with this offset, example: "+30 minutes"
 *
 * @return string The formatted date.
 */
function dsb_format_timestamp($date, $offset = false)
{
	$date = new DateTime($date);

	if ($offset !== false)
	{
		$date->modify($offset); //or whatever value you want
	}
	return $date->format('c');
}

/**
 * Filters the canonical URL for a post.
 *
 * @see https://developer.wordpress.org/reference/hooks/get_canonical_url/
 * @see https://developer.yoast.com/features/seo-tags/canonical-urls/api/
 *
 * @param string  $canonical_url The post's canonical URL.
 */
add_filter( 'get_canonical_url', 'dsb_get_canonical_url', 999999999999999999999999999, 1);
add_filter( 'wpseo_canonical', 'dsb_get_canonical_url', 999999999999999999999999999, 1);
function dsb_get_canonical_url ($canonical_url, $post = false)
{
	$post_id = (int)dsb_get_valid_post_id();

	if ((int)get_query_var('dsb_seo_page_archive'))
    {
		// Remove Canonical tag from paginated archive pages
        $canonical_url = false;
    }
	else if ($post_id > 0 && get_post_type($post_id) === 'dsb_seo_page' && get_post_status($post_id) === 'publish' && get_query_var('dsb_seo_page'))
	{
		remove_filter('post_type_link', 'dsb_remove_slug', 10, 3);

		$permalink 	= trailingslashit(get_permalink($post_id));
		$slug   	= get_query_var('dsb_seo_page');

		$canonical_url = $permalink . $slug;

		if (strstr($canonical_url, '/dsb_seo_page/'))
		{
			$canonical_url = str_replace( '/dsb_seo_page/', '/', $canonical_url );
		}

		$canonical_url 	= trailingslashit($canonical_url);
	}

	return $canonical_url;
}

// Remove <link rel='shortlink' href='http://localhost/lite/klanten/connectr/seobuilder/?p={$post_id}' /> from head
add_filter('get_shortlink', 'dsb_get_shortlink', 10, 2);
function dsb_get_shortlink($shortlink, $post_id)
{
	if ((int)$post_id === 0)
	{
		$post_id = dsb_get_valid_post_id();
	}
	
	if (($post_id > 0 && get_post_type($post_id) === 'dsb_seo_page') || (int)get_query_var('dsb_seo_page_archive'))
	{
		$shortlink = false;
		
	}
	
	return $shortlink;
}

// Remove:
// <link rel="alternate" type="application/json+oembed"
// <link rel="alternate" type="text/xml+oembed
add_filter( 'oembed_discovery_links', 'dsb_oembed_discovery_links', 10, 1);
function dsb_oembed_discovery_links($output)
{
	$post_id = dsb_get_valid_post_id();

	if (($post_id > 0 && get_post_type($post_id) === 'dsb_seo_page') || (int)get_query_var('dsb_seo_page_archive'))
	{
		$output = false;
	}

	return $output;
}

add_action('init', 'dsb_cleanup_head');
function dsb_cleanup_head()
{
	remove_action( 'wp_head', 'feed_links_extra', 3 );				// Display the links to the extra feeds such as category feeds
	remove_action( 'wp_head', 'feed_links', 2 ); 					// Display the links to the general feeds: Post and Comment Feed
	remove_action( 'wp_head', 'index_rel_link' ); 					// index link
	remove_action( 'wp_head', 'parent_post_rel_link', 10, 0 ); 		// prev link
	remove_action( 'wp_head', 'start_post_rel_link', 10, 0 ); 		// start link
	remove_action( 'wp_head', 'adjacent_posts_rel_link', 10, 0 ); 	// Display relational links for the posts adjacent to the current post.
}

/**
 * Checks if we are currently on the settings page
 * 
 * @global $pagenow
 * 
 * @return Wheter or not we are on the settings page
 */
function dsb_is_settings_page()
{
	global $pagenow;

	return $pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'dsb_seo_page' && isset($_GET['page']) && $_GET['page'] === 'dsb-settings';
}

/**
 * Checks if we are currently on the documentation page
 * 
 * @global $pagenow
 * 
 * @return Wheter or not we are on the documentation page
 */
function dsb_is_documentation_page()
{
	global $pagenow;

	return $pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'dsb_seo_page' && isset($_GET['page']) && $_GET['page'] === 'dsb-documentation';
}

/**
 * Filters the list of action links displayed for a specific plugin in the Plugins list table.
 *
 * The dynamic portion of the hook name, `$plugin_file`, refers to the path
 * to the plugin file, relative to the plugins directory.
 *
 * @param string[] $actions     An array of plugin action links. By default this can include 'activate',
 *                              'deactivate', and 'delete'. With Multisite active this can also include
 *                              'network_active' and 'network_only' items.
 */
function dsb_plugin_settings_link($links)
{ 
	$settings_page_url	= menu_page_url('dsb-settings', false);
	$settings_link		= sprintf('<a href="%s">%s</a>',
		esc_attr($settings_page_url),
		esc_html(__('Settings', 'dsb-seo-builder'))
	);

	array_unshift($links, $settings_link); 

	return $links; 
}
$dsb_seo_generator_basename = dsb_get_plugin_basename();
add_filter("plugin_action_links_{$dsb_seo_generator_basename}", 'dsb_plugin_settings_link');

// Check if we are on an arhive or single dsb_seo_page page in the frontend
function dsb_is_dsb_page()
{
	$is_dsb_page = false;

	$post_id = dsb_get_valid_post_id();
	
	if (!is_admin() && ($post_id > 0 && get_post_type($post_id) === 'dsb_seo_page') || (int)get_query_var('dsb_seo_page_archive'))
	{
		$is_dsb_page = true;
	}

	return $is_dsb_page;
}

// Locate the template to be loaded from either the plugin or overridden and load from theme
function dsb_get_template($post_id)
{
    // Default template used by Wordpress: get_page_template_slug() returns empty string and falls back to single.php (or whatever default template in theme falls back on)

    // If a custom template is selected, make sure we include this and not fall back to page.php to show a regular page
    $custom_template = get_page_template_slug($post_id);
    
    if (strstr($custom_template, 'dsb-seo-builder/templates/'))
    {
        // Give theme authors option to copy templates to the theme directory and load those templates instead
        $path_parts 		= pathinfo($custom_template);
        $template_filename	= $path_parts['basename'];
        $template 			= locate_template($template_filename);
        
        // No template was found in the theme, load template from plugin
        if ($template === '')
        {
            $template = WP_PLUGIN_DIR . '/' . $custom_template;
        }
    }
    else
    {
        $template = locate_template(array('page.php', 'single.php', 'singular.php', 'index.php'));
    }

    return $template;
}

// Create a demo page to showcase the plugin functionalities
function dsb_create_seo_gen_example_page()
{
    $post_content = 
'<h2>Professional [search_term] in [location]</h2>
Find a [search_term] in [location]. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam varius nec ex fermentum vehicula. Cras sodales est nec gravida pretium. Integer libero arcu, pulvinar vitae tempus eget, convallis ut nulla.
<h2>Meet our [search_terms] at our salon</h2>
Our [search_terms] are the best [search_term] in [location]. Meet our team now!

<h2>Get a haircut from one of our [search_terms] in [location]</h2>
Donec ac tortor vitae purus cursus tempor. Duis non hendrerit augue, ut consectetur erat. Suspendisse [search_term], magna at lobortis pharetra, massa orci lacinia massa, non tempus turpis nisl nec libero. Sed sed enim lorem. Cras et orci sapien.
<p style="text-align: center;"><a class="button button-primary" href="https://seobuilder.io/">Book now</a></p>

<h2>Wide variety of [search_term] services in [location]</h2>
Suspendisse fermentum lacus vitae tristique consectetur. Nunc luctus volutpat arcu, eu tempor odio pulvinar in. Sed urna nulla, finibus ut lorem [location], consectetur finibus orci.
<ul>
        <li>Hair coloring in [location]</li>
        <li>Eyebrow styling in [location]</li>
        <li>Washing hair in [location]</li>
</ul>
<h2>Book a [search_term] in [location]</h2>
Nulla sagittis urna ultrices tortor viverra hendrerit. Phasellus sit amet luctus mauris, eu finibus mauris. Sed blandit nulla in diam porta, ac viverra arcu pretium. Nulla facilisi. Suspendisse ut nisl consequat, maximus enim ut, congue augue. Vivamus eget vehicula augue. Aenean eget ligula sed lacus mollis congue.
<p style="text-align: center;"><a class="button button-primary" href="https://seobuilder.io/">Book now</a></p>';


    $new_page_id    = wp_insert_post(array(
        'post_title'	    => 'The best [search_term] in [location]',
        'post_status'	    => 'draft',
        'post_type'		    => 'dsb_seo_page',
        'post_author'		=> 1,
        'post_content'		=> $post_content,
        'comment_status'	=> 'closed',
        'post_name'         => 'hairdressers'
    ));
    
    // And set page template
    update_post_meta($new_page_id,	'_wp_page_template', 'dsb-seo-builder/templates/template-page-no-sidebar.php');

    // Update Custom Fields
    update_post_meta($new_page_id, 'dsb-seo-page-base', 'hairdressers');
    update_post_meta($new_page_id, 'dsb-slug-placeholder', '[search_term]-in-[location]');
    update_post_meta($new_page_id, 'dsb-archive-page-title', 'Archive');
    update_post_meta($new_page_id, 'dsb-overview-label', '');   // Start empty

    update_post_meta($new_page_id, 'dsb-search-terms',
'Hairdresser | Hairdressers
Stylist | Stylists
Barber | Barbers
Beautician | Beauticians
Coiffeur
Cosmetologist
Friseur
Hair stylist | Hair stylists');

    update_post_meta($new_page_id, 'dsb-locations',
'Aberdeen
Airway Heights
Albany
Algona
Amsterdam
Anacortes
Arlington
Asotin
Auburn
Bainbridge Island
Batavia
Battle Ground
Beacon
Bellevue
Bellingham
Benton City
Bingen
Binghamton
Black Diamond
Blaine
Bonney Lake
Bothell
Bremerton
Brewster
Bridgeport
Brier
Buckley
Buffalo
Burien
Burlington
Camas
Canandaigua
Carnation
Cashmere
Castle Rock
Centralia
Chehalis
Chelan
Cheney
Chewelah
Clarkston
Cle Elum
Clyde Hill
Cohoes
Colfax
College Place
Colville
Connell
Corning
Cortland
Cosmopolis
Covington
Davenport
Dayton
Deer Park
Des Moines
Dunkirk
DuPont
Duvall
East Wenatchee
Edgewood
Edmonds
Electric City
Ellensburg
Elma
Elmira
Entiat
Enumclaw
Ephrata
Everett
Everson
Federal Way
Ferndale
Fife
Fircrest
Forks
Fulton
Geneva
George
Gig Harbor
Glen Cove
Glens Falls
Gloversville
Gold Bar
Goldendale
Grand Coulee
Grandview
Granger
Granite Falls
Harrington
Hoquiam
Hornell
Hudson
Ilwaco
Issaquah
Ithaca
Jamestown
Johnstown
Kahlotus
Kalama
Kelso
Kenmore
Kennewick
Kent
Kettle Falls
Kingston
Kirkland
Kittitas
La Center
Lacey
Lackawanna
Lake Forest Park
Lake Stevens
Lakewood
Langley
Leavenworth
Liberty Lake
Little Falls
Lockport
Long Beach
Longview
Lynden
Lynnwood
Mabton
Maple Valley
Marysville
Mattawa
McCleary
Mechanicville
Medical Lake
Medina
Mercer Island
Mesa
Middletown
Mill Creek
Millwood
Milton
Monroe
Montesano
Morton
Moses Lake
Mossyrock
Mount Vernon
Mountlake Terrace
Moxee
Mukilteo
Napavine
New Rochelle
New York
Newburgh
Newcastle
Newport
Niagara Falls
Nooksack
Normandy Park
North Bend
North Bonneville
North Tonawanda
Norwich
Oak Harbor
Oakville
Ocean Shores
Ogdensburg
Okanogan
Olean
Olympia
Omak
Oneida
Oneonta
Oroville
Orting
Oswego
Othello
Pacific
Palouse
Pasco
Pateros
Peekskill
Plattsburgh
Pomeroy
Port Angeles
Port Jervis
Port Orchard
Port Townsend
Poughkeepsie
Poulsbo
Prescott
Prosser
Pullman
Puyallup
Quincy
Rainier
Raymond
Redmond
Rensselaer
Renton
Republic
Richland
Ridgefield
Ritzville
Rochester
Rock Island
Rome
Roslyn
Roy
Royal City
Ruston
Rye
Salamanca
Sammamish
Saratoga Springs
Schenectady
SeaTac
Seattle
Sedro-Woolley
Selah
Sequim
Shelton
Sherrill
Shoreline
Snohomish
Snoqualmie
Soap Lake
South Bend
Spangle
Spokane
Spokane Valley
Sprague
Stanwood
Stevenson
Sultan
Sumas
Sumner
Sunnyside
Syracuse
Tacoma
Tekoa
Tenino
Tieton
Toledo
Tonasket
Tonawanda
Toppenish
Troy
Tukwila
Tumwater
Union Gap
University Place
Utica
Vader
Vancouver
Waitsburg
Walla Walla
Wapato
Warden
Washougal
Watertown
Watervliet
Wenatchee
West Richland
Westport
White Plains
White Salmon
Winlock
Woodinville
Woodland
Woodway
Yakima
Yelm
Yonkers
Zillah'
);
    
    // Workaround to flush the rewrite rules:
    update_option( 'dsbd-flush-rewrite-rules', 1 );
}
