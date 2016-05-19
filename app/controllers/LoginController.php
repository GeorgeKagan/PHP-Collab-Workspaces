<?php

class LoginController extends BaseController {

    public function fb() {
        $facebook = new Facebook(Config::get('facebook'));
        $params = array(
            'redirect_uri' => url('/login/fb/callback'),
            'scope' => 'email',
        );
        return Redirect::to($facebook->getLoginUrl($params));
    }

    public function fbcallback() {
        $code = Input::get('code');
        if (strlen($code) == 0) { return Redirect::to('/')->with('message', 'There was an error communicating with Facebook'); }
        $facebook = new Facebook(Config::get('facebook'));
        $access_token =  $facebook->getAccessToken();
        $facebook->setAccessToken($access_token);
        $uid = $facebook->getUser();
        if ($access_token && !$uid && isset($_GET['uid'])) {
            $uid = $_GET['uid'];
        }
        if ($uid == 0) { return Redirect::to('/')->with('message', 'There was an error'); }
        $fb_user = $facebook->api('/' . $uid);
        $user = User::where('email', '=', $fb_user['email'])->first();
        // Persist user data
        if ($user) {
            // User exists (manually created or from fb data) - update/add info
            $user->name = $fb_user['first_name'] . ' ' . $fb_user['last_name'];
            $user->fb_user = $fb_user['id'];
        } else {
            // User doesn't exist - create from fb data
            $user = new User;
            $user->name = $fb_user['first_name'] . ' ' . $fb_user['last_name'];
            $user->email = $fb_user['email'];
            $user->fb_user = $fb_user['id'];
        }
        $user->access_token = $facebook->getAccessToken();
        $user->save();
        Auth::login($user, true);
        Session::set('user_id', Auth::user()->id);
        return Redirect::to('/');
    }

    public function welcome() {
        return View::make('welcome');
    }

    /**
     * This functions checks user's email, password and ip-address. If there is more than 4 retries to login
     * it shows timer to next login. Every retries will given error (even with right params), until countdown is not end.
     */
    public function postWelcome() {
        $email = Input::get('email');
        $password = Input::get('pass');
        $brute = DB::table('brootforce')
            ->where('email',$email)
            ->where('ip',$_SERVER['REMOTE_ADDR'])
            ->get();
        if ($brute && $brute[0]->retries >= 4 ) {
            $countdown = $this->build_counter($brute);
            if($countdown) {
                Session::flash('auth_error',
                    '<div id="mes">
                        יותר מדי נסיונות!
                        (<div id="countdown" style="display: inline;">'.$countdown['hours'].':'.$countdown['minutes'].':'.$countdown['timer'].'</div>
                        לנסיון הבא)
                </div>
                <input type="hidden" id="sec" value='.abs($countdown['time_to_attempt']).'>'
                );

                return Redirect::to('welcome');
            }
        }
        if (Auth::attempt(array('email' => $email, 'password' => $password),true)) {
            if ($brute && $brute[0]->retries !=0) {
                $this->brute_update($brute[0]->id);
            }
            Session::set('user_id', Auth::user()->id);
            return Redirect::to('/');
        } else {
            if (!empty($brute[0]->id)) {
                $timeout = (($brute[0]->retries != 0) ? $brute[0]->retries + 1 : 1) * 30;
                $this->brute_update($brute[0]->id, $brute[0]->retries + 1, $timeout, date('Y-m-d H:i:s',time()));
            } else {
                DB::table('brootforce')->insert(
                    array(
                        'email'         => $email,
                        'retries'       => 1,
                        'ip'            => $_SERVER['REMOTE_ADDR'],
                        'first_attempt' => date('Y-m-d H:i:s',time()),
                    )
                );
            }
            Session::flash('auth_error', 'אימייל או סיסמה שגויים :(');
            return Redirect::to('welcome');
        }
    }

    public function logout() {
        $facebook = new Facebook(Config::get('facebook'));
        $facebook->destroySession();
        Session::flush();
        Auth::logout();
        return Redirect::route('home');
    }

    /**
     * Building timer values for countdown on the login page
     * @param array $brute
     * @return array
     */
    private function build_counter($brute) {
        $time = strtotime($brute[0]->first_attempt) + ($brute[0]->retries *30);
        $time_to_attempt = time()-$time;
        if ($time_to_attempt < 0){
            $timer = abs($time_to_attempt);
            $hours = $timer / 3600;
            if ($hours < 0 ) $hours = 0;
            $timer = $timer - $hours * 3600;
            $minutes = $timer / 60;
            if ($minutes < 0) $minutes = 0;
            $timer = $timer - $minutes *60;
            if ($hours < 0) $hours = 0;
            return array('hours' => $hours, 'minutes' => $minutes, 'timer' => $timer, 'time_to_attempt' => $time_to_attempt);
        }
    }

    /**
     * @param int $id
     * @param int $retries
     * @param int $timeout (number of sec)
     * @param $first_attempt
     */
    private function brute_update($id, $retries = 0, $timeout = 0, $first_attempt = 0) {
        DB::table('brootforce')
            ->where('id', $id)
            ->update(
                array(
                    'retries'       => $retries,
                    'timeout'       => $timeout,
                    'first_attempt' => $first_attempt
                )
            );
    }
}