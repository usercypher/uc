<?php

class ExtController {
    protected $request, $response;

    protected function view($view, $data) {
        ob_start();
        include(App::buildPath('src/view/' . $view));
        $this->response->content = ob_get_contents();
        ob_end_clean();

        return $this->response;
    }

    protected function json($data) {
        $this->response->contentType = 'application/json';
        $this->response->content = json_encode($data);

        return $this->response;
    }

    protected function redirect($link) {
        $this->response->headers['Location'] = App::buildLink('route', $link);

        return $this->response;
    }
}