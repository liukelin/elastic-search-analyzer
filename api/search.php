<?php
require_once(__DIR__.'/elastic/vendor/autoload.php'); 

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
// $client = new Elasticsearch\Client('127.0.0.1:9200');
// $docs = $client->search($params);
$hosts = array('http://127.0.0.1:9200');
$client = Elasticsearch\ClientBuilder::create()->setHosts($hosts)->build();
// $client = $client->getElasticClient();

$rtn = $client->search($params);
echo '<pre>';
print_r($rtn);
echo '</pre>';