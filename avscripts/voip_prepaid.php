#!/usr/bin/php -q
<?php
    require('/var/www/html/agile/config.inc.php');
    try {
        $dbh = new PDO('mysql:host='.AGILE_DB_HOST.';dbname='.AGILE_DB_DATABASE, AGILE_DB_USERNAME, AGILE_DB_PASSWORD);
    } catch (PDOException $e) {
        // print 'Error!: ' . $e->getMessage() . "\n";
        die();
    }
    // require the file
    require('phpagi/phpagi.php');
    // instantiate the class
    $agi = new AGI();
    // answer the call
    $agi->answer();
    $agi->text2wav('Please enter your PIN followed by the pound key after the beep');

    $result = $agi->get_data('beep', 30000, 11);
    $pin = $result['result'];
    // grab the pin from the db
    $stmt = $dbh->prepare("SELECT * FROM ".AGILE_DB_PREFIX. "voip_prepaid WHERE pin = ?");
    if($stmt->execute($pin)) {
        while ($row = $stmt->fetch()) {
            $agi->say_number($row['balance']);
        }
    }

    $agi->hangup();
