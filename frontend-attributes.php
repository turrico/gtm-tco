<?php
/**
 * Add Data Attributes to Add-to-Cart Buttons
 * 
 * Injects product metadata (producer, category, etc.) into the Add to Cart button HTML.
 * This allows the GTM JavaScript to read these attributes when a user clicks the button.
 */

add_filter('woocommerce_loop_add_to_cart_link', 'gtm_customize_add_to_cart_attributes', 10, 2);

function gtm_customize_add_to_cart_attributes($html, $product) {
    $product_name = $product->get_name();
    $product_price = $product->get_price();
    
    $producer_name = 'Unknown';
    /*
    if (function_exists('get_associated_productor_id')) {
        $producer_id = get_associated_productor_id($product->get_id());
        $producer_name = get_the_title($producer_id);
    }
    */

    // Get product category
    $categories = get_the_terms($product->get_id(), 'product_cat');
    $product_category = (!empty($categories)) ? $categories[0]->name : "Uncategorized";

    // Prepare attributes string
    $attributes = sprintf(
        ' data-producer_name="%s" data-product_name="%s" data-product_price="%s" data-product_category="%s" ',
        esc_attr($producer_name),
        esc_attr($product_name),
        esc_attr($product_price),
        esc_attr($product_category)
    );

    // Inject attributes into anchor tag
    $html = str_replace('<a ', '<a ' . $attributes, $html);

    // Append custom class
    $html = str_replace('class="', 'class="btn--secondary ', $html);

    return $html;
}
