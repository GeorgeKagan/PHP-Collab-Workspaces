$(function() {
    var socket;
    if (window.env === 'dev') {
        socket = io.connect('http://localhost:4000');
    } else if (window.ebv === 'qa') {
        socket = io.connect('http://qa.afterclass.co.il:4000');
    } else {
        socket = io.connect('http://www.afterclass.co.il:4000');
    }
    AcSockets.socket = socket;
    /*
     * Global events
     **/
    socket.on('connect', function() {
        if (Optional.hash) {
            socket.emit('room', Optional.hash);
        }
        socket.emit('setUser', {name: CurrUser.name, fb_user: CurrUser.fb_user, type: CurrUser.type});
    });
    socket.on('disconnect', function(nickname) {

    });
    /*
     * Chat
     **/
    socket.on('markOnlineUsers', function(users) {
        var all_but_me = {};
        $.each(users, function() {
            if (this.name !== CurrUser.name) {
                all_but_me[this.name] = this;
            }
        });
        Chat.showOnlineCount(all_but_me);
        $('#wb-activities').html('');
        $.each(all_but_me, function() {
            new Whiteboard.broadcastActivity(this);
        });
    });
});

var AcSockets = {
    socket: null,
    getSocket: function() {
        return AcSockets.socket;
    }
};