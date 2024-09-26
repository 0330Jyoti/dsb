<?php

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * XML Sitemap PHP Script
 * Modified from https://github.com/jdevalk/XML-Sitemap-PHP-Script
 */

// The XSL file used for styling the sitemap output, make sure this path is relative to the root of the site.
global $dsb_seo_builder_url;
$xsl = $dsb_seo_builder_url . '/sitemap/xml-sitemap.xsl';

// Sent the correct header so browsers display properly, with or without XSL.
header( 'Content-Type: application/xml' );

echo '<?xml version="1.0" encoding="UTF-8"?>';

if (isset($xsl) && !empty($xsl)){
	echo '<?xml-stylesheet type="text/xsl" href="' . $xsl . '"?>' . "\n";
}

/**
 * Build the root sitemap (example.com/seo_builder_sitemap_index.xml) which lists sub-sitemaps
 */
dsb_build_root_map();
