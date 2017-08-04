<?php
set_time_limit(5);
ini_set("display_errors", "On");
error_reporting(E_ALL | E_STRICT);
require_once(__DIR__.'/elastic/vendor/autoload.php'); // 引入第三方 elasticsearch 操作库

$es = array(
        'host'=>'http://127.0.0.1:9200',
        'index'=>'ik-test',
        'type'=>'ik-test-doc'
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

        $word = isset_key($_REQUEST, 'word', null);
        $way_type = isset_key($_REQUEST, 'way_type', null);

        $params = array();
        $params['index'] = $es['index'];
        $params['type'] = $es['type'];

        // 查询
        $params['body'] = array();
        if ($word) {
            
            $params['body'] = array(
                "query" => array( 
                        "match" => array( 
                            "content"=> $word,
                             // '_id' => 1, 
                        )
                    ),
                "highlight" => array( 
                        "pre_tags" => array("<span class='bg1'>", "<span class='bg2'>"),
                        "post_tags" => array("</span>", "</span>"),
                        "fields" => array( 
                            "content" => (object) array(), // 这里API需要的正确格式是:{}空字典，而不是[]空数组
                        )
                    )
            );
            if ($way_type=='ik_smart') { // ik_smart
                $params['body']['query']['match'] = array("content1"=> $word);
                $params['body']['highlight']['fields'] = array("content1"=> (object) array());
            }else{ // ik_max_word
                
            }
        }
        
        $ret = $client->search($params);

        $data = array('total'=>0);
        if (isset($ret['hits'])) {
            $data['total'] = (int)$ret['hits']['total'];

            foreach ($ret['hits']['hits'] as $key => $val) {
                if ($word) { // 搜索列表

                    $content = '';
                    if(isset($val['highlight']['content'])){
                        $contents = $val['highlight']['content'];
                    }else{
                        $contents = $val['highlight']['content1'];
                    }
                    // print_r($contents);
                    foreach ($contents as $key1 => $val1) {
                        $content .= $val1;
                    }

                    $data['list'][] = array('id'=>$val['_id'],'content'=>$content);
                }else{ // 全部列表
                    $data['list'][] = array('id'=>$val['_id'],'content'=>$val['_source']['content']);
                }
            }
            
        }
        exit(json_encode($data));

        break;
    case 'analyze': // 分词
        $word = isset_key($_REQUEST, 'word', '');
        $ik_type = isset_key($_REQUEST, 'ik_type', '');

        $ik_types = $ik_type?array($ik_type):array('ik_max_word','ik_smart');

        $data = array();
        foreach ($ik_types as $key => $val) {
            $url = "{$es['host']}/_analyze?pretty&analyzer={$val}";
            $output = curl_($url, json_encode(array('text'=>$word)), 'post');
            $data[$val] = json_decode($output);
        }

        exit(json_encode($data));

        break;
    case 'stop': // 获取

        break;
    case 'add_test_content': // 添加测试文章

        $content = isset_key($_REQUEST, 'content', null);

        $data = array('ret'=>0,'msg'=>'success');
        if ($content) {
            $params = array(
                'index' => $es['index'],
                'type' => $es['type'],
                'id' => time(),
                'body' => array(
                        'content' => $content,
                        'content1' => $content,
                    )
            );
            $response = $client->index($params);
            // print_r($response);
            $data['response'] = $response;
        }else{
            $data['msg'] = '内容不能为空';
        }
        exit(json_encode($data));
        
        break;
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

search
{
"took": 7,
"timed_out": false,
"_shards": {
"total": 5,
"successful": 5,
"failed": 0
},
"hits": {
"total": 1,
"max_score": 0.28488502,
"hits": [
{
"_index": "test-ik",
"_type": "test-ik-smart",
"_id": "1",
"_score": 0.28488502,
"_source": {
"content": "应用程序层是一个附加层"
},
"highlight": {
"content": [
"<tag1>应用</tag1>程序层是一个附加层"
]
}
}
]
}
}

 */