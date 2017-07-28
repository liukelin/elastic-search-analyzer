<?php
require_once(__DIR__.'/elastic/vendor/autoload.php'); 
// $client = new Elasticsearch\Client('127.0.0.1:9200');
$hosts = array('192.168.1.10');
$client = Elasticsearch\ClientBuilder::create()->setHosts($hosts)->build();
// $client = $client->getElasticClient();
$params = array(
    'index' => 'test-ik',
    'type' => 'test-ik-smart',
    'body' => array(
        'query' => array(
            'match' => array(
                '_id' => 1,
           )
        )
    )
);
$rtn = $client->search($params);
echo '<pre>';
print_r($rtn);
echo '</pre>';