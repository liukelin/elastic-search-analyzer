<?php
/**
 * 将热词库 同步到 suggest es index
 */

$dir = __DIR__.'/../words/';
$es = array(
        'host'=>'http://127.0.0.1:9200',
        'index'=>'suggest-test',
        'type'=>'suggest-test-doc'
    );

$words = '';
$files = array();
        
$dir = $dir.'hot/';
$files = scandir($dir);

// 行读取
foreach ($files as $key => $v) {
    $f = $dir.$v;
    if (pathinfo($f)['extension']=='dic') {
        if (is_file($f)) {

            $file = @fopen($f, "r");
            while(!@feof($file)){
                $work = @fgets($file);
                $work = trim($work);
                if ($work) {

                    put_suggest($es , $work, time(), 0, 1);

                }
            }
            @fclose($file);
        }

    }
}

function put_suggest($es, $work, $id, $word_type=0, $weight=1){
    $d = array(
                "title" => $work,
                "word_type" => $word_type,
                "suggest" => array(
                    array(   
                        "input" => $work,
                        "weight" => $weight
                    )
                )
            );
    $id = time();
    $url = "{$es['host']}/{$es['index']}/{$es['type']}/{$id}/";
    $output = curl_($url, json_encode($d), 'post');
    return $output;
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

PUT /suggest-test
{
    "mappings": {
        "suggest-test-doc" : {
            "properties" : {
                "suggest" : {           // suggest
                    "type" : "completion"
                },
                "title" : {     // 记录名称，因为设置type为completion的字段，在数据上不显示，为了维护方便，设置个字段显示
                    "type": "keyword"
                },
                "word_type" : {    // 词类型 0词库词 1用户搜索搜集词
                    "type": "string"
                }
            }
        }
    }
}

PUT /suggest-test/suggest-test-doc/1
{
  "title": "阿坝师范高等专科学校",
  "suggest": [
    {
      "input": "阿坝师范高等专科学校",
      "weight": 3
    }
  ]
}

 */