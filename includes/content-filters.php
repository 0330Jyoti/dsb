<?php

if (!is_admin())
{
	// Filters the text of the page title.
    // <title>-tag
    add_filter('wp_title', 'dsb_get_seo_pages_replace_search_terms_and_locations', -99999999, 1);          // i think for older wordpress versions?

	// Filters the parts of the document title.
    // <title>-tag: https://developer.wordpress.org/reference/hooks/document_title_parts/
    add_filter('document_title_parts', 'dsb_document_title_parts_replace_search_terms_and_locations', -99999999, 1);  // i think for newer wordpress versions?

	// Filters the post title.
    // the_title()
    add_filter('the_title', 'dsb_get_seo_pages_replace_search_terms_and_locations', 99999999, 2);

	// Filters the post content.
    // the_content()
    add_filter('the_content', 'dsb_get_seo_pages_replace_search_terms_and_locations', 99999999, 2);

	// Filters the post content.
    // the_content()
    add_filter('the_content', 'dsb_add_adjacent_post_links', 99999999 + 1, 1);

    // Update featured image alt
    add_filter( 'wp_get_attachment_image_attributes', 'dsb_wp_get_attachment_image_attributes', 10, 3);

    // Title-tag
    add_filter('pre_get_document_title', 'dsb_wp_head_title_tag');

	// Meta Description
	add_action('wp_head', 'dsb_wp_head', 2);
}

/**
 * Filters the list of attachment image attributes.
 *
 * Change the img alt tag for featured images on dsb_seo_page pages
 *
 * @param string[]     $attr       Array of attribute values for the image markup, keyed by attribute name.
 *                                 See wp_get_attachment_image().
 * @param WP_Post      $attachment Image attachment post.
 * @param string|int[] $size       Requested image size. Can be any registered image size name, or
 *                                 an array of width and height values in pixels (in that order).
 */
function dsb_wp_get_attachment_image_attributes($attr, $attachment, $size)
{
    if (get_post_type() === 'dsb_seo_page')
    {
        $post_id        = dsb_get_valid_post_id();
        $attr['alt']    = get_the_title($post_id);
    }
    return $attr;
}

/**
 * Filters the parts of the document title.
 * 
 * @param array $title {
 *     The document title parts.
 *
 *     @type string $title   Title of the viewed page.
 *     @type string $page    Optional. Page number if paginated.
 *     @type string $tagline Optional. Site description when on home page.
 *     @type string $site    Optional. Site title when not on home page.
 * }
 */
function dsb_document_title_parts_replace_search_terms_and_locations($title)
{
    $title['title'] = dsb_get_seo_pages_replace_search_terms_and_locations($title['title']);

    return $title;
}

/**
 * Replace search term and location placeholders in text with actual search term and location for given SEO Page
 * 
 * @param string 	$text			    The text to replace the placeholders in with the actual values
 * @param bool|int	$post_id		    The Post ID to 
 * @param string	$search_term	    The Search term to replace. If omitted, find first search term for $post_id
 * @param string	$location		    The Location to replace. If omitted, find first location for $post_id
 * @param string	$search_term_plural	Plural form of Search term to replace. If omitted, the single search term will be used
 * @param string	$location_plural	Plural form of Location to replace. If omitted, the single location will be used
 * @param string	$the_slug	        Overrule the slug that is used on which the correct search terms and locations are searched in the lookup table
 * @param int       $spintax_offset     If Spintax is enabled, use this text combination defined by this offset value
 * 
 * @return string $text The text with the replacements made
 */
