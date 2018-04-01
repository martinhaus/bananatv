/**
 * Created by martin on 4.10.2016.
 */

jQuery(document).ready(function($){
    $('.add-image').click(function(e) {
        e.preventDefault();
        var image = wp.media({
            title: 'Nahranie obsahu',
            // mutiple: true if you want to upload multiple files at once
            multiple: false
        }).open()
            .on('select', function(e){
                // This will return the selected image from the Media Uploader, the result is an object
                var uploaded_images = image.state().get('selection');
                var thumbnail;
                var full_image_url;
                var image_resolution;
                var size;
                var zip_url;
                var pdf_url;
                var pdf_pages;
                var file_name;
                var attachment_id;
                var subtype;
                var attachment_ids = uploaded_images.map( function( attachment ) {
                    attachment = attachment.toJSON();
                    console.log(attachment);
                    if(attachment.subtype == "pdf") {
                        pdf_url = attachment.url;

                        var data = {
                            action: 'media_upload_check_num_of_pages',
                            pdf: pdf_url
                        };
                        jQuery.get(ajax_object.ajax_url, data, function(response) {
                            if(response > 0) {
                                pdf_pages = response;
                                //If it is a pdf
                                if (pdf_url && pdf_pages > 1) {
                                        html = "<h2>Súbor úspešne nahratý</h2>" +
                                            "<input type='hidden' name='pdf-url' value='" + pdf_url + "'>" +
                                            "<h3>Názov súboru: " + file_name + "</h3>" +
                                            "<h3>Veľkosť: "+ size +"</h3>" +
                                            "<h4>Zo súboru bude vytvorená nová sekvencia</h4>";
                                        jQuery('#datepicker').attr('disabled','true');
                                        jQuery('#sequence_box_overall').hide();
                                    }
                                    else if (pdf_url && pdf_pages == 1) {
                                        html = "<h2>Súbor úspešne nahratý</h2>" +
                                            "<input type='hidden' name='image-url' value='" + pdf_url + "'>" +
                                            "<h3>Názov súboru: " + file_name + "</h3>" +
                                            "<h3>Veľkosť: "+ size +"</h3>" ;
                                        // jQuery('#datepicker').attr('disabled','true');
                                        //  jQuery('#sequence_box_overall').hide();
                                    }
                                    jQuery('#name').val(file_name);
                                    jQuery('#thumbnail').prepend(html);
                            } else {

                            }
                        });
                    }
                    if(attachment.subtype == 'zip') {
                        zip_url = attachment.url;
                    }
                    if(attachment.type == 'image') {
                        full_image_url = attachment.sizes.full.url;
                        thumbnail = attachment.sizes.thumbnail.url;
                        image_resolution = attachment.width + "x" + attachment.height;
                        subtype = attachment.subtype;
                    }
                    size = attachment.filesizeHumanReadable;
                    file_name = attachment.filename;
                    attachment_id = attachment.id;
                }).join();

                //If image was selected
                var html;
                jQuery("#thumbnail").empty();
                if(full_image_url) {
                    html = "<h2>Súbor úspešne nahratý</h2>" +
                        "<input type='hidden' name='attachement-id' value= " + attachment_id + ">" +
                        "<img src=\"" + thumbnail + "\">" +
                        "<input type='hidden' name='image-url' value='" + full_image_url + "'>" +
                        "<input type='hidden' name='subtype' value='" + subtype + "'>" +
                        "<ul class='image-info'>" +
                        "<li>" + file_name + "</li>" +
                        "<li>" + image_resolution + "</li>" +
                        "<li>" + size + "</li>" +
                        "</ul>" +
                        "<div style=\"clear:both\"></div>";
                    jQuery('#datepicker').removeAttr('disabled');
                    jQuery('#sequence_box_overall').show();
                }
                else if (zip_url) {
                    html = "<h2>Súbor úspešne nahratý</h2>" +
                        "<input type='hidden' name='zip-url' value='" + zip_url + "'>" +
                        "<h3>Názov súboru: " + file_name + "</h3>" +
                        "<h3>Veľkosť: "+ size +"</h3>" +
                        "<h4>Zo súboru bude vytvorená nová sekvencia</h4>";
                    jQuery('#datepicker').attr('disabled','true');
                    jQuery('#sequence_box_overall').hide();
                }
                jQuery('#thumbnail').prepend(html);
                jQuery('#name').val(file_name);
            });
    });
});