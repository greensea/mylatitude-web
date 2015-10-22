<?php require_once('../header.php')?>
<html lang="en">
  <head>
    <meta name="google-signin-scope" content="profile email">
    <meta name="google-signin-client_id" content="<?php echo htmlspecialchars($GOOGLE_CLIENT_ID);?>">
    <script src="https://apis.google.com/js/platform.js" async defer></script>
    <script src="https://code.jquery.com/jquery-2.1.4.min.js" async></script>
  </head>
  <body>
    <div class="g-signin2" data-onsuccess="onSignIn" data-theme="dark"></div>
    <script>
    var uid = "<?php echo htmlspecialchars($_GET['uid']);?>";
    
    function onSignIn(gUser) {
        /// Google 返回了数据，我们将数据提交上去创建用户
        $.ajax({
            url: "google_verify.php",
            dataType: "json",
            method: "post",
            data: {
                uid: uid,
                token: gUser.getAuthResponse().id_token
            },
            
            beforeSend: function() {
                $(".tips").text("正在为您登录，请稍候");
            },
            
            error: function (x, s, e) {
                $(".tips").text("网络错误：" + e);
            },
            
            success: function(d) {
                if (d.code != 0) {
                    $(".tips").text("本地服务器返回了错误: " + d.message);
                }
                else {
                    $(".tips").text("登录成功，请返回应用页面");
                    history.go(-1);
                }
            }
        });
        
          /*
        // Useful data for your client-side scripts:
        var profile = googleUser.getBasicProfile();
        console.log("ID: " + profile.getId()); // Don't send this directly to your server!
        console.log("Name: " + profile.getName());
        console.log("Image URL: " + profile.getImageUrl());
        console.log("Email: " + profile.getEmail());

        // The ID token you need to pass to your backend:
        var id_token = googleUser.getAuthResponse().id_token;
        console.log("ID Token: " + id_token);
        */
    };
    </script>
    
    <div class="tips"></div>
  </body>
</html>
