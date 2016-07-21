// Flash is minacatUpdate
var minicartUpdate = false;
var cancelTimeOut = 0;
// Redirect status
var redirect_status;
// Redirect timeout
var redirect_timeout;
// Cart url
var cart_url;
// Show popup
var show_pop_up;
// popup
var win;
var ajaxLoader = "<div id='loader' style='position: fixed; text-align: center; z-index: 9999; color: black; overflow: auto; min-height: 50px; display:none; margin: 50px; left: 43%; top: 40%; padding: 25px; width: auto; height: auto; margin-left: auto; margin-right: auto;'>" +
    "<img id='loaderGif' style='margin: auto;' src='/skin/frontend/base/default/images/bluecom/opc-ajax-loader.gif'><p style='font: 16px/1.55 Arial, Helvetica, sans-serif; color: white;'>Please wait...</p></div>" +
    "<div id='acoverlay' style='display:none;position: fixed;top: 0;left: 0;width: 100%;height: 100%;background-color: #000;-moz-opacity: 0.3;opacity: .30;filter: alpha(opacity=30);z-index: 9990;'></div>";

jQuery(document).ready(function () {
    jQuery("body").append(ajaxLoader);

    // Popup show product
    win = new Window({
        id: "alphacube",
        className: "alphacube",
        title: "Please, choose product options",
        minWidth: 398,
        minHeight: 490,
        maxHeight: 1200,
        maxWidth: 1200,
        closable: true,
        minimizable: false,
        maximizable: false,
        resizable: false,
        draggable: false,
        onClose: closeAjaxCart,
        showEffect: Element.show,
        hideEffect: Element.hide
    });

    // 2016-07-21 Thanh-TV: change view button to cart button
    jQuery(".category-products a.button").prop("title", "Add to Cart");
    jQuery(".category-products a.button").text("Add to Cart");

    //for theme rwd
    // Rebind event click on minicart after load ajax.
    jQuery(document).on('click', "#header-cart a.close.skip-link-close", function (event) {
        event.preventDefault();
        hideShowMiniCart();
    });

    //for theme rwd
    // Rebind event click on minicart after load ajax.
    jQuery(document).on('click', ".header-minicart a.skip-link.skip-cart", function (event) {
        event.preventDefault();
        hideShowMiniCart();
    });

    //remove onclick attribute of button "Add to cart", save value
    jQuery.each(jQuery(".category-products .btn-cart"), function (k, v) {
        var onclick = v.onclick.toString();
        var url = onclick.substring(onclick.lastIndexOf('\''), onclick.indexOf('\'') + 1);
        v.value = url;
        jQuery(v).attr('onclick','');
    });

    // ajax add to cart from category list
    jQuery(document).on('click', ".category-products .btn-cart", function () {
        addToCart(this.value);
    });

    // 2016-07-21 Thanh-TV: view button (change on add to cart label)
    jQuery(document).on('click', ".category-products a.button", function (event) {
        event.preventDefault();
        addToCart(this.href);
    });

    //fix bug top window
    jQuery("#alphacube_row1").removeClass('top');
});

/**
 * work only when jQuery update minicart (for rwd theme)
 */
function hideShowMiniCart() {
    if (minicartUpdate) {
        if (jQuery(".header-minicart a.skip-link.skip-cart").hasClass('skip-active')) {
            jQuery(".header-minicart a.skip-link.skip-cart").removeClass('skip-active');
            jQuery("#header-cart").removeClass('skip-active');
        } else {
            jQuery(".header-minicart a.skip-link.skip-cart").addClass('skip-active');
            jQuery("#header-cart").addClass('skip-active');
        }
    }
}

/**
 * Add to cart by ajax.
 *
 * @author Thanh-TV
 * @since 2016-07-20
 *
**/
function addToCart(url) {
    showLoading();
    url = url.replace("checkout/cart", "bluecom_ajax/cart");
    if (url.indexOf('bluecom_ajax/cart') == -1) {
        url = getBaseUrl() + "/bluecom_ajax/cart/add?url=" + url;
    }

    jQuery.ajax({
        url: url,
        data: {isAjax: 1},
        dataType: 'json',
        success: function (data) {
            hideLoading();
            setAjaxData(data);
        }
    });
};

function getBaseUrl() {
    return document.location.origin;
}

function showLoading() {
    jQuery('#acoverlay').show();
    jQuery('#loader').show();
};

