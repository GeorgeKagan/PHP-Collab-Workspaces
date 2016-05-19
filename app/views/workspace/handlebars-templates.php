<!-- Homepage posts, comments & comment replies -->

<script id="post-template" type="text/x-handlebars-template">
    <div id="{{id}}" data-post-id="{{id}}" class="wall-post media clearfix {{#unless has_image}}no-img{{/unless}} {{#if answered}}answered{{else}}{{#if new}}new{{/if}}{{/if}}">
        {{#if has_image}}
            <div class="post-image-wrapper">

                {{#if non_img_attachment}}
                    <div class="post-attach-thumb pat-{{attachment_type}}">
                        <!--<a target="_blank" href="{{image_url}}">-->
                        <a href="/posts/get-attach?path={{image_url}}">
                            <h2>{{attachment_type}}</h2>
                            <div>No preview availbale<br>
                            Click to download</div>
                        </a>
                    </div>
                {{else}}
                    <a class="post-image-a" href="#post-image-{{id}}" rel="gallery">
                        <img class="post-image" src="/imagecache/medium/{{image_url}}" />
                    </a>
                {{/if}}

                <div class="post-image-proxy" style="display:none">
                    <div id="post-image-{{id}}" class="post-image-view
                            {{#if can_mark_answer}}can-mark-answer{{/if}}
                            {{#if non_img_attachment}}post-non-img-view{{/if}}" data-post-id="{{id}}" style="height:100%">
                        {{#unless non_img_attachment}}
                        <div class="lightbox-post-image-wrapper" style="float:left;padding:0 0 0 0">
                            <img class="lightbox-post-image" />
                        </div>
                        {{/unless}}
                        <div class="post-image-comments-wrapper">
                            <h2 class="clearfix">
                                {{>lightbox_author}}
                                {{{body}}}
                            </h2>
                            <div class="post-image-comments">
                                {{#each comments}}
                                {{> pcomment}}
                                {{/each}}
                            </div>
                            {{#not_android}}
                            <div class="comment clearfix">
                                <textarea class="post-comment-body post-comment-body-image form-control" id="post-comment-{{id}}" placeholder="Ctrl+Enter to comment"></textarea>
                                <button class="green-add-btn btn pull-right">Send</button>
                                <div class="fileform" id="filebutton">
                                    <div class="selectbutton"><img src="/assets/img/foto.png" style="height: 20px; width: 20px;"></div>
                                    <form>
                                        <input id="upload" type="file" name="upload" title="Add Image" value='' onchange="Wall.ImageView.sendPhoto(this,{{id}});"/>
                                    </form>
                                </div>
                                <div style="display: none;" id="preloader">
                                    <img src="/assets/img/load.gif">
                                </div>
                            </div>
                            {{/not_android}}
                        </div>
                    </div>
                </div>
            </div>
        {{else}}
            <div class="post-normal-wrapper">
                <div class="post-normal-proxy" style="display:none">
                    <div id="post-normal-{{id}}" class="post-normal-view {{#if can_mark_answer}}can-mark-answer{{/if}}" data-post-id="{{id}}" style="height:100%">
                        <div class="post-normal-comments-wrapper">
                            <h2 class="clearfix">
                                {{>lightbox_author}}
                                {{{body}}}
                            </h2>
                            <div class="post-normal-comments">
                                {{#each comments}}
                                {{> pcomment}}
                                {{/each}}
                            </div>
                            {{#not_android}}
                            <div class="comment clearfix">
                                <textarea class="post-comment-body post-comment-body-normal form-control" id="post-comment-{{id}}" placeholder="Ctrl+Enter to comment"></textarea>
                                <button class="green-add-btn btn pull-right">Send</button>
                                <div class="fileform" id="filebutton">
                                    <div class="selectbutton"><img src="/assets/img/foto.png" style="height: 20px; width: 20px;"></div>
                                    <form>
                                        <input id="upload" type="file" name="upload" title="Add Image" value='' onchange="Wall.ImageView.sendPhoto(this,{{id}});"/>
                                    </form>
                                </div>
                                <div style="display: none;" id="preloader">
                                    <img src="/assets/img/load.gif">
                                </div>
                            </div>
                            {{/not_android}}
                        </div>
                    </div>
                </div>
            </div>
        {{/if}}

        <div class="post-wrapper clearfix">
            {{#if answered}}
                <div class="answered-icon"></div>
            {{else}}
                {{#if new}}
                    <div class="new-icon"></div>
                {{/if}}
            {{/if}}

            <div class="clearfix">
                <span class="post-avatar pull-left img-circle">
                    <img class="media-object img-circle {{type}}" src="{{#if fb_user}}http://graph.facebook.com/{{fb_user}}/picture{{else}}/assets/img/default-avatar.jpg{{/if}}">
                </span>
                <div class="post-date-user">
                    <div>{{username}}</div>
                    <span class="time-ago"><i class="fa fa-2x fa-clock-o"></i> {{{time_ago}}}</span>
                </div>
            </div>

            {{#if comment_count}}
                <div class="post-body" style="border-bottom: 1px solid #EEE; padding-bottom: 10px;">{{{body}}}</div>
            {{else}}
                <div class="post-body">{{{body}}}</div>
            {{/if}}

            {{#if has_attachment}}
                <div class="post-attachment well">
                    <a href="{{attach_url}}" class="post-attach-thumb"><img height="157" src="{{attach_img_url}}" /></a>
                    <a href="{{attach_url}}">{{attach_title}}</a><br />
                    <small>{{attach_url}}</small><br />
                    <span>{{attach_desc}}</span>
                </div>
            {{/if}}

            {{#if comment_count}}
            <div class="post-comments">
                {{#each display_comments}}
                {{#if @first}}
                    {{> pcomment_wall_up}}
                {{else}}
                    {{> pcomment_wall_down}}
                {{/if}}
                {{/each}}
            </div>
            {{/if}}
        </div>

        <div class="post-actions clearfix">
            <hr>
            <div class="pull-left">
                {{#if has_image}}
                <a class="post-comment-btn" href="#post-image-{{id}}" rel="gallery">
                    <span class="bubble-icon"><span class="img-circle post-comment-count {{#unless comment_count}}zero{{/unless}}">{{comment_count}}</span>
                    </span>{{pluralize comment_count "Comment" "Comments"}}
                </a>
                {{else}}
                <a class="post-comment-btn" href="#post-normal-{{id}}" rel="normal-posts">
                    <span class="bubble-icon"><span class="img-circle post-comment-count {{#unless comment_count}}zero{{/unless}}">{{comment_count}}</span>
                    </span>{{pluralize comment_count "Comment" "Comments"}}
                </a>
                {{/if}}
            </div>
            <div class="pull-right">
                <a class="post-like-btn"><span class="star-icon {{#if me_likey}}liked{{/if}}"></span></a>
                <span class="post-like-count">{{likes}}</span> {{pluralize likes "Like" "Likes"}} &nbsp;

                {{#if can_be_locked}}
                    {{#if i_am_owner}}
                    <a class="post-lock-unlock-btn"><span class="post-lock-status {{#if locked}}locked{{else}}unlocked{{/if}}-icon"></span></a>
                    {{/if}}
                {{/if}}
            </div>
        </div>

    </div>
</script>

<script id="pcomment-partial" type="text/x-handlebars-template">
    <div id="{{id}}" data-comment-id="{{id}}" data-user-id="{{user_id}}" class="pcomment clearfix">
        <div class="pcomment-wrap {{#if is_answer}}answered{{/if}}">
            <div class="user-wrap">
                <a href="javascript:return false" class="fake-a"><img class="img-circle {{type}}" src="{{#if fb_user}}http://graph.facebook.com/{{fb_user}}/picture{{else}}/assets/img/default-avatar.jpg{{/if}}" /></a>
                <a href="javascript:return false" class="fake-a">{{name}}</a><span class="correctly-answered">&nbsp; Answered this correctly!</span>
                &nbsp;<span class="time-ago"><i class="fa fa-clock-o"></i> {{{time_ago}}}</span>
            </div>

            <div class="mark-as-answer" title="Mark as answer">
                <a href="#" class="mark-as-answer-btn" data-comment-id="{{id}}">
                    <img src="/assets/img/mark_as_answer.png" width="26" height="21"/>
                </a>
            </div>

            <div class="comment-body-wrap">
                {{{body}}}
                {{#if img}}
                <br>
                <a href="#pop-{{id}}" class="pop-up"><img src="/user_content/comment_images/{{img}}" width="30%" ></a>
                <div id="pop-{{id}}" style="display: none;">
                    <img src="/user_content/comment_images/{{img}}" id="comment-img">
                </div>
                {{/if}}
            </div>

            <div class="pcomment-action-wrap">
                <a href="#" class="pcomment-like-btn">{{#if is_liked_by_me}}Unlike{{else}}Like{{/if}}</a> ·
                <a href="#" class="pcomment-reply-btn">Reply</a>
                <span class="pcomment-like-info" style="{{#unless likes}}display:none{{/unless}}">
                     · <i class="fa fa-thumbs-o-up"></i> <span class="pcomment-like-count">{{likes}}</span>
                </span>
            </div>

            <div class="answered-icon"></div>
        </div>
        <div class="pcomment-replies">{{#each replies}}{{> pcomment_reply}}{{/each}}</div>
    </div>
</script>

<script id="pcomment-reply-form" type="text/x-handlebars-template">
    <div class="pcomment-reply-form clearfix">
        <textarea class="post-reply-body form-control" placeholder="Ctrl+Enter to reply"></textarea>
        <button class="green-add-btn btn pull-right">Send</button>
    </div>
</script>

<script id="pcomment-wall-partial-up" type="text/x-handlebars-template">
    <div {{#comment_has_anno annotations id}}class="comment_backlight_up"{{/comment_has_anno}}>
    <div id="{{id}}" class="pcomment clearfix" {{#comment_has_anno annotations id}}style="width:90%;"{{/comment_has_anno}}>
        <a href="javascript:return false" class="fake-a">
            <img class="img-circle {{type}}" src="{{#if fb_user}}http://graph.facebook.com/{{fb_user}}/picture{{else}}/assets/img/default-avatar.jpg{{/if}}" />
        </a>
        <a href="javascript:return false" class="fake-a">{{name}}</a>
        &nbsp;<span class="time-ago"><i class="fa fa-clock-o"></i> {{{time_ago}}}</span>
        <div {{#if img}} class="pc-attachment-icon" {{/if}}>
            {{{body}}}
        </div>
    </div>
    </div>
</script>

<script id="pcomment-wall-partial-down" type="text/x-handlebars-template">
    <div {{#comment_has_anno annotations id}}class="comment_backlight_down"{{/comment_has_anno}}>
    <div id="{{id}}" class="pcomment clearfix" {{#comment_has_anno annotations id}}style="width:90%;"{{/comment_has_anno}} style="margin-top: 25px;">
        <a href="javascript:return false" class="fake-a">
            <img class="img-circle {{type}}" src="{{#if fb_user}}http://graph.facebook.com/{{fb_user}}/picture{{else}}/assets/img/default-avatar.jpg{{/if}}" />
        </a>
        <a href="javascript:return false" class="fake-a">{{name}}</a>
        &nbsp;<span class="time-ago"><i class="fa fa-clock-o"></i> {{{time_ago}}}</span>
        <div {{#if img}} class="pc-attachment-icon" {{/if}}>
            {{{body}}}
        </div>
    </div>
    </div>
</script>

<script id="pcomment-reply-partial" type="text/x-handlebars-template">
    <div id="{{id}}" data-user-id="{{user_id}}" class="pcomment-reply pcomment-wrap">
        <div class="user-wrap">
            <a href="javascript:return false" class="fake-a">
                <img class="img-circle {{type}}" src="{{#if fb_user}}http://graph.facebook.com/{{fb_user}}/picture{{else}}/assets/img/default-avatar.jpg{{/if}}" />
            </a>
            <a href="javascript:return false" class="fake-a">{{name}}</a>
            &nbsp;<span class="time-ago"><i class="fa fa-clock-o"></i> {{{time_ago}}}</span>
        </div>

        <div class="comment-body-wrap">
            {{{body}}}
        </div>

        <div class="pcomment-action-wrap">
            <a href="#" class="pcomment-reply-like-btn">{{#if is_liked_by_me}}Unlike{{else}}Like{{/if}}</a>
            <span class="pcomment-reply-like-info" style="{{#unless likes}}display:none{{/unless}}">
                 · <i class="fa fa-thumbs-o-up"></i> <span class="pcomment-reply-like-count">{{likes}}</span>
            </span>
        </div>
    </div>
</script>

<script id="lightbox-author-partial" type="text/x-handlerbars-template">
    <div class="pull-right">
        <img class="img-circle {{type}}" src="{{#if fb_user}}http://graph.facebook.com/{{fb_user}}/picture{{else}}/assets/img/default-avatar.jpg{{/if}}">
        <strong>{{username}}</strong>{{{time_ago}}}
    </div>
</script>

<?php /* For uploading pictures via comments  */?>

<script id="comment-with-picture" type="text/x-handlebars-template">
    <div id="comment_wp" class="clearfix">
        <textarea class="post-comment-body post-comment-body-{{type}} form-control" id="post-comment-{{id}}" placeholder="Ctrl+Enter to comment">{{str}}</textarea>
        <button class="green-add-btn btn pull-right">Send</button>
        <input type="hidden" value="{{name}}" name="img" id="img-{{id}}">
        <img style="float: left;margin: 0 10px 0 0;" src="/imagecache/small/{{path}}">
        <img style="cursor: pointer;" src="/assets/img/close-button.gif" height="10px" width="10px" alt="delete image" title="delete image" onclick="Wall.ImageView.deletePhoto({{id}})">
    </div>
</script>

<script id="comment-without-picture" type="text/x-handlebars-template">
    <div class="comment clearfix">
        <textarea class="post-comment-body post-comment-body-{{type}} form-control" id="post-comment-{{id}}" placeholder="Ctrl+Enter to comment">{{str}}</textarea>
        <button class="green-add-btn btn pull-right">Send</button>
        <div class="fileform" id="filebutton">
            <div class="selectbutton"><img src="/assets/img/foto.png" style="height: 20px; width: 20px;"></div>
            <form>
                <input id="upload" type="file" name="upload" title="Add Image" value='' onchange="Wall.ImageView.sendPhoto(this,{{id}});"/>
            </form>
        </div>
        <div style="display: none;" id="preloader">
            <img src="/assets/img/load.gif">
        </div>
    </div>
</script>