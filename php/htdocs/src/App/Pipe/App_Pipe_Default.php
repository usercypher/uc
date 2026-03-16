<?php

class App_Pipe_Default {
    private $app, $session;

    public function args($args) {
        list(
            $this->app, 
            $this->session, 
        ) = $args;
    } 

    public function process($input, $output) {
        $success = true;

        $flash = $this->session->unset('flash');
        $sessionToken = $this->session->get('session_token');
        $userSession = $this->session->get('user');
        $userRoles = $input->data['user_roles'];
        $isAuth = isset($userSession);

        $data = array(
            'app' => $this->app,
            'is_auth' => $isAuth,
            'partial_app_script' => $this->app->template($this->app->dirRoot('src/App/res/partial/script.html.php'), array(
                'app' => $this->app,
                'flash' => $flash
            )),
        );
        if ($isAuth) {
            $data['partial_user_edit_account'] = $this->app->template($this->app->dirRoot('src/User/res/partial/edit_account.html.php'), array(
                'app' => $this->app,
                'redirect' => '',
                'redirect_alt' => '',
                'session_token' => $sessionToken,
                'user_roles' => $userRoles,
                'user' => $userSession
            ));
            
            $data['partial_user_edit_password'] = $this->app->template($this->app->dirRoot('src/User/res/partial/edit_password.html.php'), array(
                'app' => $this->app,
                'redirect' => '',
                'redirect_alt' => '',
                'session_token' => $sessionToken,
                'user' => $userSession
            ));
            
            $data['partial_user_delete'] = $this->app->template($this->app->dirRoot('src/User/res/partial/delete.html.php'), array(
                'app' => $this->app,
                'redirect' => '',
                'redirect_alt' => 'user/session-unset',
                'session_token' => $sessionToken,
                'user' => $userSession
            ));
        } else {
            $data['partial_user_session'] = $this->app->template($this->app->dirRoot('src/User/res/partial/session.html.php'), array(
                'app' => $this->app,
                'redirect' => '',
                'redirect_alt' => '',
                'session_token' => $sessionToken,
            ));
            $data['partial_user_create'] = $this->app->template($this->app->dirRoot('src/User/res/partial/create.html.php'), array(
                'app' => $this->app,
                'redirect' => '',
                'redirect_alt' => '',
                'session_token' => $sessionToken,
                'user_roles' => $userRoles
            ));
        }

        $output->content = $this->app->template($this->app->dirRoot('src/App/res/default.html.php'), $data);

        return array($input, $output, $success);
    }
}