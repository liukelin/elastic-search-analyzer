    
## 目录结构

~~~
├─controller/           请求接口目录
│  ├─elastic/           elasticsearch-php 操作类库
│  │
│  ├─api.php            请求接口
│  ├─custom_word.php    提供给es ik 远程更新的分词库url
│  └─setup_word.php     配置词库操作接口
│  
├─words/                存放词库文件夹
│  ├─hot/               热词词库文件夹
│  │  ├─sougou.dic      搜狗热词库
│  │  ├─single_word_low_freq.dic      生僻字的单字词库
│  │  └─ ...            更多词库文件
│  │  
│  ├─stop/              屏蔽分词词库文件夹
│  │  ├─ext_stopword.dic    屏蔽词词库文件
│  │  └─ ...            更多屏蔽词词库文件
│  │  
│  ├─backup/            一些待用的词库（如果你需要分词搜索结果 不出现/不出现，那就把该文件放到stop/或者 hot/ 下）
│  │  ├─sougou.dic      搜狗热词库
│  │  ├─single_word_full.dic        完整的单字词库（如果你需要分词搜索结果不出现单字，那就把该文件放到stop/下）
│  │  ├─single_word.dic             常见单字的单字词库 （如果你需要分词搜索结果不出现单字，那就把该文件放到stop/下）
│  │  ├─single_word_low_freq.dic    生僻字的单字词库
│  │  └─ ...            更多词库文件
│  
├─js/                   js
├─analyzer.html         demo页面
~~~

    一、es的ik分词插件支持远程热词 和屏蔽词 的更新

    二、尽量规避单个语气字的词汇出现

    三、自定义分词的维护，es-ik插件支持远程词库
    1、可自行设置自定义分词词汇，使用php数组文件配置保存，提供远程访问库功能
    2、提供自定义词汇、自定义规避词汇设置
    3、支持热更
    4、可自动更新

    四、自动补全、纠错机制
      es自带了一个suggester，可以使用。

    四、同义词的映射的维护
    1、使用redis zset结构保存对应关系
    2、首先使用分词操作，将语句分词，再对每个分词查找他们的对应关系
    比如{"text":"burn罗玉凤”} 拆词应该是 burn/ 罗玉凤, 
    罗玉凤:使用自定义分词维护，  凰：是burn的映射同义词， 为了避免同义词追加后与原查询语句干扰(避免分出“凤凰”)，用“-” 分割
    {"text":"burn罗玉凤-凰”}
    （希望可以在网上找到词库，进行自动维护）

    ....

demo地址:

    [http://demo.liukelin.top/elastic-search-analyzer/analyzer.html](http://demo.liukelin.top/elastic-search-analyzer/analyzer.html)

