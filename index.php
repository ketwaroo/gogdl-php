<?php
define('GOGPHPDL_BASE_DIR', __DIR__);
/**
  // uncomment and edit.
  // configuration
  define('GOGPHPDL_DOWNLOAD_DIR', '/dl/folder'); // download folder
  define('GOGPHPDL_WGET', 'wget'); // wget executable path
  define('GOGPHPDL_USERNAME', 'user@email.com'); // gog.com user
  define('GOGPHPDL_PASSWORD', 'abc123'); // gog.com password
 */
?>
<html>
    <head>
        <title>Enter game list</title>
    </head>
    <body>
        <?php
        /**
         * gog.com games downloader written in PHP
         * 
         * 
         * @author Ketwaroo D. Yaasir
         */
        if(is_file(__DIR__ . '/config.php'))
            require __DIR__ . '/config.php';


        if(!empty($_POST['games']))
        {
            require GOGPHPDL_BASE_DIR . '/lib/GOGPHPDL.php';
            require GOGPHPDL_BASE_DIR . '/lib/OAuth.php';

            ob_end_clean();

            $dl = new GOGPHPDL(GOGPHPDL_USERNAME, GOGPHPDL_PASSWORD);

            $dl->setAuthFile(GOGPHPDL_BASE_DIR . '/.gogauth')
                ->setDownloadDir(GOGPHPDL_DOWNLOAD_DIR)
                ->setWgetBin(GOGPHPDL_WGET);

            $dlList = array_filter(preg_split('~[\r\n,]+~', $_POST['games']));

            if(!empty($dlList))
            {
                $dl->run($dlList);
            }
        }
        ?>

        <form method="post">
            <p>Enter gogdownloader links. one per line. eg.</p>
            <p><code>gogdownloader://beneath_a_steel_sky/installer_win_en</code></p>
            <p><textarea name="games" id="games" cols="100" rows="15"></textarea></p>
            <p><button type="submit">Submit</button></p>
        </form>
    </body>
</html>







