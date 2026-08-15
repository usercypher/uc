<?php

class Shared_Pipe_Lang {
    var $app, $session, $translator;
    var $key = 'shared';
    var $default = 'en';
    var $languages = array('en', 'es', 'fr', 'de', 'pt');
    var $directory = 'src/Shared/res/lang/';

    function args($args) {
        list(
            $this->app,
            $this->session,
            $this->translator
        ) = $args;
    } 

    function process($input, $output) {
        $success = true;

        $lang = $this->lang($input, $this->languages);

        $this->translator->set($this->key, require($this->app->dir('ROOT', $this->directory . $lang . '.data.php')));

        $input->data[$this->key . ':languages'] = $this->languages;
        $input->data[$this->key . ':lang'] = $lang;

        return array($input, $output, $success);
    }

    function lang($input, $languages) {
        $lang = isset($input->param['lang']) ? $input->param['lang'] : $this->session->get('lang');
        if (!$lang || !in_array($lang, $languages)) {
            $aLang = isset($input->header['accept-language']) ? $input->header['accept-language'] : $this->default;
            $lang = $this->app->mimeNegotiate($aLang, $languages);
            if (!$lang) {
                $lang = $this->default;
            }
        }
        $this->session->set('lang', $lang);
        return $lang;
    }
}
