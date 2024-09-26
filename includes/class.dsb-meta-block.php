<?php

/**
 * Holds DSB_Meta_Field fields to show and save each field
 */
class DSB_Meta_Block
{
    /**
     * List of DSB_Meta_Field fields
     * 
     * @var array $fields
     */
	private $fields 		= array();

    /**
     * Shows all DSB_Meta_Field fields added to this block
     */
	public function show()
	{
        if (is_array($this->fields) && count($this->fields) > 0)
		{
			foreach ($this->fields as $field)
			{
				$field->show();
			}
		}
	}

    /**
     * Saves each DSB_Meta_Field field when the dsb_seo_page or dsb_seo_page settings are saved
     */
	public function save()
	{
		if (is_array($this->fields) && count($this->fields) > 0)
		{
			foreach ($this->fields as $field)
			{
				$field->save();
			}
		}
	}

    /**
     * Adds DSB_Meta_Field to the list
     * 
     * @param DSB_Meta_Field $field The field to add
     */
	public function add_field($field)
	{
        $this->fields[$field->get_id()] = $field;
	}
}
