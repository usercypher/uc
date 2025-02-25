<?php

class Controller {
    protected $asset = array();

    protected function view($view, $data) {
        if (isset($this->request->server['HTTP_ACCEPT_ENCODING']) && strpos($this->request->server['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
            ob_start("ob_gzhandler");
        } else {
            ob_start();
        }

        include(App::path('src/view/' . $view));
        $this->response->content = ob_get_contents();
        ob_end_clean();

        return $this->response;
    }

    protected function json($data) {
        $this->response->contentType = 'application/json';
        $this->response->content = json_encode($data);

        return $this->response;
    }

    protected function redirect($url) {
        $this->response->headers['Location'] = App::url('route', $url);

        return $this->response;
    }

    protected function addAsset($type, $link) {
        $this->asset[$type][] = $link;
    }

    protected function printAsset($type) {
        foreach ($this->asset[$type] as $link) {
            if ($type === 'css') {
                echo '<link rel="stylesheet" type="text/css" href="' . $link . '">';
            } else if ($type === 'js') {
                echo '<script src="' . $link . '"></script>' . PHP_EOL;
            }
        }
    }
}