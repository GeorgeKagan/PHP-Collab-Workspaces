$(function () {
    // COLORBOX IMAGE VIEW ON WINDOW RESIZE
    Responsive.ImageView.init();
});

var Responsive = {
    ImageView: {
        init: function() {
            var colorbox_timeout = null;
            var anno_timeout = null;
            // Keep track of width/height to prevent resize event when scrolling on mobiles
            var width = $(window).width();
            var height = $(window).height();
            $(window).on('resize', function() {
                clearTimeout(colorbox_timeout);
                clearTimeout(anno_timeout);
                if (!$('#colorbox').is(':visible') || ($(window).width() == width && $(window).height() == height)) {
                    return;
                }
                width = $(window).width();
                height = $(window).height();
                $('#colorbox, #cboxOverlay').addClass('wait');
                colorbox_timeout = setTimeout(function() {
                    Responsive.ImageView.colorbox();
                    anno_timeout = setTimeout(function() {
                        Responsive.ImageView.anno();
                    }, 1000);
                }, 500);
            });
        },
        adjust: function() {
            Responsive.ImageView.colorbox();
            Responsive.ImageView.anno();
        },
        colorbox: function() {
            var wrapper$ = $('.post-image-comments-wrapper', '#colorbox');
            var settings = jQuery.extend({}, Wall.ImageView.colorboxSettings);
            var non_img_width = 600;
            if (wrapper$.length == 0) {
                // Normal post
                settings.width = non_img_width;
                $('#colorbox').colorbox.resize(settings);
                wrapper$ = $('.post-normal-comments-wrapper', '#colorbox');
                var children$ = wrapper$.children(':not(.post-normal-comments)');
                var cls = '.post-normal-comment';
            } else {
                // Image post
                wrapper$.removeClass('full-width');
                if (!$('.lightbox-post-image', '#colorbox').length) {
                    // A zip or a non-img file
                    settings.width = non_img_width;
                }
                $('#colorbox').colorbox.resize(settings);
                var children$ = wrapper$.children(':not(.post-image-comments)');
                var cls = '.post-image-comment';
            }
            var height_sum = 0;
            children$.each(function(key, elem) {
                height_sum += $(elem).outerHeight(true);
            });
            $(cls, '#colorbox').css('height', wrapper$.height() - height_sum);
            // comments div height
            var nch_sum = 0;
            wrapper$.find('> h2, > .comment:visible').map(function() { nch_sum += $(this).outerHeight(); });
            var comments_height = wrapper$.height() - nch_sum - 50;
            $('.post-image-comments, .post-normal-comments', '#colorbox').height(comments_height);
            // Reduce image's width by the fixed width of the comments to the right
            var img_wrapper_width = $('.post-image-view', '#colorbox').outerWidth() - Wall.ImageView.getRightPaneWidth();
            $('.lightbox-post-image-wrapper', '#colorbox').css('width', img_wrapper_width);
        },
        anno: function() {
            var post_id = $('.post-image-view', '#colorbox').data('post-id');
            var img = $('.lightbox-post-image', '#colorbox')[0];
            if (post_id && img) {
                anno.destroy();
                anno.makeAnnotatable(img);
                // Add annotations
                $.each(Wall.ImageView.annotations[post_id], function(key, annotation) {
                    if (!annotation) { return; }
                    annotation['text'] = do_rtl_if_heb(annotation['text']);
                    annotation['src'] = img.src;
                    annotation['editable'] = false;
                    anno.addAnnotation(annotation);
                });
                // align anno div x axis
                $('.annotorious-annotationlayer', '#colorbox').height($('.lightbox-post-image', '#colorbox').height());
                $('.annotorious-annotationlayer', '#colorbox').width($('.lightbox-post-image', '#colorbox').width());
                // align anno div y axis
                var margin_top = ($('.lightbox-post-image-wrapper', '#colorbox').height() - $('.annotorious-annotationlayer', '#colorbox').height()) / 2;
                $('.annotorious-annotationlayer', '#colorbox').css('marginTop', margin_top);
            }
            $('#colorbox, #cboxOverlay').removeClass('wait');
        }
    }
};