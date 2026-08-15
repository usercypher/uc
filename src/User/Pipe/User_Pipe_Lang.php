<?php

class User_Pipe_Lang {
    private $pipeLang;

    public function args($args) {
        list(
            $this->pipeLang,
        ) = $args;
    } 

    public function process($input, $output) {
        $success = true;

        $this->pipeLang->key = 'user';
        $this->pipeLang->default = 'en';
        $this->pipeLang->languages = array(
            'en', 'es', 'fr', 'de', 'pt'
        );
        $this->pipeLang->directory = 'src/User/res/lang/';

        return $this->pipeLang->process($input, $output);
    }
}