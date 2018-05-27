/**
 * Created by martin on 27.6.2016.
 */

function startTimer(duration, display) {
    var timer = duration, minutes, seconds, hours;
    setInterval(function () {
        hours = parseInt(timer/3600, 10);
        minutes = parseInt((timer - (hours*3600)) / 60, 10);
        seconds = parseInt(timer % 60, 10);

        hours  =hours < 10 ? "0" + hours : hours;
        minutes = minutes < 10 ? "0" + minutes : minutes;
        seconds = seconds < 10 ? "0" + seconds : seconds;

        display.textContent = hours + ":" + minutes + ":" + seconds;
        display.textContent = Math.trunc(timer / 60);

        if (--timer < 0) {
            timer = duration;
        }
    }, 1000);
}

