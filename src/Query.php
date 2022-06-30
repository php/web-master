<?php

namespace App;

class Query {
    private $query = '';
    /** @var array $params */
    private $params = [];

    public function __construct($str = '', $params = []) {
        $this->add($str, $params);
    }

    public function add($str, $params = []) {
        $this->query .= $str;
        $this->params = array_merge($this->params, $params);
    }

    public function addQuery(Query $q) {
        $this->query .= $q->get();
        $this->params = array_merge($this->params, $q->getParams());
    }

    public function get() {
        return $this->query;
    }

    public function getParams(): array {
        return $this->params;
    }
}