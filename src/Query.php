<?php

namespace App;

class Query {
    private $query = '';

    public function __construct($str = '', $params = []) {
        $this->add($str, $params);
    }

    public function add($str, $params = []) {
        if (substr_count($str, '?') !== count($params)) {
            die("Incorrect number of parameters to query.");
        }

        $i = 0;
        $this->query .= preg_replace_callback('/\?(int)?/', function ($matches) use ($params, &$i) {
            if (isset($matches[1]) && $matches[1] === 'int') {
                return (int)$params[$i++];
            } else {
                return "'" . mysql_real_escape_string($params[$i++]) . "'";
            }
        }, $str);
    }

    public function addQuery(Query $q) {
        $this->query .= $q->get();
    }

    public function get() {
        return $this->query;
    }
}