<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AfterClass</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,400,300,600,700' rel='stylesheet' type='text/css'>
    <?php
    if ($CurrUser->type == 'tutor'){
        $workspaces_tutor = DB::table('user2group')
 	        ->select('group_id')
            ->where('user_id','=',$CurrUser->id)
 	        ->get();
 	    $where_tutor = [];
 	    foreach ($workspaces_tutor as $workspaces){
        $where_tutor[] = $workspaces->group_id;
        }
 	    $allowed_posts = DB::table('posts')
 	        ->select('id')
 	        ->whereIn('group_id',$where_tutor)
 	        ->orderBy('id')
 	        ->get();
 	    $posts = [];
 	    foreach ($allowed_posts as $allowed_post){
            $posts[] = $allowed_post->id;
        }
    }
    $user_agent =  strtolower($_SERVER['HTTP_USER_AGENT']);
    $num_workspaces = DB::table('user2group')->select('group_id')->where('user_id','=',$CurrUser->id)->count();
    ?>

    <script>
        var env = '<?php print ac_env(); ?>';
        var post_id = <?php print isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0 ?>;
        var CurrUser = <?php print $CurrUser->toJson()?>;
        var Optional = <?php print json_encode(!empty($optional) ? $optional : []); ?>;
        var posts = <?php print json_encode(!empty($posts)?$posts:[]); ?>;
        var user_agent = "<?php print $user_agent; ?>";
        var num_workspaces = <?php print $num_workspaces; ?>;
    </script>
    <?php if (ac_env() === 'prod'): ?>
        <link href="/build/<?php print $asset_names['bower.css']; ?>" type="text/css" rel="stylesheet">
        <link href="/build/<?php print $asset_names['styles.css']; ?>" type="text/css" rel="stylesheet">
        <script src="/build/<?php print $asset_names['bower.js']; ?>"></script>
        <script src="/build/<?php print $asset_names['scripts.js']; ?>"></script>
    <?php else: output_dev_assets(); endif; ?>
    <?php require('workspace/handlebars-templates.php') ?>
    <?php require('partials/google-analytics.php') ?>
</head>
<body>
    <div class="fluid-container">
        <?php require('partials/header.php') ?>

        <div class="row">
            <?php require('workspace/sidebar.php'); ?>

            <div id="main-content">
                <?php print $child; ?>
            </div>

            <?php require('workspace/chat.php') ?>
        </div>
    </div>

    <?php
    if (stristr($user_agent,'app-andorid-') === false){
        require('partials/uservoice.php');
    }
    ?>

</body>
</html>
