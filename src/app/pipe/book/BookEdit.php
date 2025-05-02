<?php

class BookEdit {
    private $app, $session;
    private $bookModel;

    public function __construct($args = array()) {
        list(
            $this->app, 
            $this->session, 
            $this->bookModel
        ) = $args;
    } 

    public function pipe($request, $response) {
        $data = $request->params;

        $response->content = $response->html($this->app->path('res', 'html/edit.php'), array(
            'app' => $this->app,
            'flash' => $this->session->unset('flash'),
            'csrf_token' => $this->session->get('csrf_token'),
            'book' => $this->bookModel->first('id = ?', array($data['id']))
        ));

        return array($request, $response);
    }
}