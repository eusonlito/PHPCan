libs folder
===========

There are some external requiriments if you want a full featured PHPCan installation.

Here all current supported packages:

* Cache: Cache support. https://github.com/eusonlito/Cache | http://packagist.org/packages/ANS/Cache
* styleCow: Library that allows parsing and manipulating css files. https://github.com/oscarotero/stylecow | http://packagist.org/packages/stylecow/stylecow
* imageCow: Library to manipulate images to web. https://github.com/oscarotero/imageCow | http://packagist.org/packages/imagecow/imagecow
* PHPMailer: Full featured mail library. http://svn.codespot.com/a/apache-extras.org/phpmailer/

I recommend you to install it from Composer (Download composer.phar from http://getcomposer.org/) using:

    cd /var/www/phpcan/

    php composer.phar update

All dependences will be installed.

Also, you can install it manually using:

    cd /var/www/phpcan/

    php composer.phar require (ANS/Cache | stylecow/stylecow | imagecow/imagecow)