(function() {
    var io;
    io = require('socket.io').listen(4000);
    io.sockets.on('connection', function(socket) {
        socket.clientData = {};
        socket.on('room', function(room) {
            socket.join(room);
            socket.clientData.room = room;
        });
        /*
        * Chat
        **/
        // Set nicknake and emit to everyone
        socket.on('setUser', function(user) {
            console.log(user);
            socket.set('user', {name: user.name, fb_user: user.fb_user, type: user.type});
            var users = [];
            for (var socketId in io.sockets.sockets) {
                // Get users from the same room only
                if (!io.sockets.sockets[socketId].clientData.room
                    || io.sockets.sockets[socketId].clientData.room !== socket.clientData.room) {
                    continue;
                }
                io.sockets.sockets[socketId].get('user', function(err, user) {
                    users.push(user);
                });
            }
            io.sockets.in(socket.clientData.room).emit('markOnlineUsers', users);
        });
        socket.on('chatTyping', function(name) {
            socket.in(socket.clientData.room).broadcast.emit('showChatTyping', name);
        });
        // Emit to everyone
        socket.on('disconnect', function() {
            socket.get('user', function(err, me) {
//                socket.broadcast.emit('disconnect', nickname);
                var users = [];
                for (var socketId in io.sockets.sockets) {
                    // Get users from the same room only
                    if (!io.sockets.sockets[socketId].clientData.room
                        || io.sockets.sockets[socketId].clientData.room !== socket.clientData.room) {
                        continue;
                    }
                    io.sockets.sockets[socketId].get('user', function(err, user) {
                        if (me.name !== user.name) { users.push(user); }
                    });
                }
                io.sockets.in(socket.clientData.room).emit('markOnlineUsers', users);
            });
        });
    });
}).call(this);
