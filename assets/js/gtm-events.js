// Google Tag Manager - Frontend Event Listeners

// Helper: Fetch Cart Items & Log Event
function fetchCartItems(eventType) {
    try {
        let trigger_source = window.location.pathname.toLowerCase().includes("/cart")
            ? "view_cart_from_modal"
            : window.itemAddedToCart
            ? "add_to_cart"
            : "cart_icon";

        jQuery.ajax({
            type: "POST",
            url: MyAjax.ajaxurl,
            data: { action: "obdc_gtm_get_cart_content" },
            success: function (response) {
                if(response.success) {
                    window.dataLayer = window.dataLayer || [];
                    window.dataLayer.push({ ecommerce: null });
                    window.dataLayer.push({
                        event: eventType,
                        ecommerce: {
                            currency: "CRC",
                            value: response.data.total,
                            items: response.data.items,
                        },
                        trigger_source: trigger_source
                    });
                    window.itemAddedToCart = false;
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error("Cart GTM Error:", errorThrown);
            },
        });
    } catch (error) {
        console.error("GTM Error:", error);
    }
}

// 1. Add To Cart Triggers
if (typeof window.itemAddedToCart === 'undefined') {
    window.itemAddedToCart = false;
}

document.addEventListener('DOMContentLoaded', function() {
    
    // Variable Products (Form Submission)
    document.body.addEventListener('submit', function(event) {
        let target = event.target;
        if (target && target.matches('.variations_form')) { 
            // We don't preventDefault() here to allow add to cart to proceed, 
            // but we capture the data for GTM.
            try {
                let selectedOption = target.querySelector('select[name="variation_id"] option:checked') || target.querySelector('input[name="variation_id"]'); // Fallback for hidden inputs
                // Note: Standard Woo variation forms use a hidden input for variation_id that updates on selection.
                // The snippet assumed a select. Adjusting logic to be safer:
                let variationInput = target.querySelector('input[name="variation_id"]');
                let variationId = variationInput ? variationInput.value : null;

                if(variationId) {
                    // This relies on the form having data-attributes updated, which isn't standard Woo behavior without custom JS.
                    // Assuming the data attributes from the snippet exist on the form:
                    let product_name = target.getAttribute('data-product-name');
                    let price = target.getAttribute('data-price'); // Needs to be updated dynamically usually
                    let product_category = target.getAttribute('data-product-category');
                    let producer = target.getAttribute('data-producer_name');
                    
                    // Simple push if data exists
                    if(product_name) {
                        window.dataLayer.push({ ecommerce: null });
                        window.dataLayer.push({
                            'event': 'add_to_cart',
                            'ecommerce': {
                                'currency': 'CRC',
                                'value': price,
                                'items': [{
                                    'item_id': variationId, 
                                    'item_name': product_name,
                                    'price': price,
                                    'item_brand': producer,
                                    'item_category': product_category,
                                    'quantity': 1
                                }]
                            }
                        });
                        window.itemAddedToCart = true;
                    }
                }
            } catch (err) {
                console.error("GTM Variable Product Error: ", err);
            }
        }
    });

    // Simple Products (Button Click)
    let addToCartButtons = document.querySelectorAll('.product_type_simple');
    addToCartButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            try {
                let product_sku = this.getAttribute('data-product_sku'); // Standard Woo attribute? No, custom.
                // Using attributes injected by frontend-attributes.php
                let product_name = this.getAttribute('data-product_name');
                let price = parseFloat(this.getAttribute('data-product_price')); 
                let product_category = this.getAttribute('data-product_category');
                let producer = this.getAttribute('data-producer_name'); 

                if(product_name) {
                    window.dataLayer.push({ ecommerce: null });
                    window.dataLayer.push({
                        'event': 'add_to_cart',
                        'ecommerce': {
                            'currencyCode': 'CRC',
                            'items': [{
                                'item_id': product_sku, // Ensure this attr exists
                                'item_name': product_name,
                                'price': price,
                                'item_brand': producer,
                                'item_category': product_category,
                                'quantity': 1
                            }]
                        }
                    });
                    window.itemAddedToCart = true;
                }
            } catch (err) {
                console.error("GTM Simple Product Error: ", err);
            }
        });
    });
});

