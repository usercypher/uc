<?php

class Shared_Pipe_Lang {
    var $app, $session;
    var $key = '';
    var $default = 'en';
    var $languages = array('en');

    function args($args) {
        list(
            $this->app,
            $this->session,
        ) = $args;
    } 

    function process($input, $output) {
        $success = true;

        $input->data[$this->key . 'languages'] = $this->languages;
        $input->data[$this->key . 'lang'] = $this->lang($input, $this->languages);

        return array($input, $output, $success);
    }

    function lang($input, $languages) {
        $lang = isset($input->param['lang']) ? $input->param['lang'] : $this->session->get('lang');
        if (!$lang) {
            $aLang = isset($input->header['accept-language']) ? $input->header['accept-language'] : $this->default;
            $lang = $this->app->mimeNegotiate($aLang, $languages);
            if (!$lang) {
                $lang = $this->default;
            }
            $this->session->set('lang', $lang);
        }
        return $lang;
    }
}
