/**
 * Created by martin on 29.10.2016.
 */



jQuery(document).ready(function () {
    //When add tag is clicked
    jQuery('.add-tag').click(function () {
      //add text from the textfield to ul element
        var tags = jQuery('#tags');
        var li = "<li class='tag'><input name='tag-list[]' type='hidden' value='"
            + tags.val() + "'>" +
            tags.val() + "<input type='button' class='remove-tag' value='x'>"
            + "</li>";

        jQuery('#tag-list').append(li);
        //remove text value
        tags.val('');
    });

    jQuery('.remove-tag').live('click', function () {
        jQuery(this).parent().remove();
    });
  

});


jQuery( function() {
    jQuery( "#date_start" ).datepicker({
        dateFormat: "dd.mm.yy"
    });
    jQuery( "#date_end" ).datepicker({
        dateFormat: "dd.mm.yy"
    });

} );