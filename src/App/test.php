<?php

class test {
    private $az;
    private $x;
    private $c;
    private $v;
    private $s;
    private $e;

    public function args($args) {
        // add dependency-only class
        list(
            $this->az,
            $this->x,
            $this->c,
            $this->v,
            $this->s,
            $this->e,
        ) = $args;
    }

    public function pipe($request, $response) {
        $break = false;
        // code
        return array($request, $response, $break);
    }
}