<?php

class DSB_Settings {
	
	private $tabs	= array();
	private $blocks = array();
	private $nonce_name;
	private $dsb_settings_page_id = 'dsb_seo_page_page_dsb-settings';
	public function __construct(){
		$meta_box_config = array(
			'id'	=> 'dsb-meta-box-settings',
			'title'	=> __('SEO Builder settings', 'dsb_seo_builder'),
			'context'	=> 'normal',
			'priority'	=> 'core',
			'screen'	=> 'dsb_seo_page_page_settings'
		);
		
		$this->nonce_name 		= $meta_box_config['id'] . '_nonce';

		add_action('admin_menu', array($this, 'dsb_admin_menu'));

		if (dsb_is_settings_page())
		{
			add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
			add_action('admin_enqueue_scripts', array($this, 'dsb_options_page_enqueue_scripts'));
		}
	}

	public function dsb_admin_menu(){

		$settings_page = add_submenu_page(
			'edit.php?post_type=dsb_seo_page',
			__('Settings', 'dsb_seo_builder'),
			__('Settings', 'dsb_seo_builder'),
			'edit_posts',
			'dsb-settings',
			array($this, 'dsb_settings_page'),
			null
		);

		if ($settings_page)
		{
			$dsb = DSB_Seo_Builder::get_instance();

			$search_term_single = new DSB_Meta_Input_Field(
				array(
					'attr'          => array(
						'type'          => 'text',
						'id'    		=> 'search-term-placeholder',
						'placeholder'	=> $dsb->search_term_single_placeholder,
					),
					'label' 		=> __('Search term placeholder', 'dsb_seo_builder'),
					'wrapper_class' => array('dsb-col', 'dsb-small-12 dsb-medium-3'),
					'default'		=> $dsb->search_term_single_placeholder,
					'is_option'		=> true,
					'desc'			=> ''
				)
			);

			$search_term_plural = new DSB_Meta_Input_Field(
				array(
					'attr'          => array(
						'type'          => 'text',
						'id'    		=> 'search-terms-placeholder',
						'placeholder'	=> $dsb->default_search_term_plural_placeholder,
					),
					'label' 		=> __('Search terms (plural) placeholder', 'dsb_seo_builder'),
					'wrapper_class' => array('dsb-col', 'dsb-small-12 dsb-medium-3'),
					'default'		=> $dsb->default_search_term_plural_placeholder,
					'is_option'		=> true,
					'desc'			=> ''
				)
			);

            $enable_search_term_case_sensitivity = new DSB_Meta_Radiobutton_Group_Field(
				array(
					'attr'          => array(
						'type'      => 'radio',
						'id'        => "enable_search_term_case_sensitivity",
					),
					'label'         => __('Enable case sensitive placeholders', 'dsb_seo_builder'),
					'wrapper_class' => array('dsb-col', 'dsb-small-12 dsb-medium-6', 'dsb-styled-radios'),
					'radios'       => array(
						0 => __('No', 'dsb_seo_builder'),
						1 => __('Yes', 'dsb_seo_builder'),
					),
					'default'       => 0,
					'is_option'     => true,
                    'desc'			=> sprintf(
                        __("If enabled you can use %s and %s for the single placeholder.<br>If enabled you can use %s and %s for the plural placeholder.", 'dsb_seo_builder'),
                        strtolower($dsb->search_term_single_placeholder),
                        $dsb->dsb_get_ucfirst_placeholder($dsb->search_term_single_placeholder),
                        strtolower($dsb->search_term_plural_placeholder),
                        $dsb->dsb_get_ucfirst_placeholder($dsb->search_term_plural_placeholder)
                    )
				)
			);

			$location_single = new DSB_Meta_Input_Field(
				array(
					'attr'          => array(
						'type'          => 'text',
						'id'    		=> 'location-placeholder',
						'placeholder'	=> $dsb->default_location_single_placeholder,
					),
					'label' 		=> __('Location placeholder', 'dsb_seo_builder'),
					'wrapper_class' => array('dsb-col', 'dsb-small-12 dsb-medium-3'),
					'default'		=> $dsb->default_location_single_placeholder,
					'is_option'		=> true,
					'desc'			=> ''
				)
			);

			$location_plural = new DSB_Meta_Input_Field(
				array(
					'attr'          => array(
						'type'          => 'text',
						'id'    		=> 'locations-placeholder',
						'placeholder'	=> $dsb->default_location_plural_placeholder,
					),
					'label' 		=> __('Locations (plural) placeholder', 'dsb_seo_builder'),
					'wrapper_class' => array('dsb-col', 'dsb-small-12 dsb-medium-3'),
					'default'		=> $dsb->default_location_plural_placeholder,
					'is_option'		=> true,
					'desc'			=> ''
				)
			);

            $enable_location_case_sensitivity = new DSB_Meta_Radiobutton_Group_Field(
				array(
					'attr'          => array(
						'type'      => 'radio',
						'id'        => "enable_location_case_sensitivity",
					),
					'label'         => __('Enable case sensitive placeholders', 'dsb_seo_builder'),
					'wrapper_class' => array('dsb-col', 'dsb-small-12 dsb-medium-6', 'dsb-styled-radios'),
					'radios'       => array(
						0 => __('No', 'dsb_seo_builder'),
						1 => __('Yes', 'dsb_seo_builder'),
					),
					'default'       => 0,
					'is_option'     => true,
                    'desc'			=> sprintf(
                        __("If enabled you can use %s and %s for the single placeholder.<br>If enabled you can use %s and %s for the plural placeholder.", 'dsb_seo_builder'),
                        strtolower($dsb->location_single_placeholder),
                        $dsb->dsb_get_ucfirst_placeholder($dsb->location_single_placeholder),
                        strtolower($dsb->location_plural_placeholder),
                        $dsb->dsb_get_ucfirst_placeholder($dsb->location_plural_placeholder)
                    )
				)
			);

			$placeholder_warning_html_field = new DSB_Meta_HTML_Field(
				array(
					'attr'          => array(
						'type'      => 'html',
						'id'    	=> 'placeholders-warning',
					),
					'label'         => __('Placeholders', 'dsb_seo_builder'),
					'wrapper_class' => array('dsb-small-12'),
					'is_option'     => true
				)
			);

			$enable_adjacent_seo_pages_links = new DSB_Meta_Radiobutton_Group_Field(
				array(
					'attr'          => array(
						'type'      => 'radio',
						'id'        => "enable_adjacent_seo_pages_links",
					),
					'label'         => __('Enable adjacent SEO pages links', 'dsb_seo_builder'),
					'wrapper_class' => array('dsb-col', 'dsb-small-12', 'dsb-styled-radios'),
					'radios'       => array(
						0 => __('No', 'dsb_seo_builder'),
						1 => __('Yes', 'dsb_seo_builder'),
					),
					'default'       => 1,
					'is_option'     => true
				)
			);

			$include_styling = new DSB_Meta_Radiobutton_Group_Field(
				array(
					'attr'          => array(
						'type'      => 'radio',
						'id'        => "include_front_end_styling",
					),
					'label'         => __('Include styling', 'dsb_seo_builder'),
					'wrapper_class' => array('dsb-col', 'dsb-small-12', 'dsb-styled-radios'),
					'radios'       => array(
						0 => __('No', 'dsb_seo_builder'),
						1 => __('Yes', 'dsb_seo_builder'),
					),
					'default'       => 1,
					'is_option'     => true
				)
			);

            $enable_canonical_tag = new DSB_Meta_Radiobutton_Group_Field(
				array(
					'attr'          => array(
						'type'      => 'radio',
						'id'        => "enable_canonical_tag",
					),
					'label'         => __('Enable canonical tag', 'dsb_seo_builder'),
					'wrapper_class' => array('dsb-col', 'dsb-small-12', 'dsb-styled-radios'),
					'radios'       => array(
						0 => __('No', 'dsb_seo_builder'),
						1 => __('Yes', 'dsb_seo_builder'),
					),
					'default'       => 0,
					'is_option'     => true
				)
			);

            $enable_spintax = new DSB_Meta_Radiobutton_Group_Field(
				array(
					'attr'          => array(
						'type'      => 'radio',
						'id'        => "enable_spintax",
					),
					'label'         => __('Enable spintax', 'dsb_seo_builder'),
					'wrapper_class' => array('dsb-col', 'dsb-small-12', 'dsb-styled-radios'),
					'radios'       => array(
						0 => __('No', 'dsb_seo_builder'),
						1 => __('Yes', 'dsb_seo_builder'),
					),
					'default'       => 0,
					'is_option'     => true
				)
			);

            $randomize_spintax = new DSB_Meta_Radiobutton_Group_Field(
				array(
					'attr'          => array(
						'type'      => 'radio',
						'id'        => "randomize_spintax",
					),
					'label'         => __('Spintax rotation', 'dsb_seo_builder'),
					'wrapper_class' => array('dsb-col', 'dsb-small-12', 'dsb-styled-radios'),
					'radios'       => array(
						0 => __('Sequential', 'dsb_seo_builder'),
						1 => __('Random', 'dsb_seo_builder'),
					),
					'default'       => 0,
					'is_option'     => true,
                    'desc'          => __("<strong>Random: </strong> Generate a random combination of the text each time. Adds variability and freshness to the content, as the combination will be different on each page load.<br><strong>Sequential:</strong> Generate a specific combination based on which SEO Page is shown. Ensures consistency where each page has a unique and fixed version of the text.", 'dsb_seo_builder')
				)
			);


			$html_field = new DSB_Meta_HTML_Field(
				array(
					'attr'          => array(
						'type'      => 'html',
						'id'    	=> 'sitemap',
					),
					'label'         => __('Sitemap', 'dsb_seo_builder'),
					'wrapper_class' => array('dsb-small-12'),
					'is_option'     => true
				)
			);

			$settings_general = new DSB_Meta_Block();

			$settings_general->add_field($placeholder_warning_html_field);
			
			$settings_general->add_field($search_term_single);
			$settings_general->add_field($search_term_plural);
            $settings_general->add_field($enable_search_term_case_sensitivity);

			$settings_general->add_field($location_single);
			$settings_general->add_field($location_plural);
            $settings_general->add_field($enable_location_case_sensitivity);

			$settings_general->add_field($enable_adjacent_seo_pages_links);
			$settings_general->add_field($include_styling);
            $settings_general->add_field($enable_canonical_tag);

            $spintax_block = new DSB_Meta_Block();
            $spintax_block->add_field($enable_spintax);
            $spintax_block->add_field($randomize_spintax);

			$sitemap_block = new DSB_Meta_Block();
			$sitemap_block->add_field($html_field);

			$this->add_block("dsb-settings-general", __('Settings', 'dsb_seo_builder'), $settings_general);
            $this->add_block("dsb-settings-spintax", __('Spintax', 'dsb_seo_builder'), $spintax_block);
			$this->add_block("dsb-settings-sitemap", __('Sitemap', 'dsb_seo_builder'), $sitemap_block);
		}
	}

