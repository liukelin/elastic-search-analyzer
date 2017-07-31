<?php
require_once(__DIR__.'/elastic/vendor/autoload.php'); 


// $client = new Elasticsearch\Client('127.0.0.1:9200');
// $docs = $client->search($params);
$hosts = array('http://127.0.0.1:9200');
$client = Elasticsearch\ClientBuilder::create()->setHosts($hosts)->build();
// $client = $client->getElasticClient();

// 查询
$params = array(
    'index' => 'test-ik',
    'type' => 'test-ik-smart',
    "query" => array( 
            "match" => array( 
                "content"=>"应用程序层是一个附加层",
                 // '_id' => 1, 
            )
        ),
    "highlight" => array( 
            "pre_tags" => array("<tag1>", "<tag2>"),
            "post_tags" => array("</tag1>", "</tag2>"),
            "fields" => array( 
                "content" => array()
            )
        )
);


$rtn = $client->search($params);

// 分词


echo '<pre>';
print_r($rtn);
echo '</pre>';