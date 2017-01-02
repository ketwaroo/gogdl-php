<?php
if (!is_file(__DIR__ . '/config.php'))
{
    throw new Exception('configuration not found.');
}
require __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';
?>
<!doctype html>
<html>
    <head>
        <title>Enter game list</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.4/css/bootstrap.min.css" />

        <script type="text/javascript" src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/handlebars.js/3.0.2/handlebars.min.js"></script>
        <script type="text/javascript">
            var progressInterval;
            $(document).ready(function () {

                var tpl = Handlebars.compile($('#progressTemplate').html());

                progressInterval = setInterval(function () {
                    $.get('./progress.php', function (data) {
                        $("#out").html(tpl({"data": data}));
                    });
                }
                , 2000);

            });
        </script>

    </head>
    <body>
        <?php
        /**
         * gog.com games downloader written in PHP
         * 
         * 
         * @author Ketwaroo D. Yaasir
         */
        $dl = new \Gogdl\Downloader(GOGPHPDL_USERNAME, GOGPHPDL_PASSWORD);

        $dl->setAuthFile(GOGPHPDL_BASE_DIR . '/.gogauth')
            ->setDownloadDir(GOGPHPDL_DOWNLOAD_DIR)
            ->setWgetBin(GOGPHPDL_WGET);

        $progress = new \Gogdl\ProgressServer();

        $progress->setProgressChacheDir(GOGPHPDL_DOWNLOAD_DIR);

        if (!empty($_POST['games']))
        {
            $dlList = array_filter(preg_split('~[\r\n,]+~', $_POST['games']));

            if (!empty($dlList))
            {
                // run through decorator
                $progress->runDownloader($dl, $dlList);
            }
        }

        if (!empty($_POST['gamelist']))
        {


           $user= ($dl->init()->apiGetUserDetails());
           p($user);
           try
           {
               //p($dl->init()->apiGetGamesList($user['user']['id'])); 
               p($dl->init()->apiGetGamesList($user['user']['xywka'])); 
               p($dl->init()->apiGetGamesList($user['user']['email'])); 
               p($dl->init()->apiGetGamesList($user['user']['hash'])); 
           }
           catch (\Exception $exc)
           {
               p($exc);
           }

          
           
           die;
    
        }
        ?>

        <form method="post">
            <p>Enter gogdownloader links. one per line. eg.</p>
            <p><code>gogdownloader://beneath_a_steel_sky/installer_win_en</code></p>
            <p><textarea name="games" id="games" cols="100" rows="15"></textarea></p>
            <p><button type="submit" class="btn btn-default">Submit</button></p>
        </form>
        <form method="post">
            <input type="hidden"  name="gamelist" value="1"/>
            <button type="submit" class="btn btn-default">Game list</button>
        </form>
        <div id="out"></div>

        <div id="progressTemplate" class="hide">

            <div class="row">
                {{#each data}}
                <h4 class="col-xs-12">{{game}}</h4>
                {{#each this.files}}
                <div class="game-progress col-xs-6">{{file}}</div>
                <div class="completed col-xs-2">{{currentSize}}</div>
                <div class="completed col-xs-2">{{expectedSize}}</div>
                <div class="completed col-xs-2">{{progress}}%</div>
                {{/each}}
                {{/each}}
            </div>
        </div>

        <?php
        $log = $dl->log();

        if (!empty($log))
        {
            echo '<pre>', print_r($log, 1), '</pre>';
        }
        ?>

    </body>
</html>







