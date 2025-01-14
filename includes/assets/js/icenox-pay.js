jQuery(($) => {
    $('#wc_gateway_name').val('');
    $(".icenox_pay_multiselect").selectWoo();
    $(".icenox-pay-method-icon-url").wpMediaPicker({
        store: "url",
        query: {
            type: "image"
        },
        multiple: false,
    });
});