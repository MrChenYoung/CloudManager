
var scrollToBottom = true;
var stopScrollTime = 0;
$(document).ready(function () {
    // 手动滑动日志文件，停止自动滚动到最底部
    $(".log-box").scroll(function () {
        console.log("手动滚动日志");
        // 手动滚动监听
        scrollToBottom = false;
        stopScrollTime = 0;
    });
});

// 展示日志
function checkLog() {
    console.log("打开日志");

    // 开启计时器
    $('#timer').timer({
        duration : '2s',
        callback : reloadLog,
        repeat : true
    });
    $(".log-container").css("display","block");

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
            },
            end:function () {
                $(".log-container").css("display","none");
                // 停止计时器
                $('#timer').timer("remove");
            }
        });
    });
}

// 刷新日志信息
function reloadLog() {
    console.log("刷新日志");
    var url = baseUrl + "/Logs/log.txt";
    get(url,function (data) {

        // 停止手动滚动5秒后自动开启自动刷新并滚动到日志最下方
        if (stopScrollTime > 5 && !scrollToBottom){
            scrollToBottom = true;
        }

        var $scroll = $(".log-box");
        $scroll.html('<pre>'+ data +'</pre>');
        if (scrollToBottom){
            // 自动滚动到底部
            $scroll.scrollTop($scroll[0].scrollHeight);
        }
    },false,false,10000,null,'text');
}

// 跳转到添加rsa密钥页面携带的参数
function pageFlag() {
    return "login";
}



