<?php

class DSB_Meta_Block {

	private $fields 		= array();

	public function show(){
        if (is_array($this->fields) && count($this->fields) > 0)
		{
			foreach ($this->fields as $field)
			{
				$field->show();
			}
		}
	}

	public function save(){
		if (is_array($this->fields) && count($this->fields) > 0)
		{
			foreach ($this->fields as $field)
			{
				$field->save();
			}
		}
	}

	public function add_field($field){
        $this->fields[$field->get_id()] = $field;
	}
}
