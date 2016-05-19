<?php

class PostController extends BaseController
{

    const POST_IMG_PATH = '/user_content/post_images/';
    const COMMENT_IMG_PATH = '/user_content/comment_images/';
    protected $allowed_ext = ['jpg','jpeg','png','gif','zip','7zip','rar','pdf','doc','docx','xls','xlsx','txt','mp3','wav','wma'];

    /**
     * Get a single post
     * @param $user_id
     * @param $post_id
     * @return array|static[]
     */
    public function getPost($user_id, $post_id)
    {
        if (!Session::get('user_id')) {
            exit('Unauthorized');
        }
        $post = Post::getWallPosts($user_id, false, false, null, null, $post_id);
        return $post;
    }

    /**
     * Add new Wall Post
     */
    public function postPost()
    {
        if (!Session::get('user_id')) {
            exit('Unauthorized');
        }
        $user_id = Session::get('user_id');
        $show_only_this_users_posts = Input::get('show_only_this_users_posts', false);
        $body = Input::get('post-body');
        $workspace_hash = Input::get('group-name');
        $group = DB::table('groups')->select('id','name')->where('hash', $workspace_hash)->first();
        $Post = new Post;
        $Post->user_id = $user_id;
        $Post->group_id = $group->id;
        $Post->body = trim($body);
        $Post->label = 'homework';
        $Post->save();

        $this->findTutor($user_id,$group);

        Event::fire('post.create', array($Post));

        $annotations = '';
        if (Input::has('post-image-annotations')) {
            $annotations = json_decode(Input::get('post-image-annotations'));
            foreach ($annotations as $anno) {
                $Comment = new PostComment;
                $Comment->post_id = $Post->id;
                $Comment->user_id = $user_id;
                $Comment->body = trim(str_replace('<br>', "\n", $anno->text));
                $Comment->save();
                $anno->comment_id = $Comment->id;
            }
        }

        // Image attachment for "image view"
        if (Input::has('post-has-image-attachment')) {
            // multiple attachments, zip them
            if (Input::has('post-attachment-count')) {
                $attach_count = Input::get('post-attachment-count');
                $attachments = [];
                for ($i = 0; $i < $attach_count; $i++) {
                    $extension = Input::file('post-upload-' . $i)->getClientOriginalExtension();
                    if (in_array(strtolower($extension), $this->allowed_ext)) {
                        $attachments[] = [
                            'name' => Input::file('post-upload-' . $i)->getClientOriginalName(),
                            'path' => Input::file('post-upload-' . $i)->getPathName()
                        ];
                    }
                }
                $attach_url = $this->zipAttachments($attachments);
            }
            // one attachment, save as is
            else {
                $extension = Input::file('post-upload')->getClientOriginalExtension();
                if(in_array(strtolower($extension), $this->allowed_ext)){
                    $attach_url = $this->saveImage(Input::file('post-upload'));
                }
            }
            $PostImage = new PostImage;
            $PostImage->post_id = $Post->id;
            $PostImage->img_url = $attach_url;
            $PostImage->annotations = json_encode($annotations);
            $PostImage->save();
        }

        $posts = Post::getWallPosts($user_id, $show_only_this_users_posts, false, $workspace_hash, null, $Post->id);

        return $posts;
    }

    /**
     * Create and save a zip of 2 or more files
     * @param $attachments
     * @return string
     */
    private function zipAttachments($attachments)
    {
        $zip = new ZipArchive();
        $filename = '/user_content/zips/afterclass_' . Session::get('user_id') . '_' . time() . '.zip';
        if ($zip->open('.' . $filename, ZipArchive::OVERWRITE) !== TRUE) {
            exit("cannot open zip file for writing\n");
        }
        foreach ($attachments as $attach) {
            $zip->addFile($attach['path'], $attach['name']);
        }
        $zip->close();
        return $filename;
    }

