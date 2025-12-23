<?php
/**
 * AJAX Handler for Cart Content
 * 
 * Returns current cart items and total for GTM events.
 */

add_action("wp_ajax_get_cart_content", "get_cart_content");
add_action("wp_ajax_nopriv_get_cart_content", "get_cart_content");

function get_cart_content() {
    if (!function_exists('WC')) {
        wp_send_json_error(['message' => 'WooCommerce not found.']);
        return;
    }

    $cart_contents = WC()->cart->get_cart();
    $items = array();

    foreach ($cart_contents as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $product_id = $product->get_sku(); // Using SKU as ID
        $product_name = $product->get_name();
        $product_price = $product->get_price();
        $quantity = $cart_item['quantity'];
        
        // Handle producer logic safely
        $producer = 'Unknown';
        if (function_exists('get_associated_productor_id')) {
            $producer_id = get_associated_productor_id($product->get_id());
            $producer = esc_attr(get_the_title($producer_id));
        }

        // Get category
        $categories = get_the_terms($product->get_id(), 'product_cat');
        $product_category = (!empty($categories)) ? $categories[0]->name : "Uncategorized";

        $items[] = array(
            'item_id' => $product_id,
            'item_name' => $product_name,
            'price' => $product_price,
            'quantity' => $quantity,
            'item_brand' => $producer,
            'item_category'=> $product_category
        );
    }
    
    $cart_total_value = WC()->cart->total;

    wp_send_json_success([
        "items" => $items,
        "total" => $cart_total_value
    ]);
}
