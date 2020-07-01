// 网络请求baseUrl
var baseUrl = $("#base_url").val();
// 图片所在文件夹
var imageBaseUrl = $("#image_base_url").val();
//layui loading
var loadIndex;
// 保存到cookie的公钥键
var publickKey = "rsaPublicKey";
// 保存到cookie的私钥键
var privateKey = "rsaPrivateKey";
// rsa公钥内容
var rsaPublicContent;
// rsa私钥内容
var rsaPrivateContent;
$(document).ready(function () {
    // 获取公钥和私钥
    rsaPublicContent = getCookie(publickKey);
    rsaPrivateContent = getCookie(privateKey);

    // 如果公钥或私钥为空 提示去添加
    if (rsaPrivateContent == null || rsaPublicContent == null || rsaPublicContent.length == 0 || rsaPrivateContent.length == 0){
        // rsa密钥为空 跳转到添加页面
        var url = baseUrl+"/addRsaKey.php&m=admin";
        window.location = url;
    }
});

// 显示hud
function showHud() {
    layui.use('layer', function() {
        var layer = layui.layer;
        layer.ready(function () {
            loadIndex = layer.load(0,{
                shade: [0.6, '#FFF'],
                shadeClose: true
            });
        });
    });
};

// 隐藏hud
function hideHud() {
    layui.use('layer', function() {
        var layer = layui.layer;
        layer.ready(function () {
            layer.close(loadIndex);
        });
    });
}

/**
 * 显示提示信息
 * @param message
 */
function toast(message,success=true) {
    layui.use('layer',function () {
        var layer = layui.layer;
        if (typeof message == 'string'){
            var time = message.length * 0.3 * 1000;
            var skin = success ? "" : "toast-fail-style";
            layer.msg(message, {
                // 多长事件后自动关闭(ms)
                time: time,
                offset: 'auto',
                skin: skin
            });
        }
    });
}

// 发送GET网络请求
function get(url, complete=null,withHud=true,showToast=false,timeOut=10000,failFunc=null,type='json') {
    request(url,null,complete,withHud,showToast,timeOut,failFunc,type);
}

// 发送post网络请求
function post(url, data=null, complete=null,withHud=true,showToast=false,timeOut=10000,ailFunc=null,type='json') {
    request(url,data,complete,withHud,showToast,timeOut,failFunc,type);
}

// 发送请求
function request(url, data=null, complete=null,withHud=true,showToast=false,timeOut=10000,failFunc=null,type='json') {
    if (withHud){
        showHud();
    }

    var method = data == null ? "get" : "post";
    console.log("方法:" + method);
    console.log("请求地址:" + url);

    $.ajax({
        type : method,
        url : url,
        data: data,
        cache : false,
        async : true,
        timeout: timeOut,
        dataType : type,
        beforeSend:function (jqxhr,settings) {
            jqxhr.requestURL = url;
        }
        ,complete: function () {
            console.log("加载完成");
            if (withHud) {
                hideHud();
            }
        }
        ,success: function (data,state,xhr) {
            console.log("成功:" + xhr.requestURL);
            if (type == 'json'){
                var message = typeof(data.data) == 'string' ? data.data : data.message;
                if (data.code == 200){
                    showToast ? toast(message) : "";
                    complete ? complete(data) : "";

                }else if(data.code != 200){
                    // 提示失败信息
                    toast(message,false);
                }
            }else {
                complete ? complete(data) : "";
            }
        },
        error: function (xhr,textStatus,errorMessage) {
            if (withHud) {
                hideHud();
            }
            toast("请求失败:" + errorMessage);

            console.log("请求失败了偶尔Lee:" + errorMessage);
            if (failFunc){
                failFunc();
            }
        }
    });
}

// 提示框
function showAlert(message,btns=null) {
    var btnTitles = [];
    var btnFunctions = [];
    if (btns){
        for (var i = 0; i < btns.length; i++){
            var btn = btns[i];
            btnTitles[i] = btn.title;
            btnFunctions[i] = btn.func;
        }
    }else {
        btnTitles = ["好的"]
        btnFunctions = [function () {}]
    }

    layui.use('layer',function () {
        var layer = layui.layer;
        layer.open({
            type: 1
            ,title: false //不显示标题栏
            ,closeBtn: false
            ,area: '300px;'
            ,shade: 0.8
            ,id: 'LAY_layuipro' //设定一个id，防止重复弹出
            ,btn: btnTitles
            ,btnAlign: 'c'
            ,moveType: 1 //拖拽模式，0或者1
            ,content: '<div style="padding: 50px; line-height: 22px; background-color: #393D49; color: #fff; font-weight: 300;">'+message+'</div>'
            ,yes: function(index, layero){
                var btnIndex = 0;
                // 按钮1回调
                if (btnIndex <= btnFunctions.length){
                    btnFunctions[btnIndex](btnIndex);
                }
                // 关闭layer
                layer.close(index);
            }
            ,btn2:function (index,layero) {
                var btnIndex = 1;
                if (btnIndex<btnFunctions.length){
                    btnFunctions[btnIndex](btnIndex);
                }
            }
        });
    });
}

layui.use('form',function () {
    var form = layui.form;
    form.render();
});

// 复制输入框文本到剪切板
function copyInputContent(inputDom,message="复制成功") {
    inputDom.select();
    document.execCommand("Copy");
    toast(message);
}

// 检查需要函数是否被禁用了
function checkDisableFunc() {
    var functions = ["exec","proc_open"];
    functions = functions.join(",");
    var url = baseUrl
        + "?m=admin&c=API_GetLocalMovies&a=functionDisabled&API=&function_name="+functions;

    get(url,function (data) {
        var result = data.data;
        if (result["disabled"]){
            showAlert("以下函数被禁用了，请取消禁用\r\n"+result["funcs"]);
        }
    },false)
}

// 给密码加密
function RSAEncryptPass(pass) {
    return publicEncrypt(rsaPublicContent,pass);
}

// 给密码解密
function RSADecryptPass(pass) {
    return privateDecrypt(rsaPrivateContent,pass);
}

// 确认提示 操作前提示确认，防止误操作
function confirmAlert(confirmFunction,message="确定删除？") {
    var btns = [
        {
            title: "确定",
            func: function (index) {
                if (confirmFunction){
                    confirmFunction();
                }
            }
        },
        {
            title: "取消",
            func: function () {}
        }
    ];
    showAlert(message,btns);
}

// 初始化右键菜单
function mouseRightMenuInit($element,data,openAction=null,bindAction=null) {
    layui.config({base: '/public/common/layui_exts/mouseRightMenu/'});
    layui.use(['mouseRightMenu','layer'],function(){
        var mouseRightMenu = layui.mouseRightMenu,layer = layui.layer;
        //右键监听
        $element.bind("contextmenu",function(e){
            if (bindAction){
                bindAction($(this));
            }
            mouseRightMenu.open(data,false,function(d){
                if (openAction){
                    openAction(d);
                }
            });
            return false;
        });
    });
}