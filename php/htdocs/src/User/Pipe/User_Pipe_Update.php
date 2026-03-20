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

        $route = $input->query['redirect_alt'];
        $user = $input->frame['user'];
        $userOld = $input->frame['user_old'];
        $context = $input->frame['context'];
        $userSession = $this->session->get('user');
        $userRoles = $input->data['user_roles'];

        if ($userSession['role'] !== 'root') {
            $user['id'] = $userSession['id'];
        }

        $error = array();

        if (!isset($context['update_password']) || !($error[0] = $this->userRepo->passwordVerify($userOld['password'], $userSession['password'], 'Current password is incorrect', array('field' => 'old_assword')))) {
            list($user, $error) = $this->app->cast($user, $this->userRepo->getSchema('update', array_merge($context, array(
                'user_old' => $userOld,
                'user_roles' => $userRoles,
                'is_session_user_role_root' => $userSession['role'] === 'root'
            ))));
        }

        if ($error) {
            $route = $input->query['redirect'];
            foreach ($error as $e) {
                $this->userRepo->addMessage('error', $e['data']['content'], $e['data']);
            }
        } else {
            foreach ($user as $field => $value) {
                $userSession[$field] = $value;
            }
            $this->session->set('user', $userSession);
            $this->userRepo->update($user);
            $this->userRepo->addMessage('success', 'user updated successfully.');
        }

        $this->session->set('flash', $this->userRepo->getMessages());

        $output->header['location'] = $this->app->urlRoute(trim($route, '/'));

        return array($input, $output, $success);
    }
}