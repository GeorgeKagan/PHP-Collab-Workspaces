var Wall = Wall || {};

Wall.Post = {
    like: function() {
        var that = this;
        var star = $(that).find('.star-icon');
        var post_id = $(this).parents('.wall-post').attr('id');
        var like_count = $(this).parents('.wall-post').find('.post-like-count');
        $.ajax({type: 'POST', url: '/posts/like', data: {'post-id': post_id}, success: function(data) {
            if (data === '0') {
                like_count.text(parseInt(like_count.text()) - 1);
                star.removeClass('liked');
            } else {
                like_count.text(parseInt(like_count.text()) + 1);
                star.addClass('liked');
            }

        }, async: true});
    },
    lockUnlock: function() {
        var post_id = $(this).parents('.wall-post').attr('id');
        var $lock_status = $(this).find('.post-lock-status');
        $.ajax({type: 'POST', url: '/posts/lock-unlock', data: {'post-id': post_id}, dataType: 'json', success: function(data) {
            if(data.status == 'ok') {
                $lock_status.removeClass('locked-icon').removeClass('unlocked-icon').addClass((data.post_locked) ? 'locked-icon' : 'unlocked-icon');
            }
        }, async: true});
    },
    markAsAnswer: function(event) {
        event.preventDefault();
        var $self = $(this);
        var comment_id = $(this).attr('data-comment-id');
        $.ajax({type: 'POST', url: '/posts/mark-as-answer', data: {'comment-id': comment_id}, dataType: 'json', success: function(data) {
            if(data.status == 'ok') {
                $self.closest('.post-normal-comments').find('.pcomment-wrap').removeClass('answered');
                $self.closest('.post-image-comments').find('.pcomment-wrap').removeClass('answered');
                $self.closest('.pcomment-wrap').addClass('answered')
            }
        }, async: true});
    },
    commentSubmit: function(textarea$) {
        var body = textarea$.val();
        if ($.trim(body) === '') {
            return Utils.swal.fillRequired();
        }
        var view, container;
        if (textarea$.hasClass('post-comment-body-image')) {
            view = '.post-image-view';
            container = '.post-image-comments';
        } else {
            view = '.post-normal-view';
            container = '.post-normal-comments';
        }
        // Persist comment
        var post_id = $(view, '#colorbox').data('post-id');
        var img = $("#img-" + post_id).val();
        $.ajax({type: 'POST', url: '/posts/comment', data: {'post-id': post_id, body: body, img: img}, success: function(pcomment_id) {
            if ($.trim(img) !== ''){
                Wall.ImageView.deletePhoto(post_id,1);
            }
            var template = Handlebars.compile($("#pcomment-partial").html());
            var data = {id: pcomment_id, name: CurrUser.name, fb_user: CurrUser.fb_user, body: do_rtl_if_heb(body), img: img, time_ago: 'Just now'};
            $(container, '#colorbox').append(template(data)).children(':last').hide().slideDown('normal', function() {
                $(container, '#colorbox').animate({
                    scrollTop: $(container, '#colorbox')[0].scrollHeight
                }, 500);
            });
            var $commentCount = $('[data-post-id="' + post_id + '"] .post-comment-count');
            var count = parseInt($commentCount.text()) || 0;
            count ++;
            $commentCount.text(count);
        }});
        textarea$.val('');
    },
    commentLike: function() {
        var pcomment$ = $(this).parents('.pcomment');
        var like_info$ = $(this).siblings('.pcomment-like-info');
        var like_count$ = pcomment$.find('.pcomment-like-count');
        var comment_id = pcomment$.attr('id');
        var op = $(this).text();
        if (op === 'Unlike') {
            like_count$.decrement();
            $.ajax({type: 'POST', url: '/posts/commentunlike', data: {comment_id: comment_id}, success: function(data) { }});
            $(this).text('Like');
        } else {
            like_count$.increment();
            $.ajax({type: 'POST', url: '/posts/commentlike', data: {comment_id: comment_id}, success: function(data) { }});
            $(this).text('Unlike');
        }
        if (like_count$.text() === '0') {
            like_info$.fadeOut();
        } else {
            like_info$.fadeIn();
        }
        return false;
    },
    commentReply: function() {
        var comment$ = $(this).parents('.pcomment');
        // If android app - let native stuff do the reply
        if (Utils.isAndroidApp()) {
            console.info('comment_id_' + comment$.data('comment-id'));
            return;
        }
        var text = $(this).html();
        if (text === 'Reply') {
            text = 'Cancel';
            $(this).css('color','#428bca');
        } else {
            text = 'Reply';
            $(this).css('color','#888888');
        }
        $(this).html(text);
        // Cancel clicked
        if (comment$.find('.pcomment-reply-form').length) {
            comment$.find('.pcomment-reply-form').remove();
        }
        // Reply clicked - show form
        else {
            var reply_form$ = $(Handlebars.compile($("#pcomment-reply-form").html())());
            comment$.find('.pcomment-replies').prepend(reply_form$);
            var textarea$ = comment$.find('textarea');
            textarea$.focus().keydown(function(e) {
                // Ctrl+Enter pressed
                if (e.keyCode == 13 && e.ctrlKey) {
                    e.stopPropagation();
                    Wall.Post.commentReplySubmit(comment$, text, $(this).val());
                }
            });
            reply_form$.find('button').click(function() {
                Wall.Post.commentReplySubmit(comment$, text, textarea$.val());
            });
        }
        return false;
    },
    /**
     * Submit comment via shortcut or button
     */
    commentReplySubmit: function(comment$, text, body) {
        if ($.trim(body) === '') {
            return Utils.swal.fillRequired();
        }
        // Persist comment
        var comment_id = comment$.attr('id');
        $.ajax({type: 'POST', url: '/posts/commentreply', data: {comment_id: comment_id, body: body}, success: function(pcomment_reply_id) {
            var template = Handlebars.compile($("#pcomment-reply-partial").html());
            var data = {id: pcomment_reply_id, name: CurrUser.name, fb_user: CurrUser.fb_user, body: do_rtl_if_heb(body), time_ago: 'Just now'};
            comment$.find('.pcomment-replies').append(template(data)).children(':last').hide().slideDown('normal');
        }});
        text = comment$.find('.pcomment-reply-btn');
        $(text).css('color','#888888').html('Reply');
        comment$.find('.pcomment-reply-form').slideUp('normal', function() {
            comment$.find('.pcomment-reply-form').remove();
        });
    },
    commentReplyLike: function() {
        var reply$ = $(this).parents('.pcomment-reply');
        var like_info$ = $(this).siblings('.pcomment-reply-like-info');
        var like_count$ = reply$.find('.pcomment-reply-like-count');
        var comment_id = $(this).parents('.pcomment').attr('id');
        var reply_id = reply$.attr('id');
        var op = $(this).text();
        if (op === 'Unlike') {
            like_count$.decrement();
            $.ajax({type: 'POST', url: '/posts/replyunlike', data: {reply_id: reply_id}, success: function(data) { }});
            $(this).text('Like');
        } else {
            like_count$.increment();
            $.ajax({type: 'POST', url: '/posts/replylike', data: {comment_id: comment_id, reply_id: reply_id}, success: function(data) { }});
            $(this).text('Unlike');
        }
        if (like_count$.text() === '0') {
            like_info$.fadeOut();
        } else {
            like_info$.fadeIn();
        }
        return false;
    }
};