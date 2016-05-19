$(function () {
    Handlebars.registerPartial("pcomment", $("#pcomment-partial").html());
    Handlebars.registerPartial("pcomment_wall_up", $("#pcomment-wall-partial-up").html());
    Handlebars.registerPartial("pcomment_wall_down", $("#pcomment-wall-partial-down").html());
    Handlebars.registerPartial("pcomment_reply", $("#pcomment-reply-partial").html());
    Handlebars.registerPartial("lightbox_author", $("#lightbox-author-partial").html());

    // Wall Post Adder
    $('#post-add').click(Wall.PostAdder.add);
    var $body = $('body');

    // Post Like Button
    $body.on('click', '.post-like-btn', Wall.Post.like);

    // Lock / unlock post
    $body.on('click', '.post-lock-unlock-btn', Wall.Post.lockUnlock);

    // Mark as answer
    $body.on('click', '.mark-as-answer-btn', Wall.Post.markAsAnswer);

    // Post Comment Like Button
    $body.on('click', '.pcomment-like-btn', Wall.Post.commentLike);

    // Post Comment Reply Button
    $body.on('click', '.pcomment-reply-btn', Wall.Post.commentReply);

    // Post Comment Reply Like Button
    $body.on('click', '.pcomment-reply-like-btn', Wall.Post.commentReplyLike);

    // Display all group posts
    Wall.displayAll();
    Wall.ImageView.init();

    //preload img in sidebar
    preload(['/assets/img/sidebar/annotation_animation.gif']);
    // Close welcome message
    $('#gw-close').click(function() {
        $('#grid-welcome').hide(function() { $(this).remove() });
        $.ajax({type: 'GET', url: '/user/seen_welcome'});
    });

    // Auto RTL/LTR to inputs
    $body.on('keyup', ':text, textarea', function() {
        // If begins with a hebrew letter
        if (is_str_contains_hebrew($(this).val())) {
            $(this).css('direction', 'rtl');
        } else {
            $(this).css('direction', 'ltr');
        }
    });

    $('#anno_guide').click(function(){
        if ($.trim($('#anno_guide').html()) == '<img src="/assets/img/sidebar/annotation_guide.png">'){
            $('#anno_guide').html('<img src="/assets/img/sidebar/annotation_animation.gif" style="border-radius:7px">')
        }else{
            $('#anno_guide').html('<img src="/assets/img/sidebar/annotation_guide.png">');
        }
    });

    // Open the post on clicking the clickable area
    $body.on('click', '.post-wrapper', function() {
        $(this).parents('.wall-post').find('.post-comment-btn').click();
    });

    if (num_workspaces < 2) {
        $('#sidebar-wrapper').addClass('has-one-workspace');
    }
});

var Wall = Wall || {};

Wall.displayAll = function() {
    var data = {}, url = '/groups';
    data.user_id = CurrUser.id;
    data.group = location.pathname.replace('/ws/', '');
    // Show only a single post - for dev purposes
    if (window.env === 'dev' && window.post_id) {
        data.post_id = window.post_id;
    }
    LoadingEffect.apply($('#my-groups'));
    $.ajax({type: 'GET', url: url, data: data, success: function(posts) {
        LoadingEffect.cancel($('#my-groups'));
        $('#mega-adder').css('display', 'inline-block');
        $.each(posts, function(key, post) {
            Wall.appendPost(post, $('#my-groups'));
        });
        Wall.initDynamicGrid($('#my-groups'));
        // Timeout hack to make image posts work (race condition...)
        // Even 0 as timeout would work
        setTimeout(Wall.ImageView.openFromUrl, 500);
    }});
};

Wall.appendPost = function(post, content$) {
    var self = Wall.appendPost;
    if (!self.template) { self.template = Handlebars.compile($("#post-template").html()); }
    post = Wall.PostAdder.transformPostObj(post);
    var post$ = $(self.template(post));
    if (post.user_id == CurrUser.id) {
        post$.find('.post-actions').deleteablePost();
    }
    content$.append(post$);
    if (post.has_image && !post.non_img_attachment) {
        // Image view post
        Wall.ImageView.setupLightbox(post, post$);
    } else {
        // Normal post (including attachments)
        Wall.ImageView.setupLightboxNormal(post, post$);
    }
};

Wall.initDynamicGrid = function($container) {
    var id = $container.attr('id');
    Wall.iso = new Isotope( '#' + id, {
        itemSelector: '.wall-post',
        filter: '*',
        getSortData: {
            number: function(elem) {
                return parseInt($(elem).data('post-id'), 10);
            }
        },
        sortBy: 'number',
        sortAscending: false
    });
};