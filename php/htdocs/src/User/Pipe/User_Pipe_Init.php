<?php

class User_Pipe_Init {
    public function process($input, $output) {
        $success = true;

        $input->data['user_is_auth_route'] = 'login';
        $input->data['user_is_not_auth_route'] = '';

        $input->data['user_roles'] = array('root', 'user');
        
        return array($input, $output, $success);
    }
}