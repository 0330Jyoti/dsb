<?php

if (!is_admin())
{
    add_filter('wp_title', 'dsb_get_seo_pages_replace_search_terms_and_locations', -99999999, 1);
    add_filter('document_title_parts', 'dsb_document_title_parts_replace_search_terms_and_locations', -99999999, 1); 
    add_filter('the_title', 'dsb_get_seo_pages_replace_search_terms_and_locations', 99999999, 2);
    add_filter('the_content', 'dsb_get_seo_pages_replace_search_terms_and_locations', 99999999, 2);
    add_filter('the_content', 'dsb_add_adjacent_post_links', 99999999 + 1, 1);
    add_filter( 'wp_get_attachment_image_attributes', 'dsb_wp_get_attachment_image_attributes', 10, 3);
    add_filter('pre_get_document_title', 'dsb_wp_head_title_tag');
	add_action('wp_head', 'dsb_wp_head', 2);
}

function dsb_wp_get_attachment_image_attributes($attr, $attachment, $size){
    if (get_post_type() === 'dsb_seo_page')
    {
        $post_id        = dsb_get_valid_post_id();
        $attr['alt']    = get_the_title($post_id);
    }
    return $attr;
}

function dsb_document_title_parts_replace_search_terms_and_locations($title){
    $title['title'] = dsb_get_seo_pages_replace_search_terms_and_locations($title['title']);

    return $title;
}

function dsb_get_seo_pages_replace_search_terms_and_locations($text, $the_post_id = false, $search_term = false, $location = false, $search_term_plural = false, $location_plural = false, $the_slug = false, $spintax_offset = 0){
    $post_id = get_the_ID();
        
	if (get_post_type($the_post_id) === 'dsb_seo_page' && $the_post_id !== false && $post_id !== $the_post_id)
	{
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
					$search_term_plural = !empty($lookup_table[$slug][2]) ? $lookup_table[$slug][2] : $search_term;
					$location_plural    = !empty($lookup_table[$slug][3]) ? $lookup_table[$slug][3] : $location;
				}
			}

            if (!empty($search_term))
            {
                if (get_option('dsb-enable_search_term_case_sensitivity', false))
                {
                    $search_term_placeholder_single_lowercase   = strtolower($search_term_placeholder_single);
                    $search_term_placeholder_plural_lowercase   = strtolower($search_term_placeholder_plural);

                    $search_term_lowercase                      = strtolower($search_term);
                    $search_term_plural_lowercase               = strtolower($search_term_plural);

                    $text            = str_replace($search_term_placeholder_single_lowercase, $search_term_lowercase, $text);
                    $text            = str_replace($search_term_placeholder_plural_lowercase, $search_term_plural_lowercase, $text);
                    $search_term_placeholder_single_ucfirst     = $dsb->dsb_get_ucfirst_placeholder($search_term_placeholder_single);
                    $search_term_placeholder_plural_ucfirst     = $dsb->dsb_get_ucfirst_placeholder($search_term_placeholder_plural);

                    $search_term_ucfirst                        = ucfirst($search_term);
                    $search_term_plural_ucfirst                 = ucfirst($search_term_plural);

                    $text            = str_replace($search_term_placeholder_single_ucfirst, $search_term_ucfirst, $text);
                    $text            = str_replace($search_term_placeholder_plural_ucfirst, $search_term_plural_ucfirst, $text);
                }
                else
                {
                    $text            = str_ireplace($search_term_placeholder_single, $search_term, $text);
				    $text            = str_ireplace($search_term_placeholder_plural, $search_term_plural, $text);
                }
            }

            if (!empty($location))
			{
                if (get_option('dsb-enable_location_case_sensitivity', false))
                {
                    $location_placeholder_single_lowercase      = strtolower($location_placeholder_single);
                    $location_placeholder_plural_lowercase      = strtolower($location_placeholder_plural);

                    $location_lowercase                         = strtolower($location);
                    $location_plural_lowercase                  = strtolower($location_plural);

                    $text            = str_replace($location_placeholder_single_lowercase, $location_lowercase, $text);
                    $text            = str_replace($location_placeholder_plural_lowercase, $location_plural_lowercase, $text);

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
                    $text            = str_ireplace($location_placeholder_single, $location, $text);
				    $text            = str_ireplace($location_placeholder_plural, $location_plural, $text);
                }
			}
		}
		else
		{
			if ((int)get_query_var('dsb_seo_page_archive'))
			{
				if ($current_filter === 'document_title_parts' || $current_filter === 'wpseo_title' || $current_filter === 'wpseo_opengraph_title' || $current_filter === 'rank_math/frontend/title')
				{
					$text = dsb_get_field('dsb-archive-page-title', $post_id, __('Archive', 'dsb_seo_builder'), false);
				}
				else if ($current_filter === 'wpseo_metadesc' || $current_filter === 'wpseo_opengraph_desc' || $current_filter === 'rank_math/frontend/description')
				{
					$text = dsb_get_archive_page_meta_description();
				}
			}
		}
	}

    return $text;
}

function dsb_get_archive_page_meta_description(){
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

function dsb_add_adjacent_post_links($content){
	if (get_option('dsb-enable_adjacent_seo_pages_links', true))
	{
		$post_id 		= get_the_ID();

		if ($post_id > 0 && get_post_type($post_id) === 'dsb_seo_page' && get_post_status($post_id) === 'publish')
		{
			$slug    	   	= get_query_var('dsb_seo_page');
			
			if (!empty($slug))
			{
				$post			= get_post($post_id);
				$post_title		= $post->post_title;
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

				$content	.= sprintf("<a class='dsb-overview-seo-pages' href='%s'>%s</a>",
									esc_url(trailingslashit(home_url($seo_page_base))),
									dsb_get_field('dsb-overview-label', $post_id, __('Overview', 'dsb_seo_builder'), false));

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

add_filter('wp_setup_nav_menu_item', 'dsb_wp_setup_nav_menu_item', 10, 1);
function dsb_wp_setup_nav_menu_item($menu_item){
	if (is_admin() && $menu_item->object === 'dsb_seo_page')
	{
		$post_id 		= $menu_item->object_id;

		$dsb = DSB_Seo_Builder::get_instance();

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

function dsb_wp_head_title_tag(){
    $title = '';

    if (dsb_is_dsb_page())
	{
		$title = dsb_get_field('dsb-title-tag');
		$title = apply_filters('dsb-title-tag', esc_html($title));
	}

    return $title;
}

function dsb_wp_head(){
	if (dsb_is_dsb_page())
	{
		$meta_description = dsb_get_field('dsb-meta-description');
		if ((int)get_query_var('dsb_seo_page_archive') && !empty($meta_description))
		{
			$meta_description = dsb_get_archive_page_meta_description();
		}

		$meta_description = apply_filters('dsb-meta-description', $meta_description);

		if (!empty($meta_description))
		{
			echo sprintf("\n\t<meta name='description' content='%s' class='dsb-seo-meta-tag' />\n\n", esc_html($meta_description));
		}

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


// function dsb_wpseo_schema_webpage($data)
// {
//     $data = dsb_recursive_array_replace ('[search_term]', "MYSEARCHTERM", $data);
//     $data = dsb_recursive_array_replace ('[location]', "MYLOCATION", $data);
//     return $data;
// }
