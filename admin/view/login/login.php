<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta content="webkit" name="renderer">
    <meta content="IE=edge,Chrome=1" http-equiv="X-UA-Compatible">
    <meta content="width=device-width,initial-scale=1,maximum-scale=1" name="viewport">
    <meta content="yes" name="apple-mobile-web-app-capable">
    <meta content="blank" name="apple-mobile-web-app-status-bar-style">
    <meta content="telephone=no" name="format-detection">
    <title>账号管家</title>

    <?php $webSite = "http://".$_SERVER['HTTP_HOST'];  $pubP = $webSite."/public"?>
    <link href="<?php echo $pubP?>/common/css/bootstrap.css" rel="stylesheet" type="text/css">
    <link href="<?php echo $pubP?>/common/css/icon.css" rel="stylesheet" type="text/css">
    <link href="<?php echo $pubP?>/common/css/login.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" type="text/css" href="//at.alicdn.com/t/font_1877015_jkq7dca58si.css">
</head>

<body>
    <input id="encrypt_pass" type="hidden" value="<?php echo $_GET["pass"]?>"/>
    <div id="cotent" class="aw-login">
        <div class="mod center-block" style="padding-top: 60px;">
            <div class="mod-logo" style="text-align: center">
                <h1 style="font:50px 'microsoft yahei';color: #FFFFFF">账号管家</h1>
            </div>

            <form method="post" action="<?php echo $webSite.'/index.php'?>" id="login_submit">
                <?php if ($_GET["loginerr"]) {
                    $disp='display';
                }else{
                    $disp='none';
                }
                ?>
                <div style="color: red; height: 30px; text-align: center;font-size: 20px;margin-top: -10px; display: <?php echo $disp?>">
                    <span><?php echo urldecode($_GET["errMsg"])?></span>
                </div>

                <input type="hidden" name="login" value="true"/>
                <div class="form-group">
                    <label>用户名</label>
                    <input type="text"
                           name="username"
                           value=""
                           placeholder="用户名"
                           class="form-control">
                    <i class="icon icon-user"></i>
                </div>

                <div class="form-group">
                    <label>密码</label>
                    <input id="passwd_input" type="password" autofocus=""
                           onkeydown="if (event.keyCode == 13) {
                               document.getElementById('login_submit').click();
                           };"
                           name="pass"
                           placeholder="密码"
                           class="form-control">
                    <i class="icon icon-lock"></i>
                    <i id="check_pass_icon" class="iconfont icon-Xtubiao-chakan" onclick="checkMyPasswd()"></i>
                </div>
                <input id="user_pass_input" type="password" name="loginSuccess" style="display: none">
                <input type="text" name="m" value="<?php echo $_GET['m']?>" style="display: none">

                <button class="btn btn-primary" type="submit" >登录</button>
            </form>
        </div>
    </div>
</body>
</html>
<script src="<?php echo $pubP?>/common/js/jquery.2.js" type="text/javascript"></script>
<script type="text/javascript" src="<?php echo $pubP?>/common/js/RSAEncrypt.js"></script>
<script type="text/javascript" src="<?php echo $pubP?>/common/js/cookie.js"></script>
<script type="text/javascript" src="<?php echo $pubP?>/common/js/jsencrypt.min.js"></script>
<script>
    var realPass = "";
    $(document).ready(function () {
        // 解密传过来的密文
        var encryptPass = $("#encrypt_pass").val();
        if (encryptPass.length > 0){
            // base64解码
            encryptPass = atob(encryptPass);
            var privateKeyContent = getCookie("rsaPrivateKey");
            realPass = privateDecrypt(privateKeyContent,encryptPass);
        }

        console.log("正确密码:" + realPass);

        // 监听密码输入 同时把输入的明文密码加密为密文
        $("#passwd_input").bind("input propertychange", function (event) {
            var userPass = $("#passwd_input").val();

            console.log("验证：" + realPass);
            console.log("验证1：" + userPass);
            if (realPass == userPass){
                $("#user_pass_input").val("1");
            }else {
                $("#user_pass_input").val("0");
            }
        });
    });

    // 查看密码
    function checkMyPasswd() {
        // 密码框
        var passInput = $("#passwd_input");
        if (passInput.attr("type") == "password"){
            // 显示密码
            passInput.attr("type","text");
            $("#check_pass_icon").attr("class","iconfont icon-Xtubiao-guanbichakan");
        }else {
            // 隐藏密码
            passInput.attr("type","password");
            $("#check_pass_icon").attr("class","iconfont icon-Xtubiao-chakan");
        }
    }

</script>