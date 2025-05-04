<?php

class Test {
    private $z;
    private $x;
    private $c;
    private $v;
    private $b;
    private $n;
    private $m;

    public function __construct($args = array()) {
        // add dependency-only class
        list(
            $this->z,
            $this->x,
            $this->c,
            $this->v,
            $this->b,
            $this->n,
            $this->m,
        ) = $args;
    }

    public function pipe($request, $response) {
        // code
        return array($request, $response);
    }
}