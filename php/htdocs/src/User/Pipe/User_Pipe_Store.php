<?php

class User_Pipe_Store {
    private $app, $session;
    private $userRepo;

    public function args($args) {
        list(
            $this->app, 
            $this->session, 
            $this->userRepo,
        ) = $args;
    } 

    public function process($input, $output) {
        $success = true;

        $route = $input->query['redirect_alt'];
        $user = $input->frame['user'];
        $userConfirm = $input->frame['user_confirm'];
        $userSession = $this->session->get('user');
        $userRoles = $input->data['user_roles'];

        if (!$userSession || $userSession['role'] !== 'root') {
            $user['role'] = 'user';
        }

        list($user, $error) = $this->app->cast($user, $this->userRepo->getSchema('insert', array(
            'user_confirm' => $userConfirm,
            'user_roles' => $userRoles
        )));

        if ($error) {
            $route = $input->query['redirect'];
            foreach ($error as $e) {
                $this->userRepo->addMessage('error', $e['data']['content'], $e['data']);
            }
        } else {
            $this->userRepo->insert($user);
            $this->userRepo->addMessage('success', 'user created successfully.');
        }

        $this->session->set('flash', $this->userRepo->getMessages());

        $output->header['location'] = $this->app->urlRoute(trim($route, '/'));

        return array($input, $output, $success);
    }
}