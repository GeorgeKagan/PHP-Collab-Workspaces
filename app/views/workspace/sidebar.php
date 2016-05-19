<div id="sidebar-wrapper">
    <div id="sidebar">
        <div id="sidebar-inner">
            <h2 class="icon-workspaces">Workspaces</h2>
            <div class="icon-links">
                <?php foreach ($my_groups as $ws): ?>
                    <a href="/ws/<?php print $ws->hash; ?>">
                        <span style="background: <?php print '#' . $ws->color; ?>"><?php print substr($ws->name, 0, 2); ?></span>
                        <?php print $ws->name; ?>
                    </a>
                <?php endforeach; ?>
            </div><div style="margin:15px 0 0 5px"></div>
            <br>
<!--            <div class="btn-with-icon">New workspace</div>-->
            <h2 class="icon-diduknow">Did you know?</h2>
            <div class="separated-list">
                <span style="text-align: center;display: block;">You can upload and add notes on images!</span>
                <span style="cursor: pointer; margin-top: 10px;display: block;" id="anno_guide">
                    <img src="/assets/img/sidebar/annotation_guide.png">
                </span>
            </div>
            <div class="report-a-bug"><a href="mailto:tickets@afterclass.uservoice.com?subject=I'm in a wrong college/class&body=Please change my college to:">Wrong college/class?</a></div>
        </div>
    </div>
</div>