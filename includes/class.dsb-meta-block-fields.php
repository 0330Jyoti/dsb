<?php

/**
 * Shows and saves different types of HTML form fields
 */
class DSB_Meta_Field
{
	/**
	 * @var string	$prefix	Prefix all field names to avoid naming conflicts in post_meta table
	 */
    protected $prefix   = 'dsb-';

	/**
	 * Field vars set by the user
	 * 
	 * @var array $args
	 */
	public $args        = array();

	/**
	 * Constructor.
	 *
	 * Sets up a new field
	 * 
	 * @param array $args Array of arguments to build the field
	 */
	public function __construct($args)
	{
        $prefix = $this->get_prefix();
        
        // Prefix all field names to avoid conflicts
        $args['attr']['id']         = $prefix . $args['attr']['id'];
        
        // Add classes
        $args['attr']['class'][]    = $prefix . 'field';
        $args['attr']['class'][]    = $prefix . 'field-' . $args['attr']['type'];
        
		$this->args = $args;
        
		if (isset($args['is_option']) && $args['is_option'] === true)
		{
			// Register our setting with Wordpress so Wordpress can save them automatically
			register_setting(
				'dsb',							// Option Group
				$args['attr']['id'],			// Option Name
				array($this, 'sanitize_value')	// Sanitize Callback
			);
		}

		// https://wordpress.stackexchange.com/questions/114719/editor-role-cannot-save-custom-theme-options
		// Give user role = Editor the permission to save fields added to the Settings page
		add_filter('option_page_capability_dsb', array($this, 'dsb_map_options_capability'));
	}

	/**
	 * Give user role = Editor the permission to save fields added to the Settings page
	 */
	public function dsb_map_options_capability($cap)
	{
		return 'edit_pages';
	}

	/**
     * Shows the html field
     */
    public function show()
    {
        $this->before_field();
        
        $this->show_field();
        
        $this->after_field();
    }
    
	/**
     * Saves the html field when submitted
     */
    public function save()
	{
        global $post;
        
        $value      = null;
        $field_id   = $this->get_id();
		
        if(isset($_POST[$field_id]) && !dsb_is_empty($_POST[$field_id]))
		{
            $value = $this->sanitize_value($_POST[$field_id]);
            
			update_post_meta($post->ID, $field_id, $value);
		}
		else
		{
			delete_post_meta($post->ID, $field_id);
		}

		/**
		 * Fires once the field has been saved
		 * 
		 * @param string  			$value  	Field value
		 * @param int				$post->ID  	Post ID
		 * @param DSB_Meta_Field 	$this 		The current DSB_Meta_Field object
		 */
        do_action('dsb-save-field', $value, $post->ID, $this);

		/**
		 * Fires once the field has been saved
		 * 
		 * The dynamic portion of the hook name, `$field_id`, refers to the field id
		 * 
		 * @param string  			$value  	Field value
		 * @param int				$post->ID  	Post ID
		 * @param DSB_Meta_Field 	$this 		The current DSB_Meta_Field object
		 */
		do_action('dsb-save-field-' . $field_id, $value, $post->ID, $this);
	}

	/**
	 * Sanitizes a string from user input or from the database.
	 *
	 * @param string $value String to sanitize.
	 * 
	 * @return string Sanitized string.
	 */
	public function sanitize_value($value)
	{
		$value = apply_filters('dsb-meta-block-field-sanitize-value', $value, $this);

		return sanitize_text_field($value);
	}

	/**
	 * Returns the field value from the database
	 * 
	 * @return string The field value
	 */
	public function get_value()
	{
        global $post;
        $post_id = false;
                
		if (isset($this->args['is_option']) && $this->args['is_option'] === true)
		{
			$value = get_option($this->get_id(), '');
		}
		else
		{
            $post_id    = $post->ID;
			$value      = get_post_meta($post_id, $this->get_id(), true);
		}

		if (isset($this->args['default']) && dsb_is_empty($value) && !metadata_exists('post', $post_id, $this->get_id()))
		{
			$value = $this->args['default'];
		}

		$field_id 	= $this->get_id();

		/**
		 * Fires once the field value has been retrieved from the database
		 * 
		 * @param string  			$value  	Field value
		 * @param int				$post->ID  	Post ID
		 * @param DSB_Meta_Field 	$this 		The current DSB_Meta_Field object
		 */
		$value		= apply_filters('dsb-get-value', $value, $post_id, $this);

		/**
		 * Fires once the field value has been retrieved from the database
		 * 
		 * The dynamic portion of the hook name, `$field_id`, refers to the field id
		 * 
		 * @param string  			$value  	Field value
		 * @param int				$post->ID  	Post ID
		 * @param DSB_Meta_Field 	$this 		The current DSB_Meta_Field object
		 */
		$value		= apply_filters('dsb-get-value-' . $field_id, $value, $post_id, $this);

        return $value;
	}

