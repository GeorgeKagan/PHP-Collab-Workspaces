/*global $,window,AcConfig,CurrUser,Optional,do_rtl_if_heb,AcSockets*/

var Chat = {
    timestamp: null,
    interval: null,
    getMessages: function (group_name) {
        'use strict';
        group_name = group_name === 'Followed Groups' ? 'all' : group_name;
        var data = {group_name: group_name};
        $.ajax({type: 'GET', url: '/chat', data: data, success: function (chat) {
            chat = JSON.parse(chat);
            Chat.timestamp = chat.server_timestamp;
            $('#chat').html('');
            $.each(chat.messages, function (key, val) {
                var avatar = val.fb_user ? 'http://graph.facebook.com/' + val.fb_user + '/picture' : '/assets/img/default-avatar.jpg';
                $('<div id="' + val.id + '" class="chat-msg clearfix"><img class="img-circle ' + val.type + '" src="' + avatar + '"><small>' +
                    '<span style="float:left">' + val.name + '</span> &nbsp;<em style="color:#59B3E7">' + val.created_at + '</em></small>' + val.message + '</div>').
                    appendTo($('#chat'));
            });
            $('#chat').scrollTop($('#chat')[0].scrollHeight);
            Chat.interval = window.setInterval(function () {
                Chat.pollforNew(group_name);
            }, 1000);
            Chat.pollforNew(group_name);
        }});
    },
    pollforNew: function (group_name) {
        'use strict';
        // Chat is hidden below this width, so don't fetch new msgs to save bandwidth
        if (window.innerWidth <= 1266) {
            return;
        }
        group_name = group_name === 'Followed Groups' ? 'all' : group_name;
        var data = {group_name: group_name, timestamp: Chat.timestamp, exclude_curr_user: true};
        $.ajax({type: 'GET', url: '/chat', data: data, dataType: 'json', cache: false, success: function (chats) {
            Chat.timestamp = chats.server_timestamp;
            if (chats.messages.length === 0) { return; }
            $.each(chats.messages, function (key, val) {
                var avatar = val.fb_user ? 'http://graph.facebook.com/' + val.fb_user + '/picture' : '/assets/img/default-avatar.jpg';
                $('<div class="chat-msg clearfix"><img class="img-circle ' + val.type + '" src="' + avatar + '"><small>' + val.name + '</small>' + val.message + '</div>').
                    appendTo($('#chat'));
            });
            $('#chat-typing').remove();
            $('#chat').scrollTop($('#chat')[0].scrollHeight);
        }});
    },
    addMessage: function () {
        'use strict';
        var data = {user_id: CurrUser.id, group_hash: Optional.hash, message: $(this).val()},
            avatar = CurrUser.fb_user ? 'http://graph.facebook.com/' + CurrUser.fb_user + '/picture' : '/assets/img/default-avatar.jpg';
        $.ajax({type: 'POST', url: '/chat', data: data});
        $('<div class="chat-msg clearfix"><img class="img-circle ' + CurrUser.type + '" src="' + avatar + '"><small>' + CurrUser.name + '</small>' + do_rtl_if_heb($(this).val()) + '</div>').
            appendTo($('#chat'));
        $('#chat').scrollTop($('#chat')[0].scrollHeight);
        $(this).val('');
    },
    showOnlineCount: function (nicknames) {
        'use strict';
        var $chat = $('#chat'),
            count = Object.size(nicknames),
            $chat_container = $('#chat-container'),
            $chat_online = $('.chat-online'),
            $chat_offline = $('.chat-offline'),
            stu_txt = '';
        $chat_container.removeClass('chat-online-border chat-offline-border');
        if (count === 0) {
            $chat_container.addClass('chat-offline-border');
            $('#chat-online').css('margin-bottom', '35px');
            if ($chat_online) {
                $chat_online.remove();
            }
            if ($chat_offline) {
                $chat_offline.remove();
            }
            $chat_container.prepend('<div class="chat-offline"><img src="/assets/img/chat-offline-icon.png" style="position: absolute; top: 0; left: 0;">' +
                '<span style="padding-left: 35px;">Group chat</span></div>');
            $('#co-chat-online').html('<div class="none-online">No students online</div>' +
                '<div class="chat-leave-msg">But you can leave a message!</div>');
        } else {
            $chat_container.addClass('chat-online-border');
            stu_txt = count === 1 ? 'student' : 'students';
            if ($chat_offline) {
                $chat_offline.remove();
            }
            if ($chat_online) {
                $chat_online.remove();
            }
            $chat_container.prepend('<div class="chat-online"><img src="/assets/img/chat-online-icon.png" style="position: absolute; top: 0; left: 0;">' +
                '<span style="padding-left: 35px;">Group chat</span></div>');
            $('#co-chat-online').html('<div class="chat-online-count">' + count + ' ' + stu_txt + ' online</div>');
        }
        Chat.resize();
        $chat.scrollTop($chat[0].scrollHeight);
    },
    /**
     * Emit typing event every X seconds via a socket
     */
    emitChatTyping: function () {
        'use strict';
        var last_emit = new Date().getTime();
        $('#chat-add').on('input', function () {
            if (last_emit + 3000 > new Date().getTime()) {
                return;
            }
            last_emit = new Date().getTime();
            AcSockets.socket.emit('chatTyping', CurrUser.name);
        });
    },
    /**
     * Show typing users as received from nodejs server
     */
    showTypingUsers: function () {
        'use strict';
        var typing_timeout,
            typing = [];
        AcSockets.socket.on('showChatTyping', function (name) {
            if (typing.indexOf(name) === -1) {
                typing.push(name);
            }
            $('#chat-typing').remove();
            if (typing.length > 1) {
                $('#chat').append('<div id="chat-typing">' + typing.join(', ') + ' are typing...</div>');
            } else {
                $('#chat').append('<div id="chat-typing">' + name + ' is typing...</div>');
            }
            $('#chat').scrollTop($('#chat')[0].scrollHeight);
            window.clearTimeout(typing_timeout);
            typing_timeout = window.setTimeout(function () {
                typing = [];
                $('#chat-typing').remove();
            }, 3500);
        });
    },
    resize: function () {
        'use strict';
        var $chat = $('#chat');
        $chat.height($(window).height() - $chat.position().top - 210);
        $('#chat-container').width($('#chat-wrapper').width());
    }
};

$(function () {
    'use strict';
    var $chat = $('#chat'),
        chat_resize_timeout;
    if (!$chat.length) {
        return;
    }
    if (AcConfig.isEnabled('chat')) {
        Chat.getMessages(Optional.hash);
    }
    $('#chat-add').keyup(function (e) {
        if (e.keyCode === 8 && $.trim($('#chat-add').val()).length === 0) {
            $('#send_text').css('visibility', 'hidden');
        }
    });
    $('#chat-add').keypress(function (e) {
        $('#send_text').css('visibility', 'visible');
        // Enter pressed
        if (e.keyCode === 13 && $.trim($('#chat-add').val()).length > 0) {
            Chat.addMessage.call(this);
            $('#send_text').css('visibility', 'hidden');
        }
    });
    // Resize chat on window resize
    $(window).resize(function () {
        window.clearTimeout(chat_resize_timeout);
        chat_resize_timeout = window.setTimeout(Chat.resize, 250);
    });
    Chat.resize();
    // Chat typing indication
    Chat.emitChatTyping();
    window.setTimeout(function () {
        Chat.showTypingUsers();
    }, 1000);
});