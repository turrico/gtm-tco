# Turri.cr Google Tag Manager & Ecommerce Tracking

## Overview
This plugin implements a robust tracking layer for Turri.cr. It initializes the Google Tag Manager (GTM) container and populates the `dataLayer` with standard and custom events, specifically optimized for WooCommerce Enhanced Ecommerce.

**Plugin Folder**: `Dev/Web-Frontend/Wordpress/plugins/obdc-gtm-tracking/`

## Features
- **GTM Initialization**: Automatically injects the GTM container (`GTM-WM6NPSN`) into the `<head>` (Priority 1) and `noscript` fallback in the footer.
- **Data Layer Shortcode**: Provides `[datalayer_output]` to push page-level context (User ID, Content Type, Content Group, Experiment Case) to GTM.
- **Enhanced Ecommerce Events**:
    - `view_item`: Triggered on single product pages.
    - `add_to_cart`: Captured via JavaScript for both simple and variable products.
    - `begin_checkout`: Triggered via PHP when the checkout form is loaded.
    - `purchase`: Triggered via PHP on the WooCommerce "Thank You" page.
- **AJAX Cart Integration**: Backend handler to fetch real-time cart data for frontend events.
- **Custom Tracking**: Includes visibility timing for producer information and modal experiments.

## Contents
- `obdc-gtm-tracking.php` - Main plugin entry point and GTM/Data Layer setup.
- `ajax-cart-handler.php` - AJAX endpoint for fetching cart items.
- `ecommerce-events.php` - Server-side PHP triggers for ecommerce tracking.
- `frontend-attributes.php` - Injects product metadata into "Add to Cart" buttons.
- `assets/js/gtm-events.js` - Frontend event listeners and visibility timers.
- `.gitignore` - Standard WordPress and OS file exclusions.

## Installation & Usage
1. Upload the folder to `/wp-content/plugins/`.
2. Activate the plugin in the WordPress Admin.
3. Ensure the `[datalayer_output]` shortcode is used if you need specific page metadata.
4. **Dependency**: This plugin assumes WooCommerce is active and uses a custom function `get_associated_productor_id()` (handled with safe fallbacks).

## For Agents
- **AJAX Action**: `get_cart_content`
- **Data Layer Objects**: Follows GA4 Schema for ecommerce items.
- **Global JS Variables**:
    - `window.timeModalHasBeenVisible()`: Returns time spent in experiment modal in seconds.
    - `window.itemAddedToCart`: Boolean flag to track the source of cart views.

---
**Last Updated**: 2025-12-22
**Author**: Orlando Bruno
