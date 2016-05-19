<div id="post-panes" class="tab-content clearfix">
    <?php require('workspace/mega-adder.php') ?>
    <?php if (!$CurrUser->seen_welcome): ?>
        <div id="grid-welcome" class="wall-post welcome clearfix">
            <h2>ברוך הבא <?php print $CurrUser->name; ?>!</h2>
            <div class="welcome-info"><?php print do_rtl_if_heb('מתקשה בחומר או בשאלה ספיציפית? שלחו שאלה למורה עם AfterClass ותוכלו לקבל ממנו תשובה עוד היום!
איך זה עובד?'); ?></div>
            <div class="right-col">
                <div class="num">1</div>
                <div class="welcome-text">
                    לחץ על Type your question <br>
                    הקלד את השאלה או צלם אותה
                </div>
            </div>
            <div class="left-col">
                <div class="num">2</div>
                <div class="welcome-text">
                    תוכל להוסיף הערות על איזורים בתמונה<br>
                    לשליחה לחץ Get an Answer!
                </div>
            </div>
            <!--<div class="icon-questions">Ask a question</div>
            <div class="icon-doc-checked">Get a premium, professional answer</div>
            <div class="icon-friends">Tell your friends how simple it was!</div>-->
            <div id="gw-close" class="icon-close"></div>
        </div>
    <?php endif; ?>
    <div class="tab-pane active clearfix" id="my-groups"></div>
</div>