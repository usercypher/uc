<?php

class Controller {
    protected function view($view, $data) {
        $data['flash'] = array();

        if (session_id() !== '' && isset($_SESSION['flash'])) {
            $data['flash'] = $_SESSION['flash'];
            unset($_SESSION['flash']);
        }

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
        $jsonData = json_encode($data);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $jsonData = '{"error": "Unable to encode data"}';
        }

        $this->response->content = $jsonData;

        return $this->response;
    }


    protected function redirect($url) {
        $this->response->headers['Location'] = App::url('route', $url);

        return $this->response;
    }
}
?>