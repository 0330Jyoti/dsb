<?php

// SEO pages are regular pages of the CPT dsb_seo_page
// We have removed the CPT base
// Now these pages have a regular slug for the url
// We add the search terms and locations after this slug part

// Examples:
// domain.com/webdevelopment/webdesign-in-location/
// /webdevelopment/ is the slug of the actual CPT page
// /webdesign-in-location/ is the combination of [search_term]-in-[location]
// and the dynamic part is stored in a query_var: dsb_seo_page

/**
 * Filters the query variables allowed before processing.
 *
 * Adds query vars used by the plugin for the dynamiccaly generated urls
 *
 * @param string[] $public_query_vars The array of allowed query variable names.
 * 
 * @return array
 */
function dsb_add_query_vars($public_query_vars)
{
    // Use to generate all unique slugs that come after the url of the SEO Pages
    $public_query_vars[] = 'dsb_seo_page';
    $public_query_vars[] = 'dsb_seo_page_archive';
    
    // Used for XML sitemap
    $public_query_vars[] = 'dsb_sitemap';
    $public_query_vars[] = 'dsb_sitemap_number';

    return $public_query_vars;
}
add_filter('query_vars', 'dsb_add_query_vars', 0, 1);

/**
 * Fires after WordPress has finished loading but before any headers are sent.
 *
 * Adds rewrite rules
 */
function dsb_add_rewrite_rules()
{
    $dsb        = DSB_Seo_Builder::get_instance();

    $pages      = $dsb->dsb_get_seo_pages();

    $page_names = array();

    if (is_array($pages) && count($pages) > 0)
    {
        foreach ($pages as $page)
        {
            $page_names[$page->ID] = $page->post_name;
        }

        // /post-1/[search_term-1]-in-[location-1]
        // /post-1/[search_term-2]-in-[location-1]
        // /post-1/[search_term-1]-in-[location-2]
        // /post-1/[search_term-2]-in-[location-2]

        // /post-2/[search_term-1]-in-[location-1]
        // /post-2/[search_term-2]-in-[location-1]
        // /post-2/[search_term-1]-in-[location-2]
        // /post-2/[search_term-2]-in-[location-2]

        // etc

        global $wp_rewrite;
        
        // Create a separate rewrite rule for each CPT dsb_seo_page so we can add the slug after these pages
        foreach ($page_names as $post_id => $page_name)
        {
            // ^hairdressers\/([\p{L}0-9-_]+)\/?$


            // [(.?.+?)/page/?([0-9]{1,})/?$]       index.php?pagename=$matches[1]&paged=$matches[2]
            add_rewrite_rule('^' . $page_name . '/' . $wp_rewrite->pagination_base . '/?([0-9]{1,})/?$',
                'index.php?post_type=dsb_seo_page&name=' . $page_name . '&paged=$matches[1]&dsb_seo_page_archive=1&p=' . (int)$post_id,
                'top');

            // index.php?&paged=$matches[1]
            $pattern = preg_quote($page_name, '') . '/([^/]+)(?:/(.*))?/?$';

            // IMPORTANT!!! 2nd PARAM MUST BE IN SINGLE QUOTES
            add_rewrite_rule(
                $pattern,
                'index.php?post_type=dsb_seo_page&name=' . $page_name . '&dsb_seo_page=$matches[1]',
                'top'
            );
            
            // Create a rewrite rule for the fake archive page
            add_rewrite_rule(
                "^{$page_name}/?$",
                'index.php?post_type=dsb_seo_page&name=' . $page_name . '&dsb_seo_page_archive=1',
                'top'
            );
        } 
    }
}
add_action('init', 'dsb_add_rewrite_rules', 999);

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//                                      PAGINATION - START
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Filters the canonical redirect URL.
 *
 * Returning false to this filter will cancel the redirect.
 *
 * @since 2.3.0
 *
 * @param string $redirect_url  The redirect URL.
 * @param string $requested_url The requested URL.
 */
