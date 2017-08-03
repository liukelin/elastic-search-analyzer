<?php
/**
 * 提供远程 字扩展词字典、远程扩展停止词字典 
 *
 * ik 接收两个返回的头部属性 Last-Modified 和 ETag，只要其中一个有变化，就会触发更新，ik 会每分钟获取一次
 * 为了避免对es造成不必要的压力，最好做成更新了词库 再改变头部属性
 *
 * liukelin
 * 
 * http://xxx/api/custom_word.php?action=hot
 * http://xxx/api/custom_word.php?action=stop
 * 
 */

$action = isset($_GET['action'])?$_GET['action']:null;
$dir = __DIR__.'/../words/';
$words = '';

// $action = 'hot';

switch ($action) {

    case 'hot': // 扩展词
        
        $dir = $dir.'hot/';
        $file = scandir($dir);
        foreach ($file as $key => $v) {
            $words .= @include($dir.$v);
            $words .= "\r\n";
        }
        $words = trim($words);

        break;  
    case 'stop': // 扩展停止词

        $dir = $dir.'stop/';
        $file = scandir($dir);
        foreach ($file as $key => $v) {
            $words .= @include($dir.$v);
            $words .= "\r\n";
        }
        $words = trim($words);

        break;
    default:
        break;

    $s = <<<'EOF'
        {$words}
EOF;
    // 为了避免对es造成不必要的压力，最好做成更新了词库 再改变头部属性 time()
    header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).' GMT', true, 200);
    header('ETag: "5816f349-19"');
    exit($s);
}
