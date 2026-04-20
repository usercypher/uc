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

        $route = $input->query['redirect'];
        $user = $input->frame['user'];
        $userSession = $this->session->get('user');

        if ($userSession['role'] !== 'root') {
            $user['id'] = $userSession['id'];
        }

        $errorEarly = array();
        $error = array();

        if ($tmp = $this->userRepo->passwordVerify($user['password'], $userSession['password'], 'Password is incorrect', array('field' => 'password'))) {
            $errorEarly[] = $tmp;
        }

        if (!$errorEarly) {
            list($user, $error) = $this->app->cast($user, $this->userRepo->getSchema('delete'));
        }

        $error = array_merge($errorEarly, $error);

        if ($error) {
            foreach ($error as $e) {
                $this->userRepo->addMessage('error', $e['data']['content'], $e['data']);
            }
        } elseif ($this->userRepo->delete($user['id'])) {
            $route = $input->query['redirect_alt'];
            $this->userRepo->addMessage('success', 'user deleted successfully.');
        }

        $this->session->set('flash', $this->userRepo->getMessages());

        $output->header['location'] = $this->app->urlRoute(trim($route, '/'));

        return array($input, $output, $success);
    }
}