/**
 * Created by martin on 4.8.2016.
 */

jQuery(function(){
    var i = 0;
    setInterval(function () {
        var sel = 'table tbody tr:nth-child(' + i + ')';
        jQuery(sel).css("display", "none");
        var last = i + 5;
        if(last > 10) last = last % 10;
        var sel1 = 'table tbody tr:nth-child(' + last+ ')';
        jQuery(sel1).css("display", "table-row");

        i++;
        if(i == 6) {
            i = 0;
            //Reset table
            for(j=5;j<11;j++) {
                var sel = 'table tbody tr:nth-child(' + j + ')';
                jQuery(sel).fadeOut(500);
            }

            for(j=0;j<6;j++) {
                var sel = 'table tbody tr:nth-child(' + j + ')';
                //jQuery(sel).css("display", "table-row");
                jQuery(sel).delay(500).fadeIn(500);
            }
        }
    },3000);
});
