/*global $,anno,document,Responsive,window,console,Image,CurrUser,do_rtl_if_heb,Handlebars,posts,FormData*/
var Wall = Wall || {};

Wall.ImageView = {
    annotations: {},
    init: function () {
        'use strict';
        Wall.ImageView.addAnnotationHandlers();
        anno.setProperties({
            outline: '#428BCA',
            stroke: '#428BCA',
            fill: 'rgba(255, 255, 255, 0.2)',
            hi_stroke: '#3276B1',
            hi_fill: 'rgba(255, 255, 255, 0.3)'
        });
        Wall.ImageView.addLightboxHandlers();
        Wall.ImageView.handleAddComment();
        Wall.ImageView.bindNavArrowsShowHide();
        Wall.ImageView.bindCommentHighlightsAnnotation();
        Wall.ImageView.bindCommentHighlights();
    },
    getRightPaneWidth: function () {
        'use strict';
        return $('.post-image-comments-wrapper').eq(0).outerWidth();
    },
    addAnnotationHandlers: function () {
        'use strict';
        anno.addHandler('onMouseOverAnnotation', function (annotation) {
            if (!annotation.$annotation$) { return; }
            var comment_id = annotation.$annotation$.comment_id,
                container = $('.pcomment#' + comment_id, '#colorbox .post-image-comments'),
                // Calculate the offset of the comment to scroll to
                prev$ = $('.pcomment#' + comment_id, '#colorbox').prevAll(),
                offset = 0;
            if (prev$.length > 1) {
                prev$.each(function () { offset += $(this).outerHeight(); });
            }
            //
            $(container).addClass('lightbox-comment-highlight');
            $(container).children('div.pcomment-replies').css('background', '#eee');
            $('.post-image-comments', '#colorbox').animate({
                scrollTop: offset
            }, 500);
        });
        anno.addHandler('onMouseOutOfAnnotation', function (annotation) {
            if (!annotation.$annotation$) { return; }
            $('.pcomment', '#colorbox').removeClass('lightbox-comment-highlight');
            $('.pcomment', '#colorbox').children('div.pcomment-replies').css('background', '#fff');
        });
        anno.addHandler('onAnnotationCreated', function (annotation) {
            annotation.text = annotation.text;
            Wall.ImageView.saveMark(annotation);
        });
    },
    addLightboxHandlers: function () {
        'use strict';
        // When the lightbox opens
        $(document).bind('cbox_complete', function () {
            var post_id = $('.post-normal-view, .post-image-view', '#colorbox').data('post-id');
            Responsive.ImageView.adjust();
            // Hide arrows by default
            $('#cboxPrevious, #cboxNext').hide();
            window.location.hash = '#post-id-' + post_id;
            // To be intercepted by native mobile apps
            console.info('post_open');
        });
        // Prevent annoying background page scrolling
        $(document).bind('cbox_open', function () {
            $('html').css({ overflow: 'hidden' });
            // Add padding to prevent jerkiness
            $('#site-header').css({ padding: '0 17px 0 0' });
            $('body').css({ padding: '0 15px 0 0' });
        });
        $(document).bind('cbox_closed', function () {
            $('html').css({ overflow: 'auto' });
            $('#site-header').css({ padding: 0 });
            $('body').css({ padding: 0 });
            window.location.hash = '';
        });
    },
    bindNavArrowsShowHide: function () {
        'use strict';
        $('body').on('mouseover', '#colorbox .lightbox-post-image-wrapper', function () {
            if ($('.post-image-a[rel="gallery"]').length < 2) { return; }
            $('#cboxPrevious, #cboxNext').show();
        });
        $('body').on('mouseout', '#colorbox .lightbox-post-image-wrapper, #cboxPrevious, #cboxNext', function (e) {
            if ($('.post-image-a[rel="gallery"]').length < 2) { return; }
            if (e.relatedTarget.id === 'cboxPrevious' || e.relatedTarget.id === 'cboxNext') { return; }
            $('#cboxPrevious, #cboxNext').hide();
        });
    },
    /**
     * Handles highlighting of an associated annotation on hovering a comment.
     */
    bindCommentHighlightsAnnotation: function () {
        'use strict';
        $('body').on('mouseover', '.post-image-view .pcomment', function () {
            if ($(this).hasClass('lightbox-comment-highlight')) {
                return;
            }

            if ($(this).find('.pcomment-wrap').hasClass('comment-highlight')) {
                return;
            }

            $(this).find('.pcomment-wrap').addClass('comment-highlight');

            var post_id = $('.post-image-view', '#colorbox').data('post-id'),
                comment_id = $(this).attr('id'),
                annotation = null;
            $.each(Wall.ImageView.annotations[post_id], function (key, item) {
                if (item && item.comment_id === comment_id) { annotation = item; }
            });
            if (annotation) {
                $(this).addClass('lightbox-comment-highlight');
                $(this).children('div.pcomment-replies').css('background', '#eee');
                anno.highlightAnnotation(annotation);
            }
        });
        $('body').on('mouseleave', '.post-image-view .pcomment', function () {
            $(this).removeClass('lightbox-comment-highlight');
            $(this).children('div.pcomment-replies').css('background', '#fff');
            $(this).find('.pcomment-wrap').removeClass('comment-highlight');
            anno.highlightAnnotation(undefined);
        });
    },
    bindCommentHighlights: function () {
        'use strict';
        var $body = $('body');
        $body.on('mouseover', '.post-normal-view .pcomment', function () {
            if ($(this).hasClass('lightbox-comment-highlight')) {
                return;
            }
            if ($(this).find('.pcomment-wrap').hasClass('comment-highlight')) {
                return;
            }
            $(this).find('.pcomment-wrap').addClass('comment-highlight');
        });
        $body.on('mouseleave', '.post-normal-view .pcomment', function () {
            $(this).removeClass('lightbox-comment-highlight');
            $(this).children('div.pcomment-replies').css('background', '#fff');
            $(this).find('.pcomment-wrap').removeClass('comment-highlight');
            anno.highlightAnnotation(undefined);
        });
    },
    /**
     * Setup the lightbox along with its annotations
     * @param post
     * @param post$
     */
    colorboxSettings: {
        transition: 'none',
        speed: 0,
        fadeOut: 150,
        inline: true,
        scrolling: false,
        fixed: true,
        arrowKey: false,
        // Texts
        current: '',
        // Dimensions
        width: '95%',
        height: '90%'
    },
    setupLightbox: function (post, post$) {
        'use strict';
        var post_img$ = post$.find('.post-image'),
            post_img_lightbox$ = post$.find('.lightbox-post-image'),
            post_img_proxy$ = post$.find('.post-image-proxy'),
            post_img_comments$ = post$.find('.post-image-wrapper, .post-normal-wrapper').find('.pcomment'),
            post_img_comments_replies$ = post_img_comments$.find('.pcomment-reply'),
            annotationsObj = JSON.parse(post.image_annotations) || [],
            annotations = [],
            attach = post$.find('.post-attach-thumb'),
            wrap = attach.parents().find('div[data-post-id]'),
            ext = post.image_url.split('.').pop().toLowerCase(),
            href = '',
            img = null;
        // Convert object of objects into array of objects
        annotations = $.map(annotationsObj, function (value, index) {
            return [value];
        });
        Wall.ImageView.annotations[post.id] = annotations.length ? annotations : [];
        // non-image attach
        wrap.click(function () {
            Wall.ImageView.bindCommentDeleteEvent(post, annotations, post_img_comments$);
            Wall.ImageView.bindCommentReplyDeleteEvent(post_img_comments_replies$);
        });
        //
        post_img_lightbox$.load(function () {
            post_img_proxy$.show();
            post_img$.parent().colorbox(Wall.ImageView.colorboxSettings);
            post_img_proxy$.hide();
            //
            Wall.ImageView.bindCommentDeleteEvent(post, annotations, post_img_comments$);
            Wall.ImageView.bindCommentReplyDeleteEvent(post_img_comments_replies$);
        });
        post_img_lightbox$.attr('src', '/user_content/post_images/' + post.image_url);
        if ($.inArray(ext, {0: 'jpg', 1: 'jpeg', 2: 'png', 3: 'gif'}) !== -1) {
            href = '/user_content/post_images/' + post.image_url;
            img = new Image();
            img.src = href;
            img.onload = function () {
                if (img.width <= 400 || img.height <= 400) {
                    post_img_lightbox$.css('margin', '20px');
                }
            };
        }
        post$.find('.post-comment-btn').click(function () {
            post_img$.parent().click();
        });
    },
    /**
     * Setup a lightbox for a normal post (non-image)
     * @param post
     * @param post$
     */
    setupLightboxNormal: function (post, post$) {
        'use strict';
        post$.find('.post-comment-btn').colorbox(Wall.ImageView.colorboxSettings);
        var post_comments$ = post$.find('.post-image-wrapper, .post-normal-wrapper').find('.pcomment'),
            post_comments_replies$ = post_comments$.find('.pcomment-reply');
        Wall.ImageView.bindCommentDeleteNormalEvent(post_comments$);
        Wall.ImageView.bindCommentReplyDeleteEvent(post_comments_replies$);
    },
    /**
     * Is a comment with an annotation/mark
     */
    saveMark: function (annotation) {
        'use strict';
        var post_id = $('#colorbox .post-image-view').data('post-id'),
            new_annotation = {shapes: annotation.shapes, text: annotation.text};
        // In upload preview mode, don't save the marks as they are added (will be saved on post submit)
        if (!post_id) {
            return;
        }
        $.ajax({type: 'POST', url: '/posts/mark', data: {'post-id': post_id, body: new_annotation.text, annotation: JSON.stringify(new_annotation)}, success: function (pcomment_id) {
            var template = Handlebars.compile($("#pcomment-partial").html()),
                data = {id: pcomment_id, name: CurrUser.name, fb_user: CurrUser.fb_user, body: do_rtl_if_heb(new_annotation.text), time_ago: 'Just now'};
            annotation.comment_id = pcomment_id;
            Wall.ImageView.annotations[post_id].push(annotation);
            $('#colorbox .post-image-comments').append(template(data)).children(':last').hide().slideDown('normal', function () {
                $('.post-image-comments', '#colorbox').animate({
                    scrollTop: $('.post-image-comments', '#colorbox')[0].scrollHeight
                }, 500);
            });
        }});
    },
    /**
     * A regular comment, without an annotation/mark
     */
    handleAddComment: function () {
        'use strict';
        $('body').on('keydown', '#colorbox .post-comment-body', function (e) {
            // Enter pressed
            if (e.keyCode === 13 && e.ctrlKey) {
                var textarea$ = $(this);
                e.stopPropagation();
                Wall.Post.commentSubmit(textarea$);
            }
        }).on('click', '#colorbox .comment button, #colorbox #comment_wp button', function () {
            var textarea$ = $(this).siblings('textarea');
            Wall.Post.commentSubmit(textarea$);
        });
    },
    /**
     * Handle deleting image view comments & their annotations
     * @param post
     * @param annotations
     * @param post_img_comments$
     */
    bindCommentDeleteEvent: function (post, annotations, post_img_comments$) {
        'use strict';
        post_img_comments$.each(function () {
            var post_id = $(this).parents('div[data-post-id]').data('post-id'),
                user_id = $(this).data('user-id'),
                comment_id = $(this).attr('id');

            if (user_id === CurrUser.id || (CurrUser.type === 'tutor' && $.inArray(post_id, posts) !== -1)) {
                $(this).closable(function () {
                    var tutor = 0;
                    // Remove the linked annotation from the canvas
                    $.each(annotations, function (index, annotation) {
                        if (annotation && annotation.comment_id === comment_id) {
                            anno.removeAnnotation(annotation);
                        }
                    });
                    // Remove the annotation from annos array
                    $.each(Wall.ImageView.annotations[post.id], function (index, annotation) {
                        if (annotation && annotation.comment_id === comment_id) {
                            delete Wall.ImageView.annotations[post.id][index];
                        }
                    });
                    // Remove comment and related data from DB
                    if (CurrUser.type === 'tutor' && $.inArray(post_id, posts) !== -1) { tutor = 1; }
                    $.ajax({type: 'DELETE', url: '/posts/comment', data: {comment_id: comment_id, tutor: tutor}});
                });
            }
        });
    },
    bindCommentDeleteNormalEvent: function (post_img_comments$) {
        'use strict';
        post_img_comments$.each(function () {
            var tutor = 0,
                post_id = $(this).parents('div[data-post-id]').data('post-id'),
                user_id = $(this).data('user-id'),
                comment_id = $(this).attr('id');
            if (user_id === CurrUser.id || (CurrUser.type === 'tutor' && $.inArray(post_id, posts) !== -1)) {
                $(this).closable(function () {
                    // Remove comment and related data from DB
                    if ((CurrUser.type === 'tutor' && $.inArray(post_id, posts) !== -1)) { tutor = 1; }
                    $.ajax({type: 'DELETE', url: '/posts/comment', data: {comment_id: comment_id, regular: true, tutor: tutor}});
                });
            }
        });
    },
    /**
     * Handle deleting a comment reply
     */
    bindCommentReplyDeleteEvent: function (post_comments_replies$) {
        'use strict';
        post_comments_replies$.each(function () {
            var tutor = 0,
                post_id = $(this).parents('div[data-post-id]').data('post-id'),
                user_id = $(this).data('user-id'),
                comment_id = $(this).attr('id');
            if (user_id === CurrUser.id || (CurrUser.type === 'tutor' && $.inArray(post_id, posts) !== -1)) {
                $(this).closable(function () {
                    // Remove comment and related data from DB
                    if (CurrUser.type === 'tutor' && $.inArray(post_id, posts) !== -1) { tutor = 1; }
                    $.ajax({type: 'DELETE', url: '/posts/commentreply', data: {comment_id: comment_id, tutor: tutor}});
                });
            }
        });
    },
    sendPhoto: function (str, id) {
        'use strict';
        var data = new FormData();
        data.append('upload', str.files[0]);
        $.ajax({
            type: "POST",
            url: "/posts/save-comment-image",
            data: data,
            dataType: 'json',
            contentType: false,
            processData: false,
            beforeSend: function () {
                $("#preloader", '#colorbox').css('display', 'block');
            },
            error: function () {
                $("#preloader", '#colorbox').css('display', 'none');
            }
        }).done(function (html) {
            var container,
                doneData,
                body = $("#post-comment-" + id, '#colorbox').val(),
                template = Handlebars.compile($("#comment-with-picture").html());
            if ($("#post-comment-" + id, '#colorbox').hasClass('post-comment-body-image')) {
                container = 'image';
            } else {
                container = 'normal';
            }
            $("#preloader", '#colorbox').css('display', 'none');
            doneData = {path: html.success, name: html.success, str: body, id: id, type: container};
            $(".post-normal-comments-wrapper", "#colorbox").append(template(doneData)).show().children('.comment').hide();
            $(".post-image-comments-wrapper", "#colorbox").append(template(doneData)).show().children('.comment').hide();
            $("#img-" + id, '#colorbox').val(html.success);
            $("#post-comment-" + id, '#colorbox').val(body);
        });
    },
    deletePhoto: function (id, success) {
        'use strict';
        success = success !== 'undefined' ? success : 0;
        var data,
            container,
            body = $("#post-comment-" + id, '#colorbox').val(),
            template = Handlebars.compile($("#comment-without-picture").html());
        if ($("#post-comment-" + id, '#colorbox').hasClass('post-comment-body-image')) {
            container = 'image';
        } else {
            container = 'normal';
        }
        if (success === 1) {
            data = {str: '', id: id, type: container};
            $("#post-comment-" + id, '#colorbox').val('');
        } else {
            data = {str: body, id: id, type: container};
            $("#post-comment-" + id, '#colorbox').val(body);
        }
        $(".post-normal-comments-wrapper", "#colorbox").append(template(data)).show().children('#comment_wp').hide();
        $(".post-image-comments-wrapper", "#colorbox").append(template(data)).show().children('#comment_wp').hide();
        $("#img-" + id, '#colorbox').val('');
    },
    openFromUrl: function () {
        'use strict';
        //#post-id-123
        var post_type = window.location.hash,
            post_type_info = post_type.split('-'),
            post$ = $('#' + post_type_info[2]);
        if (post$ !== 'undefined') {
            $('.post-comment-btn', post$).click();
        }
    }
};