<?php

class App_Pipe_Lang {
    private $pipeLang;

    public function args($args) {
        list(
            $this->pipeLang,
        ) = $args;
    } 

    public function process($input, $output) {
        $success = true;

        $this->pipeLang->key = 'app:';
        $this->pipeLang->default = 'en';
        $this->pipeLang->languages = array(
            'en', 'es', 'fr', 'de', 'pt'
        );

        return $this->pipeLang->process($input, $output);
    }
}