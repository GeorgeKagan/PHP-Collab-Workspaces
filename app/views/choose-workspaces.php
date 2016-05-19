<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AfterClass | Choose Workspaces</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:400italic,400,300,600,700' rel='stylesheet' type='text/css'>
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.0.1/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets_guest/css/welcome.css" type="text/css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="bower_components/sweetalert/lib/sweet-alert.css">
    <script src="bower_components/sweetalert/lib/sweet-alert.js"></script>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <?php require('partials/google-analytics.php')?>
</head>
<body style="background: url(http://afterclass.co.il/assets/img/B5.jpg) center 30% no-repeat">

<div class="container">
    <header class="header">
        <a href="" style="margin-left: 25px;">
            <img src="/assets/img/logo.png" style="padding-top: 5px;"/>
        </a>
    </header>
    <div style="height: 1px; background: #ccc;"></div>
    <div style="padding: 0 25px 0 25px;">
        <h1 class="heading_text">עוד דבר אחד...</h1>
        <h4 class="heading_text">
            אנא בחר במכללה שלך<br>
        </h4>
        <h4 class="heading_text">החברים שלך כבר שם!</h4>
        <br>
    </div>
    <div style="padding:0 10px">
        <form>
            <style>
                button {
                    font-family: sans-serif !important;
                }
            </style>
            <div class="col-sm-4">
                <h4 class="form-head" style="text-align: center"><img src="/assets/img/institutions/IDC.jpg"></h4>
                <div class="separator"></div>
                <button type="button" name="workspace_batch" value="idc_statistics" class="btn btn-lg btn-primary btn-block">
                    תואר ראשון, כלכלה, שנה א
                </button>
                <button type="button" name="workspace_batch" value="idc_mba" class="btn btn-lg btn-primary btn-block">
                    תואר שני
                </button>
            </div>
            <div class="col-sm-4">
                <h4 class="form-head" style="text-align: center"><img src="/assets/img/institutions/COMAS.jpg"></h4>
                <div class="separator"></div>
                <button type="button" name="workspace_batch" value="comas_comp" class="btn btn-lg btn-primary btn-block">
                    המכללה למנהל
                </button>
            </div>
            <div class="col-sm-4">
                <h4 class="form-head" style="text-align: center"><img src="/assets/img/institutions/Ono.jpg"></h4>
                <div class="separator"></div>
                <button type="button" name="workspace_batch" value="ono_phys" class="disabled btn btn-lg btn-primary btn-block">
                    בקרוב
                </button>
            </div>
            <script>
                $('button[name="workspace_batch"]').click(function() {
                    var workspace_name = $.trim($(this).text());
                    var name = $.trim($(this).attr('name'));
                    var val = $.trim($(this).val());
                    swal({
                            title: "לאשר בחירה?",
                            text: "בחרת להצטרף ל-"
                                    + '"' + workspace_name + '"',
                            type: "warning",
                            showCancelButton: true,
                            confirmButtonColor: "#DD6B55",
                            confirmButtonText: "כן, זאת המכללה שלי!",
                            cancelButtonText: 'ביטול',
                            closeOnConfirm: false
                        },
                        function(isConfirm){
                            if(isConfirm){
                                window.location.href = '?' + name + '=' + val;
                            }
                        }
                    );
                });
            </script>
        </form>
    </div>
</div>

<?php require('partials/uservoice.php')?>

</body>
</html>
