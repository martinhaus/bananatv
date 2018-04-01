/**
 * Created by martin on 21.1.2017.
 */

function cycleMeasurements() {
    jQuery('.all-mesurements').find('.measurement').each(function (index) {
        jQuery(this).delay(index * 2500).fadeIn(250).delay(2000).fadeOut(250);
        //console.log(jQuery(this));
    });
}

jQuery(document).ready(function () {
    //fix for displaying sunny animation
    var animation = jQuery('.animation');
    if (animation.find('.sunny').length) {
        animation.css('height','100px');
        animation.css('transform','scale(1.5)');
    }

    jQuery('.all-mesurements').find('div').each(function () {
        jQuery(this).hide();
    });

    cycleMeasurements();
    setInterval(cycleMeasurements,8000);



});