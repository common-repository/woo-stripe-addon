jQuery(document).ready(function () {
    if(jQuery(".api_toggle_unique").prop('checked') == true){
       jQuery(".api_key_toggle").attr("type", "password");
       jQuery(".api_key_toggle").css("padding","6px");
    }
    jQuery('.api_toggle_unique').click(function () {
        if (jQuery(".api_key_toggle").attr("type") === "password") {
            jQuery(".api_key_toggle").attr("type", "text");
        } else {
            jQuery(".api_key_toggle").attr("type", "password");
            jQuery(".api_key_toggle").css("padding","6px");
        }
    });
});
