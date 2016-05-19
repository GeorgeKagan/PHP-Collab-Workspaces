$(function() {
    var $post_body = $('#post-body');
    $post_body.on('input click', Wall.PostAdder.Upload.showOpenState);
    $post_body.on('keydown', function(e) {
        // ctrl+enter
        if ($.trim($post_body.val()) !== '' && e.keyCode == 13 && e.ctrlKey) {
            $('#post-add').click();
            return false;
        }
        // esc
        if ($('#post-form-add').hasClass('open') && e.keyCode == 27) {
            $('#pa-cancel').click();
            return false;
        }
    });
    $post_body.autogrow({animate: false});
    var $mega_adder = $('#mega-adder');
    $mega_adder.on('click', '#pa-cancel', function() {
        Utils.swal.areYouSure(Wall.PostAdder.Upload.showClosedState);
    });
    var image_types = ['image/jpeg', 'image/png', 'image/gif'];
    if ($('form#pa-upload-area').length) {
        Dropzone.autoDiscover = false;
        Wall.PostAdder.myDropzone = new Dropzone("form#pa-upload-area", {
            paramName: "file",
            maxFilesize: Wall.PostAdder.maxFilesize,
            maxFiles: Wall.PostAdder.maxFiles,
            addRemoveLinks: true,
            uploadMultiple: true,
            autoProcessQueue: true,
            acceptedFiles: Wall.PostAdder.acceptedFiles,
            init: function() {
                this.on('addedfile', function(file) {
                    setTimeout(function() {
                        // Detect a single image file & allow it to be annotatable
                        if (Wall.PostAdder.myDropzone.files.length === 1 && $.inArray(Wall.PostAdder.myDropzone.files[0].type, image_types) > -1) {
                            Wall.PostAdder.State.hasImageAttachment = true;
                            Whiteboard.handleImage(file);
                        }
                    }, 500);
                });
                this.on('error', function(file) {
                    if (file.status === 'canceled') { return; }
                    // Display accepted file tpyes & max file size on error
                    if (!$('#pa-errors').length) {
                        $('<div id="pa-errors">' +
                            '<div class="label label-danger">' + 'Accepted files: ' + Wall.PostAdder.acceptedFiles.replace(/\./g, ' ') + '</div>' +
                            '<div class="label label-danger">' + 'Max files / filesize: ' + Wall.PostAdder.maxFiles + ' / ' + Wall.PostAdder.maxFilesize + ' MiB' + '</div>' +
                        '</div>').appendTo('#post-form-add').hide(0).slideDown();
                    }
                });
            }
        });
    }
});

var Wall = Wall || {};

Wall.PostAdder = {
    acceptedFiles: '.jpg,.jpeg,.png,.gif,.zip,.7zip,.rar,.pdf,.doc,.docx,.xls,.xlsx,.txt,.mp3,.wav,.wma',
    maxFilesize: 5,
    maxFiles: 10,
    add: function() {
        var formData = new FormData();
        var body = $.trim($('#post-body').val());
        if (!body) {
            $('#post-body').focus();
            return Utils.swal.fillRequired();
        }
        formData.append('post-body', body);
        formData.append('group-name', location.pathname.replace('/ws/', ''));
        if (Wall.PostAdder.State.hasImageAttachment) {
            var annos = anno.getAnnotations($('#post-add-upload-preview img').attr('src'));
            var annotations = [];
            // We don't need all props
            $.each(annos, function(key, anno) {
                annotations.push({shapes: anno.shapes, text: anno.text});
            });
            formData.append('post-has-image-attachment', true);
            formData.append('post-image-annotations', JSON.stringify(annotations));
        }
        var images = Wall.PostAdder.myDropzone.getAcceptedFiles();
        var attach_count = images.length;
        if (attach_count < 1 ) {
            return Utils.swal.withoutAttach(formData);
        }
        if (attach_count > 1) {
            for (var i = 0; i < attach_count; i++) {
                formData.append('post-upload-' + i, images[i]);
            }
            formData.append('post-attachment-count', attach_count);
            formData.append('post-has-image-attachment', true);
        } else if (attach_count) {
            formData.append('post-upload', images[0]);
            formData.append('post-has-image-attachment', true);
        }
        Wall.PostAdder.saveAndReload(formData, null);
    },
    saveAndReload: function(formData, callback) {
        $('#post-add').attr('disabled', true).append('<div class="ma-spinner"><i class="fa-li fa fa-spinner fa-spin"></i></div>');
        $.ajax({
            url: '/posts/post',
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            type: 'POST',
            success: function(post){
                var source = $("#post-template").html();
                var template = Handlebars.compile(source);
                post[0] = Wall.PostAdder.transformPostObj(post[0]);
                var $new_post = $(template(post[0]));
                callback && callback();
                $('#post-add').attr('disabled', false).find('.ma-spinner').remove();
                Wall.PostAdder.Upload.showClosedState();
                Wall.PostAdder.State.hasImageAttachment = false;
                if (post[0].has_image && !post[0].non_img_attachment) {
                    // Image view post
                    Wall.ImageView.setupLightbox(post[0], $new_post);
                } else {
                    // Normal post (including attachments)
                    Wall.ImageView.setupLightboxNormal(post[0], $new_post);
                }
                Wall.iso.insert($new_post[0]);
                return Utils.swal.success();
            }
        });
    },
    hideNonImportantUi: function() {
        $('#sidebar-wrapper, #chat-wrapper').addClass('hidden');
        $('#main-content').addClass('full-width');
    },
    unhideNonImportantUi: function() {
        $('#sidebar-wrapper, #chat-wrapper').removeClass('hidden');
        $('#main-content').removeClass('full-width');
    },
    /**
     * Transforms a Post object according to dynamic logic
     * @param post
     * @returns {*}
     */
    transformPostObj: function(post) {
        // Check if attachment is img or text-based
        if (post.image_url) {
            var ext = post.image_url.split('.').pop().toLowerCase();
            post.attachment_type = ext;
            if ($.inArray(ext, ['png', 'jpg', 'jpeg', 'gif']) === -1) {
                post.non_img_attachment = true;
            }
        }
        return post;
    }
};

Wall.PostAdder.State = {
    hasImageAttachment: false
};

Wall.PostAdder.Upload = {
    showOpenState: function() {
        $('#post-form-add').removeClass('open closed').addClass('open');
    },
    showClosedState: function() {
        $('#mega-adder').removeClass('annotating');
        $('#post-form-add').removeClass('open closed').addClass('closed');
        $('#post-body').val('').removeAttr('style');
        $('#post-add-upload-preview').html('');
        Wall.PostAdder.State.hasImageAttachment = false;
        Wall.PostAdder.myDropzone.removeAllFiles(true);
        Wall.PostAdder.unhideNonImportantUi();
        Wall.iso.layout();
    }
};