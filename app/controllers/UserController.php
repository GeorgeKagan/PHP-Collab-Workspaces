<?php

class UserController extends BaseController {

    /**
     * Mark current user as one that has dismissed the welcome message in the grid
     * @return int
     */
    public function seenWelcome()
    {
        if (!Session::get('user_id')) {
            exit('Unauthorized');
        }
        return DB::table('users')->where('id', '=', Session::get('user_id'))->update(array('seen_welcome' => '1'));
    }

}