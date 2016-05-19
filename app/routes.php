<?php

// Root path - check where to redirect logged in user
Route::get('/', ['as' => 'home', function() {
    Session::set('choose_workspace', true);
    return Redirect::to('choose_workspaces');
}])->before('auth|got_workspaces');

// Mappings
Route::get('welcome', array('as' => 'welcome', 'uses' => 'LoginController@welcome'));
Route::get('choose_workspaces', array('as' => 'choose_workspaces', 'uses' => 'IndexController@chooseWorkspaces'))->before('auth|got_workspaces');
Route::post('mobile_error_logger', 'IndexController@mobileErrorLogger');
Route::post('posts/dropzone-images', function() { return 'ok'; });
//
Route::get('user/seen_welcome', 'UserController@seenWelcome')->before('auth');
Route::get('ws/{hash}', array('as' => 'workspace', 'uses' => 'IndexController@index'))->before('auth');
Route::post('subscribe', 'IndexController@subscribe');
Route::controller('chat', 'ChatController');
Route::controller('posts', 'PostController');
Route::controller('groups', 'GroupController');

// Facebook login
Route::get('login/fb', array('uses' => 'LoginController@fb'));
Route::get('login/fb/callback', array('uses' => 'LoginController@fbcallback'));

// Normal login
Route::post('auth', array('before' => 'csrf', 'as' => 'welcome', 'uses' => 'LoginController@postWelcome'));

// Logout
Route::get('logout', array('as' => 'logout', 'uses' => 'LoginController@logout'))->before('auth');

// Remind password
Route::get('remind', array('as' => 'remind', 'uses' => 'RemindersController@getRemind'));
Route::post('password/remind', array('as' => 'password/remind', 'uses' => 'RemindersController@postRemind'));
Route::get('password/reset/{token}', array('as' => 'password/reset', function($token){
    if (is_null($token)) App::abort(404);
    return View::make('password.reset')->with('token', $token);
}))->where('token','[A-Za-z0-9]+');
Route::post('reset', array('as' => 'reset', 'uses' => 'RemindersController@postReset'));

App::after(function($request, $response) {
    if (Auth::check()) {
        $user = Auth::user();
        // If 5 mins have passed since last page refresh
        if (time() - strtotime($user->last_activity) > 5 * 60) {
            $user->last_activity = date('Y-m-d H:i:s', time());
            $user->save();
        }
    }
});
