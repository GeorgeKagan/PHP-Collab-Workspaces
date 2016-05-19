<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AfterClass | Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:400italic,400,300,600,700' rel='stylesheet' type='text/css'>
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.0.1/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets_guest/css/welcome.css" type="text/css" rel="stylesheet">
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <script src="/assets_guest/js/welcome.js"></script>
    <?php require('partials/google-analytics.php')?>
</head>
<body style="background: url(/assets/img/B5.jpg) center 30% no-repeat">

<div class="container">
    <header class="header">
        <a href="/" style="margin-left: 25px;">
            <img src="/assets/img/logo.png" style="padding-top: 5px;"/>
        </a>
    </header>
    <div style="height: 1px; background: #ccc;"></div>
    <div style="padding: 0 25px 0 25px;">
        <h1 class="heading_text">ברוך הבא ל-AfterClass</h1>
        <h4 class="heading_text">
            AfterClass הוא כלי בעזרתו תוכל לשאול ולקבל תשובות ממורים.
        </h4>
        <h4 class="heading_text">
            אז אם גם אתם מתקשים מעט בחומר או בשאלה ספיציפית - מוזמנים להכנס ולקבל תשובות ישירות ממורים!
        </h4>

        <div class="left">
           <h4 class="form-head">כניסה עם פייסבוק</h4>
           <div class="separator"></div>
           <button class="facebook_button" onclick="window.location='/login/fb'">
               <img src="/assets/img/facebook-icon.png" class="fb-icon">כניסה עם פייסבוק
           </button>
        </div>
        <div class="right">
            <h4 class="form-head">כניסה</h4>
            <div class="separator"></div>
            <?php print Session::has('auth_error') ? '<div style="color:red">' . Session::get('auth_error') . '</div><br>' : '' ?>
            <form method="post" action="/auth">
                <input type="email" class="input" name="email" placeholder="אימייל" required>
                <input type="password" class="input" name="pass" placeholder="סיסמה" required>
                <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                <input type="submit" class="login_button" value="כניסה">
            </form>
            <div style="text-align: left; padding-top: 10px;">
                <a href="remind" title="Click here to request a new password" style="color:#71757d;">שכחת סיסמה?</a>
            </div>
        </div>
    </div>
</div>

<?php require('partials/uservoice.php')?>

</body>
</html>
