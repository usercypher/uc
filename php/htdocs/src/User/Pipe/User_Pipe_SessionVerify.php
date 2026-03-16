<?php

class User_Pipe_SessionVerify {
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

        $error = [];

        $userFound = $this->userRepo->one('WHERE username = ? OR email = ?', array($user['username'], $user['username']));

        if (!$userFound) {
            $error[] = [
                'data' => [
                    'content' => 'User not found.'
                ]
            ];
        }

        if ($userFound && !password_verify($user['password'], $userFound['password'])) {
            $error[] = [
                'data' => [
                    'content' => 'Incorrect password.'
                ]
            ];
        }

        if ($error) {
            $route = $input->query['redirect'];
            foreach ($error as $e) {
                $this->userRepo->addMessage('error', $e['data']['content'], $e['data']);
            }
        } else {
            $this->session->set("user", $userFound);
        }

        $this->session->set('flash', $this->userRepo->getMessages());

        $output->header['location'] = $this->app->urlRoute(trim($route, '/'));

        return array($input, $output, $success);
    }
}