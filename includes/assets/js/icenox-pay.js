jQuery(($) => {
    $("#wc_gateway_name").val("");
    $(".icenox_pay_multiselect").selectWoo();

    const $methodIconUrl = $(".icenox-pay-method-icon-url");
    $methodIconUrl.wpMediaPicker({
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

    $(".wp-mediapicker-remove-button")
        .after('<button type="button" class="method-icon-edit-url button-link">' + IceNoxPayMethods.strings.label_edit_url + '</button>');

    $(".method-icon-edit-url").click(() => {
        $(".icenox-pay-method-icon-url")
            .after('<button type="button" class="method-icon-load-url button-primary">' + IceNoxPayMethods.strings.label_load_url + '</button><hr>')
            .show();

        $(".method-icon-load-url").click(() => {
            $methodIconUrl.wpMediaPicker("value", $methodIconUrl.val());
        });

        $(".method-icon-edit-url").remove();
    });
});