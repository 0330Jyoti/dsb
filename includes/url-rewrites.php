<?php
function dsb_add_query_vars($public_query_vars){
    $public_query_vars[] = 'dsb_seo_page';
    $public_query_vars[] = 'dsb_seo_page_archive';
    $public_query_vars[] = 'dsb_sitemap';
    $public_query_vars[] = 'dsb_sitemap_number';

    return $public_query_vars;
}
add_filter('query_vars', 'dsb_add_query_vars', 0, 1);
function dsb_add_rewrite_rules(){
    $dsb        = DSB_Seo_Builder::get_instance();

    $pages      = $dsb->dsb_get_seo_pages();

    $page_names = array();

    if (is_array($pages) && count($pages) > 0)
    {
        foreach ($pages as $page)
        {
            $page_names[$page->ID] = $page->post_name;
        }

        global $wp_rewrite;
        
        foreach ($page_names as $post_id => $page_name)
        {
            add_rewrite_rule('^' . $page_name . '/' . $wp_rewrite->pagination_base . '/?([0-9]{1,})/?$',
                'index.php?post_type=dsb_seo_page&name=' . $page_name . '&paged=$matches[1]&dsb_seo_page_archive=1&p=' . (int)$post_id,
                'top');

            $pattern = preg_quote($page_name, '') . '/([^/]+)(?:/(.*))?/?$';

            add_rewrite_rule(
                $pattern,
                'index.php?post_type=dsb_seo_page&name=' . $page_name . '&dsb_seo_page=$matches[1]',
                'top'
            );
            
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

add_filter('redirect_canonical', 'dsb_redirect_canonical_paged', 10, 2);
function dsb_redirect_canonical_paged($redirect_url, $requested_url){
    if ((int)get_query_var('dsb_seo_page_archive') && (int)get_query_var('paged') > 1)
    {
        $post_id        = (int)get_query_var('p');
        $lookup_table   = dsb_get_search_terms_and_locations_lookup_table($post_id);

        $num_seo_pages  = count($lookup_table);
        $num_per_page   = apply_filters('dsb_archive_num_per_page', 10);
        $current_page   = (int)get_query_var('paged');
        $max_pages      = (int)ceil($num_seo_pages / $num_per_page);

        if ($current_page > 0 && $current_page <= $max_pages)
        {
            $redirect_url = false;
        }

        
    }

    return $redirect_url;
}

add_action('pre_get_posts', 'dsb_pre_get_posts', 10, 1);
function dsb_pre_get_posts ($query){
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
function dsb_rewrite_rules_array($rules){

    $rules = array_filter($rules, function($rule) 
    {
        return (strpos( $rule , 'dsb_seo_page/' ) !== 0);
    }, ARRAY_FILTER_USE_KEY);

    return $rules;
}

function dsb_template_include($template){
    if (is_singular())
    {
        $post = get_queried_object();
        
        if ($post->post_type === 'dsb_seo_page')
        {
            if (get_query_var('dsb_seo_page') !== '')
            {
                $dsb    = DSB_Seo_Builder::get_instance();
                
                $slug   = strtolower(get_query_var('dsb_seo_page'));
                $slugs  = $dsb->dsb_get_seo_page_slugs($post->ID); 
                $slugs  = array_map('strtolower', $slugs);
                $slugs  = array_map('esc_html', $slugs);

                if ($slug !== false)
                {
                    if (is_array($slugs) && in_array($slug, $slugs))
                    {
                        $template = dsb_get_template($post->ID);

                        if (wp_is_block_theme())
                        {
                            $template = locate_block_template($template, 'page', array());
                        }
                    }
                    else
                    {
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

    $template = apply_filters('dsb_template_include', $template);
    
    return $template;
}

function dsb_template_include_archive($templates){
    if (is_singular())
    {
        $post = get_queried_object();
        
        if ($post->post_type === 'dsb_seo_page')
        {
            if ((int)get_query_var('dsb_seo_page_archive') === 1)
            {
                if (isset($_REQUEST['elementor-preview']) && (int)$_REQUEST['elementor-preview'] === get_the_ID())
                {
                    $template = dsb_get_template($post->ID);
                }
                else
                {
                    $template_filename  = 'dsb-archive.php';
                    $template           = locate_template($template_filename);
                    if ($template === '')
                    {
                        $template = WP_PLUGIN_DIR . '/' . 'dsb-seo-builder/templates/' . $template_filename;
                    }
                }
            }
        }
    }
    $template = apply_filters('dsb_template_include_archive', $templates);
    
    return $template;
}

$dsb_template_include_archive_priority = 99999; 
if (isset($_GET['et_fb']) && (int)$_GET['et_fb'] === 1)
{
    $dsb_template_include_archive_priority = 10;
}
add_filter('template_include', 'dsb_template_include', 10, 1);
add_filter('template_include', 'dsb_template_include_archive', $dsb_template_include_archive_priority, 1);

function redirect_canonical_callbackss($redirect){
    $dsb_sitemap_query_var = get_query_var('dsb_sitemap', false);
    if ($dsb_sitemap_query_var === 'dsb_show_sitemap_index' || $dsb_sitemap_query_var === 'dsb_show_sitemap')
    {
        return false;
    }

    return $redirect;
}
add_filter('redirect_canonical', 'redirect_canonical_callbackss', 100, 1);


function dsb_remove_slug($post_link, $post, $leavename){
    if ($post->post_type === 'dsb_seo_page' && ($post->post_status === 'publish' || $post->post_status === 'draft'))
    {
        if (get_query_var('dsb_seo_page') === '' || strstr($post_link, '/dsb_seo_page/'))
        {
            if (strstr($post_link, '/dsb_seo_page/'))
            {
                $post_link = str_replace( '/dsb_seo_page/', '/', $post_link );
            }
        }
    }

    return $post_link;
}
add_filter('post_type_link', 'dsb_remove_slug', 10, 3);

function filter_wp_nav_menu_objectss( $sorted_menu_items, $args){ 
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


function dsb_seo_builder_sitemap_xml_add_rewrite_rule(){
    add_rewrite_rule(
        'seo_builder_sitemap_index.xml',
        'index.php?dsb_sitemap=dsb_show_sitemap_index',
        'top'
    );

    add_rewrite_rule(
        'seo_builder_sitemap_([0-9]+)?\.xml',
        'index.php?dsb_sitemap=dsb_show_sitemap&dsb_sitemap_number=$matches[1]', 'top',
        'top'
    );
}
add_action( 'init', 'dsb_seo_builder_sitemap_xml_add_rewrite_rule' );


add_filter( 'pre_handle_404', 'dsb_pre_handle_404', 10, 1);
function dsb_pre_handle_404 ($preempt){
    $dsb_sitemap_query_var = get_query_var('dsb_sitemap', false);
    if ($dsb_sitemap_query_var === 'dsb_show_sitemap_index' || $dsb_sitemap_query_var === 'dsb_show_sitemap')
    {
        $preempt = true;
    }
    
    return $preempt;
}

function dsb_template_redirect_sitemap(){
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

function dsb_show_sitemap_index(){
    global $dsb_seo_builder_dir;

    require_once "{$dsb_seo_builder_dir}/sitemap/sitemap-functions.php";
    require_once "{$dsb_seo_builder_dir}/sitemap/xml-sitemap-index.php";

    exit;
}

function dsb_show_sitemap(){
    global $dsb_seo_builder_dir;

    require_once "{$dsb_seo_builder_dir}/sitemap/sitemap-functions.php";
    require_once "{$dsb_seo_builder_dir}/sitemap/xml-sitemap.php";

    exit;
}

function dsb_get_sitemap_url($current_page = false){
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