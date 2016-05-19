<header id="site-header" class="clearfix">
    <div class="pull-right">
        <div id="header-avatar">
            <img class="img-circle"
                 src="<?php print $CurrUser->fb_user ? 'http://graph.facebook.com/'.$CurrUser->fb_user.'/picture' : '/assets/img/default-avatar.jpg'?>"
                 width="50" height="50" alt="<?php print $CurrUser->name?>">
        </div>
        <div id="header-userinfo">
            <a href="javascript:return false" class="fake-a">
                <?php print $CurrUser->name?>
            </a><br />
            <a href="/logout"><small>Logout</small></a>
            <?php print $CurrUser->type==='tutor'?' <small>(Tutor)</small>':''?><br />
            <a href="" style="color: #428bca"><small>Invite friends from facebook</small></a>
        </div>
    </div>
    <div id="wb-activities" class="pull-right"></div>
    <a id="site-logo" href="/">
        <?php if (in_array($optional['hash'], ['kfg498', 'hldfj24'])): ?>
            <img src="/assets/img/logo.png" width="120" height="31" />
            <span class="idc-logo">
                <div style="margin: 0 10px;display: inline-block"><small>בשיתוף</small></div>
                <img src="/assets/img/logo-idc.png" style="width: auto;" width="58" height="35" />
            </span>
        <?php else: ?>
            <img src="/assets/img/logo.png" width="120" height="31" style="margin:0 0 0 60px" />
        <?php endif; ?>
    </a>
    <div class="pull-left">

    </div>
</header>