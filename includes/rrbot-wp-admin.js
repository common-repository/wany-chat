function showError(errorOutputId, errorCode, errorMsg) {
    let text = '';
    if (errorCode)
        text += ' [error_code=' + errorCode + ']';
    if (errorMsg)
        text += '<br>' + errorMsg;

    if (!text)
        text = 'Something went wrong!';
    console.log(text);
    jQuery('#' + errorOutputId).html(text);
}

function sendAjax(url, successOutputId, reloadOnSuccess, errorOutputId, data = null, confirmMessage = null ) {
    let post_data = {};

    if (data)
        for (let key in data)
            post_data[key] = jQuery('#' + data[key]).val();

    if (url && (!confirmMessage || confirm(confirmMessage)))
        jQuery.ajax({
            url: url,
            type: 'POST',
            contentType: 'application/json; charset=utf-8',
            data: JSON.stringify(post_data),
            success: function (result) {
                if (result['status'] === 'success') {
                    if (successOutputId && result['html'] != null)
                        jQuery('#' + successOutputId).html(result['html']);
                    if (reloadOnSuccess)
                        window.location.reload(true);
                } else
                    showError(errorOutputId, (result['error_code'] ? result['error_code'] : ''), (result['error_msg'] ? result['error_msg'] : ''))
            },
            error: function (errMsg) {
                showError(errorOutputId, null, '');
                console.log(errMsg);
            }
        });
}

function changeVisibility(element, class_show, class_hide) {
    if (class_show)
        jQuery('.' + class_show).css('display', (element.checked ? 'inherit' : 'none'));
    if (class_hide)
        jQuery('.' + class_hide).css('display', (element.checked ? 'none' : 'inherit'));
}

function setShippingCostVisibility(shippingCostCode) {
    if (shippingCostCode === 'FREE') {
        jQuery('.rrb-field-shipping_cost').addClass('rrb-hidden');
        jQuery('.rrb-field-shipping_cost_text').addClass('rrb-hidden');
        jQuery('.rrb-field-delivery_estimates').removeClass('rrb-hidden');

        jQuery('.rrb-field-shipping_code_free').removeClass('rrb-hidden');
        jQuery('.rrb-field-shipping_code_flat').addClass('rrb-hidden');
        jQuery('.rrb-field-shipping_code_manual').addClass('rrb-hidden');
        jQuery('.rrb-field-shipping_code_woo').addClass('rrb-hidden');
    } else if (shippingCostCode === 'FLAT') {
        jQuery('.rrb-field-shipping_cost').removeClass('rrb-hidden');
        jQuery('.rrb-field-shipping_cost_text').addClass('rrb-hidden');
        jQuery('.rrb-field-delivery_estimates').removeClass('rrb-hidden');

        jQuery('.rrb-field-shipping_code_free').addClass('rrb-hidden');
        jQuery('.rrb-field-shipping_code_flat').removeClass('rrb-hidden');
        jQuery('.rrb-field-shipping_code_manual').addClass('rrb-hidden');
        jQuery('.rrb-field-shipping_code_woo').addClass('rrb-hidden');
    } else if (shippingCostCode === 'MANUAL') {
        jQuery('.rrb-field-shipping_cost').removeClass('rrb-hidden');
        jQuery('.rrb-field-shipping_cost_text').removeClass('rrb-hidden');
        jQuery('.rrb-field-delivery_estimates').removeClass('rrb-hidden');

        jQuery('.rrb-field-shipping_code_free').addClass('rrb-hidden');
        jQuery('.rrb-field-shipping_code_flat').addClass('rrb-hidden');
        jQuery('.rrb-field-shipping_code_manual').removeClass('rrb-hidden');
        jQuery('.rrb-field-shipping_code_woo').addClass('rrb-hidden');
    } else if (shippingCostCode === 'WOO') {
        jQuery('.rrb-field-shipping_cost').addClass('rrb-hidden');
        jQuery('.rrb-field-shipping_cost_text').removeClass('rrb-hidden');
        jQuery('.rrb-field-delivery_estimates').addClass('rrb-hidden');

        jQuery('.rrb-field-shipping_code_free').addClass('rrb-hidden');
        jQuery('.rrb-field-shipping_code_flat').addClass('rrb-hidden');
        jQuery('.rrb-field-shipping_code_manual').addClass('rrb-hidden');
        jQuery('.rrb-field-shipping_code_woo').removeClass('rrb-hidden');
    }
}

function setReadonly(readonly, ids) {
    for (let i = 0; i < ids.length; i++) {
        jQuery('#' + ids[i]).prop('readonly', readonly);
    }
}

function setVisible(visible, ids) {
    for (let i = 0; i < ids.length; i++) {
        if (visible) {
            jQuery('#' + ids[i]).removeClass('rrb-hidden');
        }
        else {
            jQuery('#' + ids[i]).addClass('rrb-hidden');
        }
    }
}

//IMAGE IN PRODUCT CAT
// Uploading files
var file_frame;

// Only show the "remove image" button when needed
jQuery(document).ready(function () {
    if ('0' === jQuery('#wy_product_cat_thumbnail_id').val()) {
        jQuery('.wy_remove_image_button').hide();
    }
});

jQuery(document).on('click', '.wy_upload_image_button', function (event) {

    event.preventDefault();

    // If the media frame already exists, reopen it.
    if (file_frame) {
        file_frame.open();
        return;
    }

    // Create the media frame.
    file_frame = wp.media.frames.downloadable_file = wp.media({
        title: rrb_str_choose_image,
        button: {
            text: rrb_str_use_image
        },
        multiple: false
    });

    // When an image is selected, run a callback.
    file_frame.on('select', function () {
        var wy_attachment = file_frame.state().get('selection').first().toJSON();
        var wy_attachment_thumbnail = wy_attachment.sizes.thumbnail || wy_attachment.sizes.full;

        jQuery('#wy_product_cat_thumbnail_id').val(wy_attachment.id);
        jQuery('#wy_product_cat_thumbnail').find('img').attr('src', wy_attachment_thumbnail.url);
        jQuery('.wy_remove_image_button').show();
    });

    // Finally, open the modal.
    file_frame.open();
});

jQuery(document).on('click', '.wy_remove_image_button', function () {
    jQuery('#wy_product_cat_thumbnail').find('img').attr('src', rrb_wc_placeholder_img_src);
    jQuery('#wy_product_cat_thumbnail_id').val('');
    jQuery('.wy_remove_image_button').hide();
    return false;
});

