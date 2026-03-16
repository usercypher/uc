<?php

class User_Pipe_Delete {
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
        $userSession = $this->session->get('user');

        if ($userSession['role'] !== 'root') {
            $user['id'] = $userSession['id'];
        }

        $error = [];

        if (!password_verify($user['password'], $userSession['password'])) {
            $error[] = [
                'data' => [
                    'content' => 'Password is incorrect'
                ]
            ];
        }

        if (!$error) {
            list($user, $error) = $this->app->cast($user, $this->userRepo->getSchema('delete'));
        }

        if ($error) {
            $route = $input->query['redirect'];
            foreach ($error as $e) {
                $this->userRepo->addMessage('error', $e['data']['content'], $e['data']);
            }
        } else {
            $this->userRepo->delete($user['id']);
            $this->userRepo->addMessage('success', 'user deleted successfully.');
        }

        $this->session->set('flash', $this->userRepo->getMessages());

        $output->header['location'] = $this->app->urlRoute(trim($route, '/'));

        return array($input, $output, $success);
    }
}