	public function dsb_options_page_enqueue_scripts($hook_suffix){
		$page_hook_id = $this->dsb_settings_page_id;

		if ($hook_suffix == $page_hook_id)
		{
			wp_enqueue_script( 'common' );
			wp_enqueue_script( 'wp-lists' );
			wp_enqueue_script( 'postbox' );
		}
	}

	function dsb_settings_page(){
		$hook_suffix = $this->dsb_settings_page_id;

		do_action('add_meta_boxes', $hook_suffix, false );
	?>
		<div class="wrap">
			<h2><?php _e('SEO Builder Settings', 'dsb_seo_builder'); ?></h2>

			<?php settings_errors(); ?>

			<div class="dsb-settings-meta-box-wrap">

				<form id="dsb-form" method="post" action="options.php">

					<?php settings_fields( 'dsb' );  ?>
					<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
					<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>

					<div id="poststuff">

						<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">

							<div id="postbox-container-1" class="postbox-container">
								<?php do_meta_boxes( $hook_suffix, 'side', null ); ?>
							</div>

							<div id="postbox-container-2" class="postbox-container">
								<?php do_meta_boxes( $hook_suffix, 'normal', null ); ?>
								<?php do_meta_boxes( $hook_suffix, 'advanced', null ); ?>
							</div>

						</div>
						<br class="clear">
					</div>
				</form>
			</div>
		</div>
	<?php
	}

