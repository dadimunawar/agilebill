#!/usr/bin/php -q
<?php

    // require the file
    require('phpagi/phpagi.php');
    // instantiate the class
    $agi = new AGI();
    // answer the call
    $agi->answer();
    $agi->text2wav("Please enter your PIN and press the pound key.");
    $result = $agi->get_data('beep', 5000, 20);
    $keys = $result['result'];
    $agi->text2wav("You entered $keys");
    $agi->hangup();