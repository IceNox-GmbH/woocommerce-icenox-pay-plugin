jQuery(($) => {
    $('#wc_gateway_name').val('');
    $(".icenox_pay_multiselect").selectWoo();
    $(".icenox-pay-method-icon-url").wpMediaPicker({
        store: "url",
        query: {
            type: "image"
        },
        multiple: false,
        label_add: IceNoxPayMethods.strings.label_add_icon,
        label_remove: IceNoxPayMethods.strings.label_remove_icon,
        label_replace: IceNoxPayMethods.strings.label_replace_icon,
        label_modal: IceNoxPayMethods.strings.label_add_icon_modal_title,
        label_button: IceNoxPayMethods.strings.label_add_icon_modal_button,
    });
});