	/**
	 * Returns the field id
	 * 
	 * @return string The field id
	 */
	public function get_id()
	{
		return $this->args['attr']['id'];
	}
    
	/**
	 * Returns the field prefix
	 * 
	 * @return string The field prefix
	 */
    public function get_prefix()
    {
		/**
		 * Allows for other prefix
		 * 
		 * @param string  			$this->prefix The field prefix
		 */
		$prefix = apply_filters('dsb-field-prefix', $this->prefix);

        return $prefix;
    }

	public function show_field() {}

	/**
	 * Echos some HTML that precedes a field (container, label, description, etc.)
	 * 
	 * @param string $label_postfix Optionally postfix to show after the label
	 */
	public function before_field($label_postfix = '')
	{
        $wrapper_class      = $this->args['wrapper_class'];
        $wrapper_class[]    = 'css-id-' . sanitize_title($this->get_id());
        $wrapper_class[]    = 'css-value-' . sanitize_title($this->get_value());

		$wrapper_class  = isset($wrapper_class) ? implode(" ", $wrapper_class) : "";
        $prefix         = $this->get_prefix();
        
		echo sprintf(
			"<div class='%s %s %s'>\r\n",
			esc_attr( $prefix . 'field-container' ),
			esc_attr( $prefix . 'field-container-' . $this->args['attr']['type'] ),
			esc_attr($wrapper_class)
		);

		echo "<div class='dsb-content'>\r\n";
        
		if(isset($this->args['label'])) {
			echo sprintf(
				'<label class="%s" for="%s">%s</label>',
				esc_attr( $prefix . 'label' ),
				esc_attr( $this->get_id() ),
				esc_html( $this->args['label'] . $label_postfix )
			);
		}

		do_action('dsb-before-field', $this);
	}

	/**
	* Echos HTML that comes after a field (container, description, etc).
	*/
	public function after_field()
	{
        if(isset($this->args['desc']))
		{
			$this->get_field_description($this->args['desc']);
		}
        
		echo "</div>\r\n";
		echo "</div>\r\n";
	}

	/**
	* Echos a paragraph element with some description text that serves as an assistant to the operator of the meta box.
	*
	* @param string $desc
	*/
	public function get_field_description($desc)
	{
        $prefix         = $this->get_prefix();

		$desc	= apply_filters('dsb-get-field-description', $desc, $this);
        $desc	= apply_filters('dsb-get-field-description-' . $this->get_id(), $desc, $this);

		echo sprintf(
			'<p class="%s">%s</p>',
			esc_attr( $prefix . 'description' ),
			$desc
		);
	}
    
	/**
	 * Returns field attributes to be printed in a html form field tag
	 * 
	 * @param bool $include_value Whether or not to include the field value in the result
	 * 
	 * @return array The field attributes
	 */
    public function get_attributes($include_value = true)
    {
        // Get all attributes
        $attr           = $this->args['attr'];
        
        // Add the field value as an attribute
        if ($include_value)
        {
            $attr['value']  = $this->get_value();
        }
        
        // Make sure we have a name attribute so the field can be submitted
        if (!isset($attr['name']))
        {
            $attr['name'] = $attr['id'];
        }
        
        // And convert the css classes to a string
        $attr['class']  = implode(" ", $attr['class']);

		$attr = apply_filters('dsb-field-get-attributes', $attr, $this);
		$attr = apply_filters('dsb-field-get-attributes-' . $this->get_id(), $attr, $this);
        
        // Create key = value pairs
        $attributes     = $this->array_to_key_value_pairs($attr);

        return $attributes;
    }
    
