<?php
defined('ANS') or die();

return array(
    'classes' => array(
        'PHPMailer' => __DIR__.'/phpmailer/phpmailer/class.phpmailer.php',
        'POP3' => __DIR__.'/phpmailer/phpmailer/class.pop3.php',
        'SMTP' => __DIR__.'/phpmailer/phpmailer/class.smtp.php',
        'phpmailerException' => __DIR__.'/phpmailer/phpmailer/class.phpmailer.php'
    ),
    'namespaces' => array(
        'Stylecow' => __DIR__.'/stylecow/stylecow',
        'Imagecow' => __DIR__.'/imagecow/imagecow',
        'Faker' => __DIR__.'/fzaninotto/Faker/src',
        'ANS\\Cache' => __DIR__.'/ANS/Cache/libs'
    )
);
