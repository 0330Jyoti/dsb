<?php

/**
 * Builds the root sitemap (example.com/seo_builder_sitemap_index.xml) which lists sub-sitemaps
 */
function dsb_build_root_map(){
    $dsb        		= DSB_Seo_Builder::get_instance();
    $entries_per_page 	= $dsb->dsb_get_entries_per_sitemap_page();
    $links            	= [];
    $date				= dsb_get_last_modified_gmt();

    $lookup_tables	= dsb_get_search_terms_and_locations_lookup_tables();
    $max_num_pages	= ceil(count($lookup_tables) / $entries_per_page);

    for ($current_page = 1; $current_page <= $max_num_pages; $current_page++)
    {
        $links[] = array(
            'loc'     => dsb_get_sitemap_url($current_page),
            'lastmod' => $date,
        );
    }

    if (empty($links))
    {
        $bad_sitemap = true;
        $sitemap     = '';

        return;
    }

    $sitemap = dsb_get_sitemap_index($links);

    echo $sitemap;
}

/**
 * Builds the sitemap index.
 *
 * @param array $links Set of sitemaps index links.
 *
 * @return string
 */
function dsb_get_sitemap_index($links){
	$xml = '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

	foreach ($links as $link)
	{
		$xml .= dsb_sitemap_index_url($link);	// returns <sitemap><loc>URL</loc><lastmod>DATE</lastmod></sitemap>
	}

	$xml .= '</sitemapindex>';

	return $xml;
}

/**
 * Build the <sitemap> tag for a given URL.
 *
 * @param array $url Array of parts that make up this entry.
 *
 * @return string
 */
function dsb_sitemap_index_url($url){
	$date 		= $url['lastmod'];
	$charset 	= 'UTF-8';
	$url['loc'] = htmlspecialchars($url['loc'], ENT_COMPAT, $charset, false);

	$output		= "\t<sitemap>\n";
	$output		.= "\t\t<loc>" . $url['loc'] . "</loc>\n";
	$output		.= "\t\t<lastmod>" . $date . "</lastmod>\n";
	$output		.= "\t</sitemap>\n";

	return $output;
}

/**
 * Build the <url> tags and print to screen
 *
 * @return void
 */
function dsb_output_seo_pages_sitemap_xml(){
    $dsb        		= DSB_Seo_Builder::get_instance();
    
    // The Change Frequency for files, should probably not be 'never', unless you know for sure you'll never change them again.
    // https://www.sitemaps.org/protocol.html
    $chfreq 			= 'monthly';

    // The Priority Frequency for files. There's no way to differentiate so it might just as well be 1.
    $prio 				= 1;

    $dsb_sitemap_number = (int)get_query_var('dsb_sitemap_number', false) - 1;  // urls count from 1, we count from 0
    $entries_per_page   = $dsb->dsb_get_entries_per_sitemap_page();

    $offset             = $entries_per_page * $dsb_sitemap_number;
    $length             = $entries_per_page;

    $lookup_tables		= dsb_get_search_terms_and_locations_lookup_tables();
    $urls				= array();
    foreach ($lookup_tables as $slug => $data)
    {
        $date			= $data[4];
        $seo_page_base 	= $data[5];
        $urls[$date]  	= esc_url( trailingslashit( home_url($seo_page_base . '/' . strtolower(sanitize_title($slug))) ) );
    }

    if (is_array($urls))
    {
        $urls 				= array_slice($urls, $offset, $length);
    }

    foreach ($urls as $date => $url)
    {
        // Start creating the output
        // <lastmod>2021-01-22T08:01:50+00:00</lastmod>
    ?>

    <url>
        <loc><?php echo $url; ?></loc>
        <lastmod><?php echo $date; ?></lastmod>
        <changefreq><?php echo $chfreq; ?></changefreq>
        <priority><?php echo $prio; ?></priority>
    </url><?php
    }
}

/**
 * Get the GMT modification date for the last modified post in the post type.
 *
 * @return string|false
 */
function dsb_get_last_modified_gmt(){
    global $wpdb;

    $sql = "
        SELECT post_type, MAX(post_modified_gmt) AS date
        FROM $wpdb->posts
        WHERE post_status = 'publish'
            AND post_type = 'dsb_seo_page'
        GROUP BY post_type
        ORDER BY date DESC
    ";

    $result = $wpdb->get_row($sql);
    $date   = false;
    if ($result !== null)
    {
        $date = $result->date;
    }

	$date = dsb_format_timestamp($date);
    
    return $date;
}

