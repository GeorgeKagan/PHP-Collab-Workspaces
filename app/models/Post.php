<?php

class Post extends Eloquent
{

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'posts';

    /**
     * Get posts
     * @param null $user_id
     * @param bool $show_only_this_users_posts
     * @param bool $show_only_image_posts
     * @param null $workspace_hash
     * @param null $label_name
     * @param null $post_id
     * @return array|static[]
     */
    public static function getWallPosts(
        $user_id = null,
        $show_only_this_users_posts = false,
        $show_only_image_posts = false,
        $workspace_hash = null,
        $label_name = null,
        $post_id = null)
    {
        $q = DB::table('groups')
            ->select(
                'posts.*',
                // Post attachment
                'post_attachments.id AS has_attachment',
                'post_attachments.url AS attach_url',
                'post_attachments.img_url AS attach_img_url',
                'post_attachments.title AS attach_title',
                'post_attachments.desc AS attach_desc',
                // Post image
                'post_images.id AS has_image',
                'post_images.img_url AS image_url',
                'post_images.annotations AS image_annotations',
                // The rest
                'users.name AS username',
                'users.id AS user_id',
                'users.fb_user',
                'users.type',
                'groups.name AS groupname')
            ->join('posts', 'groups.id', '=', 'posts.group_id')
            ->leftJoin('post_attachments', 'posts.id', '=', 'post_attachments.post_id')
            ->join('users', 'posts.user_id', '=', 'users.id')
            ->join('user2group', 'groups.id', '=', 'user2group.group_id')
            ->orderBy('posts.id', 'desc')
            ->groupBy('posts.id');

        // If no user_id -> showing all posts under /explore
        if ($user_id) {
            $q->where('user2group.user_id', '=', $user_id);
        }
        // Get posts for a specific user
        if ($show_only_this_users_posts && $user_id) {
            $q->where('users.id', '=', $user_id);
        }
        // Get only image posts OR not
        if ($show_only_image_posts) {
            $q->join('post_images', 'posts.id', '=', 'post_images.post_id');
        } else {
            $q->leftJoin('post_images', 'posts.id', '=', 'post_images.post_id');
        }
        // Get posts from a specific group
        if ($workspace_hash) {
            $q->where('groups.hash', '=', $workspace_hash);
        }
        // Get one post
        if ($post_id) {
            $q->where('posts.id', '=', $post_id);
        }

        $posts = $q->get();
        $currentUser = User::find(Session::get('user_id'));
        $isTutor = $currentUser->type == 'tutor';
        $workspace_tutors = self::getWorkspace2TutorsMapping();

        if ($isTutor) {
            $tmpPosts = $posts;
            $posts = array();
            foreach ($tmpPosts as $p) {
                if (!$p->locked) $posts[] = $p;
            }
        }


        // Transform posts
        foreach ($posts as $post) {
            $ws_has_tutor = isset($workspace_tutors[$post->group_id]) ? true : false;
            $is_ws_tutor = $ws_has_tutor && in_array($currentUser->id, $workspace_tutors[$post->group_id]);
            //
            $post->time_ago = time_ago($post->created_at, 1);
            $post->can_be_locked = !$isTutor;
            $post->locked = (bool)$post->locked;
            $post->i_am_owner = ($post->user_id == $currentUser->id);
            $post->can_mark_answer = ($post->i_am_owner && !$ws_has_tutor) || $is_ws_tutor;
            $post->body = do_rtl_if_heb($post->body);

            if($post->likes > 0) {
                //check if I liked the post
                $meLikey = DB::table('post_likes')
                    ->select('id')
                    ->where('post_id', '=', $post->id)
                    ->where('user_id', '=', $currentUser->id)
                    ->get();
                $post->me_likey = (bool)($meLikey && !empty($meLikey));
            }
        }

        // Get comments
        foreach ($posts as $post) {
            $comments = DB::table('post_comments')
                ->select('post_comments.*', 'pcomment_likes.id as is_liked_by_me', 'users.name', 'users.fb_user', 'users.type','post_images.annotations')
                ->join('users', 'post_comments.user_id', '=', 'users.id')
                ->leftJoin('post_images','post_comments.post_id','=','post_images.post_id')
                ->leftJoin('pcomment_likes', function($join) {
                    $join->on('post_comments.id', '=', 'pcomment_likes.pcomment_id')
                        ->where('pcomment_likes.user_id', '=', Session::get('user_id'));
                })
                ->where('post_comments.post_id', '=', $post->id)
                ->orderBy('post_comments.id', 'asc')
                ->groupBy('post_comments.id')
                ->get();
            $post->comments = $comments;
            $post->comment_count = count($comments);
            $answered = false;
            $showSinceComment = 0;
            foreach($post->comments as $k => $c) {
                $c->body = do_rtl_if_heb($c->body);
                $c->is_answer = (bool)$c->is_answer;
                $c->time_ago = time_ago($c->created_at, 1);
                if($c->is_answer) {
                    $answered = true;
                    $showSinceComment = $k;
                }
            }

            $post->answered = $answered;
            $new = false;
            // IF post added after last last_activity update AND post creator is not current user
            if (strtotime($post->created_at) > strtotime($currentUser->last_activity) && $post->user_id != $currentUser->id) {
                $new = true;
            }
            $post->display_comments = array();
            $totalCommentsToShow = min(2, $post->comment_count - $showSinceComment);
            for($i = 0; $i < $totalCommentsToShow; ++$i) {
                $comment = $post->comments[$i + $showSinceComment];
                $post->display_comments[] = $comment;
            }

            // Get comment replies
            foreach ($post->comments as $comment) {
                if (strtotime($comment->created_at) > strtotime($currentUser->last_activity) && $comment->user_id != $currentUser->id) {
                    $new = true;
                }
                $replies = DB::table('pcomment_replies')
                    ->select('pcomment_replies.*', 'pcomment_reply_likes.id as is_liked_by_me', 'users.name', 'users.fb_user', 'users.type')
                    ->join('users', 'pcomment_replies.user_id', '=', 'users.id')
                    ->leftJoin('pcomment_reply_likes', function($join) {
                        $join->on('pcomment_replies.id', '=', 'pcomment_reply_likes.reply_id')
                            ->where('pcomment_reply_likes.user_id', '=', Session::get('user_id'));
                    })
                    ->where('pcomment_replies.comment_id', '=', $comment->id)
                    ->orderBy('pcomment_replies.id', 'asc')
                    ->groupBy('pcomment_replies.id')
                    ->get();
                // Normalize values (for some reason discrepancy between environments)
                $comment->likes = (int)$comment->likes;
                foreach($replies as $reply) {
                    if (strtotime($reply->created_at) > strtotime($currentUser->last_activity) && $reply->user_id != $currentUser->id) {
                        $new = true;
                        break;
                    }
                }
                array_walk($replies, function ($reply) {
                    $reply->likes = (int)$reply->likes;
                    $reply->body = do_rtl_if_heb($reply->body);
                    $reply->time_ago = time_ago($reply->created_at, 1);
                });
                //
                $comment->replies = $replies;
            }
            $post->new = $new;
        }

        return $posts;
    }

    /**
     * Workspace to tutors mapping array
     * Used to determine if there are tutors for a specific workspace
     * Structure: [workspace_id1 => [tutor_id1, tutor_id2], workspace_id2 => [tutor_id1]]
     */
    private static function getWorkspace2TutorsMapping() {
        $q2 = DB::table('users')
            ->select('users.id', 'groups.id AS group_id')
            ->join('user2group', 'users.id', '=', 'user2group.user_id')
            ->join('groups', 'groups.id', '=', 'user2group.group_id')
            ->where('users.type', '=', 'tutor');
        $workspace_tutors = $q2->get();
        $tmp = [];
        foreach ($workspace_tutors as $wst) {
            if (!isset($tmp[$wst->group_id])) {
                $tmp[$wst->group_id] = [];
            }
            $tmp[$wst->group_id][] = $wst->id;
        }
        return $tmp;
    }
}