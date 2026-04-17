<?php

class User_Pipe_Cli_Create {
    var $app;

    function args($args) {
        list($this->app) = $args;
    }

    function process($input, $output) {
        $success = true;
        $message = '';

        if (empty($input->query['username']) || empty($input->query['password'])) {
            $message .= 'Error: Missing required parameters.' . "\n";
            $message .= 'Usage: php [file] user create --username=[value] --password=[value]' . "\n";
            $output->content = $message;
            $output->code = 1;
            $success = false;
            return array($input, $output, $success);
        }
        
        $input->frame['user'] = array(
            'username' => $input->query['username'],
            'password' => $input->query['password']
        );

        $input->query['redirect'] = '';
        $input->query['redirect_alt'] = '';
        
        $output->content = $message;

        return array($input, $output, $success);
    }
}
