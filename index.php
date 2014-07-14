<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>京东夺宝岛竞拍工具</title>
<style>
body {width:900px; border:5px solid #cccccc; align:center; margin:auto;font-size:12px;}
.content {width:100%; text-align:center;height:800px;}
.login {width:49%;float:left;margin:2px; border:1px solid green; }
.list {width:48%;float:right;margin:2px; border:1px solid green; height:700px;}
.label_text {width:70px;text-align:right;display:block;float:left;}
.login_form {list-style:none;}
.login_form li {height:40px;line-height:40px;}
.login_form li input {height:30px;clear:both;float:none;}
.btn {padding: 10px 15px 10px 15px;}
</style>
<script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
<script>
//获取验证码
var url = 'post.php';
$(function(){
    $.post(url, {get_code:true}, function(data){
        if (data.status) {
            $('#code').html(data.html);
            $('#rand_key').val(data.rand_key);
            $('#rand_val').val(data.rand_val);
        } else {
            alert(data.errmsg);
        }
    }, 'json');
});


function tologin(){
    var user = $('input[name="username"]').val();
    var pass = $('input[name="password"]').val();
    var purl = $('input[name="purl"]').val();
    var high_price = $('input[name="high_price"]').val();
    var rand_key = $('#rand_key').val();
    var rand_val = $('#rand_val').val();
    var uuid = $('#uuid').val();
    $.post(url, {username:user, password:pass, purl:purl, high_price:high_price, rand_key: rand_key, rand_val: rand_val, uuid: uuid, login:true}, function(reponse){
        if (reponse.status) {
            //执行竞拍
            to_bid(reponse.id);
            //获取竞拍信息
            bid_list(reponse.id);
        } else {
            alert(reponse.errmsg);
        }
    });
}

var interval_id;
var interval_id1;
function to_bid(id){
    interval_id = setInterval("bid("+id+")", 1000);
}
//竞拍
function bid(id){
     $.post(url, {id:id, to_bid:true}, function(data){
        if (!data.status) {
            alert(data.errmsg);
        } else {
            if (data.code == 300){
                alert('超出最高价，停止竞标！');
                clearInterval(interval_id);
                clearInterval(interval_id1);
            }
        }
    });
}

function bid_list(id){
    interval_id1 = setInterval("get_bid_list("+id+")", 10000);
}

//获取竞拍信息
function get_bid_list(id){
    $.post(url, {id:id, get_bid_list:true}, function(data){
        if (data.status) {
            var html = '<h3>竞拍信息</h3><table><tr><th>用户</th><th>出价</th><th>IP</th></tr>';
            for (var i = 0; i < data.list.length; i++) {
                html = html + "<tr><td>"+data.list[i].userNickName+"</td><td>"+data.list[i].price+"</td><td>"+data.list[i].ipAddress+"</td></tr>";
            }
            html = html + "</table>";
            $("#list").html(html);
        } else {
            alert(data.errmsg);
        }
    });
}
</script>
</head>
<body>
<div class="content">
<h2>京东夺宝岛竞拍工具</h2>
    <div class="login">
        <h3>竞拍设置</h3>
        <form action="http://127.0.0.1/post.php" method="post">
        <input type="hidden" name="uuid" id="uuid"/>
        <input type="hidden" name="rand_key" id="rand_key"/>
        <input type="hidden" name="rand_val" id="rand_val" />
        <ul class="login_form">
        <li><span class="label_text">用户名：</span><input type="text" name="username" size="30"/></li>
        <li><span class="label_text">密码：</span> <input type="password" name="password" size="30"/></li>
        <li><span class="label_text">商品URL：</span><input type="text" name="purl" size="30"/></li>
        <li><span class="label_text">最高出价：</span><input type="text" name="high_price" size="30"/></li>
        <!--验证码：<input type="text" name="authcode" id="authcode"/> <br/>-->
        <span id="code" style="display:none;"></span>
        </ul>
        <input type="button" name="login" onclick="tologin();return false;" value="登录并竞标" class="btn"/>
        </form>
    </div>
    
    <div class="list" id="list">
          <h3>竞拍信息</h3>
    </div>
</div>
</body>
</html>