<?php

class Shared_Pipe_Theme {
    var $app;
    var $key = 'shared';
    var $default = 'light dark';
    var $themes = array('light', 'dark', 'light dark');

    function args($args) {
        $this->app = $args[0];
        $this->key = isset($args[2]) ? $args[2] : $this->key;
        $this->default = isset($args[3]) ? $args[3] : $this->default;
        $this->themes = isset($args[4]) ? $args[4] : $this->themes;
    } 

    function call($input, $output) {
        $success = true;

        list($input, $output, $theme) = $this->theme($input, $output, $this->themes);

        $input->data[$this->key . ':default'] = $this->default;
        $input->data[$this->key . ':theme'] = $theme;
        $input->data[$this->key . ':themes'] = $this->themes;

        return array($input, $output, $success);
    }

    function theme($input, $output, $themes) {
        $theme = isset($input->param['theme']) ? $input->param['theme'] : null;
        if (!$theme || !in_array($theme, $themes)) {
            $theme = isset($input->query['theme']) ? $input->query['theme'] : null;
            if (!$theme || !in_array($theme, $themes)) {
                $theme = $this->default;
            }
        }
        $output->header['set-cookie'][] = strtr('theme=:theme; Path=/; Max-Age=31536000; Secure; HttpOnly; SameSite=Lax', array(
            ':theme' => $theme
        ));
        return array($input, $output, $theme);
    }
}
