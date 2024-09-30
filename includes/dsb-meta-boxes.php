<?php

if (is_admin())
{
    add_action('admin_init', 'dsb_init_dsb_meta_boxes');
}

function dsb_init_dsb_meta_boxes(){
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
    
    if ($load_dsb)
    {
        new DSB_Config();
    }
}
