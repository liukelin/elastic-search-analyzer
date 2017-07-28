<?php
require __DIR__.'/elastic/vendor/autoload.php'; 
$client = new Elasticsearch\Client('127.0.0.1',9200);