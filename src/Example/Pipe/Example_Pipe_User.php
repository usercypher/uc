<?php

class Example_Pipe_User {
    private $app, $session, $translator;

    public function args($args) {
        list(
            $this->app, 
            $this->session,
            $this->translator
        ) = $args;
    } 

    public function call($input, $output) {
        $success = true;

        $flash = $this->session->remove('flash');
        $sessionToken = $this->session->get('session_token');
        $userSession = $this->session->get('user');
        $userRoles = $input->data['user_roles'];
        $isAuth = isset($userSession);

        $appTranslator = $this->translator->get('app');

        $exampleLanguages = $input->data['example:languages'];
        $exampleLang = $input->data['example:lang'];
        $exampleTranslator = $this->translator->get('example');

        $userLang = $input->data['user:lang'];
        $userTranslator = $this->translator->get('user');

        $data = array(
            'app' => $this->app,
            'is_auth' => $isAuth,
            'route' => $input->route,
            't' => $exampleTranslator,
            'languages' => $exampleLanguages,
            'lang' => $exampleLang,
            'partial_app_script' => $this->app->template($this->app->dir('ROOT', 'src/App/res/partial/script.html.php'), array(
                'app' => $this->app,
                'flash' => $flash,
                't' => $appTranslator
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
                't' => $userTranslator,
                'lang' => $userLang,
                'redirect' => $input->route,
                'redirect_alt' => $input->route,
                'session_token' => $sessionToken,
                'user_roles' => $userRoles,
                'user' => $userSession,
            ));
            
            $data['partial_user_edit_password'] = $this->app->template($this->app->dir('ROOT', 'src/User/res/partial/edit_password.html.php'), array(
                'app' => $this->app,
                't' => $userTranslator,
                'lang' => $userLang,
                'redirect' => $input->route,
                'redirect_alt' => $input->route,
                'session_token' => $sessionToken,
                'user' => $userSession
            ));
            
            $data['partial_user_delete'] = $this->app->template($this->app->dir('ROOT', 'src/User/res/partial/delete.html.php'), array(
                'app' => $this->app,
                't' => $userTranslator,
                'lang' => $userLang,
                'redirect' => $input->route,
                'redirect_alt' => 'user/session-unset?redirect=' . $input->route,
                'session_token' => $sessionToken,
                'user' => $userSession
            ));
        } else {
            $data['partial_user_session'] = $this->app->template($this->app->dir('ROOT', 'src/User/res/partial/session.html.php'), array(
                'app' => $this->app,
                't' => $userTranslator,
                'lang' => $userLang,
                'redirect' => $input->route,
                'redirect_alt' => $input->route,
                'session_token' => $sessionToken,
            ));
            $data['partial_user_create'] = $this->app->template($this->app->dir('ROOT', 'src/User/res/partial/create.html.php'), array(
                'app' => $this->app,
                't' => $userTranslator,
                'lang' => $userLang,
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