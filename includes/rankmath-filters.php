<?php

if (!is_admin())
{
    // https://rankmath.com/kb/filters-hooks-api-developer/
    add_filter( 'rank_math/frontend/title', 'dsb_get_seo_pages_replace_search_terms_and_locations', 9999999, 1);
    add_filter( 'rank_math/frontend/description', 'dsb_get_seo_pages_replace_search_terms_and_locations', 9999999, 1);
    add_filter( 'rank_math/frontend/canonical', 'dsb_get_seo_pages_replace_search_terms_and_locations', 9999999, 1);
    add_action( 'rank_math/head', 'dsb_rank_math_head', 10, 0);

    // Disable meta og"image tag
    add_filter( 'rank_math/opengraph/pre_set_content_image', 'dsb_rank_math_opengraph_pre_set_content_image', 99999999, 1);

    // Add SEO Builder sitemap to the Rank Math Sitemap
    // add_filter( 'rank_math/sitemap/index', 'dsb_rank_math_sitemap_index', 11, 1);   
}

function dsb_rank_math_head()
{
    if (dsb_is_dsb_page())
    {
        // Remove Facebook and Twitter meta opengraph tags
        remove_all_actions( 'rank_math/opengraph/facebook' );
        remove_all_actions( 'rank_math/opengraph/twitter' );

        // Remove remove json+ld data
        global $wp_filter;
        if ( isset( $wp_filter["rank_math/json_ld"] ) )
        {
            unset( $wp_filter["rank_math/json_ld"] );
        }
    }
}

function dsb_rank_math_opengraph_pre_set_content_image($result)
{
    if (dsb_is_dsb_page())
    {
        $result = true;
    }

    return $result;
}

// function dsb_rank_math_sitemap_index($xml)
// {
//     $xml .= '
//         <sitemap>
//             <loc>' . dsb_get_sitemap_url() . '</loc>
//             <lastmod>' . dsb_get_last_modified_gmt() . '</lastmod>
//         </sitemap>';
//     return $xml;
// }

add_filter( 'rank_math/frontend/canonical', 'dsb_rank_math_frontend_canonical', 10, 1);
function dsb_rank_math_frontend_canonical($canonical)
{
    if (dsb_is_dsb_page())
    {
        $canonical = urldecode($canonical);
        $canonical = dsb_get_seo_pages_replace_search_terms_and_locations($canonical);
        $canonical = strtolower($canonical);
    }
	return $canonical;
}
