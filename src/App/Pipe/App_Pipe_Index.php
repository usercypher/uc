<?php

class App_Pipe_Index {
    private $app, $translator;

    public function args($args) {
        list(
            $this->app,
            $this->translator,
        ) = $args;
    } 

    public function process($input, $output) {
        $success = true;

        $appLanguages = $input->data['app:languages'];
        $appLang = $input->data['app:lang'];

        $output->content = $this->app->template($this->app->dir('ROOT', 'src/App/res/index.html.php'), array(
            'app' => $this->app,
            't' => $this->translator,
            'translation_dir' => $this->app->dir('ROOT', 'src/App/lang/' . $appLang . '.data.php'),
            'languages' => $appLanguages,
            'lang' => $appLang
        ));

        return array($input, $output, $success);
    }
}