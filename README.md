gogdl-php
=========

PHP wrapper class to use the gog.com dowloader API.

# Disclaimer

THIS SOFTWARE IS PROVIDED "AS IS" AND ANY EXPRESSED OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE REGENTS OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)

HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

# When is this ever useful?

If you have a linux box which runs as a home server with an outward facing Apache+PHP install accessible via some dynamic DNS (like http://www.noip.com/free) and you have a gog.com account and you're just sitting at work when you get an email about that game you wanted that's 90% off, which you then buy on the spot but think it be nice if you didn't have to wait to go back home to start downloading it and could just set it to download onto your home server to a folder you have shared with the other machines on your network via samba so you could then just install it once you get back.

What you do then is SSH to your home server from work, clone this repository to your `/var/www` folder or whatever your web root is and edit the config a bit to fit your needs. Access the

There are other commandline gog downloaders out there written in other languages which allow you to use the gog.com downloader API but none of them seem to really 

# Requirements

 * A gog.com account in good standing
  * some games available on that account
 * wget
 * PHP 5.3 (or 5.4 just to be safe)
  * ext-curl
  * write permission to a folder on the server executing the script


# Usage:

```php
<?php 

/*
 * The instance of the GOGPHPDL class is created with login credentials
 * 
 * 
 * A gogdownloader url looks something like this.
 * gogdownloader://beneath_a_steel_sky/installer_win_en
 * 
 * If you wanted to download the games `Beneath a Steel Sky` and `Lure of the Temptress`
 * to the folder `/home/user/gog-dl`
 */

$dl = new GOGPHPDL('user@mail.org', 'abc123');

$dl->setDownloadDir('/home/user/gog-dl')
    ->run(array(
        'gogdownloader://beneath_a_steel_sky/installer_win_en',
        'gogdownloader://lure_of_the_temptress/installer_win_en',
));

```

# To do/fix

 * Bonus content doesn't always download correctly
 * Parallelise downloads with AJAX progress updates.
 * make phar file for direct cli execution
