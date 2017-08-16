<?php
/**
 * 将热词库 同步到 suggest es index
 * 将用户输入 同步到 suggest es index
 *
 * crontab: 0 2 * * *  crontab/sync_suggest_words.php > /var/log/crontab/log-$(date +\%Y-\%m-\%d).log 2>&1
 */

$dir = __DIR__.'/../words/';
$es = array(
        'host'=>'http://127.0.0.1:9200',
        'index'=>'suggest-test',
        'type'=>'suggest-test-doc'
    );

if (!$argv) {
    exit('error please cli.');
}

$action = isset($argv[1])?trim($argv[1]):'hot_word';
$words = array();

switch ($action) {
    case 'hot_word': // 加入热词

        $allow = array('mydict.dic','sougou.dic','test.dic','custom_word.dic'); // 允许文件
        $ban = array('single_word_low_freq.dic'); // 屏蔽文件

        $files = array();
        $dir = $dir.'hot/';
        $files = scandir($dir);

        // 行读取
        foreach ($files as $key => $v) {
            if (count($allow)>0 && !in_array($v, $allow)) {
                continue;
            }
            if (in_array($v, $ban)) {
                continue;
            }

            $f = $dir.$v;
            if (pathinfo($f)['extension']=='dic') {
                if (is_file($f)) {

                    $file = @fopen($f, "r");
                    while(!@feof($file)){
                        $word = @fgets($file);
                        $word = trim($word);
                        if ($word) {
                            $words[] = $word;
                        }
                    }
                    @fclose($file);
                }

            }
        }

        break;
    case 'collect': // 用户收集词

    default:
        break;
}

foreach ($words as $k => $word) {
    put_suggest($es , $word, md5($word), 0, 1);
}

function put_suggest($es, $word, $id, $word_type=0, $weight=1){
    $d = array(
                "title" => $word,
                "word_type" => $word_type,
                "suggest" => array(
                    array(   
                        "input" => $word,
                        "weight" => $weight
                    )
                )
            );
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 180);
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

XPUT /suggest-test
{
    "mappings": {
        "suggest-test-doc" : {
            "properties" : {
                "suggest" : {           // 字段别名
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

XPOST /suggest-test/suggest-test-doc/1
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