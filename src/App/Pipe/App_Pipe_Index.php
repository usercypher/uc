<?php

class App_Pipe_Index {
    private $app, $translator;

    public function args($args) {
        list(
            $this->app,
            $this->translator
        ) = $args;
    } 

    public function process($input, $output) {
        $success = true;

        $appLanguages = $input->data['app:languages'];
        $appLang = $input->data['app:lang'];
        $appTranslator = $this->translator->get('app');

        $output->content = $this->app->template($this->app->dir('ROOT', 'src/App/res/index.html.php'), array(
            'app' => $this->app,
            't' => $appTranslator,
            'languages' => $appLanguages,
            'lang' => $appLang
        ));

        return array($input, $output, $success);
    }
}