<?php

if (!is_admin())
{
    class DSB_Spintax {
        private $text               = '';
        private $choices            = array();
        private $total_combinations = -1;
        private static $instance    = null;
        private function __construct($text){
            $this->text                 = $text;
            $this->choices              = $this->extract_choices($text);
            $this->total_combinations   = $this->calculatetotal_combinations($this->choices);
        }

        public static function get_instance($text){
            if (self::$instance === null)
            {
                self::$instance = new self($text);
            }
            else
            {
                self::$instance->set_spintax($text);
            }

            return self::$instance;
        }

        private function set_spintax($text){
            $this->text                 = $text;

            if (!empty($text))
            {
                $this->choices              = $this->extract_choices($text);
                $this->total_combinations   = $this->calculatetotal_combinations($this->choices);
            }
        }

        private function extract_choices($text){
            $choices = array();

            if (!empty($text))
            {
                preg_match_all('/\{(((?>[^\{\}]+)|(?R))*)\}/s', $text, $matches);

                $choices = array_map(function($block)
                {
                    if (preg_match('/\{(.+?)\}/s', $block, $choice_match))
                    {
                        return explode('|', preg_replace('/\s+/', ' ', trim($choice_match[1])));
                    }
                    else
                    {
                        return [];
                    }
                }, $matches[0]);
            }

            $custom_trim = function($value)
            {
                return trim($value, " \t\n\r\0\x0B\xC2\xA0");
            };

            $trimmed_choices = array_map(function($choice_group) use ($custom_trim) {
                return array_map($custom_trim, $choice_group);
            }, $choices);

            return $trimmed_choices;
        }

        private function calculatetotal_combinations($choices){
            $num_combinations = 1;
            if (!empty($choices))
            {
                $num_combinations = array_product(array_map('count', $choices));
            }
            return $num_combinations;
        }

        public function get_combination($index_offset = 0){
            $dsb            = DSB_Seo_Builder::get_instance();
            $index          = $dsb->nsg_get_lookup_table_slug_index() + $index_offset;
            $combinations   = $this->total_combinations;

            if ($combinations === 0)
            {
                $combinations = 1;  
            }

            $index  = $index % $combinations; 
            
            $result = $this->text;

            if (!empty($this->choices))
            {
                foreach ($this->choices as $block_choices)
                {
                    if (get_option('dsb-randomize_spintax', false))
                    {
                        $choice_index   = array_rand($block_choices);
                    }
                    else
                    {
                        $block_count    = count($block_choices);
                        if ($block_count === 0)
                        {
                            $block_count = 1;  
                        }
                        
                        $choice_index   = $index % $block_count;
                        $index          = intdiv($index, $block_count);
                    }
                    
                    if (isset($block_choices[$choice_index]))
                    {
                        $result           = preg_replace('/\{(((?>[^\{\}]+)|(?R))*)\}/', $block_choices[$choice_index], $result, 1);
                    }
                }
            }
            return $result;
        }

        public function get_total_combinations(){
            return $this->total_combinations;
        }
    }
}
