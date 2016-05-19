<?php

class ChatController extends BaseController {

    /**
     * Get chat messages for a workspace
     * @return string
     */
    public function getIndex()
    {
        if (!Session::get('user_id')) {
            exit('Unauthorized');
        }
        $msg_count_from_end = 50;
        $exclude_curr_user = Input::get('exclude_curr_user');
        $group_name = Input::get('group_name');
        $timestamp = Input::get('timestamp', false);
        $chat = DB::table('chat')
            ->select(
                'chat.id',
                'chat.message',
                'users.name',
                'users.fb_user',
                'users.type',
                'chat.created_at_ms',
                'chat.created_at'
            )
            ->join('users', 'chat.user_id', '=', 'users.id')
            ->orderBy('chat.id', 'DESC')
            ->limit($msg_count_from_end);
        if ($group_name !== 'all') {
            $chat = $chat->where('chat.group_id', '=', $group_name);
        }
        if ($timestamp) {
            $chat = $chat->where('chat.created_at_ms', '>', $timestamp);
        }
        if ($exclude_curr_user) {
            $chat = $chat->where('chat.user_id', '!=', Session::get('user_id'));
        }
        $chat = $chat->get();
        // Newest in the end
        $chat = array_reverse($chat);
        foreach ($chat as $msg) {
            $msg->message = do_rtl_if_heb($msg->message, false);
            $msg->created_at = time_ago($msg->created_at, 1);
        }
        $milliseconds = round(microtime(true) * 1000);
        return json_encode(['server_timestamp' => $milliseconds, 'messages' => $chat]);
    }

    /**
     * Add new chat message
     */
    public function postIndex() {
        if (!Session::get('user_id')) {
            exit('Unauthorized');
        }
        $milliseconds = round(microtime(true) * 1000);

        $Chat = new Chat;
        $Chat->message = e(strip_tags(Input::get('message')));
        $Chat->user_id = (int)Input::get('user_id');
        $Chat->group_id = Input::get('group_hash');
        $Chat->created_at_ms = $milliseconds;
        $Chat->save();

        echo $milliseconds;
    }
}