/**
 * Created by martin on 14.8.2016.
 */


/*
 * Row highlighting
 */
jQuery(document).ready(function () {
    var hovered_id = -1;
    jQuery(".item-row").hover(function () {

        jQuery(this).addClass("row-selected");
        //Gets ID of the entry
        hovered_id = jQuery(this).find('input').attr('value');

        //Check each value in left table
        jQuery('#sequence-pages-table').find('tr').each(
            function () {
                var sequence_id = jQuery(this).find('input').attr('value');
                //if same id is found mark it with color
                if( sequence_id == hovered_id ) {
                    jQuery(this).addClass("row-selected");
                }
            }
        );
    },function () {
        //remove all colors
        jQuery(this).removeClass('row-selected');
        jQuery('#sequence-pages-table').find('tr').each(
            function () {
                jQuery(this).removeClass('row-selected')
            }
        );
        jQuery('#all-pages-table').find('tr').each(
            function () {
                jQuery(this).removeClass('row-selected')
            }
        );
    });


});


/*
 * Double-click to clone row
 */
jQuery(document).ready(function () {
   jQuery(".item-row").dblclick(function () {
       if (jQuery(this).parent().parent().attr('id') == 'sequence-pages-table')
         jQuery(this).clone(true).insertAfter(this);
   })
});

/*
 * Remove all occurances
 */
jQuery(document).ready(function () {
   jQuery('#dialog-confirm').hide();
   jQuery('.delete-all').click(function () {
       //Find id of the desired row
       var entry_id = jQuery(this).parent().find('input').attr('value');
       var entries = [];
       //Find all rows with same id and save them in an array
       jQuery('#sequence-pages-table').find('input').each(function () {
           if (jQuery(this).attr('value') == entry_id && jQuery(this).attr('name') == "pages[]") {
               entries.push(jQuery(this).parent().parent());
               jQuery(this).parent().parent().removeClass('row-selected');
               jQuery(this).parent().parent().addClass('row-delete');
           }
       });
        //Show dialog to confirm action
       jQuery( "#dialog-confirm" ).dialog({

           resizable: false,
           height: "auto",
           width: 400,
           modal: true,
           buttons: {
               "Zmazať všetky": function() {
                   jQuery( this ).dialog( "close" );
                   //Remove those rows
                   jQuery(entries).each(function () {
                       jQuery(this).remove();
                   })
               },
               "Zrušiť": function() {
                   jQuery( this ).dialog( "close" );
                   jQuery(entries).each(function () {
                       jQuery(this).removeClass('row-delete');
                   })
               }
           }
       });
   })
});


/*
 * Add multiple
 */
jQuery(document).ready(function () {
    jQuery('#dialog-form').hide();

    //Highlighting wrong input
    function check_input() {
            var text = parseInt(jQuery('#number').val());
            var row_count = jQuery('#sequence-pages-table').find('tr').length - 1;
            if (row_count / (text + 1) < 1 && row_count > 1) {
                jQuery('#number').attr('style', "border-radius: 5px; border:#FF0000 1px solid; display: block;");
                jQuery('#dialog-form').find('p.error-message').attr('style', 'display:inline-block;');
            }
            else {
                jQuery('#number').attr('style', "display: block;");
                jQuery('#dialog-form').find('p.error-message').attr('style', 'display:none;');
            }
    }

    jQuery('#number').on('keyup', function () {
        check_input();
    });

    jQuery('.add-multiple').click(function () {
        check_input();
        //Get how many rows are in the table
        var row_count = jQuery('#sequence-pages-table').find('tr').length - 1;
        var clicked_row = jQuery(this).parent();
        var dialog, form;
        console.log(clicked_row);
        dialog = jQuery( "#dialog-form" ).dialog({
            autoOpen: false,
            height: 300,
            width: 300,
            modal: true,
            buttons: {
                "Pridaj stránky": function() {
                    var time = parseInt(jQuery('#time').val());
                    var number = parseInt(jQuery('#number').val());
                    var insert_row;
                    var iterator;

                    if (number == 1) {
                        iterator = Math.floor(row_count / 2) + 1;
                    }
                    else
                        iterator =  Math.floor(row_count / (number));
                    iterator_original = iterator;
                    if(iterator == 0) {
                        iterator = 1;
                    }
                        for (var i = 0; i < number; ++i) {
                            var x_icon = jQuery('#delete-all img').attr("src");
                            insert_row = clicked_row.clone(true);
                            insert_row.find('td.delete-all').remove();
                            insert_row.find('td.add-multiple').remove();
                            //Check for hidden checkbox
                            if(jQuery(insert_row).find('input').attr("type") == "hidden") {
                                jQuery(insert_row).find('input').attr("name","pages[]");
                            }
                            insert_row.append("<td class='time-input'><input name='pages-timing[]' value=" + time +
                                " class='pages-timing' type='text'></td>");
                            insert_row.append("<td class='delete-one' style='padding-top: 12px;'><a href='#' id='delete-one'>" +
                                "<img src=" + x_icon +" /></a></td>");
                            jQuery('#sequence-pages-table').find('tr').eq(iterator).after(insert_row);

                            iterator += iterator_original + 1;
                        }


                    dialog.dialog("close");
                },
                "Zrušiť": function() {
                    dialog.dialog( "close" );
                }
            }
        });

        dialog.dialog( "open" );
    })
});


/*
 * Delete one row
 */
jQuery(document).ready(function () {
   jQuery('.delete-one').live('click', function () {
       jQuery(this).parent().remove();
   })
});

/*
 * Row movement
 */
jQuery( function() {
    jQuery( "#sequence-pages-table" ).find('tbody').sortable({

        items: "tr.item-row",

        receive: function (e, ui) {
            ui.sender.data('copied', true);
            sortableIn = 1;
            var x_icon = jQuery('#delete-all img').attr("src");
            ui.item.append("<td class='time-input'><input name='pages-timing[]' class='pages-timing' type='text'></td>");
            ui.item.append("<td class='delete-one' style='padding-top: 12px;'><a href='#' id='delete-one'><img src='" + x_icon + "' /></a></td>");
            ui.item.find('td.delete-all').remove();
            ui.item.find('td.add-multiple').remove();
        },

        over: function() { sortableIn = 1; },
        out: function() { sortableIn = 0; },
        beforeStop: function(e, ui) {
            if (sortableIn == 0) {
                ui.item.remove();
            }
        }
    });

    jQuery( "#all-pages-table" ).find('tbody').sortable({
        connectWith: "#sequence-pages-table tbody",
        //Replace all name attributes in the left table with according values
        items: "tr.item-row",
        helper: function (e, ui) {
            this.copyHelper = ui.clone(true).insertAfter(ui);
            jQuery(this).data('copied', false);
            return ui.clone(true);
        },
        stop: function () {

            var copied = jQuery(this).data('copied');

            if (!copied) {
                this.copyHelper.remove();
            }

            this.copyHelper = null;
        },

        update : function () {
            jQuery('#sequence-pages-table').find('input').each(
                function () {
                    //Check for hidden checkbox
                    if(jQuery(this).attr("type") == "hidden") {
                        jQuery(this).attr("name","pages[]")
                    }
                    //Check for time input text
                    if(jQuery(this).attr("type") == "text") {
                        jQuery(this).attr("name","pages-timing[]")
                    }
                }
            );
        }

    });
} );


