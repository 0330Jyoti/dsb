<?php

function dsb_add_slug_column($post_columns){

    if (isset($post_columns['title']))
    {
        $post_columns = dsb_array_insert_after(
            $post_columns,
            'title',
            'dsb_seo_page_base',
            __('SEO Page base', 'dsb_seo_builder')
        );
        $post_columns = dsb_array_insert_after(
            $post_columns,
            'dsb_seo_page_base',
            'dsb_archive_page_title',
            __('Archive page title', 'dsb_seo_builder')
        );
        $post_columns = dsb_array_insert_after(
            $post_columns,
            'dsb_archive_page_title',
            'dsb_search_term',
            __('1st Search Term', 'dsb_seo_builder')
        );
        $post_columns = dsb_array_insert_after(
            $post_columns,
            'dsb_search_term',
            'dsb_location',
            __('1st Location', 'dsb_seo_builder')
        );
        $post_columns = dsb_array_insert_after(
            $post_columns,
            'dsb_location',
            'dsb_num_generated_urls',
            __('Generated URLs', 'dsb_seo_builder')
        );
    }
    else
    {
        $post_columns['dsb_seo_page_base']      = __('SEO Page base', 'dsb_seo_builder');
        $post_columns['dsb_archive_page_title'] = __('Archive page title', 'dsb_seo_builder');
        $post_columns['dsb_search_term']        = __('1st Search Term', 'dsb_seo_builder');
        $post_columns['dsb_location']           = __('1st Location', 'dsb_seo_builder');
        $post_columns['dsb_num_generated_urls'] = __('Generated URLs', 'dsb_seo_builder');
    }

    return $post_columns;
}
add_filter('manage_dsb_seo_page_posts_columns', 'dsb_add_slug_column');
function dsb_add_custom_column_data($column_name, $post_id){
    if ($column_name === 'dsb_seo_page_base')
    {
        $post = get_post($post_id);
        echo urldecode($post->post_name);
    }
    else if ($column_name === 'dsb_archive_page_title')
    {
        echo dsb_get_field('dsb-archive-page-title', $post_id, __('Archive', 'dsb_seo_builder'), false);
    }
    else if ($column_name === 'dsb_search_term')
    {
        $dsb            = DSB_Seo_Builder::get_instance();
        $search_term    = $dsb->dsb_get_search_terms($post_id);

        if (is_array($search_term) && count($search_term) > 0)
        {
            echo current($search_term);
        }
        else
        {
            printf("<span class='dashicons-before dashicons-no' style='color: #ff0000;'></span> %s", 
                    __("No Searchterms"));
        }
    }
    else if ($column_name === 'dsb_location')
    {
        $dsb            = DSB_Seo_Builder::get_instance();
        $locations      = $dsb->dsb_get_locations($post_id);

        if (is_array($locations) && count($locations) > 0)
        {
            echo current($locations);
        }
        else
        {
            printf("<span class='dashicons-before dashicons-no' style='color: #ff0000;'></span> %s", 
                    __("No Locations"));
        }
    }
    else if ($column_name === 'dsb_num_generated_urls')
    {
        $dsb    = DSB_Seo_Builder::get_instance();

        $search_terms       = $dsb->dsb_get_search_terms($post_id);
        $locations          = $dsb->dsb_get_locations($post_id);
        $num_urls           = 0;
        
        if (is_array($search_terms) && is_array($locations))
        {
            $num_urls           = count($search_terms) * count($locations);
        }

        echo "<span>". number_format($num_urls, 0, ",", ".") . "</span>";
    }
}
add_action( 'manage_dsb_seo_page_posts_custom_column' , 'dsb_add_custom_column_data', 10, 2 );
function dsb_theme_page_templates($post_templates){
    $post_templates = array();

    if (!wp_is_block_theme())
    {
        $post_templates['dsb-seo-builder/templates/template-page-full-width.php'] = __('Full width', 'dsb_seo_builder');
        $post_templates['dsb-seo-builder/templates/template-page-no-sidebar.php'] = __('No sidebar', 'dsb_seo_builder');
    }

	return $post_templates;
}
add_filter("theme_dsb_seo_page_templates", "dsb_theme_page_templates", 999, 1);

function dsb_wp_enqueue_scripts(){
    if (get_option('dsb-include_front_end_styling', true))
    {
        wp_enqueue_style(
            'dsb-styles',
            dsb_get_plugin_url() . '/assets/dsb-styles.css',
            array(),
            DSB_PLUGIN_VERSION
        );
    }
}
add_action('wp_enqueue_scripts', 'dsb_wp_enqueue_scripts', 10, 0);

function dsb_load_admin_scripts(){
    global $typenow;

    if ($typenow === 'dsb_seo_page')
    {
        wp_enqueue_style(
            'dsb-admin-styles',
            dsb_get_plugin_url() . '/assets/dsb-admin-styles.css',
            array(),
            DSB_PLUGIN_VERSION
        );
        wp_enqueue_script('jquery-ui-tabs');
        wp_enqueue_script(
            'dsb-admin-scripts',
            dsb_get_plugin_url() . '/assets/dsb-admin-scripts.js',
            array('jquery-ui-tabs'),
            DSB_PLUGIN_VERSION
        );
        $dsb = DSB_Seo_Builder::get_instance();
        wp_localize_script(
            'dsb-admin-scripts',
            'dsb',
            array(
                'max_search_terms'      => $dsb->dsb_get_max_search_terms(),
                'max_locations'         => $dsb->dsb_get_max_locations(),
                'label_search_terms'    => __('Search terms', 'dsb_seo_builder'),
                'label_locations'       => __('Locations', 'dsb_seo_builder'),
                'lines'                 => __('Lines: ', 'dsb_seo_builder'),
                'max'                   => __('max: ', 'dsb_seo_builder')
            )
        );
    }
}
add_action('admin_enqueue_scripts', 'dsb_load_admin_scripts', 10, 0);
