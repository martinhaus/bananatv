/**
 * Created by martin on 20.9.2016.
 */

var start = new Date;

setInterval(function() {
    var time = new Date();
    jQuery('.time').text(
        (time.getHours()<10?'0':'') + time.getHours()
        + ":" +
        (time.getMinutes()<10?'0':'')+  time.getMinutes() + ":"
        + (time.getSeconds()<10?'0':'') +  time.getSeconds());
}, 1000);
