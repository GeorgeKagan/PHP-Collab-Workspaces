<?php

Event::listen('post.create', function (Post $post) {
    //get emails of users who belong to the group in which the post was posted
    //and who is subscribed to receive email notifications for posts, excluding the owner of the post
    $users = Db::select("
        SELECT
            u.name as name,
            u.email as email
        FROM user2group ug
        INNER JOIN users u ON ug.user_id = u.id
        INNER JOIN user_notifications un ON ug.user_id = un.user_id
        WHERE ug.group_id = ?
        AND u.id != ?
        AND un.posts = 1
    ",
        array($post->group_id, $post->user_id));

    if (empty($users)) return;

    $postingUser = Db::select("SELECT name FROM users WHERE id = ?", array($post->user_id));
    $postingGroup = Db::select("SELECT name, hash FROM groups WHERE id = ?", array($post->group_id));

    $data = array();
    $data['group'] = array();
    $data['group']['name'] = $postingGroup[0]->name;
    $data['group']['url'] = URL::to('/ws/' . $postingGroup[0]->hash);
    Mail::queue('emails.notifications.new_post', $data, function ($message) use ($users, $postingUser, $postingGroup) {
        /** @var $message \Illuminate\Mail\Message */
        $message->to('notifications@afterclass.co.il');
        foreach ($users as $user) $message->bcc($user->email, $user->name);
        $message->subject($postingUser[0]->name . ' posted a new post in ' . $postingGroup[0]->name);
    });
});

Event::listen('comment.create', function($postId, $userId, $commentId) {
    $postingUser = DB::select("SELECT name FROM users WHERE id = ?", array($userId));
    $post = DB::select("SELECT group_id, label FROM posts WHERE id = ?", array($postId));
    $group = DB::select("SELECT name, hash FROM groups WHERE id = ?", array($post[0]->group_id));

    $postingUser = $postingUser[0];
    $post = $post[0];
    $group = $group[0];

    //find all users who have subscribed to receive notifications on comments, inside the group the comments was made in
    //excluding comment owner
    $onCommentUsers = Db::select("
        SELECT
            u.name as name,
            u.email as email
        FROM user2group ug
        INNER JOIN users u ON ug.user_id = u.id
        INNER JOIN user_notifications un ON ug.user_id = un.user_id
        WHERE ug.group_id = ?
        AND u.id != ?
        AND un.comments = 1
    ", array($post->group_id, $userId));

    if(!empty($onCommentUsers)) {
        $data = array();
        $data['post'] = array();
        $data['post']['label'] = $post->label;
        $data['group'] = array();
        $data['group']['name'] = $group->name;
        $data['group']['url'] = URL::to('/ws/' . $group->hash);
        Mail::queue('emails.notifications.new_comment', $data, function ($message) use ($onCommentUsers, $postingUser, $post) {
            /** @var $message \Illuminate\Mail\Message */
            $message->to('notifications@afterclass.co.il');
            foreach ($onCommentUsers as $user) $message->bcc($user->email, $user->name);
            $message->subject($postingUser->name . ' posted a new comment on ' . $post->label);
        });
    }

    //find the owner of the post and check if he is subscribed to receive comment notifications on his post
    //exclude the poster of the comment (we dont want to send emails about comments the owner made)
    $postOwner = Db::select("
        SELECT
            u.name as name,
            u.email as email
        FROM posts p
        INNER JOIN users u ON p.user_id = u.id
        INNER JOIN user_notifications un ON u.id = un.user_id
        WHERE p.id = ?
        AND u.id != ?
        and un.post_comments = 1
    ", array($postId, $userId));

    if(!empty($postOwner)) {
        $postOwner = $postOwner[0];

        $data = array();
        $data['post'] = array();
        $data['post']['label'] = $post->label;
        $data['group'] = array();
        $data['group']['name'] = $group->name;
        $data['group']['url'] = URL::to('/ws/' . $group->hash);
        Mail::queue('emails.notifications.new_post_comment', $data, function ($message) use ($postOwner, $postingUser, $post) {
            /** @var $message \Illuminate\Mail\Message */
            $message->to('notifications@afterclass.co.il');
            $message->bcc($postOwner->email, $postOwner->name);
            $message->subject($postingUser->name . ' posted a new comment on your post: ' . $post->label);
        });
    }
});