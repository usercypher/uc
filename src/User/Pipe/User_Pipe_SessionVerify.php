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

        if (empty($user['username']) || empty($user['password'])) {
            $error[] = [
                'data' => [
                    'content' => 'User not found.'
                ]
            ];
        }

        if (!$error && !($userFound = $this->userRepo->one('WHERE username = ? OR email = ?', array($user['username'], $user['username'])))) {
            $error[] = [
                'data' => [
                    'content' => 'User not found.'
                ]
            ];
        }

        if (!$error && $userFound && !password_verify($user['password'], $userFound['password'])) {
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

        $output->header['location'] = $this->app->url('ROUTE', trim($route, '/'));

        return array($input, $output, $success);
    }
}