<?php
require_once(__DIR__.'/elastic/vendor/autoload.php'); 


// $client = new Elasticsearch\Client('127.0.0.1:9200');
// $docs = $client->search($params);
$hosts = array('http://127.0.0.1:9200');
$client = Elasticsearch\ClientBuilder::create()->setHosts($hosts)->build();
// $client = $client->getElasticClient();
// $client->index = 'test-ik';
// $client->type = 'test-ik-smart';

$params = array();
$params['index'] = 'test-ik';
$params['type'] = 'test-ik-smart';

// 查询
$params['body'] = array(
    // 'index' => 'test-ik',
    // 'type' => 'test-ik-smart',
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
                "content" => (object) array(), // 这里API需要的正确格式是：{} 而不是 []
            )
        )
);


$rtn = $client->search($params);

// 分词


echo '<pre>';
print_r($rtn);
echo '</pre>';