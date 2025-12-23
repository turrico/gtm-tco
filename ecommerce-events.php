<?php
/**
 * Ecommerce Events (Server-Side Triggered)
 * 
 * Handles pushing dataLayer events for:
 * 1. begin_checkout (on checkout page)
 * 2. view_item (on single product page)
 * 3. purchase (on order received page)
 */

// Helper: Safety wrapper for producer ID
function gtm_get_producer_name($product_id) {
    // Debug: Temporarily disabled to rule out crashes
    /*
    if (function_exists('get_associated_productor_id')) {
        $producer_id = get_associated_productor_id($product_id);
        return get_the_title($producer_id);
    }
    */
    return 'Unknown';
}

// 1. Begin Checkout Event
add_action('woocommerce_before_checkout_form', 'gtm_begin_checkout_event');
function gtm_begin_checkout_event() {
    if (!class_exists('WooCommerce')) return;
    
    $cart = WC()->cart;
    $cart_items = $cart->get_cart();
    $items_array = [];

    foreach ($cart_items as $cart_item) {
        $product = $cart_item['data'];
        $categories = get_the_terms($product->get_id(), 'product_cat');
        $category = (!empty($categories)) ? esc_js($categories[0]->name) : "Uncategorized";
        $brand = esc_js(gtm_get_producer_name($product->get_id()));

        $items_array[] = [
            'item_id' => esc_js($product->get_sku()),
            'item_name' => esc_js($product->get_name()),
            'item_brand' => $brand,
            'item_category' => $category,
            'price' => esc_js($product->get_price()),
            'quantity' => $cart_item['quantity']
        ];
    }

    $json_items = json_encode($items_array, JSON_UNESCAPED_SLASHES);
    $cart_total = esc_js($cart->cart_contents_total);

    echo "
    <script>
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({ ecommerce: null });
        window.dataLayer.push({
            'event': 'begin_checkout',
            'ecommerce': {
                'currency': 'CRC',
                'value': '{$cart_total}',
                'items': {$json_items}
            }
        });
    </script>";
}

// 2. View Item Event
add_action('woocommerce_after_single_product', 'gtm_view_item_event');
function gtm_view_item_event() {
    global $post;
    $product = wc_get_product($post->ID);

    if ($product) {
        $producer_name = esc_js(gtm_get_producer_name($product->get_id()));
        $categories = get_the_terms($product->get_id(), 'product_cat');
        $category = (!empty($categories)) ? esc_js($categories[0]->name) : "Uncategorized";

        echo "
        <script>
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({ ecommerce: null });
        window.dataLayer.push({
            'event': 'view_item',
            'ecommerce': {
                'currency': 'CRC',
                'value': '" . esc_js($product->get_price()) . "',
                'items': [{
                    'item_id':'" . esc_js($product->get_sku()) . "',
                    'item_name':'" . esc_js($product->get_name()) . "',
                    'item_brand':'" . $producer_name . "',
                    'item_category':'" . $category . "',
                    'price':'" . esc_js($product->get_price()) . "'
                }]
            }
        });
        </script>";
    }
}

// 3. Purchase Event
add_action('woocommerce_thankyou', 'gtm_purchase_event');
function gtm_purchase_event($order_id) {
    if (!$order_id) return;

    $order = wc_get_order($order_id);
    if (!$order) return;

    $order_items = [];
    foreach ($order->get_items() as $item) {
        $product = $item->get_variation_id() ? wc_get_product($item->get_variation_id()) : wc_get_product($item->get_product_id());
        $producer_name = esc_js(gtm_get_producer_name($product->get_id()));
        $categories = get_the_terms($product->get_id(), "product_cat");
        $category = (!empty($categories)) ? esc_js($categories[0]->name) : "Uncategorized";

        $order_items[] = [
            "item_id" => $product->get_sku(),
            "item_name" => $item->get_name(),
            "item_variant" => $item->get_variation_id() ? $product->get_name() : null,
            "price" => $product->get_price(),
            "item_brand" => $producer_name,
            "item_category" => $category,
            "quantity" => $item->get_quantity(),
        ];
    }

    $items_js = json_encode($order_items);
    $coupons_js = json_encode($order->get_coupon_codes());

    echo "
    <script>
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ ecommerce: null });
    window.dataLayer.push({
        'event': 'purchase',
        'ecommerce': {
            'currency': 'CRC',
            'transaction_id': '{$order_id}',
            'value': '{$order->get_total()}', 
            'shipping': '{$order->get_shipping_total()}',
            'coupon': '{$coupons_js}',
            'items': {$items_js}
        }
    });
    </script>";
}
