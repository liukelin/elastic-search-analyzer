<?php
/**
 * liukelin
 */
set_time_limit(5);
ini_set("display_errors", "On");
error_reporting(E_ALL | E_STRICT);
require_once(__DIR__.'/elastic/vendor/autoload.php'); // 引入第三方 elasticsearch 操作库

$dir = __DIR__.'/../words/';
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
        $ik_type = isset_key($_REQUEST, 'ik_type', null);

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
                // 搜索结果返回设置
                "highlight" => array( 
                        "pre_tags" => array("<span class='bg1'>", "<span class='bg2'>"),
                        "post_tags" => array("</span>", "</span>"),
                        "fields" => array( 
                            // "content" => (object) array(), // 这里API需要的正确格式是:{}空字典，而不是[]空数组
                            "content" => array(
                                        "fragment_size"=> 150, //每个字段都可以设置高亮显示的字符片fragment_size段大小（默认为100），
                                        "number_of_fragments"=>0, // 如果number_of_fragments值设置为0则片段产生（可以理解为一个片段返回字段所有内容），
                                        // "order"=>0, // 当order设置为score时候可以按照评分进行 片段 排序
                                )
                        )
                    )
            );
            if ($ik_type=='ik_smart') { // 切换 ik_smart模式字段 
                $params['body']['query']['match'] = array("content1"=> $word);
                $params['body']['highlight']['fields'] = array(
                                                    "content1"=> array(
                                                            "fragment_size"=> 150,
                                                            "number_of_fragments"=>0
                                                        )
                                                    );
            }else{ // ik_max_word
                
            }
        }
        
        $ret = $client->search($params);

        $data = array('ret'=>0,'total'=>0);
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
                    // 将所有搜索结果片段 取出
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
            // 在elasticsearch-php 库中没有找到相应的方法，自己curl
            $url = "{$es['host']}/_analyze?pretty&analyzer={$val}";
            $output = curl_($url, json_encode(array('text'=>$word)), 'post');
            $data[$val] = json_decode($output);
        }

        exit(json_encode($data));

        break;
    case 'suggester': // 输入 词库 纠错补全api

        exit(json_encode(array('as','asd','asd','asd')));
        break;
    case 'custom_word': // 自定义分词管理

        $type = isset_key($_REQUEST, 'type', 'hot');
        $work = isset_key($_REQUEST, 'work', null);

        $hotDir = $dir.'hot/custom_word.dic';
        $stopDir = $dir.'stop/custom_word.dic';
        $Dir = ($type=='stop')?$stopDir:$hotDir;

        if ($_GET) { // get

            $works = array();
            if (is_file($Dir)) {

                $file = @fopen($Dir, "r");
                $i=0;
                while(!@feof($file)){
                    $works[$i]= @fgets($file);
                    $i++;
                }
                @fclose($file);
                
                $works = array_filter($works);
            }
            
            exit(json_encode(array('ret'=>0,'data'=>$works)));

        }elseif($_POST){ // add 
            $ret = array();
            $work = trim($work);
            if ($work) {
                $file=fopen($Dir, "a");
                if($file){
                    fwrite($file, $work."\r\n");
                    fclose($file);
                }
                $ret = array('ret'=>0, 'msg'=>'添加成功，但es-ik生效需要1分钟以上~');

                
                $file=fopen($dir.'word_version.txt', "w");
                if($file){
                    fwrite($file, time());
                    fclose($file);
                }

            }else{
                $ret = array('ret'=>-1, 'msg'=>'内容为空.');
            }
            exit(json_encode($ret));
        }

        break;
    case 'add_test_content': // 添加测试文章

        $content = isset_key($_REQUEST, 'content', null);

        $data = array('ret'=>0,'msg'=>'success.');
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