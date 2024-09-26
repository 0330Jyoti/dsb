<?php

/**
 * Holds DSB_Meta_Field fields to store HTML documentation how to use the plugin
 */
class DSB_Documentation
{
	/**
	 * List of tabs labels
	 * 
	 * @var array 
	 */
	private $tabs	= array();

	/**
	 * List of tab panels with DSB_Meta_Field fields
	 * 
	 * @var array 
	 */
	private $blocks = array();

	/**
	 * Nonce value that was used for verification, usually via a form field.
	 * 
	 * @var string
	 */
	private $nonce_name;

	/**
	 * Metabox ID
	 * 
	 * @var string
	 */
	private $dsb_documentation_page_id = 'dsb_seo_page_page_dsb_documentation';

	/**
	 * Creates an instance of DSB_Documentation
	 */
	public function __construct()
	{
		$meta_box_config = array(
			'id'	=> 'dsb-meta-box-documentation',
			'title'	=> __('SEO Builder documentation', 'dsb_seo_builder'),
			'context'	=> 'normal',
			'priority'	=> 'core',
			'screen'	=> 'dsb_seo_page_page_documentation'
		);
		
		$this->nonce_name 		= $meta_box_config['id'] . '_nonce';

		add_action('admin_menu', array($this, 'dsb_admin_menu'));

		if (dsb_is_documentation_page())
		{
			add_action('add_meta_boxes', array($this, 'add_meta_boxes'));

			// Load the JavaScript needed for the documentation screen
			add_action('admin_enqueue_scripts', array($this, 'dsb_options_page_enqueue_scripts'));
		}
	}

	/**
	 * Adds new submenu page with tabs and fields to hold documentation
	 */
	public function dsb_admin_menu()
	{
		// Add documentation menu page
		$documentation_page = add_submenu_page(
			'edit.php?post_type=dsb_seo_page',
			__('Documentation', 'dsb_seo_builder'),
			__('Documentation', 'dsb_seo_builder'),
			'edit_posts',
			'dsb-documentation',
			array($this, 'dsb_documentation_page'),
			null
		);

		if ($documentation_page)
		{
			$dsb = DSB_Seo_Builder::get_instance();

			$html_field = new DSB_Meta_HTML_Field(
				array(
					'attr'          => array(
						'type'      => 'html',
						'id'    	=> 'documentation',
					),
					'wrapper_class' => array('dsb-small-12'),
					'is_option'     => true
				)
			);

			$documentation_block = new DSB_Meta_Block();
			$documentation_block->add_field($html_field);
			
            $this->add_block("dsb-documentation-documentation", __('Documentation', 'dsb_seo_builder'), $documentation_block);
		}
	}

	/**
	 * Loads script needed for the Metabox
	 * 
	 * @param string $hook_suffix The current admin page.
	 */
	public function dsb_options_page_enqueue_scripts($hook_suffix)
	{
		$page_hook_id = $this->dsb_documentation_page_id;

		if ($hook_suffix == $page_hook_id)
		{
			wp_enqueue_script( 'common' );
			wp_enqueue_script( 'wp-lists' );
			wp_enqueue_script( 'postbox' );
		}
	}

	/**
	 * Documentation Page Callback
	 */
	function dsb_documentation_page()
	{
		$hook_suffix = $this->dsb_documentation_page_id;
		
		// enable add_meta_boxes function in this page.
		do_action('add_meta_boxes', $hook_suffix, false );
	?>
		<div class="wrap">
			<h2><?php _e('SEO Builder Documentation', 'dsb_seo_builder'); ?></h2>

			<?php settings_errors(); ?>

			<div class="dsb-documentation-meta-box-wrap">

				<form id="dsb-form" method="post" action="options.php">

					<?php settings_fields( 'dsb' ); // options group  ?>
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

	/**
	 * Adds meta boxes
	 */
	public function add_meta_boxes()
	{
		$page_hook_id = $this->dsb_documentation_page_id;

		// Save Options page meta box on the right side:
		add_meta_box(
			'submitdiv',               
			__('Save options', 'dsb_seo_builder'),
			array($this, 'dsb_submit_meta_box'),
			$page_hook_id,
			'side',
			'high'
		);

		add_meta_box(
			'dsb-documentation',
			__('Documentation', 'dsb_seo_builder'),
			array($this, 'show'),
			$page_hook_id,
			'normal',
			'default'
		);
	}

	/**
	 * Submit meta box callback
	 */
	public function dsb_submit_meta_box()
	{
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

	/**
	 * Adds DSB_Meta_Block
	 * 
	 * @param int				$tab_id		The tab ID
	 * @param string			$tab_title	The tab title
	 * @param DSB_Meta_Block 	$block		The tab pabel content with a DSB_Meta_Block object
	 */
	public function add_block($tab_id, $tab_title, $block)
	{
		if (apply_filters('dsb-add-block', true, $tab_id, $tab_title, $block))
		{
			$this->blocks[]	= $block;
		}
	}

	/**
	 * Shows fake meta box with tabs and tab panels with custom DSB_Meta_Field fields
	 */
	public function show()
	{
		wp_nonce_field(basename(__FILE__), $this->nonce_name);

		echo "\r\n<div id='dsb-documentation' class='ui-helper-clearfix'>\r\n";

		foreach ($this->blocks as $block)
		{
			$block->show();
		}

		echo "</div>\r\n";
	}
}

add_action('init', 'init_dsb_documentation');
function init_dsb_documentation()
{
	if (is_admin())
	{
		new DSB_Documentation();
	}
}
