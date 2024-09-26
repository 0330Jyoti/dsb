<?php

/**
 * Adds a fake metabox on CPT dsb_seo_page edit pages
 * 
 * Adds fields to define URL structure of dynamically generated SEO Pages
 * 
 * Only loadded on Post Edit screen of CPT = dsb_seo_page
 */
class DSB_Config
{
	/**
	 * Holds DSB_Meta_Field fields to show and save each field
	 * 
	 * @var DSB_Meta_Block
	 */
	private $block;

	/**
	 * Fake meta box configuration
	 * 
	 * @var array
	 */
	private $meta_box_config;

	/**
	 * Nonce value that was used for verification, usually via a form field.
	 * 
	 * @var string
	 */
	private $nonce_name;

	/**
	 * 
	 */
	public function __construct()
	{
		// Fake meta box configuration
		$this->meta_box_config = array(
			'id'		=> 'dsb-seo-page-config',
			'title'		=> __('SEO Page settings', 'dsb_seo_builder'),
		);

		$dsb							= DSB_Seo_Builder::get_instance();
		$search_term_placeholder		= $dsb->get_search_term_single_placeholder();
		$search_term_plural_placeholder	= $dsb->get_search_term_plural_placeholder();

		$location_placeholder 			= $dsb->get_location_single_placeholder();
		// $location_plural_placeholder 	= $dsb->get_location_plural_placeholder();

		$this->nonce_name 				= $this->meta_box_config['id'] . '_nonce';

		// Create DSB_Meta_field fields
		$seo_page_base_field = new DSB_Meta_Input_Field(
			array(
                'attr'          => array(
                    'type'          => 'text',
                    'id' 			=> 'seo-page-base',
                    'placeholder'	=> __("SEO Page base", 'dsb_seo_builder'),
                    'autocomplete'	=> 'off'
                ),
				'label' 		=> __('SEO Page base', 'dsb_seo_builder'),
				'wrapper_class'	=> array('dsb-small-12', 'dsb-medium-3', 'dsb-seo-page-base'),
				'desc'			=> __("The SEO Page base will be used in all generated SEO Page URLs. This would make your URLs look like the URL Structure below.", 'dsb_seo_builder'),
			)
		);

		$slug_placeholder_field = new DSB_Meta_Input_Field(
			array(
                'attr'          => array(
                    'type'          => 'text',
                    'id' 			=> 'slug-placeholder',
                    'placeholder'	=> "{$search_term_placeholder}-in-{$location_placeholder}",
					'autocomplete'	=> 'off',
                ),
				'label' 		=> __('Slug', 'dsb_seo_builder'),
				'wrapper_class'	=> array('dsb-small-12', 'dsb-medium-3', 'dsb-slug-placeholder'),
				'default'		=> "{$search_term_placeholder}-in-{$location_placeholder}",
				'desc'			=> sprintf(
									__("Enter the slug with %s and %s to generate unique URLs for each combination of a search term and a location.", 'dsb_seo_builder'),
									$search_term_placeholder,
									$location_placeholder
								)
			)
		);

		$archive_page_title_field = new DSB_Meta_Input_Field(
			array(
                'attr'          => array(
                    'type'          => 'text',
                    'id' 			=> 'archive-page-title',
                    'placeholder'	=> __('Archive', 'dsb_seo_builder'),
					'autocomplete'	=> 'off',
                ),
				'label' 		=> __('Archive page title', 'dsb_seo_builder'),
				'wrapper_class'	=> array('dsb-small-12', 'dsb-medium-3', 'dsb-slug-placeholder'),
				'default'		=> __('Archive', 'dsb_seo_builder'),
				'desc'			=> __("Enter the page title to show on the archive page.", 'dsb_seo_builder'),
			)
		);

        $overview_label_field = new DSB_Meta_Input_Field(
			array(
                'attr'          => array(
                    'type'          => 'text',
                    'id' 			=> 'overview-label',
                    'placeholder'	=> __('Overview', 'dsb_seo_builder'),
					'autocomplete'	=> 'off',
                ),
				'label' 		=> __('Overview label', 'dsb_seo_builder'),
				'wrapper_class'	=> array('dsb-small-12', 'dsb-medium-3', 'dsb-slug-placeholder'),
				'default'		=> '',
				'desc'			=> __("Enter the label to use for the link to archive page.", 'dsb_seo_builder'),
			)
		);
		
		$url_example_field = new DSB_Meta_HTML_Field(
			array(
                'attr'          => array(
                    'id'    => 'url-structure',
                    'type'  => 'html'
                ),
				'label'         => __('URL Structure', 'dsb_seo_builder'),
				'wrapper_class' => array('dsb-small-12')
			)
		);

		$dsb_get_search_terms_field = new DSB_Meta_Textarea_Field(
			array(
                'attr'          => array(
                    'id' 			=> 'search-terms',
                    'placeholder'	=> __('One search term per line. Separate single and plural values with the pipe | character', 'dsb_seo_builder'),
                    'type'          => 'textarea',
                    'rows'          => 5
                ),
				'label' 		=> __('Search terms', 'dsb_seo_builder'),
				'wrapper_class'	=> array('dsb-small-12', 'dsb-medium-6', 'dsb-search-terms', 'dsb-textarea-max-length'),
				'desc'			=> sprintf(
									__("Enter a list of unique search terms. For every SEO Page each %s and %s placeholder will be replaced by a search term from this list. Separate two search terms on a single line by the pipe | character for single and plural values", 'dsb_seo_builder'),
									$search_term_placeholder,
									$search_term_plural_placeholder
								)
			)
		);

		$dsb_locations_field = new DSB_Meta_Textarea_Field(
			array(
                'attr'          => array(
                    'id' 			=> 'locations',
                    'placeholder'	=> __('One location per line.  Separate single and plural values with the pipe | character', 'dsb_seo_builder'),
                    'type'          => 'textarea',
                    'rows'          => 5
                ),
				'label' 		=> __('Locations', 'dsb_seo_builder'),
				'wrapper_class'	=> array('dsb-small-12', 'dsb-medium-6', 'dsb-locations', 'dsb-textarea-max-length'),
				'desc'			=> sprintf(
									__("Enter a list of unique locations. For every SEO Page each %s placeholder will be replaced by a location from this list.", 'dsb_seo_builder'),
									$location_placeholder
								)
			)
		);

		$html_field = new DSB_Meta_HTML_Field(
			array(
                'attr'          => array(
                    'id'    => 'explanation',
                    'type'  => 'html'
                ),				
				'label'         => __('Overview generated URLs', 'dsb_seo_builder'),
				'wrapper_class' => array('dsb-small-12')
			)
		);

        $title_tag_field = new DSB_Meta_Input_Field(
			array(
                'attr'          => array(
                    'type'          => 'text',
                    'id' 			=> 'title-tag',
                    'placeholder'	=> __('SEO title', 'dsb_seo_builder'),
					'autocomplete'	=> 'off',
                ),
				'label' 		=> __('SEO title', 'dsb_seo_builder'),
				'wrapper_class'	=> array('dsb-small-12', 'dsb-medium-6', 'dsb-title-tag'),
				'default'		=> '',
				'desc'			=> __("Enter the title tag for the head. Leave empty to use the default Wordpress title-tag or of your SEO plugin, if it supports SEO Builder.", 'dsb_seo_builder'),
			)
		);

        $meta_description_field = new DSB_Meta_Textarea_Field(
			array(
                'attr'          => array(
                    'id' 			=> 'meta-description',
                    'placeholder'	=> __('Meta description', 'dsb_meta_description'),
                    'type'          => 'textarea',
                    'rows'          => 3
                ),
				'label' 		=> __('Meta description', 'dsb_seo_builder'),
				'wrapper_class'	=> array('dsb-small-12', 'dsb-medium-6', 'dsb_meta_description'),
				'desc'			=> sprintf(
									__("Enter the meta description with %s and %s to generate a unique meta description for each page. Leave empty to use the Meta description of your SEO plugin, if it supports SEO Builder.", 'dsb_seo_builder'),
									$search_term_placeholder,
									$location_placeholder
								)
			)
		);

		// Create DSB_Meta_Block and add fields
		$block = new DSB_Meta_Block();
		$block->add_field($seo_page_base_field);
		$block->add_field($slug_placeholder_field);
		$block->add_field($archive_page_title_field);
        $block->add_field($overview_label_field);
        $block->add_field($url_example_field);
		$block->add_field($dsb_get_search_terms_field);
		$block->add_field($dsb_locations_field);
		$block->add_field($html_field);
		
        $block->add_field($title_tag_field);
		$block->add_field($meta_description_field);

		// Store the DSB_Meta_Block
		$this->set_block($block);

		// Actions
        add_action('edit_form_after_title', array($this, 'edit_form_after_title'));
		add_action('save_post', array($this, 'save'), 10, 3);
		add_filter('get_sample_permalink_html', '__return_empty_string');	// Hides the Permalink just below the Post title field

		// Remove the permalink from the Screen Options as it is replaced by dsb-seo-page-base field
		remove_meta_box('slugdiv', 'dsb_seo_page', 'normal');
	}
    
