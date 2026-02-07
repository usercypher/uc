<?php

class User_Pipe_Init {
    public function process($input, $output) {
        $success = true;

        $input->data['user_roles'] = array('root', 'user');
        
        return array($input, $output, $success);
    }
}