function hideLoading() {
    jQuery('#acoverlay').hide();
    jQuery('#loader').hide();
};

function setAjaxData(data) {
    //fill all global variables
    redirect_status = data.redirect_status;
    redirect_timeout = data.redirect_timeout;
    cart_url = data.cart_url;
    show_pop_up = data.show_pop_up;
    if (data.status == 'ERROR') {
        alert(data.message);
    } else {
        jQuery('#acoverlay').show();
        //if response with option
        if (data.options_url) {
            win.setURL(data.options_url);
            var na = navigator.userAgent;
            var selector_iframe = jQuery("#alphacube iframe");
            if(na.toLowerCase().indexOf('firefox') > -1){
                selector_iframe.attr('scrolling', 'yes');
            }
            if(na.indexOf('MSIE')!= -1){
                selector_iframe.attr('scrolling', 'yes');
            }
            if(na.indexOf('Chrome')!= -1){
                selector_iframe.attr('scrolling', 'no');
            }
        }
        /*//if show product info enable
        if (data.productinfo) {
            if (jQuery('#choice').length) {
                jQuery('#choice').remove();
            }
            jQuery('.col-main').append(data.productinfo);
            ajaxshow();
        }*/
        //update top links for cart
        if (data.toplink) {
            jQuery('.header .links').replaceWith(data.toplink);
        }
        //update sidebar cart
        if (data.sidebar) {
            if (jQuery('.block-cart')) {
                jQuery('.block-cart').replaceWith(data.sidebar);
            }
            ajaxshow();
        }
        //update minicart for rwd theme
        if (data.minicart) {
            if (jQuery('.header-minicart')) {
                jQuery('.header-minicart').empty().append(data.minicart);
            }
            ajaxshow();
        }
        //update cart
        if (data.checkout) {
            if (jQuery('.cart').length) {
                jQuery('.cart').replaceWith(data.checkout);
            }
            ajaxshow();
            jQuery(".button.btn-update").remove();
            jQuery(".button2.btn-update").remove();
        }
        /*//if response contains wishlist block
        if (data.wishlist) {
            updateWishlist(data);
        }*/
        minicartUpdate = true;
    }
};

/**
 * Auto close popup options
 *
 * @author Thanh-TV
 * @since 2016-07-21
**/
function respondToClick() {
    cancelTimeOut = 1;
    if (!redirect_timeout > 0) {
        cancelTimeOut = 1;
    }
    closeAjaxCart();
    win.close();
};

/**
 * Close popup
 *
 * @author Thanh-TV
 * @since 2016-07-21
**/
function closeAjaxCart() {
    cancelTimeOut = 1;
    jQuery('#ajaxcart').hide();
    hideLoading();
    win.setHTMLContent("");
};

/**
 * Load popup options of product
 *
 * @author Thanh-TV
 * @since 2016-07-21
**/
function ajaxCartLoad() {
    var frame = document.getElementById(win.getId());
    frame.style.zIndex = "9995";
    win.showCenter();
    Event.observe("#acoverlay", 'click', respondToClick);
    hideLoading();
};

function ajaxshow() {
    if (redirect_status == 1) {
        win.hide();
        var acoverlay = document.getElementById("acoverlay");
        if (redirect_timeout > 0) {
            showChoice();
        } else {
            jQuery('#loader').show();
            window.location.replace(cart_url);
        }
    } else {
        win.hide();
        showChoice();
    }
};

function showChoice() {
    if (show_pop_up > 0) {
        cancelTimeOut = 0;
        Event.observe("acoverlay", 'click', respondToClick);
        jQuery('#loader').hide();
        jQuery('#choice').show();
        jQuery('#acoverlay').show();
        if (redirect_timeout > 0) {
            countdown(redirect_timeout);
        }
    } else {
        closeAjaxCart();
    }
};

function countdown(seconds) {
    function tick() {
        var counter = document.getElementById("timeout");
        if (counter) {
            counter.innerHTML = String(seconds);
            if (seconds > 0) {
                seconds--;
                if (cancelTimeOut == 0) {
                    setTimeout(tick, 1000);
                }
            } else {
                if (cancelTimeOut == 0) {
                    showLoading();
                    jQuery('#choice').hide();
                    window.location.replace(cart_url);
                }
            }
        }
    }

    if (cancelTimeOut == 0 || seconds > 0) {
        tick();
    }
};