<?php

class User_Pipe_Update {
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
        $userOld = $input->frame['user_old'];
        $context = $input->frame['context'];
        $userSession = $this->session->get('user');
        $userRoles = $input->data['user_roles'];

        if ($userSession['role'] !== 'root') {
            $user['id'] = $userSession['id'];
        }

        $errorEarly = array();
        $error = array();

        if (isset($context['update_password']) && $temp = $this->userRepo->passwordVerify($userOld['password'], $userSession['password'], 'Current password is incorrect', array('field' => 'old_password'))) {
            $errorEarly[] = $temp;
        }

        if (!$errorEarly) {
            list($user, $error) = $this->app->cast($user, $this->userRepo->getSchema('update', array_merge($context, array(
                'user_old' => $userOld,
                'user_roles' => $userRoles,
                'is_session_user_role_root' => $userSession['role'] === 'root'
            ))));
        }

        $error = array_merge($errorEarly, $error);

        if ($error) {
            foreach ($error as $e) {
                $this->userRepo->addMessage('error', $e['data']['content'], $e['data']);
            }
        } elseif ($this->userRepo->update($user)) {
            $route = $input->query['redirect_alt'];
            foreach ($user as $field => $value) {
                $userSession[$field] = $value;
            }
            $this->session->set('user', $userSession);
            $this->userRepo->addMessage('success', 'user updated successfully.');
        }

        $this->session->set('flash', $this->userRepo->getMessages());

        $output->header['location'] = $this->app->url('ROUTE', trim($route, '/'));

        return array($input, $output, $success);
    }
}