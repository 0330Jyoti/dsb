(function($){
	'use strict';

	$(document).ready(function()
	{
        // Init DSB Meta Box tabs
        $( "#dsb-tabs" ).tabs();

        // Mark URL structure part when matching input field gets focus
        jQuery('#dsb-seo-page-base, #dsb-slug-placeholder').on('focus blur', function (e) {
            var for_label = $(this).attr('id');
            if(e.type === 'focus')
            {
                $('.dsb-url-structure label[for="' + for_label + '"').css('background-color', 'yellow');
            }
            else
            {
                $('.dsb-url-structure label[for="' + for_label + '"').css('background-color', '');
            }
        });

        // Live update the URL Structure when SEO Page Base and Slug placeholder are being updated to reflect new URL Structure
        jQuery('#dsb-seo-page-base, #dsb-slug-placeholder').on('input propertychange paste',function() {
            var for_label   = $(this).attr('id');
            var value       = $(this).val();
            if (value !== '' && for_label === 'dsb-seo-page-base')
            {
                value = "/" + value;
            }
            $('.dsb-url-structure label[for="' + for_label + '"').html(value);
        });

        // Add counter to the Search Term textarea to count number of lines
        jQuery(".dsb-search-terms .dsb-content").each(function() {
            var textarea_wrapper = jQuery(this);
            dsb_update_textarea_lines(textarea_wrapper, 'dsb-search-terms');
        });

        jQuery(".dsb-search-terms .dsb-content textarea").on('input propertychange paste', function() {
            var textarea_wrapper = jQuery(this).closest(".dsb-content");
            dsb_update_textarea_lines(textarea_wrapper, 'dsb-search-terms');
        });

        // Add counter to the Location textarea to count number of lines
		jQuery(".dsb-locations .dsb-content").each(function() {
            var textarea_wrapper = jQuery(this);
            dsb_update_textarea_lines(textarea_wrapper, 'dsb-locations');
        });

        jQuery(".dsb-locations .dsb-content textarea").on('input propertychange paste', function() {
            var textarea_wrapper = jQuery(this).closest(".dsb-content");
            dsb_update_textarea_lines(textarea_wrapper, 'dsb-locations');
        });

        // Update numb lines counter below textarea and mark if max lines has been exceeded
        function dsb_update_textarea_lines(textarea_wrapper, field_type)
        {
            var my_textarea     = textarea_wrapper.find("textarea");
            var text            = my_textarea.val();
            var lines           = text.split(/\r|\r\n|\n/);
            lines               = lines.filter(function(v){return v!==''}); // remove empty lines
            var count           = lines.length;
            var max_items       = field_type === 'dsb-search-terms' ? dsb.max_search_terms : dsb.max_locations;

            if (count > max_items)
            {
                textarea_wrapper.find('.num_lines').addClass('error');
            }
            else
            {
                textarea_wrapper.find('.num_lines').removeClass('error');
            }

            if (textarea_wrapper.find('.num_lines').length < 1)
            {
                $("<p class='num_lines'>" + dsb.lines + count + " (" + dsb.max + " " + max_items + ")</p>").insertAfter(textarea_wrapper.find('textarea'));
            }
            else
            {
                textarea_wrapper.find('.num_lines').html(dsb.lines + count + " (" + dsb.max + max_items + ")");
            }
        }

        // Add toggle on Settings options page
        if ($('.dsb-settings-meta-box-wrap').length > 0)
        {
            $('.if-js-closed').removeClass('if-js-closed').addClass('closed');
            postboxes.add_postbox_toggles( 'nw_seo_page_page_dsb-settings' );

            // display spinner
            $('#dsb-form').submit( function()
            {
                $('#publishing-action .spinner').css('visibility','initial');
            });
        }
    });

})(jQuery);
