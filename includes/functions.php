<?php

function dsb_regenerate_search_terms_and_locations_lookup_tables(){
	$dsb        = DSB_Seo_Builder::get_instance();
    $seo_pages  = $dsb->dsb_get_seo_pages();
    if (is_array($seo_pages) && count($seo_pages) > 0)
    {
        foreach ($seo_pages as $seo_page)
        {
            $dsb->dsb_store_search_word_and_location_for_slugs($seo_page->ID);
        }
    }
}

function dsb_get_search_terms_and_locations_lookup_tables(){
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

function dsb_get_search_terms_and_locations_lookup_table($post_id){
	$lookup_table = array();
	if ($post_id > 0)
	{
		$lookup_table = get_post_meta($post_id, 'dsb-search-word-and-location-for-slugs', true);
	}

    return $lookup_table;
}

function dsb_get_field($key, $post_id = false, $default_value = false, $do_replace = true){
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

function dsb_get_valid_post_id($post_id = 0){
	if( !$post_id )
	{
		$post_id = (int) get_the_ID();
		if( !$post_id )
		{
			$post_id = get_queried_object();
		}
	}

	if( is_object($post_id) )
	{
		if( isset($post_id->post_type, $post_id->ID) )
		{
			$post_id = $post_id->ID;

		} elseif( isset($post_id->roles, $post_id->ID) ) {

			$post_id = 'user_' . $post_id->ID;

		} elseif( isset($post_id->taxonomy, $post_id->term_id) ) {

			$post_id = 'term_' . $post_id->term_id;

		} elseif( isset($post_id->comment_ID) ) {

			$post_id = 'comment_' . $post_id->comment_ID;

		} else {

			$post_id = 0;
		}
	}

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

	return $post_id;
}

function dsb_get_single_value($value){
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

function dsb_get_plural_value($value){
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

function dsb_late_init_flush_rewrite_rules(){
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

function dsb_limit_max_lines($string, $max_lines){
	
    if (!empty($string))
	{
		$lines  = dsb_textarea_value_to_array($string);
		$lines  = array_slice($lines, 0, $max_lines);
		$string = implode(PHP_EOL, $lines);
	}
    return $string;
}

function dsb_textarea_value_to_array($textarea_value){
	$array = false;
    if (!empty($textarea_value))
    {
        $array                 = array_values(array_filter(explode(PHP_EOL, $textarea_value))); 
        $array                 = array_map('trim', $array);                      
        $array                 = array_unique($array);
        $array                 = array_map('esc_html', $array);
    }
    return $array;
}

function dsb_recursive_array_replace ($find, $replace, $array){
    if (! is_array($array)) {
        return str_replace($find, $replace, $array);
    }

    $newArray = array();

    foreach ($array as $key => $value) {
        $newArray[$key] = dsb_recursive_array_replace($find, $replace, $value);
    }

    return $newArray;
}


function dsb_is_empty( $var ){
	return ( !$var && !is_numeric($var) );
}

function dsb_add_sitemap_to_robots($output, $public){
    $output .= "\r\nSitemap: " . dsb_get_sitemap_url();

	return $output;
}
add_filter('robots_txt', 'dsb_add_sitemap_to_robots', 10, 2);

function dsb_array_insert_after($array, $insert_after, $key, $new){
    $pos = (int) array_search($insert_after, array_keys($array)) + 1;
    return array_merge(
        array_slice($array, 0, $pos),
        array($key => $new),
        array_slice($array, $pos)
    );
}


function dsb_option_exists($option){
    global $wpdb;

	$table	= $wpdb->prefix . "options";
	$sql	= $wpdb->prepare("SELECT * FROM {$table} WHERE option_name LIKE %s", $option);
	$result = (bool)$wpdb->query($sql);

	return $result;
}

add_filter( "previous_post_link", "dsb_get_adjacent_post_link", 10, 5);
add_filter( "next_post_link", "dsb_get_adjacent_post_link", 10, 5);
function dsb_get_adjacent_post_link($output, $format, $link, $post, $adjacent){
	if (!empty($output) && get_post_type($post) === 'dsb_seo_page')
	{
		libxml_use_internal_errors(true);
		$html = new DOMDocument();
		$html->loadHTML($output);

		foreach($html->getElementsByTagName('a') as $link)
		{
			$dsb 		= DSB_Seo_Builder::get_instance();
			$new_link 	= $dsb->dsb_get_seo_page_urls($post->post_name, $post->ID, 1);
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
			$link->setAttribute('href', $new_link[0]);			
			$link->nodeValue = $title;
		}

		$output = $html->saveHtml();
	}

	 return $output;
}

function dsb_format_timestamp($date, $offset = false){
	$date = new DateTime($date);

	if ($offset !== false)
	{
		$date->modify($offset);
	}
	return $date->format('c');
}


add_filter( 'get_canonical_url', 'dsb_get_canonical_url', 999999999999999999999999999, 1);
add_filter( 'wpseo_canonical', 'dsb_get_canonical_url', 999999999999999999999999999, 1);
function dsb_get_canonical_url ($canonical_url, $post = false){
	$post_id = (int)dsb_get_valid_post_id();

	if ((int)get_query_var('dsb_seo_page_archive'))
    {
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

add_filter('get_shortlink', 'dsb_get_shortlink', 10, 2);
function dsb_get_shortlink($shortlink, $post_id){
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

add_filter( 'oembed_discovery_links', 'dsb_oembed_discovery_links', 10, 1);
function dsb_oembed_discovery_links($output){
	$post_id = dsb_get_valid_post_id();

	if (($post_id > 0 && get_post_type($post_id) === 'dsb_seo_page') || (int)get_query_var('dsb_seo_page_archive'))
	{
		$output = false;
	}

	return $output;
}

add_action('init', 'dsb_cleanup_head');
function dsb_cleanup_head(){
	remove_action( 'wp_head', 'feed_links_extra', 3 );
	remove_action( 'wp_head', 'feed_links', 2 ); 				
	remove_action( 'wp_head', 'index_rel_link' ); 					
	remove_action( 'wp_head', 'parent_post_rel_link', 10, 0 ); 		
	remove_action( 'wp_head', 'start_post_rel_link', 10, 0 ); 		
	remove_action( 'wp_head', 'adjacent_posts_rel_link', 10, 0 ); 	
}

function dsb_is_settings_page(){
	global $pagenow;

	return $pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'dsb_seo_page' && isset($_GET['page']) && $_GET['page'] === 'dsb-settings';
}

function dsb_is_documentation_page(){
	global $pagenow;

	return $pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'dsb_seo_page' && isset($_GET['page']) && $_GET['page'] === 'dsb-documentation';
}

function dsb_plugin_settings_link($links){ 
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

function dsb_is_dsb_page(){
	$is_dsb_page = false;

	$post_id = dsb_get_valid_post_id();
	
	if (!is_admin() && ($post_id > 0 && get_post_type($post_id) === 'dsb_seo_page') || (int)get_query_var('dsb_seo_page_archive'))
	{
		$is_dsb_page = true;
	}

	return $is_dsb_page;
}

function dsb_get_template($post_id){
    $custom_template = get_page_template_slug($post_id);
    
    if (strstr($custom_template, 'dsb-seo-builder/templates/'))
    {
        $path_parts 		= pathinfo($custom_template);
        $template_filename	= $path_parts['basename'];
        $template 			= locate_template($template_filename);

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

function dsb_create_seo_gen_example_page(){
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
    update_option( 'dsbd-flush-rewrite-rules', 1 );
}
