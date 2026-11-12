<?php

class User_Pipe_SessionVerify {
    private $app, $session, $translator;
    private $userRepo;

    public function args($args) {
        list(
            $this->app, 
            $this->session, 
            $this->userRepo,
            $this->translator,
        ) = $args;
    } 

    public function call($input, $output) {
        $success = true;

        $route = $input->query['redirect_alt'];
        $user = $input->frame['user'];
        $t = $this->translator->get('user');

        $error = [];

        if (empty($user['username']) || empty($user['password'])) {
            $error[] = [
                'data' => [
                    'content' => $t->t('user_not_found')
                ]
            ];
        }

        if (!$error && !($userFound = $this->userRepo->one('WHERE username = ? OR email = ?', array($user['username'], $user['username'])))) {
            $error[] = [
                'data' => [
                    'content' => $t->t('user_not_found')
                ]
            ];
        }

        if (!$error && $userFound && !password_verify($user['password'], $userFound['password'])) {
            $error[] = [
                'data' => [
                    'content' => $t->t('incorrect_password')
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

        $flash = array();
        foreach (array(
            $this->userRepo->getMessages(),
        ) as $messages) {
            foreach ($messages as $m) {
                $m['data']['route'] = $input->route;
                $flash[] = $m;
            }
        }

        $this->session->set('flash', $flash);

        $output->header['location'] = $this->app->url('route', $route);

        return array($input, $output, $success);
    }
}