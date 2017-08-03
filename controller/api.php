<?php
require_once(__DIR__.'/elastic/vendor/autoload.php'); // 引入第三方 elasticsearch 操作库

$es = array(
        'host'=>'http://127.0.0.1:9200',
    );

// $client = new Elasticsearch\Client('127.0.0.1:9200');
// $docs = $client->search($params);
$hosts = array($es['host']);
$client = Elasticsearch\ClientBuilder::create()->setHosts($hosts)->build();
// $client = $client->getElasticClient();
// $client->index = 'test-ik';
// $client->type = 'test-ik-smart';

$action = isset_key($_REQUEST, 'action', null);

switch ($action) {
    case 'search': // 搜索

        $wd = isset_key($_REQUEST, 'wd', '');

        $params = array();
        $params['index'] = 'test-ik';
        $params['type'] = 'test-ik-smart';

        // 查询
        $params['body'] = array(
            "query" => array( 
                    "match" => array( 
                        "content"=> $wd,
                         // '_id' => 1, 
                    )
                ),
            "highlight" => array( 
                    "pre_tags" => array("<tag1>", "<tag2>"),
                    "post_tags" => array("</tag1>", "</tag2>"),
                    "fields" => array( 
                        "content" => (object) array(), // 这里API需要的正确格式是:{}空字典，而不是[]空数组
                    )
                )
        );
        $rtn = $client->search($params);

        break;
    case 'analyze': // 分词
        $wd = isset_key($_REQUEST, 'wd', '');
        $ik_type = isset_key($_REQUEST, 'ik_type', '');

        $ik_types = $ik_type?array($ik_type):array('ik_max_word','ik_smart');

        $data = array();
        foreach ($ik_types as $key => $val) {
            $url = "{$es['host']}/_analyze?pretty&analyzer={$val}";
            $output = curl_($es['host'], array('text'=>$wd), 'post');
            $data[$val] = json_decode($output);
        }

        exit(json_encode($data));

        break;
    case 'stop': // 获取

    default:
        break;
}

/**
 * [isset_key description]
 * @param  [type] $arr [description]
 * @param  [type] $key [description]
 * @param  string $t   [description]
 * @return [type]      [description]
 */
function isset_key($arr, $key, $t=''){
    return (isset($arr[$key]) && $arr[$key]!='')?$arr[$key]:$t;
}

/**
 * [curl_ description]
 * @param  [type] $url    [description]
 * @param  [type] $data   [description]
 * @param  string $action [description]
 * @return [type]         [description]
 */
function curl_($url, $data, $action='get'){
    // $post_data = array ("username" => "bob","key" => "12345");
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if($action=='post'){
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }else{
        curl_setopt($ch, CURLOPT_HEADER, 0);
    }
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}


/**
_analyze: 
{
"tokens": [
{
"token": "马",
"start_offset": 0,
"end_offset": 1,
"type": "CN_WORD",
"position": 0
}
,
{
"token": "克莱",
"start_offset": 1,
"end_offset": 3,
"type": "CN_WORD",
"position": 1
}
,
{
"token": "莱",
"start_offset": 3,
"end_offset": 4,
"type": "CN_WORD",
"position": 2
}
]
}



 */