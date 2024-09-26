<?php

if (is_admin())
{
    /**
     * Fires as an admin screen or script is being initialized.
     */
    add_action('admin_init', 'dsb_init_dsb_meta_boxes');
}

/**
 * Creates Config Meta box with base slug, search terms and locations
 */
function dsb_init_dsb_meta_boxes()
{
    $post_id    = dsb_get_valid_post_id();

    $load_dsb   = false;
    if ((int)$post_id > 0 && get_post_type((int)$post_id) === 'dsb_seo_page')
    {
        $load_dsb   = true;
    }
    else if (!$post_id)
    {
        global $pagenow;
        if (($pagenow === 'edit.php' || $pagenow === 'post-new.php') && isset($_GET['post_type']) && $_GET['post_type'] === 'dsb_seo_page')
        {
            $load_dsb   = true;
        }
    }

    // Only load on Post Edit screen of CPT = dsb_seo_page and CPT overview
    if ($load_dsb)
    {
        new DSB_Config();
    }
}