	/**
	 * Show fake meta box above the_content() editor and make sure it's not sortable, foldable, etc
	 */
    public function edit_form_after_title()
    {
        $this->show();
    }

	/**
	 * Save fields in fake meta box
	 * 
	 * Fires once a post has been saved.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an existing post being updated.
	 */
	public function save($post_id, $post, $update)
	{
		if(
			(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ||	// prevent the data from being auto-saved
			(!current_user_can('edit_post', $post_id)) || 		// check user permissions
			((!isset($_POST[$this->nonce_name]))) || 			// verify nonce (same with below)
			(!wp_verify_nonce($_POST[$this->nonce_name], basename(__FILE__)))
		)
		{
			return;
		}

		$this->block->save();

		$dsb = DSB_Seo_Builder::get_instance();
		$dsb->dsb_store_search_word_and_location_for_slugs($post_id);

		// Workaround to flush the rewrite rules:
		update_option( 'dsb-flush-rewrite-rules', 1 );
	}

	/**
	 * Sets DSB_Meta_Block
	 * 
	 * @param DSB_Meta_Block $block	DSB_Meta_Block object
	 */
	public function set_block($block)
	{
		$this->block = $block;
	}

	/**
	 * Shows fake meta box with custom DSB_Meta_Field fields
	 */
	public function show()
	{
		// By faking the metabox design with class=postbox, we avoid all problems related to the metabox being sortable, foldable, etc
		printf ("\r\n<div id='%s' class='postbox'>\r\n",
				$this->meta_box_config['id']
			);
		
		printf ("<div class='postbox-header'><h2>%s</h2></div>\r\n",
				$this->meta_box_config['title']
			);

		echo "<div class='inside'>\r\n";
		
		wp_nonce_field(basename(__FILE__), $this->nonce_name);

		echo "\r\n<div class='dsb-meta-box'>\r\n";
		echo "<div class='dsb-row'>\r\n";

		$this->block->show();

		echo "</div>\r\n";
		echo "</div>\r\n";
		echo "</div>\r\n";
		echo "</div>\r\n";
	}
}
