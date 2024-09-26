<?php

/**
 * Plugin Name:             DynamicSEO Builder
 * Plugin URI:              https://seobuilder.io/
 * Description:             Builder SEO landingpages based on different search terms and locations.
 * Version:                 2.6.0
 * Author:                  seobuilder.io
 * Author URI:              https://seobuilder.io/
 * Text Domain:             dsb_seo_builder
 * Domain Path:             /languages
 * 
 * Copyright:               © 2024 seobuilder.io
 * License:                 GNU General Public License v3.0
 * License URI:             http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('ABSPATH') or die();

define('DSB_PLUGIN_VERSION', '2.6.0');

add_action('init', 'dsb_plugin_init', -999999);
function dsb_plugin_init()
{
    load_plugin_textdomain('dsb_seo_builder', false, 'dsb-seo-builder/languages');
}

/**
 * Activate the plugin.
 */
register_activation_hook( __FILE__, 'dsb_activate' );
function dsb_activate()
{ 
    // Register CPT
    dsb_register_cpt_dsb_seo_page();

    // Create a demo page to showcase the plugin functionalities
    dsb_create_seo_gen_example_page();

    // Clear the permalinks after CPT has been registered
    flush_rewrite_rules(); 
}
 
/**
 * Deactivation hook.
 */
register_deactivation_hook( __FILE__, 'dsb_deactivate' );
function dsb_deactivate()
{
    // Unregister the CPT, so the rules are no longer in memory
    unregister_post_type( 'dsb_seo_page' );

    // Clear the permalinks to remove our CPTs rules from the database.
    flush_rewrite_rules();
}

function dsb_get_plugin_dir()
{
    $dsb_seo_builder_dir = plugin_dir_path(__FILE__);
    return $dsb_seo_builder_dir;
}

function dsb_get_plugin_url()
{
    $dsb_seo_builder_url = plugins_url('dsb-seo-builder');
    return $dsb_seo_builder_url;
}

function dsb_get_plugin_basename()
{
    $dsb_seo_builder_basename = plugin_basename(__FILE__);
    return $dsb_seo_builder_basename;
}

$dsb_seo_builder_dir = dsb_get_plugin_dir();
$dsb_seo_builder_url = dsb_get_plugin_url();

require_once "{$dsb_seo_builder_dir}includes/field-filters.php";

require_once "{$dsb_seo_builder_dir}includes/admin.php";
require_once "{$dsb_seo_builder_dir}includes/content-filters.php";
require_once "{$dsb_seo_builder_dir}includes/yoast-filters.php";
require_once "{$dsb_seo_builder_dir}includes/rankmath-filters.php";
require_once "{$dsb_seo_builder_dir}includes/custom-post-type.php";
require_once "{$dsb_seo_builder_dir}includes/functions.php";
require_once "{$dsb_seo_builder_dir}includes/url-rewrites.php";

require_once "{$dsb_seo_builder_dir}includes/class.dsb-config.php";
require_once "{$dsb_seo_builder_dir}includes/class.dsb-meta-block.php";
require_once "{$dsb_seo_builder_dir}includes/class.dsb-meta-block-fields.php";
require_once "{$dsb_seo_builder_dir}includes/class.dsb-spintax.php";

require_once "{$dsb_seo_builder_dir}includes/class.dsb.php";
require_once "{$dsb_seo_builder_dir}includes/class.dsb-settings.php";
require_once "{$dsb_seo_builder_dir}includes/class.dsb-documentation.php";
require_once "{$dsb_seo_builder_dir}includes/dsb-meta-boxes.php";
