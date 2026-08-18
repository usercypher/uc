<?php

class User_Pipe_Store {
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

        $route = $input->query['redirect'];
        $user = $input->frame['user'];
        $userRoles = $input->data['user_roles'];

        $t = $this->translator->get('user');

        $error = array();

        list($user, $error) = $this->app->cast($user, $this->userRepo->getSchema('insert', array(
            'user_roles' => $userRoles,
            'is_user_role_root' => false
        )));

        if ($error) {
            foreach ($error as $e) {
                $this->userRepo->addMessage('error', $e['data']['content'], $e['data']);
            }
        } elseif ($this->userRepo->insert($user)) {
            $route = $input->query['redirect_alt'];
            $this->userRepo->addMessage('success', $t->t('user_created_successfully'));
        }

        $this->session->set('flash', $this->userRepo->getMessages());

        $output->header['location'] = $this->app->url('ROUTE', trim($route, '/'));

        return array($input, $output, $success);
    }
}