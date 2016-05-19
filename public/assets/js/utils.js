$.fn.increment = function() {
    var val = parseInt($(this).text()) || 0;
    $(this).text(val + 1);
};

$.fn.decrement = function() {
    var val = parseInt($(this).text()) || 0;
    $(this).text(val - 1);
};

/**
 * Add a close icon to an entity
 * @param close_callback
 */
$.fn.closable = function(close_callback, anim_end_callback) {
    var close$ = $('<i class="fa fa-times btn-close"></i>');
    var that = this;
    close$.click(function() {
        Utils.swal.areYouSure(function() {
            $(that).removeClass('close-btn-wrapper').fadeTo('normal', 0.5, function() {
                $(that).slideUp('normal', function() {
                    $(that).remove();
                    anim_end_callback && anim_end_callback();
                });
            });
            close_callback && close_callback.call(that);
        });
    });
    $(this).addClass('close-btn-wrapper').append(close$);
};

/**
 * Deletes a post from the Masonry post grid (client+server)
 */
$.fn.deleteablePost = function() {
    var $close = $('<i class="fa fa-times btn-close"></i>');
    var $post = $(this).parents('.wall-post');
    $(this).addClass('close-btn-wrapper').append($close);
    $close.click(function() {
        Utils.swal.areYouSure(function() {
            Wall.iso.remove($post[0]);
            Wall.iso.layout();
            $.ajax({type: 'DELETE', url: '/posts/post', data: {post_id: $post.attr('id')}});
        });
    });
};

var Utils = {
    isValidUrl: function(url) {
        var RegExp = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;
        if (RegExp.test(url)) {
            return true;
        }
        return false;
    },
    rgb2hex: function(rgb) {
        rgb = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
        function hex(x) {
            return ("0" + parseInt(x).toString(16)).slice(-2);
        }
        return "#" + hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]);
    },
    /**
     * SweetAlert wrappers
     */
    swal: {
        areYouSure: function(callback) {
            swal({
                title: '',
                text: 'Are you sure?',
                type: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                confirmButtonColor: '#DD6B55'
            }, callback);
        },
        fillRequired: function() {
            swal({
                title: "",
                text: "Please fill required fields",
                type: "error",
                confirmButtonText: "Close"
            });
        },
        success: function(){
            swal({
                title: "Thank you!",
                text: "",
                type: "success",
                confirmButtonText: "OK"
            });
        },
        withoutAttach: function(formData){
            swal({
                title: "",
                text: "You can add an image (and add notes on it) to your question to explain yourself better",
                type: "warning",
                showCancelButton: true,
                confirmButtonText: "Ask",
                cancelButtonText: "Let me add an image",
                closeOnConfirm: false,
                closeOnCancel: true
            },
                function(isConfirm){
                    if (isConfirm) {
                        Wall.PostAdder.saveAndReload(formData, null);
                    }
                }
            );
        }
    },
    isAndroidApp: function() {
        return window.user_agent.indexOf('app-andorid-') > -1;
    }
};


/**
 * Truncate a string, optionally taking word boundaries into account.
 * @param n
 * @param useWordBoundary
 * @returns {string}
 */
String.prototype.trunc =
    function(n,useWordBoundary){
        var toLong = this.length>n,
            s_ = toLong ? this.substr(0,n-1) : this;
        s_ = useWordBoundary && toLong ? s_.substr(0,s_.lastIndexOf(' ')) : s_;
        return  toLong ? s_ + '&hellip;' : s_;
    };

Object.size = function(obj) { var size = 0, key; for (key in obj) { if (obj.hasOwnProperty(key)) size++; } return size; };

function is_str_contains_hebrew(str) {
    return new RegExp(/[\u0590-\u05FF]/).test(str);
}

function do_rtl_if_heb(str) {
    if (is_str_contains_hebrew(str)) {
        str = '<div style="direction:rtl">' + str + '</div>';
    }
    return str.replace(/\n/g, '<br>');
}