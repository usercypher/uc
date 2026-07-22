<?php

class Example_Pipe_User {
    private $app, $session, $translator;

    public function args($args) {
        list(
            $this->app, 
            $this->session, 
            $this->translator, 
        ) = $args;
    } 

    public function process($input, $output) {
        $success = true;

        $flash = $this->session->remove('flash');
        $sessionToken = $this->session->get('session_token');
        $userSession = $this->session->get('user');
        $userRoles = $input->data['user_roles'];
        $isAuth = isset($userSession);

        $appLang = $input->data['app:lang'];
        $exampleLanguages = $input->data['example:languages'];
        $exampleLang = $input->data['example:lang'];

        $data = array(
            'app' => $this->app,
            'is_auth' => $isAuth,
            'route' => $input->route,
            't' => $this->translator,
            'languages' => $exampleLanguages,
            'lang' => $exampleLang,
            'translation_dir' => $this->app->dir('ROOT', 'src/Example/lang/' . $exampleLang . '.data.php'),
            'partial_app_script' => $this->app->template($this->app->dir('ROOT', 'src/App/res/partial/script.html.php'), array(
                'app' => $this->app,
                'flash' => $flash,
                't' => $this->translator,
                'translation_dir' => $this->app->dir('ROOT', 'src/App/lang/' . $appLang . '.data.php'),
            )),
            'partial_user_session' => null,
            'partial_user_create' => null,
            'partial_user_edit_account' => null,
            'partial_user_edit_password' => null,
            'partial_user_delete' => null,
        );
        if ($isAuth) {
            $data['partial_user_edit_account'] = $this->app->template($this->app->dir('ROOT', 'src/User/res/partial/edit_account.html.php'), array(
                'app' => $this->app,
                'redirect' => $input->route,
                'redirect_alt' => $input->route,
                'session_token' => $sessionToken,
                'user_roles' => $userRoles,
                'user' => $userSession,
            ));
            
            $data['partial_user_edit_password'] = $this->app->template($this->app->dir('ROOT', 'src/User/res/partial/edit_password.html.php'), array(
                'app' => $this->app,
                'redirect' => $input->route,
                'redirect_alt' => $input->route,
                'session_token' => $sessionToken,
                'user' => $userSession
            ));
            
            $data['partial_user_delete'] = $this->app->template($this->app->dir('ROOT', 'src/User/res/partial/delete.html.php'), array(
                'app' => $this->app,
                'redirect' => $input->route,
                'redirect_alt' => 'user/session-unset',
                'session_token' => $sessionToken,
                'user' => $userSession
            ));
        } else {
            $data['partial_user_session'] = $this->app->template($this->app->dir('ROOT', 'src/User/res/partial/session.html.php'), array(
                'app' => $this->app,
                'redirect' => $input->route,
                'redirect_alt' => $input->route,
                'session_token' => $sessionToken,
            ));
            $data['partial_user_create'] = $this->app->template($this->app->dir('ROOT', 'src/User/res/partial/create.html.php'), array(
                'app' => $this->app,
                'redirect' => $input->route,
                'redirect_alt' => $input->route,
                'session_token' => $sessionToken,
                'user_roles' => $userRoles
            ));
        }

        $output->content = $this->app->template($this->app->dir('ROOT', 'src/Example/res/user.html.php'), $data);

        return array($input, $output, $success);
    }
}