function dsb_get_seo_pages_replace_search_terms_and_locations($text, $the_post_id = false, $search_term = false, $location = false, $search_term_plural = false, $location_plural = false, $the_slug = false, $spintax_offset = 0)
{
    $post_id = get_the_ID();
        
	if (get_post_type($the_post_id) === 'dsb_seo_page' && $the_post_id !== false && $post_id !== $the_post_id)
	{
		// We are being called by somehting link get_next_post_link / get_previous_post_link or something similar
		// Work with given $the_post_id, not from current page
		$post_id = $the_post_id;
	}

    if (get_post_type($post_id) === 'dsb_seo_page')
	{
		$current_filter = current_filter();

		$slug = get_query_var('dsb_seo_page');

		if (empty($slug) && !empty($the_slug))
		{
			$slug = $the_slug;
		}

		// First find the search word and location in the url
		$dsb                		    = DSB_Seo_Builder::get_instance();

        if (!empty($slug))
		{
            if (get_option('dsb-enable_spintax', false))
            {
                $spinner    = DSB_Spintax::get_instance($text);
                $text       = $spinner->get_combination($spintax_offset);
            }
            
			$search_term_placeholder_single = $dsb->get_search_term_single_placeholder();
			$search_term_placeholder_plural	= $dsb->get_search_term_plural_placeholder();

			$location_placeholder_single    = $dsb->get_location_single_placeholder();
			$location_placeholder_plural	= $dsb->get_location_plural_placeholder();
			
			if ($search_term === false && $location === false)
			{
				$lookup_table					= dsb_get_search_terms_and_locations_lookup_table($post_id);

                if ($lookup_table !== false && isset($lookup_table[$slug]))
				{
					$search_term    	= $lookup_table[$slug][0];
					$location       	= $lookup_table[$slug][1];
					$search_term_plural = !empty($lookup_table[$slug][2]) ? $lookup_table[$slug][2] : $search_term;	// fallback to single
					$location_plural    = !empty($lookup_table[$slug][3]) ? $lookup_table[$slug][3] : $location;	// fallback to single
				}
			}

			// $search_term and $location have already been escaped with esc_html() in dsb_textarea_value_to_array()
            // Only replace placeholders if $search_term is not empty... otherwise just leave the placeholder
            if (!empty($search_term))
            {
                // First check if enabled
                if (get_option('dsb-enable_search_term_case_sensitivity', false))
                {
                    // Lowercase: replace [search_term] with lowercase value
                    $search_term_placeholder_single_lowercase   = strtolower($search_term_placeholder_single);
                    $search_term_placeholder_plural_lowercase   = strtolower($search_term_placeholder_plural);

                    $search_term_lowercase                      = strtolower($search_term);
                    $search_term_plural_lowercase               = strtolower($search_term_plural);

                    $text            = str_replace($search_term_placeholder_single_lowercase, $search_term_lowercase, $text);
                    $text            = str_replace($search_term_placeholder_plural_lowercase, $search_term_plural_lowercase, $text);

                    // UCFirst: replace [Search_term] with ucfirst Value
                    $search_term_placeholder_single_ucfirst     = $dsb->dsb_get_ucfirst_placeholder($search_term_placeholder_single);
                    $search_term_placeholder_plural_ucfirst     = $dsb->dsb_get_ucfirst_placeholder($search_term_placeholder_plural);

                    $search_term_ucfirst                        = ucfirst($search_term);
                    $search_term_plural_ucfirst                 = ucfirst($search_term_plural);

                    $text            = str_replace($search_term_placeholder_single_ucfirst, $search_term_ucfirst, $text);
                    $text            = str_replace($search_term_placeholder_plural_ucfirst, $search_term_plural_ucfirst, $text);
                }
                else
                {
                    // str_ireplace case insensitive replace will replace all placeholders anyway they are written
                    $text            = str_ireplace($search_term_placeholder_single, $search_term, $text);
				    $text            = str_ireplace($search_term_placeholder_plural, $search_term_plural, $text);
                }
            }

			// Only replace placeholders if $location is not empty... otherwise just leave the placeholder
            if (!empty($location))
			{
                // First check if enabled
                if (get_option('dsb-enable_location_case_sensitivity', false))
                {
                    // Lowercase: replace [location] with lowercase value
                    $location_placeholder_single_lowercase      = strtolower($location_placeholder_single);
                    $location_placeholder_plural_lowercase      = strtolower($location_placeholder_plural);

                    $location_lowercase                         = strtolower($location);
                    $location_plural_lowercase                  = strtolower($location_plural);

                    $text            = str_replace($location_placeholder_single_lowercase, $location_lowercase, $text);
                    $text            = str_replace($location_placeholder_plural_lowercase, $location_plural_lowercase, $text);

                    // UCFirst: replace [Location] with ucfirst Value
                    if (!empty($location))
                    {
                        $location_placeholder_single_ucfirst        = $dsb->dsb_get_ucfirst_placeholder($location_placeholder_single);
                        $location_placeholder_plural_ucfirst        = $dsb->dsb_get_ucfirst_placeholder($location_placeholder_plural);

                        $location_ucfirst                           = ucfirst($location);
                        $location_plural_ucfirst                    = ucfirst($location_plural);

                        $text            = str_replace($location_placeholder_single_ucfirst, $location_ucfirst, $text);
                        $text            = str_replace($location_placeholder_plural_ucfirst, $location_plural_ucfirst, $text);
                    }
                }
                else
                {
                    // str_ireplace case insensitive replace will replace all placeholders anyway they are written
                    $text            = str_ireplace($location_placeholder_single, $location, $text);
				    $text            = str_ireplace($location_placeholder_plural, $location_plural, $text);
                }
			}
		}
		else
		{
			if ((int)get_query_var('dsb_seo_page_archive'))
			{
				// Update <title> tag on archive pages
				if ($current_filter === 'document_title_parts' || $current_filter === 'wpseo_title' || $current_filter === 'wpseo_opengraph_title' || $current_filter === 'rank_math/frontend/title')
				{
					$text = dsb_get_field('dsb-archive-page-title', $post_id, __('Archive', 'dsb_seo_builder'), false);
				}
				// Update meta description on archive pages
				else if ($current_filter === 'wpseo_metadesc' || $current_filter === 'wpseo_opengraph_desc' || $current_filter === 'rank_math/frontend/description')
				{
					$text = dsb_get_archive_page_meta_description();
				}
			}
		}
	}

    return $text;
}

