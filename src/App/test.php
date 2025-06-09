<?php

class test {
    private $a;
    private $b;
    private $c;
    private $d;
    private $e;

    public function args($args) {
        // add dependency-only class
        list(
            $this->a,
            $this->b,
            $this->c,
            $this->d,
            $this->e,
        ) = $args;
    }

    public function pipe($request, $response) {
        $break = false;
        // code
        return array($request, $response, $break);
    }
}