// 2. View Cart Trigger (Side Cart Modal)
document.addEventListener('DOMContentLoaded', () => {
  const cartModal = document.querySelector('.xoo-wsc-modal');
  let cartViewed = false;
  
  if (window.location.pathname.toLowerCase().includes('/cart')) {
    fetchCartItems('view_cart');
  }

  if (cartModal) {
    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.attributeName === 'class') {
          const classList = mutation.target.classList;
          if (classList.contains('xoo-wsc-cart-active') && !classList.contains('xoo-wsc-loading') && !cartViewed) {
            fetchCartItems('view_cart');
            cartViewed = true;
          }
          if (!classList.contains('xoo-wsc-cart-active')) {
            cartViewed = false;
          }
        }
      });
    });
    observer.observe(cartModal, { attributes: true });
  }
});

// 3. Button Click Tracking & Visibility Timer
document.addEventListener("DOMContentLoaded", function() {
    var buttonIds = ["modal-experiment__button-close", "modal-experiment__button-producer", "header__link-logo", "header__link-acount-icon"];
    
    buttonIds.forEach(function(id) {
        let btn = document.getElementById(id);
        if(btn) addListenersToButton(btn);
    });

    document.querySelectorAll("[id^='product-cat-bar__btn").forEach(addListenersToButton);
    document.querySelectorAll("[id^='product-cat-modal__link").forEach(addListenersToButton);
    document.querySelectorAll("[id^='footer__link").forEach(addListenersToButton);

    function addListenersToButton(button) {
        if (button) {
            button.addEventListener("click", function() { pushToDataLayer(button.id); });
            button.addEventListener("touchend", function() { pushToDataLayer(button.id); });
        }
    }

    function pushToDataLayer(buttonId) {
        var timeSpent = window.timeModalHasBeenVisible ? window.timeModalHasBeenVisible() : 0;
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            'event': 'button_clicked',
            'buttonId': buttonId,
            'timeSpentInExperimentModal': timeSpent
        });
    }
});

// 4. Visibility Timer Logic (Producer Info / Modal)
(function () {
    var contentElement = document.querySelector(".gt4class__producerinfo");
    var modalElement = document.getElementById("modal-experiment");
    
    if (!contentElement && !modalElement) return;

    var timerStartTimestamp = null;
    var timeModalIsVisible = 0; 
    var visibilityThreshold = 0.4;
    var animationFrameId = null;

    function handleVisibilityChange() {
        document.hidden ? pauseTrackingTime() : resumeTrackingTime();
    }

    function resumeTrackingTime() {
        if (timerStartTimestamp === null) {
            timerStartTimestamp = performance.now();
            animationFrameId = requestAnimationFrame(updateTimers);
        }
    }

    function updateTimers() {
        var now = performance.now();
        if (modalElement && modalElement.style.display !== "none" && modalElement.getBoundingClientRect().width > 0) {
            timeModalIsVisible += now - timerStartTimestamp;
        }
        timerStartTimestamp = now;
        animationFrameId = requestAnimationFrame(updateTimers);
    }

    function pauseTrackingTime() {
        if (animationFrameId !== null) {
            cancelAnimationFrame(animationFrameId);
            animationFrameId = null;
            timerStartTimestamp = null;
        }
    }

    function intersectionCallback(entries) {
        entries.forEach(function (entry) {
            if (entry.intersectionRatio >= visibilityThreshold) {
                if (!document.hidden) resumeTrackingTime();
                document.addEventListener("visibilitychange", handleVisibilityChange);
            } else {
                pauseTrackingTime();
                document.removeEventListener("visibilitychange", handleVisibilityChange);
            }
        });
    }

    if ("IntersectionObserver" in window) {
        var observer = new IntersectionObserver(intersectionCallback, { threshold: visibilityThreshold });
        if (contentElement) observer.observe(contentElement);
        if (modalElement) observer.observe(modalElement);
    }

    // Expose global functions
    window.timeModalHasBeenVisible = function () {
        return timeModalIsVisible / 1000; 
    };
    window.timeSpentViewingProducerContent = window.timeModalHasBeenVisible; // Alias
})();

// 5. Page Unload Event
window.addEventListener('beforeunload', function() {
    var timeSpent = window.timeSpentViewingProducerContent ? window.timeSpentViewingProducerContent() : 0;
    if(timeSpent > 0) {
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            'event': 'page_unload',
            'timeSpentInProducer': timeSpent
        });
    }
});