function dsb_get_archive_page_meta_description()
{
	$dsb               	= DSB_Seo_Builder::get_instance();
	$post_id			= dsb_get_valid_post_id();
	$meta_description	= '';
	$archive_page_title	= dsb_get_field('dsb-archive-page-title', $post_id, __('Archive', 'dsb_seo_builder'), false);
	$search_terms 		= $dsb->dsb_get_search_terms($post_id);
	$locations			= $dsb->dsb_get_locations($post_id);

	if (is_array($search_terms))
	{
		$search_terms = array_slice($search_terms, 0, 3);
		$search_terms = array_map('dsb_get_plural_value', $search_terms);
	}

	if (is_array($locations))
	{
		$locations = array_slice($locations, 0, 3);
		$locations = array_map('dsb_get_single_value', $locations);
	}

	if (is_array($search_terms) && is_array($locations))
	{
		$meta_description = sprintf(
			__("Archive for %s and more in %s and other locations - %s", 'dsb_seo_builder'),
			implode(", ", $search_terms),
			implode(", ", $locations),
			$archive_page_title
		);
	}

	return $meta_description;
}

/**
 * Add previous / index / next links to improve internal linking
 */
function dsb_add_adjacent_post_links($content)
{
	if (get_option('dsb-enable_adjacent_seo_pages_links', true))
	{
		$post_id 		= get_the_ID();

		if ($post_id > 0 && get_post_type($post_id) === 'dsb_seo_page' && get_post_status($post_id) === 'publish')
		{
			$slug    	   	= get_query_var('dsb_seo_page');
			
			if (!empty($slug))
			{
				$post			= get_post($post_id);
				$post_title		= $post->post_title;	// fetch unfiltered title
				$lookup_table	= dsb_get_search_terms_and_locations_lookup_table($post_id);
				
				$keys			= array_keys($lookup_table);
				$index	    	= array_search($slug, $keys);
				$seo_page_base 	= $lookup_table[$slug][5];

				$content		.= "<div class='dsb-adjacent-links'>";
				
				// Previous link with correct title
				if ($index - 1 >= 0)
				{
					$prev_slug 	= $keys[$index - 1];
					$prev		= $lookup_table[$prev_slug];
                    
                    $prev_post_title    = $post_title;

                    if (get_option('dsb-enable_spintax', false))
                    {
                        $index_offset       = -1;
                        $spinner            = DSB_Spintax::get_instance($post_title);
                        $prev_post_title    = $spinner->get_combination($index_offset);
                    }
                    
					$prev_title	= dsb_get_seo_pages_replace_search_terms_and_locations($prev_post_title, $post_id, $prev[0], $prev[1]);

					$prev_url	= esc_url( trailingslashit(home_url($seo_page_base . '/' . strtolower(sanitize_title($prev_slug)))));
					
					$content	.= sprintf("<a class='dsb-prev-seo-page' href='%s'>%s</a>",
									$prev_url,
									$prev_title);
				}

				// Link to overview of all SEO Pages for this $seo_page_base
				$content	.= sprintf("<a class='dsb-overview-seo-pages' href='%s'>%s</a>",
									esc_url(trailingslashit(home_url($seo_page_base))),
									dsb_get_field('dsb-overview-label', $post_id, __('Overview', 'dsb_seo_builder'), false));

				// Next link with correct title
				if ($index + 1 < count($keys))
				{
					$next_slug 	= $keys[$index + 1];
					$next		= $lookup_table[$next_slug];

                    $next_post_title    = $post_title;

                    if (get_option('dsb-enable_spintax', false))
                    {
                        $index_offset   = 1;
                        $spinner        = DSB_Spintax::get_instance($post_title);
                        $next_post_title = $spinner->get_combination($index_offset);
                    }

					$next_title	= dsb_get_seo_pages_replace_search_terms_and_locations($next_post_title, $post_id, $next[0], $next[1]);

					$next_url  	= esc_url( trailingslashit( home_url($seo_page_base . '/' . strtolower(sanitize_title($next_slug))) ) );

					$content	.= sprintf("<a class='dsb-next-seo-page' href='%s'>%s</a><br>",
									$next_url,
									$next_title);
				}

				$content .= "</div>";
			}
		}
	}

	return $content;
}

