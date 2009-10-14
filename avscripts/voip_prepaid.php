#!/usr/bin/php -q
<?php
    require('/var/www/html/agile/config.inc.php');
    try {
        $dbh = new PDO('mysql:host='.AGILE_DB_HOST.';dbname='.AGILE_DB_DATABASE, AGILE_DB_USERNAME, AGILE_DB_PASSWORD);
    } catch (PDOException $e) {
        print 'Error!: ' . $e->getMessage() . "\n";
        die();
    }
    // require the file
    require('phpagi/phpagi.php');
    // instantiate the class
    $agi = new AGI();
    // answer the call
    $agi->answer();
    $agi->say_phonetic('Please enter your PIN followed by the pound key after the beep');

    $result = $agi->get_data('beep', 30000, 11);
    $keys = $result['result'];
    
    $agi->hangup();