add_filter('redirect_canonical', 'dsb_redirect_canonical_paged', 10, 2);
function dsb_redirect_canonical_paged($redirect_url, $requested_url)
{
    if ((int)get_query_var('dsb_seo_page_archive') && (int)get_query_var('paged') > 1)
    {
        $post_id        = (int)get_query_var('p');
        $lookup_table   = dsb_get_search_terms_and_locations_lookup_table($post_id);

        // Check if the current paged URL is in range
        $num_seo_pages  = count($lookup_table);
        $num_per_page   = apply_filters('dsb_archive_num_per_page', 10);
        $current_page   = (int)get_query_var('paged');
        $max_pages      = (int)ceil($num_seo_pages / $num_per_page);

        // We are still within range, keep user on /page/X/ and load the archive.php template
        if ($current_page > 0 && $current_page <= $max_pages)
        {
            $redirect_url = false;
        }

        
    }

    return $redirect_url;
}

add_action('pre_get_posts', 'dsb_pre_get_posts', 10, 1);
function dsb_pre_get_posts ($query)
{
    // Wordpress thinks we are querying a Post because the URL is paged
    if (!is_admin() && $query->is_main_query())
    {
        if ((int)get_query_var('dsb_seo_page_archive') && (int)get_query_var('paged') > 1)
        {
            $query->set('post_type', 'dsb_seo_page');
        }
    }
    
    return $query;
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//                                      PAGINATION - END
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

add_filter( 'rewrite_rules_array', 'dsb_rewrite_rules_array', 10, 1);
function dsb_rewrite_rules_array($rules)
{
    // Remove all other rewrite rules added by Wordpress for our CPT as we do not use these
    $rules = array_filter($rules, function($rule) 
    {
        return (strpos( $rule , 'dsb_seo_page/' ) !== 0);
    }, ARRAY_FILTER_USE_KEY);

    return $rules;
}

/**
 * Filters the path of the current template before including it.
 *
 * Checks if we are on a SEO Page url with search term and location in the URL.
 * Will return selected template or 404 found
 * 
 * @param string $template The path of the template to include.
 * 
 * @return string The path of the template to include.
 */
function dsb_template_include($template)
{
    // Gradually increase checks. Easiest most lightweight first:
    if (is_singular())
    {
        $post = get_queried_object();
        
        if ($post->post_type === 'dsb_seo_page')
        {
            if (get_query_var('dsb_seo_page') !== '')
            {
                $dsb    = DSB_Seo_Builder::get_instance();
                
                $slug   = strtolower(get_query_var('dsb_seo_page')); // match the query var to the list of slugs
                $slugs  = $dsb->dsb_get_seo_page_slugs($post->ID);     // get all slugs combinations for searchword and location for this specific seo page

                $slugs  = array_map('strtolower', $slugs);
                $slugs  = array_map('esc_html', $slugs);

                // We are actually on the url of a NW Seo Page!
                if ($slug !== false)
                {
                    // Can we find an existing slug with the correct searchword location pair?
                    if (is_array($slugs) && in_array($slug, $slugs))
                    {
                        // Default template used by Wordpress: get_page_template_slug() returns empty string and falls back to single.php (or whatever default template in theme falls back on)
                        $template = dsb_get_template($post->ID);

                        // Make sure Block Themes fall back on a default template:
                        if (wp_is_block_theme())
                        {
                            $template = locate_block_template($template, 'page', array());
                        }
                    }
                    else
                    {
                        // Show 404 page when url = webdevelopment/webdevelopment-in-asd
                        // test / find out if template_include is correct location. maybe template redirect?
                        global $wp_query;
                        $wp_query->set_404();
                        status_header(404);
                        nocache_headers();

                        $template = locate_template('404.php');
                    }
                }
            }
        }
    }
    
    // Allow to override template
    $template = apply_filters('dsb_template_include', $template);
    
    return $template;
}

/**
 * Filters the path of the current template before including it.
 *
 * Checks if we are on a SEO Page archive url
 * Will return selected template or 404 found
 * 
 * @param string $template The path of the template to include.
 * 
 * @return string The path of the template to include.
 */
function dsb_template_include_archive($templates)
{
    // Gradually increase checks. Easiest most lightweight first:
    if (is_singular())
    {
        $post = get_queried_object();
        
        if ($post->post_type === 'dsb_seo_page')
        {
            if ((int)get_query_var('dsb_seo_page_archive') === 1)
            {
                // Elementor support
                if (isset($_REQUEST['elementor-preview']) && (int)$_REQUEST['elementor-preview'] === get_the_ID())
                {
                    $template = dsb_get_template($post->ID);
                }
                else
                {
                    // Give theme authors option to copy templates to the theme directory and load those templates instead
                    $template_filename  = 'dsb-archive.php';
                    $template           = locate_template($template_filename);
                                
                    // No template was found in the theme, load template from plugin
                    if ($template === '')
                    {
                        $template = WP_PLUGIN_DIR . '/' . 'dsb-seo-builder/templates/' . $template_filename;
                    }
                }
            }
        }
    }
    
    // Allow to override template
    $template = apply_filters('dsb_template_include_archive', $templates);
    
    return $template;
}

// Divi fix. Otherwise Divi pagebuilder will not load in the backend and show error message about "Incompatible Post Type"
// Divi will not load in the frontend anyway...
$dsb_template_include_archive_priority = 99999; // Default priority to make sure the Archive page loads the default template
if (isset($_GET['et_fb']) && (int)$_GET['et_fb'] === 1)
{
    // Low priority to make sure Divi pagebuilder works when editing a SEO Page
    $dsb_template_include_archive_priority = 10;
}
add_filter('template_include', 'dsb_template_include', 10, 1);
add_filter('template_include', 'dsb_template_include_archive', $dsb_template_include_archive_priority, 1);


/**
 * Remove trailing slash from seo_builder_sitemap_index.xml
 * 
 * @param string $redirect The redirect URL currently determined.
 * 
 * @see https://wordpress.stackexchange.com/questions/281140/help-to-remove-last-trailing-slash-using-add-rewrite-rule
 */
function redirect_canonical_callbackss($redirect)
{
    $dsb_sitemap_query_var = get_query_var('dsb_sitemap', false);
    if ($dsb_sitemap_query_var === 'dsb_show_sitemap_index' || $dsb_sitemap_query_var === 'dsb_show_sitemap')
    {
        return false;
    }

    return $redirect;
}
add_filter('redirect_canonical', 'redirect_canonical_callbackss', 100, 1);

/**
 * Filters the permalink for a post of a custom post type.
 *
 * Removes CPT Slug from urls
 *
 * @param string  $post_link The post's permalink.
 * @param WP_Post $post      The post in question.
 * @param bool    $leavename Whether to keep the post name.
 * 
 * @return string The post's permalink.
 */
function dsb_remove_slug($post_link, $post, $leavename)
{
    if ($post->post_type === 'dsb_seo_page' && ($post->post_status === 'publish' || $post->post_status === 'draft'))
    {
        // Check get_query_var('dsb_seo_page') for the page we are currently on
        // Check strstr($post_link, '/dsb_seo_page/') used by next_post_link() / previous_post_link() which use post_type_link()
        if (get_query_var('dsb_seo_page') === '' || strstr($post_link, '/dsb_seo_page/'))
        {
            // https://gist.github.com/kellenmace/f8a3393385f01ee226a087ff19cc6056
            // Remove /dsb_seo_page/ from the url    
            if (strstr($post_link, '/dsb_seo_page/'))
            {
                $post_link = str_replace( '/dsb_seo_page/', '/', $post_link );
            }
        }
    }

    return $post_link;
}
add_filter('post_type_link', 'dsb_remove_slug', 10, 3);

// Change links added by the Wordpress menu
function filter_wp_nav_menu_objectss( $sorted_menu_items, $args)
{ 
    foreach ($sorted_menu_items as $item)
    {
        if ($item->object === 'dsb_seo_page' && strstr($item->url, '/dsb_seo_page/'))
        {
            $item->url = str_replace( '/dsb_seo_page/', '/', $item->url);
        }
    }

    return $sorted_menu_items; 
}; 
add_filter( 'wp_nav_menu_objects', 'filter_wp_nav_menu_objectss', 10, 2 ); 

////////////////////////////////////////////////////////////////////////////////////////////////////////
//                               SITEMAP URL REWRITES / REDIRECTS / ETC
////////////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Fires after WordPress has finished loading but before any headers are sent.
 *
 * Adds rewrite rules for the sitemap index and sitemap pages
 */
function dsb_seo_builder_sitemap_xml_add_rewrite_rule()
{
    // SITEMAP: listen to <SITE_URL>/seo_builder_sitemap_index.xml
    add_rewrite_rule(
        'seo_builder_sitemap_index.xml',
        'index.php?dsb_sitemap=dsb_show_sitemap_index',
        'top'
    );

    // SITEMAP: listen to <SITE_URL>/seo_builder_sitemap_<pagenumber>.xml
    add_rewrite_rule(
        'seo_builder_sitemap_([0-9]+)?\.xml',
        'index.php?dsb_sitemap=dsb_show_sitemap&dsb_sitemap_number=$matches[1]', 'top',
        'top'
    );
}
add_action( 'init', 'dsb_seo_builder_sitemap_xml_add_rewrite_rule' );

/**
 * Filters whether to short-circuit default header status handling.
 * 
 * Do not return 404 headers for existing sitemap urls
 * 
 * @param bool     $preempt  Whether to short-circuit default header status handling. Default false.
 */
add_filter( 'pre_handle_404', 'dsb_pre_handle_404', 10, 1);
function dsb_pre_handle_404 ($preempt)
{
    $dsb_sitemap_query_var = get_query_var('dsb_sitemap', false);
    if ($dsb_sitemap_query_var === 'dsb_show_sitemap_index' || $dsb_sitemap_query_var === 'dsb_show_sitemap')
    {
        $preempt = true;
    }
    
    return $preempt;
}

/**
 * Fires once the WordPress environment has been set up.
 * 
 * Adds actions to redirect sitemap urls and show correct content
 */
function dsb_template_redirect_sitemap()
{
    $dsb_sitemap = get_query_var('dsb_sitemap', false);
    
    if ($dsb_sitemap === 'dsb_show_sitemap_index')
    {
        add_action('template_redirect', 'dsb_show_sitemap_index');
    }
    else if ($dsb_sitemap === 'dsb_show_sitemap')
    {
        add_action('template_redirect', 'dsb_show_sitemap');
    }
}
add_action('wp', 'dsb_template_redirect_sitemap');

/**
 * Shows sitemap index with all sitemap pages
 */
function dsb_show_sitemap_index()
{
    global $dsb_seo_builder_dir;

    require_once "{$dsb_seo_builder_dir}/sitemap/sitemap-functions.php";
    require_once "{$dsb_seo_builder_dir}/sitemap/xml-sitemap-index.php";

    exit;
}

/**
 * Shows sitemap page with dynamically generated SEO pages
 */
function dsb_show_sitemap()
{
    global $dsb_seo_builder_dir;

    require_once "{$dsb_seo_builder_dir}/sitemap/sitemap-functions.php";
    require_once "{$dsb_seo_builder_dir}/sitemap/xml-sitemap.php";

    exit;
}



/**
 * Gets sitemap URL
 * 
 * @param bool|int  $current_page Index of current page to retrieve. Or return sitemap index url if false
 * 
 * @return string The sitemap url
 */
function dsb_get_sitemap_url($current_page = false)
{
    $sitemap_url = '';

    if ($current_page !== false)
    {
        $current_page   = (int)$current_page;
        $sitemap_url    = home_url() . "/seo_builder_sitemap_{$current_page}.xml";
    }
    else
    {
        $sitemap_url = home_url() . '/seo_builder_sitemap_index.xml';
    }

    return $sitemap_url;
}