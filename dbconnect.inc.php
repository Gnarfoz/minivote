<?php
try {
    $dbh = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    foreach($dbh->query('SELECT * from users') as $row) {
        print_r($row);
    }
    $dbh = null;
} catch (PDOException $e) {
    print "Error!: " . $e->getMessage() . "<br/>";
    die();
}
?>
