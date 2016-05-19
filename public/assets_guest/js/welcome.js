(function () {
    'use strict';
    function timer(sec, block, direction) {
        var time    = sec;
        direction   = direction || false;

        var hour    = parseInt(time / 3600);
        if ( hour < 1 ) hour = 0;
        time = parseInt(time - hour * 3600);
        if ( hour < 10 ) hour = '0'+hour;

        var minutes = parseInt(time / 60);
        if ( minutes < 1 ) minutes = 0;
        time = parseInt(time - minutes * 60);
        if ( minutes < 10 ) minutes = '0'+minutes;

        var seconds = time;
        if ( seconds < 10 ) seconds = '0'+seconds;

        block.innerHTML = hour+':'+minutes+':'+seconds;

        if ( direction ) {
            sec++;
            setTimeout(function(){ timer(sec, block, direction); }, 1000);
        } else {
            sec--;
            if ( sec > 0 ) {
                setTimeout(function(){ timer(sec, block, direction); }, 1000);
            } else {
                window.location.href='welcome';
            }
        }
    }

    function start_countdown(time) {
        var block = document.getElementById('countdown');
        timer(time, block);
    }

    $(document).ready(function(){
        if ($('#countdown').length) {
            var time = $('#sec').val();
            start_countdown(time);
        }
    });
})();