	public function add_meta_boxes(){
		$page_hook_id = $this->dsb_settings_page_id;

		add_meta_box(
			'submitdiv',               
			__('Save options', 'dsb_seo_builder'),
			array($this, 'dsb_submit_meta_box'),
			$page_hook_id,
			'side',
			'high'
		);

		add_meta_box(
			'dsb-settings',
			__('SEO Builder settings', 'dsb_seo_builder'),
			array($this, 'show'),
			$page_hook_id,
			'normal',
			'default'
		);
	}

	public function dsb_submit_meta_box(){
	?>
	<div id="submitpost" class="submitbox">
		<div id="major-publishing-actions">
			<div id="publishing-action">
				<span class="spinner"></span>
				<?php submit_button( esc_attr( 'Save' ), 'primary', 'submit', false );?>
			</div>
			<div class="clear"></div>
		</div>
	</div>

	<?php
	}

	public function add_block($tab_id, $tab_title, $block){
		if (apply_filters('dsb-add-block', true, $tab_id, $tab_title, $block))
		{
			$this->tabs[]	= array($tab_id, $tab_title);
			$this->blocks[]	= $block;
		}
	}

	public function show(){
		wp_nonce_field(basename(__FILE__), $this->nonce_name);

		echo "\r\n<div class='dsb-meta-box'>\r\n";
		echo "<div id='dsb-tabs' class='dsb-tabs ui-helper-clearfix'>\r\n";

		echo "<ul>\r\n";

		foreach($this->tabs as $tab)
		{
			$tab_id		= $tab[0];
			$tab_title	= $tab[1];
			echo "<li><a href='#dsb-tabs-{$tab_id}'>{$tab_title}</a></li>\r\n";
		}

		echo "</ul>\r\n";

		$index = 0;
		foreach ($this->blocks as $block)
		{
			$tab 	= $this->tabs[$index];
			$tab_id	= $tab[0];

			echo "<div id='dsb-tabs-{$tab_id}' class='dsb-meta-box-panel'>\r\n";
			echo "<div class='dsb-row'>\r\n";

			$block->show();

			echo "</div >\r\n";
			echo "</div >\r\n";

			$index++;
		}

		echo "</div>\r\n";
		echo "</div>\r\n";
	}
}

add_action('init', 'init_dsb_settings');
function init_dsb_settings(){
	if (is_admin())
	{
		new DSB_Settings();
	}
}
