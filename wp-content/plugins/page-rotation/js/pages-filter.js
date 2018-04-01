/**
 * Created by martin on 12.3.2017.
 */


jQuery(document).ready(function () {
    jQuery("#input-filter").keyup(function () {
        var all_pages = jQuery('#all-pages-table');
        var filter = jQuery('#input-filter').val().toUpperCase();
        var current;
        jQuery(all_pages).find('tbody').find('tr').each(function (index) {
            current = jQuery(this).find('.page-title-value').html();
            if (current && jQuery(this).find('.page-title-value').html().toUpperCase().indexOf(filter) > -1) {
                jQuery(this).css('display','table');
            }
            else {
                jQuery(this).css('display','none');
            }
        });
    })
});