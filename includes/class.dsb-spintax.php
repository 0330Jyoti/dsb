<?php

if (!is_admin())
{
    class DSB_Spintax
    {
        private $text               = '';
        private $choices            = array();
        private $total_combinations = -1;
        private static $instance    = null;

        // Private constructor to prevent direct instantiation
        private function __construct($text)
        {
            $this->text                 = $text;
            $this->choices              = $this->extract_choices($text);
            $this->total_combinations   = $this->calculatetotal_combinations($this->choices);
        }

        // Static method to get the singleton instance
        public static function get_instance($text)
        {
            if (self::$instance === null)
            {
                self::$instance = new self($text);
            }
            else
            {
                // Update the spintax if a new one is provided
                self::$instance->set_spintax($text);
            }

            return self::$instance;
        }

        // Method to update the spintax and recalculate choices and combinations
        private function set_spintax($text)
        {
            $this->text                 = $text;

            if (!empty($text))
            {
                $this->choices              = $this->extract_choices($text);
                $this->total_combinations   = $this->calculatetotal_combinations($this->choices);
            }
        }

        private function extract_choices($text)
        {
            $choices = array();

            if (!empty($text))
            {
                // Regex als handles new lines within the braces
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

            // Make sure { A | B | C } is not out put as ' A ' but as 'A'
            // Custom trim function to handle various whitespace characters. Make sure stuff like $nbps; etc is also trimmed
            $custom_trim = function($value)
            {
                return trim($value, " \t\n\r\0\x0B\xC2\xA0");
            };

            // Trim each choice after all choices are extracted
            $trimmed_choices = array_map(function($choice_group) use ($custom_trim) {
                return array_map($custom_trim, $choice_group);
            }, $choices);

            return $trimmed_choices;
        }

        private function calculatetotal_combinations($choices)
        {
            $num_combinations = 1;
            if (!empty($choices))
            {
                $num_combinations = array_product(array_map('count', $choices));
            }
            return $num_combinations;
        }

        public function get_combination($index_offset = 0)
        {
            $dsb            = DSB_Seo_Builder::get_instance();
            $index          = $dsb->nsg_get_lookup_table_slug_index() + $index_offset;
            $combinations   = $this->total_combinations;

            if ($combinations === 0)
            {
                $combinations = 1;  // Avoid division by zero
            }

            $index  = $index % $combinations; // Use modulo to wrap around
            
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
                            $block_count = 1;  // Avoid division by zero
                        }
                        
                        $choice_index   = $index % $block_count;
                        $index          = intdiv($index, $block_count);
                    }

                    // Only replace the spintax option in the spintax block if we can actually find it
                    if (isset($block_choices[$choice_index]))
                    {
                        $result           = preg_replace('/\{(((?>[^\{\}]+)|(?R))*)\}/', $block_choices[$choice_index], $result, 1);
                    }
                }
            }
            return $result;
        }

        public function get_total_combinations()
        {
            return $this->total_combinations;
        }
    }
}
