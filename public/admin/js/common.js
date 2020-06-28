
var scrollToBottom = true;
var stopScrollTime = 0;

// 展示日志
function checkLog() {
    console.log("打开日志");

    // 首次获取日志信息
    reloadLog(function () {
        layui.use('layer',function () {
            var layer = layui.layer;
            layerIndex = layer.open({
                type: 1,
                area:['1220px','620px'],
                title: false,
                offset:"100px",
                closeBtn: 0,
                shadeClose: true,
                content: $(".log-container"),
                success: function(){
                    $(".log-container").css("display","block");

                    // 开启计时器
                    startTimer();

                    // 自动滚动带最底部
                    var $scroll = $(".log-box");
                    $scroll.scrollTop($scroll[0].scrollHeight);
                },
                end:function () {
                    $(".log-container").css("display","none");
                    // 停止计时器
                    stopTimer();
                }
            });
        });
    },true,50000);
}

// 刷新日志信息
function reloadLog(success=null,withHud=false,timeout=10000) {
    console.log("刷新日志");
    var url = baseUrl + "/Logs/log.txt";
    // 手动滑动日志文件，停止自动滚动到最底部
    $(".log-box").scroll(function () {
        console.log("手动滚动日志");
        // 手动滚动监听
        scrollToBottom = false;
        stopScrollTime = 0;
    });

    get(url,function (data) {
        if (success){
            success();
        }

        if (!scrollToBottom){
            // 停止自动滚动计时
            stopScrollTime++;
        }

        // 停止手动滚动5秒后自动开启自动刷新并滚动到日志最下方
        if (stopScrollTime > 5 && !scrollToBottom){
            scrollToBottom = true;
        }

        var $scroll = $(".log-box");
        $scroll.html('<pre>'+ data +'</pre>');
        if (scrollToBottom){
            // 自动滚动到底部
            console.log("日志自动滚动到最底部");
            $scroll.scrollTop($scroll[0].scrollHeight);
        }
    },withHud,false,timeout,null,'text');
}

// 清空日志
function clearLog() {
    var url = baseUrl + "?m=admin&c=API_FileManager&a=clearLog&API=";
    get(url,null,false);
}


// 手动刷新日志
function refreshLog() {
    console.log("手动刷新日志");
    // 停止计时器
    stop();
    // 刷新日志
    reloadLog(function () {
        // 开启计时器
        startTimer();
        // 自动滚动带最底部
        var $scroll = $(".log-box");
        $scroll.scrollTop($scroll[0].scrollHeight);
    },true,50000);
}

// 开启计时器
function startTimer() {
    // 开启计时器
    $('#timer').timer({
        duration : '10s',
        callback : reloadLog,
        repeat : true
    });
}

// 停止计时器
function stopTimer() {
    $('#timer').timer("remove");
}

// 跳转到添加rsa密钥页面携带的参数
function pageFlag() {
    return "login";
}



