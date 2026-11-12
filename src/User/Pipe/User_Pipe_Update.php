<?php

class User_Pipe_Update {
    private $app, $session, $translator;
    private $userRepo, $userEvent;

    public function args($args) {
        list(
            $this->app, 
            $this->session, 
            $this->userRepo,
            $this->translator,
            $this->userEvent,
        ) = $args;
    } 

    public function call($input, $output) {
        $success = true;

        $route = $input->query['redirect'];
        $user = $input->frame['user'];
        $userOld = $input->frame['user_old'];
        $context = $input->frame['context'];
        $userSession = $this->session->get('user');
        $userSessionOld = $userSession;
        $userRoles = $input->data['user_roles'];
        $t = $this->translator->get('user');

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

        if (!$errorEarly && !$error && empty($user['email']) && !isset($context['update_password'])) {
            $user['email'] = $user['username'] . '@invalid';
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
            $this->userRepo->addMessage('success', $t->t('user_updated_successfully'));
            if ($this->userEvent) {
                $input->data['user_event'] = array('user' => $userSession, 'user_old' => $userSessionOld);
                list($input, $output, $success) = $this->userEvent->call($input, $output);
            }
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