// Update NW SEO Page titles in admin nav menus and replace placeholders
add_filter('wp_setup_nav_menu_item', 'dsb_wp_setup_nav_menu_item', 10, 1);
function dsb_wp_setup_nav_menu_item($menu_item)
{
	if (is_admin() && $menu_item->object === 'dsb_seo_page')
	{
		$post_id 		= $menu_item->object_id;

		$dsb = DSB_Seo_Builder::get_instance();

		// $menu_item->title and $menu_item->label are used in the box to select the SEO Page, on the accordion title and inside the menu item text field

		if ($dsb->has_any_placeholder($menu_item->title) || $menu_item->title === '')
		{
			$menu_item->title = dsb_get_field('dsb-archive-page-title', $post_id, __('Archive', 'dsb_seo_builder'), false);
		}

		if ($dsb->has_any_placeholder($menu_item->label) || $menu_item->label === '')
		{
			$menu_item->label = $menu_item->title;
		}
	}

	return $menu_item;
}

function dsb_wp_head_title_tag()
{
    $title = '';

    if (dsb_is_dsb_page())
	{
		// Title-tag title
		$title = dsb_get_field('dsb-title-tag');

		$title = apply_filters('dsb-title-tag', esc_html($title));
	}

    return $title;
}

// Print meta decription in the head tag on the front end.
function dsb_wp_head()
{
	if (dsb_is_dsb_page())
	{
		// dsb-meta-description
		$meta_description = dsb_get_field('dsb-meta-description');
		
		// Special case for the archive page
		if ((int)get_query_var('dsb_seo_page_archive') && !empty($meta_description))
		{
			$meta_description = dsb_get_archive_page_meta_description();
		}

		$meta_description = apply_filters('dsb-meta-description', $meta_description);

		if (!empty($meta_description))
		{
			echo sprintf("\n\t<meta name='description' content='%s' class='dsb-seo-meta-tag' />\n\n", esc_html($meta_description));
		}

        // Canonical tag
        if (get_option('dsb-enable_canonical_tag', true))
        {
            $canonical = apply_filters('dsb-canonical-tag', dsb_get_canonical_url(''));

            if (!empty($canonical))
            {
                echo sprintf("\n\t<link rel='canonical' href='%s' class='dsb-seo-canonical' />\n\n", esc_url($canonical));
            }
        }
	}
}

/**
 * Changes @type of Webpage Schema data.
 *
 * @param array $data Schema.org Webpage data array.
 *
 * @return array Schema.org Webpage data array.
 * 
 * Disabled above for now. this piece did work though...
 */
// function dsb_wpseo_schema_webpage($data)
// {
//     $data = dsb_recursive_array_replace ('[search_term]', "MYSEARCHTERM", $data);
//     $data = dsb_recursive_array_replace ('[location]', "MYLOCATION", $data);
//     return $data;
// }
