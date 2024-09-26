<?php
/**
 * The template for displaying an archive of SEO Builder pages for this SEO Page
 *
 * This template can be overridden by copying it to yourtheme/template-page-no-sidebar.php
 */

get_header();
?>

<div id="dsb-page-wrapper" class="dsb-no-sidebar">
	<div class="dsb-row">
		<div class="dsb-small-12 dsb-medium-9 dsb-col">
			<?php 
				if (have_posts())
				{
					while (have_posts())
					{
						the_post();

						if (has_post_thumbnail())
						{
							$size = apply_filters('dsb-post-thumbnail-size', 'post-thumbnail');
							the_post_thumbnail($size);
						}

						the_title('<h1>', '</h1>');

						the_content();
					}
				}
			?>
		</div>
	</div>
</div>

<?php

get_footer();