	/**
	 * Converts array key / value pairs to key='value' pairs
	 * 
	 * @param array $array Array to convert
	 * 
	 * @return array Converted key value pairs
	 */
    protected function array_to_key_value_pairs($array)
    {
        $result = array();
        
        // Create key = value pairs
        foreach ($array as $key => $value)
        {
            $result[] = sprintf(
                        "%s='%s'",
                        $key,
                        esc_attr($value)
                    );
        }
        
        return $result;
    }
}

/**
 * Shows and saves different types of HTML input form field
 * 
 * @see DSB_Meta_Field
 */
class DSB_Meta_Input_Field extends DSB_Meta_Field
{
	/**
	 * Shows the html input field tag
	 */
	public function show_field()
	{
        $attributes = $this->get_attributes(true);
        
        echo sprintf(
			'<input %s>',
            implode(" ", $attributes)
		);
	}
}

/**
 * Shows and saves HTML textarea form field
 * 
 * @see DSB_Meta_Field
 */
class DSB_Meta_Textarea_Field extends DSB_Meta_Field
{
	/**
	 * Shows the html textarea field tag
	 */
	public function show_field()
	{        
        $attributes = $this->get_attributes(false);
        $value      = $this->get_value();

		echo sprintf(
			'<textarea %s>%s</textarea>',
			implode(" ", $attributes),
			$value
		);
	}

	/**
	 * Sanitizes a multiline string from user input or from the database.
	 *
	 * @param string $value String to sanitize.
	 * 
	 * @return string Sanitized string.
	 */
	public function sanitize_value($value)
	{
		$value = apply_filters('dsb-meta-block-field-sanitize-value', $value, $this);

		return sanitize_textarea_field($value);
	}
}

/**
 * Shows an HTML field
 * 
 * @see DSB_Meta_Field
 */
class DSB_Meta_HTML_Field extends DSB_Meta_Field
{
	// nothing to save... so define it to make sure it is called and does nothing
	public function save() {}

	/**
	 * Shows the html
	 * 
	 * Shows nothing by itself unless 'desc' arg is set. Output can be altered by filters
	 */
	public function show()
	{
		/**
		 * Fires once the field is shown
		 * 
		 * HTML fields have no value stored in DB. Show content by applying a filter
		 * 
		 * @param string  			$html  		HTML to show
		 * @param DSB_Meta_Field 	$this 		The current DSB_Meta_Field object
		 */
		$html = apply_filters('dsb-show-field-html', "", $this);

		/**
		 * Fires once the field is shown
		 * 
		 * HTML fields have no value stored in DB. Show content by applying a filter
		 * 
		 * The dynamic portion of the hook name, `$field_id`, refers to the field id
		 * 
		 * @param string  			$html  		HTML to show
		 * @param DSB_Meta_Field 	$this 		The current DSB_Meta_Field object
		 */
        $html = apply_filters('dsb-show-field-html-' . $this->get_id(), $html, $this);

		if (!empty($html) || !empty($this->args['desc']))
		{
			parent::before_field();

			echo $html;

			parent::after_field();
		}
	}
}

/**
 * Shows and saves HTML radiobuttons form fields
 * 
 * @see DSB_Meta_Field
 */
class DSB_Meta_Radiobutton_Group_Field extends DSB_Meta_Field
{   
	/**
	 * Shows the html radio buttons tags
	 */
    public function show_field()
    {
        $field_id   = $this->get_id();
        
        // Find out if the checkbox is checked or not
        $value      = $this->get_value();
        
        $index = 0;
		echo "<div class='dsb-radios'>";
        
        foreach($this->args['radios'] as $radio_value => $label)
        {
            $radio_attr             = array();
            $radio_attr['type']     = $this->args['attr']['type'];
            $radio_attr['id']       = $field_id . "-" . sanitize_title($radio_value);
            $radio_attr['name']     = $field_id;
            $radio_attr['class']    = implode(" ", $this->args['attr']['class']);
            $radio_attr['value']    = $radio_value;
            
            // Create key = value pairs
            $attributes     = $this->array_to_key_value_pairs($radio_attr);
            
            echo sprintf(
            '
                <label class="%1$s" for="%2$s">
                    <input %3$s %4$s>
                    <span class="button">%5$s</span>
                </label>
            ',
                'dsb-radio dsb-radio-' . $index,
                $radio_attr['id'],
                implode(" ", $attributes),
                checked( $radio_value == $value, true, false ),
                esc_html($label)
            );
            
            $index++;
        }

		echo "</div>";
    }
}
