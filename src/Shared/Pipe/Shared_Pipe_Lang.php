<?php

class Shared_Pipe_Lang {
    var $app, $translator;
    var $key = 'shared';
    var $default = 'en';
    var $languages = array('en', 'es', 'fr', 'de', 'pt');
    var $directory = 'src/Shared/res/lang/';

    function args($args) {
        list(
            $this->app,
            $this->translator
        ) = $args;
    } 

    function call($input, $output) {
        $success = true;

        list($input, $output, $lang) = $this->lang($input, $output, $this->languages);

        $this->translator->set($this->key, require($this->app->dir('ROOT', $this->directory . $lang . '.data.php')));

        $input->data[$this->key . ':languages'] = $this->languages;
        $input->data[$this->key . ':lang'] = $lang;

        return array($input, $output, $success);
    }

    function lang($input, $output, $languages) {
        $lang = isset($input->param['lang']) ? $input->param['lang'] : (isset($input->cookie['lang']) ? $input->cookie['lang'] : null);
        if (!$lang || !in_array($lang, $languages)) {
            $aLang = isset($input->header['accept-language']) ? $input->header['accept-language'] : $this->default;
            $lang = $this->app->httpNegotiate($aLang, $languages);
            if (!$lang) {
                $lang = $this->default;
            }
        }
        $output->header['set-cookie'][] = strtr('lang=:lang; Path=/; Max-Age=31536000; Secure; HttpOnly; SameSite=Lax', array(
            ':lang' => $lang
        ));
        return array($input, $output, $lang);
    }
}
