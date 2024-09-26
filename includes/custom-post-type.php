<?php

// Register Custom Post Type: dsb_seo_page
add_action( 'init', 'dsb_register_cpt_dsb_seo_page', -99999 );
function dsb_register_cpt_dsb_seo_page()
{
	$labels = array(
		"name"                  => _x("SEO Builder", 'Post type general name', 'dsb_seo_builder'),
		"singular_name"         => _x("SEO Page", 'Post type singular name', 'dsb_seo_builder'),
		"menu_name"             => _x("SEO Builder", 'Admin Menu text', 'dsb_seo_builder'),
		"name_admin_bar"        => _x("SEO Builder", 'Add New on Toolbar', 'dsb_seo_builder'),
		"archives"              => __("SEO Pages", 'dsb_seo_builder'),
		"all_items"             => __("All SEO Pages", 'dsb_seo_builder'),
		"add_new_item"          => __("Add new SEO Page", 'dsb_seo_builder'),
		"add_new"               => __("Add new SEO Page", 'dsb_seo_builder'),
		"new_item"              => __("New SEO Page", 'dsb_seo_builder'),
		"edit_item"             => __("Edit SEO Page", 'dsb_seo_builder'),
		"update_item"           => __("Update SEO Page", 'dsb_seo_builder'),
		"view_item"             => __("View SEO Page", 'dsb_seo_builder'),
		"view_items"            => __("View SEO Pages", 'dsb_seo_builder'),
		"search_items"          => __("Search SEO Pages", 'dsb_seo_builder')
	);
	$args = array(
		'label'                 => __("SEO Page", 'dsb_seo_builder'),
		'labels'                => $labels,
		'supports'              => array( 'title','editor', 'thumbnail', 'revisions', 'author'),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 25,
		'menu_icon'             => 'dashicons-format-gallery',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => false,
		'feeds'					=> false,
		'exclude_from_search'   => true,
		'publicly_queryable'    => true,
		'capability_type'       => 'post',
		'show_in_rest'          => false,	// No Gutenberg support just yet as there is no room for our custom meta boxes
		'rewrite'               => array(
			// remove extra words added in the Permalinks > Custom Structure. Example /posts_front_slug/%postname%/ would become /posts_front_slug/webdesign/ for a CPT dsb_seo_page post Webdesign
			// we want to remove the WP_Rewrite::$front 'posts_front_slug' from the url by setting 'with_front' to false:
            'with_front'    => false	
        )
	);
	register_post_type( 'dsb_seo_page', $args );
}

