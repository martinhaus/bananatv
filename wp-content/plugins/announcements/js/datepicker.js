/**
 * Created by martin on 26.1.2017.
 */

jQuery(document).ready(function () {
    jQuery( function() {
        jQuery("#ann-start").datepicker({
            dateFormat: "dd.mm.yy"
        });
        jQuery("#ann-end").datepicker({
            dateFormat: "dd.mm.yy"
        });
    });
});
