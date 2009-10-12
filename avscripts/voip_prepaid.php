#!/usr/bin/php -q
<?php

    // require the file
    require('phpagi/phpagi.php');
    // instantiate the class
    $agi = new AGI();
    // answer the call
    $agi->answer();
    // $agi->text2wav("This is a test");
    $agi->say_digits(1234)
    
?>