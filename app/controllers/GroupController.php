<?php

class GroupController extends BaseController {

    /**
     * Get a workspace's posts, with their comments and comment replies
     */
    public function getIndex() {
        if (!Session::get('user_id')) {
            exit('Unauthorized');
        }
        $user_id = Input::get('user_id');
        $show_only_this_users_posts = Input::get('show_only_this_users_posts', false);
        $show_only_image_posts = Input::get('show_only_image_posts', false);
        $group_name = Input::get('group');
        $label_name = Input::get('label');
        $post_id = Input::get('post_id');
        $posts = Post::getWallPosts($user_id, $show_only_this_users_posts, $show_only_image_posts, $group_name, $label_name, $post_id);
        return $posts;
    }

}