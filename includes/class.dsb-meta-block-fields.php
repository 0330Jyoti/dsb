<?php

class DSB_Meta_Field {

    protected $prefix   = 'dsb-';
	public $args        = array();
	public function __construct($args){
        $prefix = $this->get_prefix();
        $args['attr']['id']         = $prefix . $args['attr']['id'];
        $args['attr']['class'][]    = $prefix . 'field';
        $args['attr']['class'][]    = $prefix . 'field-' . $args['attr']['type'];
        
		$this->args = $args;
        
		if (isset($args['is_option']) && $args['is_option'] === true)
		{
			register_setting(
				'dsb',							
				$args['attr']['id'],			
				array($this, 'sanitize_value')	
			);
		}

		add_filter('option_page_capability_dsb', array($this, 'dsb_map_options_capability'));
	}

	public function dsb_map_options_capability($cap){
		return 'edit_pages';
	}

    public function show(){
        $this->before_field();
        
        $this->show_field();
        
        $this->after_field();
    }

    public function save(){
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

        do_action('dsb-save-field', $value, $post->ID, $this);

		do_action('dsb-save-field-' . $field_id, $value, $post->ID, $this);
	}

	public function sanitize_value($value){
		$value = apply_filters('dsb-meta-block-field-sanitize-value', $value, $this);

		return sanitize_text_field($value);
	}

	public function get_value(){
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
		$value		= apply_filters('dsb-get-value', $value, $post_id, $this);
		$value		= apply_filters('dsb-get-value-' . $field_id, $value, $post_id, $this);

        return $value;
	}

	public function get_id(){
		
		return $this->args['attr']['id'];
	}

    public function get_prefix(){

		$prefix = apply_filters('dsb-field-prefix', $this->prefix);

        return $prefix;
    }

	public function show_field() {}
	public function before_field($label_postfix = ''){
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

	public function after_field(){
        if(isset($this->args['desc']))
		{
			$this->get_field_description($this->args['desc']);
		}
        
		echo "</div>\r\n";
		echo "</div>\r\n";
	}

	public function get_field_description($desc){
        $prefix         = $this->get_prefix();

		$desc	= apply_filters('dsb-get-field-description', $desc, $this);
        $desc	= apply_filters('dsb-get-field-description-' . $this->get_id(), $desc, $this);

		echo sprintf(
			'<p class="%s">%s</p>',
			esc_attr( $prefix . 'description' ),
			$desc
		);
	}
    
    public function get_attributes($include_value = true){

        $attr           = $this->args['attr'];

        if ($include_value)
        {
            $attr['value']  = $this->get_value();
        }

        if (!isset($attr['name']))
        {
            $attr['name'] = $attr['id'];
        }

        $attr['class']  = implode(" ", $attr['class']);

		$attr = apply_filters('dsb-field-get-attributes', $attr, $this);
		$attr = apply_filters('dsb-field-get-attributes-' . $this->get_id(), $attr, $this);
        $attributes     = $this->array_to_key_value_pairs($attr);

        return $attributes;
    }
    
    protected function array_to_key_value_pairs($array){
        $result = array();

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

class DSB_Meta_Input_Field extends DSB_Meta_Field {

	public function show_field()
	{
        $attributes = $this->get_attributes(true);
        
        echo sprintf(
			'<input %s>',
            implode(" ", $attributes)
		);
	}
}

class DSB_Meta_Textarea_Field extends DSB_Meta_Field {

	public function show_field(){        
        $attributes = $this->get_attributes(false);
        $value      = $this->get_value();

		echo sprintf(
			'<textarea %s>%s</textarea>',
			implode(" ", $attributes),
			$value
		);
	}

	public function sanitize_value($value){
		$value = apply_filters('dsb-meta-block-field-sanitize-value', $value, $this);

		return sanitize_textarea_field($value);
	}
}

class DSB_Meta_HTML_Field extends DSB_Meta_Field {

	public function save() {}
	public function show(){
		$html = apply_filters('dsb-show-field-html', "", $this);
        $html = apply_filters('dsb-show-field-html-' . $this->get_id(), $html, $this);

		if (!empty($html) || !empty($this->args['desc']))
		{
			parent::before_field();

			echo $html;

			parent::after_field();
		}
	}
}

class DSB_Meta_Radiobutton_Group_Field extends DSB_Meta_Field {   

    public function show_field(){
        $field_id   = $this->get_id();
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
