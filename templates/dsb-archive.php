<?php
get_header();

?>

<div id="dsb-page-wrapper" class="dsb-full-width">
	<?php
		if (function_exists('yoast_breadcrumb'))
		{
			$breadcrumbs_enabled = current_theme_supports('yoast-seo-breadcrumbs');
			if (!$breadcrumbs_enabled)
			{
				$breadcrumbs_enabled = WPSEO_Options::get('breadcrumbs-enable', false);
			}

			if ($breadcrumbs_enabled)
			{
	?>
		<div class="dsb-row dsb-breadcrumbs">
			<div class="dsb-small-12 dsb-col">
				<?php
					yoast_breadcrumb( '<p id="breadcrumbs">','</p>' );
				?>
			</div>
		</div>
	<?php
			}
	    }
	?>
	<div class="dsb-row">
		<div class="dsb-small-12 dsb-col">
			<?php
                if (have_posts())
                {
                    while (have_posts())
                    {
                        the_post();

                        $post_id		= get_the_ID();
                        $post_title		= $post->post_title;
                        $seo_page_base	= $post->post_name;
                        $lookup_table 	= dsb_get_search_terms_and_locations_lookup_table($post_id);
                        $keys			= array_keys($lookup_table);
                        $current_page   = !empty(get_query_var('paged')) ? (int)get_query_var('paged') : 1;
                        $num_seo_pages  = count($lookup_table);
                        $num_per_page   = apply_filters('dsb_archive_num_per_page', 10);
                        $max_pages      = (int)ceil($num_seo_pages / $num_per_page);
                        $offset         = $num_per_page * ($current_page - 1);
                        $dsb_seo_pages	= array_slice($lookup_table, $offset, $num_per_page);
                        $index			= $offset;
			?>
			<h1><?php echo dsb_get_field('dsb-archive-page-title', $post_id, __('Archive', 'dsb_seo_builder')); ?></h1>
			<?php
                        foreach ($dsb_seo_pages as $dsb_seo_page)
                        {
                            $search_term	= $dsb_seo_page[0];
                            $location		= $dsb_seo_page[1];
                            $search_terms	= $dsb_seo_page[2];
                            $locations		= $dsb_seo_page[3];

                            $the_slug		= $keys[$index];
                            $the_title 		= dsb_get_seo_pages_replace_search_terms_and_locations($post_title, $post_id, $search_term, $location, $search_terms, $locations, $the_slug, $index);
                            
                            $the_url		= esc_url( trailingslashit(home_url($seo_page_base . '/' . strtolower(sanitize_title($the_slug)))));

                            if (get_option('dsb-enable_spintax', false))
                            {
                                $post               = get_post($post_id);
                                $post_content       = $post->post_content;
                                $post_excerpt       = dsb_get_seo_pages_replace_search_terms_and_locations($post_content, $post_id, $search_term, $location, $search_terms, $locations, $the_slug, $index);

                                $post->post_excerpt = '';
                                $post->post_content = $post_excerpt;

                                $post_excerpt       = get_the_excerpt($post);
                            }
                            else
                            {
                                $post_excerpt       = get_the_excerpt();
                            }

                            echo "<div class='dsb-seo-page'>";

                            echo sprintf("<h2><a class='dsb-seo-page' href='%s'>%s</a></h2>",
                                $the_url,
                                $the_title);

                            echo "<p>";
                            echo dsb_get_seo_pages_replace_search_terms_and_locations($post_excerpt, $post_id, $search_term, $location, $search_terms, $locations, $the_slug, $index);
                            echo "</p>";

                            echo "</div>";

                            $index++;
                        }
                }
			?>
                <nav class="dsb-pagination">
                    <?php
                        $args = array(
                            'total'		=> $max_pages,
                            'current'	=> $current_page,
                            'add_args'  => false,
                            'type'      => 'list',
                            'end_size'  => 3,
                            'mid_size'  => 3,
                            'show_all'	=> false,
                            'prev_next'	=> false
                        );

                        echo paginate_links($args);
                    ?>
                </nav>
			<?php
				}
			?>
		</div>
	</div>
</div>

<?php

get_footer();
