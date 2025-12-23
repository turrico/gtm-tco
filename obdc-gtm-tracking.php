<?php
/**
 * Plugin Name: Turri.cr Google Tag Manager & Ecommerce Tracking
 * Plugin URI:  https://turri.cr
 * Description: Implements Google Tag Manager container, Data Layer structure, and Enhanced Ecommerce event tracking (AddToCart, Checkout, Purchase).
 * Version:     1.0.0
 * Author:      Orlando Bruno
 * Author URI:  https://orlandobruno.com
 * Text Domain: obdc-gtm-tracking
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// 1. Load Components
require_once plugin_dir_path(__FILE__) . 'ajax-cart-handler.php';
require_once plugin_dir_path(__FILE__) . 'ecommerce-events.php';
require_once plugin_dir_path(__FILE__) . 'frontend-attributes.php';

/**
 * ==================================================
 * GTM Container & Data Layer Setup
 * ==================================================
 */

// 2. Inject Data Layer & GTM via Inline Scripts (More reliable than wp_head)
add_action('wp_enqueue_scripts', 'obdc_gtm_enqueue_scripts', 1);

function obdc_gtm_enqueue_scripts() {
    // A. Enqueue Frontend JS
    wp_enqueue_script(
        'gtm-frontend-events',
        plugin_dir_url(__FILE__) . 'assets/js/gtm-events.js', 
        array('jquery'), 
        '1.0.0', 
        true
    );
    
    // Localize for AJAX
    wp_localize_script('gtm-frontend-events', 'MyAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php')
    ));

    // B. Construct Data Layer Object
    $current_user = wp_get_current_user();
    $userId = $current_user->ID;
    $is_archive = 0;
    $post_type = '';
    $post_type_category = '';
    
    // Get experiment cookie
    $experiment_case = isset($_COOKIE['experiment_tesis']) ? $_COOKIE['experiment_tesis'] : 'N/A';

    if(is_singular()) { 
        global $post;
        $post_type = get_post_type($post);
        $product_cats = get_the_terms($post->ID, 'product_cat');
        $post_type_category = (is_array($product_cats) && !empty($product_cats)) ? $product_cats[0]->name : 'No category';
    } elseif(is_archive()) {
        $term = get_queried_object();
        $is_archive = 1;
        $post_type_category = isset($term->name) ? $term->name : 'Archive';
    } elseif(is_404()) {
        $post_type = '404 Page';
    }

    // C. Inject Data Layer (Before GTM)
    $dataLayerScript = "
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
        'userId': '{$userId}',
        'isArchive': '{$is_archive}',
        'content_type': '{$post_type}',
        'content_group': '{$post_type_category}',
        'experiment_case': '{$experiment_case}'
    });";
    
    // Inject Data Layer at top of head (using jquery as anchor, but 'before' ensures it's early)
    wp_add_inline_script('jquery', $dataLayerScript, 'before');

    // D. Inject GTM Container (After Data Layer)
    $gtmScript = "(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-WM6NPSN');";

    wp_add_inline_script('jquery', $gtmScript, 'before');
}

// 3. Inject NoScript Body in Footer
add_action('wp_footer', function() {
    ?>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-WM6NPSN"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    <?php
});