    /**
     * Save image-view post image
     * @param $img
     * @return string
     */
    private function saveImage($img)
    {
        $extension = $img->getClientOriginalExtension();
        $save_path = public_path() . self::POST_IMG_PATH;
        $img_name = Session::get('user_id') . '-' . time() . '.' . $extension;
        $img->move($save_path, $img_name);
        if (in_array(strtolower($extension), array('jpg','png','jpeg','gif'))){
            return $img_name;
        }else{
            return self::POST_IMG_PATH . $img_name;
        }
    }

    /*
     * Delete user's post along with all related data
     */
    public function deletePost()
    {
        if (!Session::get('user_id')) {
            exit('Unauthorized');
        }
        $post_id = Input::get('post_id');
        $user_id = Session::get('user_id');
        $is_owner = DB::table('posts')
            ->where('id', '=', $post_id)
            ->where('user_id', '=', $user_id)
            ->select('id')->first()->id;
        if (!$is_owner || !$post_id || !$user_id) {
            return;
        }
        DB::table('posts')->where('id', '=', $post_id)->delete();
        DB::table('post_likes')->where('post_id', '=', $post_id)->delete();
        DB::table('post_attachments')->where('post_id', '=', $post_id)->delete();
        // Delete post images
        $post_images = DB::table('post_images')->where('post_id', '=', $post_id)->select('img_url')->get();
        DB::table('post_images')->where('post_id', '=', $post_id)->delete();
        foreach ($post_images as $img) {
            unlink(public_path() . $img->img_url);
        }
        // Delete all post comments and their replies & likes
        DB::select(DB::raw("DELETE post_comments, pcomment_likes, pcomment_replies, pcomment_reply_likes
            FROM post_comments
            JOIN pcomment_likes ON post_comments.id = pcomment_likes.pcomment_id
            JOIN pcomment_replies ON pcomment_likes.pcomment_id = pcomment_replies.comment_id
            JOIN pcomment_reply_likes ON pcomment_replies.comment_id = pcomment_reply_likes.comment_id
            WHERE post_comments.post_id = {$post_id}"));
    }

    /**
     * Like a post
     */
    public function postLike()
    {
        if (!Session::get('user_id')) {
            exit('Unauthorized');
        }
        $post_id = Input::get('post-id');
        $user_id = Session::get('user_id');
        $like_exists = DB::table('post_likes')->where('post_id', $post_id)->where('user_id', $user_id)->first();
        if ($like_exists) {
            DB::table('post_likes')->delete($like_exists->id);
            DB::table('posts')->where('id', $post_id)->decrement('likes', 1);
            return '0';
        } else {
            DB::table('post_likes')->insert(
                array('post_id' => $post_id, 'user_id' => $user_id)
            );
            DB::table('posts')->where('id', $post_id)->increment('likes', 1);
            return '1';
        }
    }

    /**
     * Add a comment to a post
     */
    public function postComment()
    {
        if (!Session::get('user_id')) {
            exit('Unauthorized');
        }
        $post_id = (int)Input::get('post-id');
        $user_id = Session::get('user_id');

        $Comment = new PostComment;
        $Comment->post_id = $post_id;
        $Comment->user_id = $user_id;
        $Comment->body = trim(Input::get('body'));
        $Comment->img = Input::get('img');
        $Comment->save();

        $this->findRecipients($post_id,$user_id,$Comment->id);

        Event::fire('comment.create', array($post_id, $user_id, $Comment->id));

        return $Comment->id;
    }

    /**
     * Add a mark (image view's comment+annotation combo) to a post
     */
    public function postMark()
    {
        if (!Session::get('user_id')) {
            exit('Unauthorized');
        }
        $post_id = Input::get('post-id');
        $user_id = Session::get('user_id');

        // Save as regular comment
        $Comment = new PostComment;
        $Comment->post_id = $post_id;
        $Comment->user_id = $user_id;
        $Comment->body = trim(str_replace('<br>', "\n", Input::get('body')));
        $Comment->save();

        // Save the annotation
        $new_annotation = json_decode(Input::get('annotation'));
        $new_annotation->comment_id = $Comment->id;
        $PostImage = DB::table('post_images')->where('post_id', '=', $post_id)->select()->first();
        $annotations = json_decode($PostImage->annotations, true);
        $annotations[] = $new_annotation;
        DB::table('post_images')->where('post_id', '=', $post_id)->update(array('annotations' => json_encode($annotations)));
        return $Comment->id;
    }

    /**
     * Delete a post comment along with any related data
     */
    public function deleteComment()
    {
        if (!Session::get('user_id')) {
            exit('Unauthorized');
        }
        $comment_id = (int)Input::get('comment_id');
        $tutor = (int)Input::get('tutor',false);
        $user_id = (int)Session::get('user_id');
        // Get post_id & make sure it belongs to current user
        $comment = DB::table('post_comments')
            ->where('id', '=', $comment_id)
            ->select('post_id','user_id','img')
            ->first();
        // Delete comment and any related data
        if ($comment->user_id == $user_id or $tutor === 1) {
            $regular = Input::get('regular', false);
            if($comment->img){
                $path = public_path().'/user_content/comment_images/'.$comment->img;
                unlink($path);
            }
            if (!$regular) $this->deleteCommentLinkedAnnotation($comment->post_id, $comment_id);
            DB::table('post_comments')->where('id', '=', $comment_id)->delete();
            DB::table('pcomment_likes')->where('pcomment_id', '=', $comment_id)->delete();
            DB::table('pcomment_replies')->where('comment_id', '=', $comment_id)->delete();
            DB::table('pcomment_reply_likes')->where('comment_id', '=', $comment_id)->delete();
        }
    }

    /**
     * Delete a post comment reply along with any related data
     */
    public function deleteCommentreply()
    {
        if (!Session::get('user_id')) {
            exit('Unauthorized');
        }
        $reply_id = (int)Input::get('comment_id');
        $tutor = (int)Input::get('tutor',false);
        $user_id = (int)Session::get('user_id');
        // Get post_id & make sure it belongs to current user
        $reply = DB::table('pcomment_replies')
            ->where('id', '=', $reply_id)
            ->select('user_id')
            ->first();
        // Delete reply and any related data
        if ($reply->user_id == $user_id or $tutor === 1) {
            DB::table('pcomment_replies')->where('id', '=', $reply_id)->delete();
            DB::table('pcomment_reply_likes')->where('reply_id', '=', $reply_id)->delete();
        }
    }

    /**
     * Delete annotation linked to a deleted comment, if any (in case comment is a post image one)
     * @param $post_id
     * @param $comment_id
     * @return bool
     */
    private function deleteCommentLinkedAnnotation($post_id, $comment_id)
    {
        $annotations_json = DB::table('post_images')->where('post_id', '=', $post_id)->select('annotations')->first()->annotations;
        if (!$annotations_json) {
            return false;
        }
        $annotations = json_decode($annotations_json, true);
        foreach ($annotations as $key => $anno) {
            if ($anno != '' && $anno['comment_id'] == $comment_id) {
                unset($annotations[$key]);
            }
        }
        DB::table('post_images')->where('post_id', '=', $post_id)->update(array('annotations' => json_encode($annotations)));
    }

    /**
     * Like a post comment
     */
    public function postCommentlike()
    {
        if (!Session::get('user_id')) {
            exit('Unauthorized');
        }
        $comment_id = Input::get('comment_id');
        $user_id = Session::get('user_id');
        $like_exists = DB::table('pcomment_likes')->where('pcomment_id', $comment_id)->where('user_id', $user_id)->first();
        if (!$like_exists) {
            DB::table('pcomment_likes')->insert(
                array('pcomment_id' => $comment_id, 'user_id' => $user_id)
            );
            DB::table('post_comments')->where('id', $comment_id)->increment('likes', 1);
        }
    }

    /**
     * UnLike a post comment
     */
    public function postCommentunlike()
    {
        if (!Session::get('user_id')) {
            exit('Unauthorized');
        }
        $comment_id = Input::get('comment_id');
        $user_id = Session::get('user_id');
        DB::table('pcomment_likes')
            ->where('pcomment_id', '=', $comment_id)
            ->where('user_id', '=', $user_id)
            ->delete();
        DB::table('post_comments')->where('id', $comment_id)->decrement('likes', 1);
    }

    /**
     * Add a reply to a post comment
     */
    public function postCommentreply()
    {
        if (!Session::get('user_id')) {
            exit('Unauthorized');
        }

        $Reply = new PostCommentReply;
        $Reply->comment_id = Input::get('comment_id');
        $Reply->user_id = Session::get('user_id');
        $Reply->body = trim(Input::get('body'));
        $Reply->save();

        return $Reply->id;
    }

    /**
     * Like a post comment reply
     */
    public function postReplylike()
    {
        if (!Session::get('user_id')) {
            exit('Unauthorized');
        }
        $comment_id = Input::get('comment_id');
        $reply_id = Input::get('reply_id');
        $user_id = Session::get('user_id');
        $like_exists = DB::table('pcomment_reply_likes')->where('reply_id', $reply_id)->where('user_id', $user_id)->first();
        if (!$like_exists) {
            DB::table('pcomment_reply_likes')->insert(
                array('comment_id' => $comment_id, 'reply_id' => $reply_id, 'user_id' => $user_id)
            );
            DB::table('pcomment_replies')->where('id', $reply_id)->increment('likes', 1);
        }
    }

    /**
     * UnLike a post comment reply
     */
    public function postReplyunlike()
    {
        if (!Session::get('user_id')) {
            exit('Unauthorized');
        }
        $reply_id = Input::get('reply_id');
        $user_id = Session::get('user_id');
        DB::table('pcomment_reply_likes')
            ->where('reply_id', '=', $reply_id)
            ->where('user_id', '=', $user_id)
            ->delete();
        DB::table('pcomment_replies')->where('id', $reply_id)->decrement('likes', 1);
    }

    // @TODO: wtf is this?? is this still in use?
    public function postLockUnlock()
    {
        if (!Session::get('user_id')) {
            exit('Unauthorized');
        }
        $post_id = Input::get('post-id');
        $user_id = Session::get('user_id');

        //get post owner and lock status
        $postData = DB::table('posts')->where('id', '=', $post_id)->select(array('user_id', 'locked'))->first();

        if (is_null($postData)) return json_encode(array('status' => 'error', 'message' => 'invalid post'));
        if ($postData->user_id != $user_id) return json_encode(array('status' => 'error', 'message' => 'not owner'));

        $locked = (bool)$postData->locked;
        DB::table('posts')->where('id', '=', $post_id)->update(array('locked' => !$locked));
        return json_encode(array('status' => 'ok', 'post_locked' => !$locked));
    }

    /**
     * Mark a post comment as the answer to the question
     * @return string
     */
    public function postMarkAsAnswer()
    {
        if (!Session::get('user_id')) {
            exit('Unauthorized');
        }
        $comment_id = Input::get('comment-id');
        $user_id = Session::get('user_id');
        $currentUser = User::find($user_id);

        //get post data
        $postData = DB::table('post_comments')
            ->join('posts', 'post_comments.post_id', '=', 'posts.id')
            ->where('post_comments.id', '=', $comment_id)
            ->select(array('posts.user_id', 'posts.id'))
            ->get();

        if (is_null($postData) || empty($postData)) {
            return json_encode(array('status' => 'error', 'message' => 'invalid post'));
        }
        $postData = $postData[0];
        if ($postData->user_id != $user_id && $currentUser->type !== 'tutor') {
            return json_encode(array('status' => 'error', 'message' => 'not the owner of the post'));
        }

        //reset all answers
        DB::table('post_comments')
            ->where('post_id', '=', $postData->id)
            ->update(array('is_answer' => false));

        //mark as answer
        DB::table('post_comments')
            ->where('id', '=', $comment_id)
            ->update(array('is_answer' => true));

        return json_encode(array('status' => 'ok'));
    }

    /**
     * Save an image attached to a post comment
     * @return string
     */
    public function postSaveCommentImage()
    {
        if (!Session::get('user_id')) {
            exit('Unauthorized');
        }
        // Save image
        $img = Input::file('upload', '');
        $extension = $img->getClientOriginalExtension();
        if (in_array(strtolower($extension), array('jpg','png','jpeg','gif'))) {
            $save_path = public_path() . self::COMMENT_IMG_PATH;
            $img_name = Session::get('user_id') . '-' . time() . '.' . $extension;
            $img->move($save_path, $img_name);
            $image = Image::make(realpath($save_path . '/' . $img_name));
            if ($image->width() > 1000 && $image->height() > 1000){
                if ($image->width() > $image->height()) {
                    $image->resize(1000, null, function($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                } else {
                    $image->resize(null, 1000, function($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                }
            } elseif ($image->height() > 1000 && $image->width() < 1000) {
                $image->resize(null, 1000, function($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            } elseif ($image->height() < 1000 && $image->width() > 1000){
                $image->resize(1000, null, function($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }
            $image->save($save_path . '/' . $img_name);
            return json_encode(array('success' => $img_name));
        }
    }

    /**
     * Force download of an attachment (example: PDF)
     */
    public function getGetAttach(){
        $file = public_path() . Input::get('path');
        if (!file_exists($file)) {
            exit;
        }
        echo 'file ' . $file . ' is in server';
        // reset php output buffer or it will overflow memory limit
        if (ob_get_level()) {
            ob_end_clean();
        }
        // output to browser
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($file));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }

    /**
     * Function finds tutor of workspace and if current user not a tutor, function adds him to mail queue.
     * @param int $user_id
     * @param object $group
     */
    private function findTutor($user_id,$group) {
        //finding workspace tutor
        $tutor = DB::table('users')
            ->select('users.email','users.id','users.name')
            ->join('user2group','user2group.user_id','=','users.id')
            ->where('user2group.group_id','=',$group->id)
            ->where('users.type','=','tutor')
            ->get();
        if (!empty($tutor) && $tutor[0]->id != $user_id) {
            DB::table('mail_queue')->insert(
                array('to' => $tutor[0]->email, 'subject' => 'New Post in '.$group->name.'!', 'content' => '<b>Hi, '.$tutor[0]->name.'! There is a new post! Someone needs your help!!!</b>')
            );
        }
    }

    /**
     * Finding mail notification recipients (user not an author of post and not need to send notification user itself. also no double notifications)
     * @param int $post_id
     * @param int $user_id
     * @param int $comment_id
     */
    private function findRecipients($post_id,$user_id,$comment_id) {
        $post = DB::table('posts')->where('id','=',$post_id)->get();
        $recipients = [];
        $i = 0;
        $post_author = DB::table('users')
            ->select('users.email','users.name','users.id')
            ->join('posts','posts.user_id','=','users.id')
            ->where('posts.id','=',$post_id)
            ->get();
        if ($post_author[0]->id != $user_id) {
            $recipients[$i]['email'] = $post_author[0]->email;
            $recipients[$i]['name'] = $post_author[0]->name;
            $i++;
        }
        $users = DB::table('users')
            ->select('users.name','users.email','post_comments.id')
            ->join('post_comments','post_comments.user_id','=','users.id')
            ->where('post_comments.post_id','=',$post_id)
            ->where('post_comments.id','<>',$comment_id)
            ->get();
        foreach($users as $user) {
            $pre = [];
            $pre['email'] = $user->email;
            $pre['name'] = $user->name;
            if (!in_array($pre,$recipients) && $user->id != $user_id) {
                $recipients[$i]['email'] = $user->email;
                $recipients[$i]['name'] = $user->name;
                $i++;
            }
        }

        foreach ($recipients as $recipient) {
            DB::table('mail_queue')->insert(
                array('to' => $recipient['email'], 'subject' => 'New Comment in '.$post[0]->body.'!', 'content' => '<b>Hi, '.$recipient['name'].'! There is a new comment! Someone needs your help!!!</b>')
            );
        }
    }
}