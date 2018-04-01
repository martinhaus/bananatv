/**
 * Created by martin on 18.3.2017.
 */

jQuery(document).ready(function () {
    /*
     Dialog for delete confirmation
     */
    var redirect;
    jQuery('.delete').click(function (e) {
        var done = false;
        e.preventDefault();
        jQuery('#dialog-delete').dialog('open');
        redirect = jQuery(this).find('a').attr('href');
    });

    jQuery("#dialog-delete").dialog({
        resizable: false,
        height:190,
        autoOpen: false,
        width: 330,
        modal: true,
        buttons: {
            "Áno": function() {
                jQuery(this).dialog("close");
                window.location.href= redirect;
            },
            "Nie": function() {
                jQuery(this).dialog("close");
                return false;
            }
        }
    });
});
