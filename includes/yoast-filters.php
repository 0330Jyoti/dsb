<?php

function dsb_is_yoast_active(){
    return (is_plugin_active('wordpress-seo/wp-seo.php') || is_plugin_active('wordpress-seo-premium/wp-seo-premium.php'));
}

if (!is_admin())
{

    add_filter('wp_title', 'dsb_get_seo_pages_replace_search_terms_and_locations', -99999999, 1);
    add_filter('document_title_parts', 'dsb_document_title_parts_replace_search_terms_and_locations', -99999999, 1);
    add_filter('wpseo_title', 'dsb_get_seo_pages_replace_search_terms_and_locations', -99999999, 1);
    add_filter('wpseo_opengraph_title', 'dsb_get_seo_pages_replace_search_terms_and_locations', -99999999, 1);
    add_filter('wpseo_metadesc', 'dsb_get_seo_pages_replace_search_terms_and_locations', -99999999, 1);
    add_filter('wpseo_opengraph_desc', 'dsb_get_seo_pages_replace_search_terms_and_locations', -99999999, 1);
    add_filter("wpseo_breadcrumb_links", "dsb_wpseo_breadcrumb_links");
    add_filter('wpseo_breadcrumb_output', "dsb_get_seo_pages_replace_search_terms_and_locations", 10, 1);
    add_filter('the_title', 'dsb_get_seo_pages_replace_search_terms_and_locations', -99999999, 1);
    add_filter('the_content', 'dsb_get_seo_pages_replace_search_terms_and_locations', 99999999, 1);
    add_filter('wpseo_frontend_presenter_classes', 'dsb_wpseo_frontend_presenter_classes');
}

function dsb_wpseo_breadcrumb_links($links){
    global $post;

    if ((int)get_query_var('dsb_seo_page_archive') && is_array($links) && count($links) === 2)
    {
        $links[1]['text'] = dsb_get_field('dsb-archive-page-title', $post->ID, __('Archive', 'dsb_seo_builder'), false);
    }
    else if (is_single() && get_post_type() === 'dsb_seo_page' && is_array($links) && count($links) === 2)
    {
        $post_link = get_permalink();
        if (strstr($post_link, '/dsb_seo_page/'))
        {
            $post_link = str_replace( '/dsb_seo_page/', '/', $post_link );
        }
        $breadcrumb[] = array(
            'url'   => $post_link,
            'text'  => dsb_get_field('dsb-archive-page-title', $post->ID, __('Archive', 'dsb_seo_builder'), false)
        );

        array_splice($links, 1, -2, $breadcrumb);
    }

    return $links;
}

function dsb_wpseo_frontend_presenter_classes ($filter){
	if (($key = array_search('Yoast\WP\SEO\Presenters\Open_Graph\Article_Modified_Time_Presenter', $filter)) !== false)
    {
		unset($filter[$key]);
	}
    
    if (($key = array_search('Yoast\WP\SEO\Presenters\Open_Graph\Article_Published_Time_Presenter', $filter)) !== false)
    {
		unset($filter[$key]);
	}

	return $filter;
}

add_filter( 'wpseo_json_ld_output', '__return_false' );
add_filter( 'wpseo_opengraph_url', 'dsb_yoast_opengraph_url' );
function dsb_yoast_opengraph_url($url){
    if (get_post_type() === 'dsb_seo_page' && get_the_ID() > 0)
    {
        $url  = dsb_get_canonical_url ('');
    }
    
    return $url;
}

