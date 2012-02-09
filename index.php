<?php
# send proper HTTP Content-Type header
header('Content-Type: text/html; charset=utf-8');

# only enable during development
#ini_set('error_reporting', -1);
#ini_set('display_errors', 1);

# include database login data: $host, $db, $user, $pass
require_once 'dbdata.inc.php';

# create database connection and set default fetch mode
$dbh = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
$dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);


# evaluate GET parameters
if (isset($_GET['name'])  && !empty($_GET['name'])  && isset($_GET['k'])  && !empty($_GET['k']))  {
    $name   =   $_GET['name'];
    $k      =   $_GET['k'];
}

# evaluate POST parameters
if (isset($_POST['name']) && !empty($_POST['name']) && isset($_POST['k']) && !empty($_POST['k']) && isset($_POST['vote']) && !empty($_POST['vote'])) {
    $name   =   $_POST['name'];
    $k      =   $_POST['k'];
    $vote   =   $_POST['vote'];
}

# name and key present, validate
if (isset($name) && isset($k)) {
    # compare the stored hash with the supplied name & key, using the stored salt, never mind the \n
    $stmt = $dbh->prepare("SELECT STRCMP(SHA1(CONCAT(salt,:name,:k,'\n')), hash) AS cmp, id FROM users WHERE name=:name");
    # different way to use bind parameters
    //$stmt->bindParam(':name', $name, PDO::PARAM_STR, 13);
    //$stmt->bindParam(':k', $k, PDO::PARAM_STR, 13);

    # bind parameters & execute query
    $stmt->execute(array('name' => $name, 'k' => $k));

    # fetch and check result and act accordingly
    while($row = $stmt->fetch()) {
        if (isset($row['cmp']) && $row['cmp'] != 0) {
            die("Ungültige Benutzerdaten!");
        } else {
            # stupid way of saying the authorization data was valid
            $id     =   $row['id'];
        }
    }
}

# someone voted!
if (isset($id) && isset($vote)) {
    $votes = array();
    for ($i = 1; $i <= 7; $i++) {
        if (isset($_POST[$i]) && ($_POST[$i] >= 0) && ($_POST[$i] <=2 )) {
            $votes[$i] = $_POST[$i];
        }
    }

    # prepare insert/update statement
    $stmt = $dbh->prepare("INSERT INTO votes (day, user, vote) VALUES (:i, :id, :vote) ON DUPLICATE KEY UPDATE day=:i, user=:id, vote=:vote");
    for ($i = 1; $i <= 7; $i++) {
        # bind & execute
        $stmt->execute(array('i' => $i, 'id' => $id, 'vote' => $votes[$i]));
    }
}

# really stupid, error-prone but simple way of differentiating between at least Firefox and Chrome
$gecko = strpos($_SERVER['HTTP_USER_AGENT'],"Gecko/");
$webkit= strpos($_SERVER['HTTP_USER_AGENT'],"AppleWebKit");

# never mind the horrible way of going into and out of php for conditional blocks and simple prints, that could probably be simplified
?>
<!DOCTYPE HTML>
<html>
    <head>
        <title>HddF Raidtage-Vote</title>
        <base href="https://www.hüter.net/vote/" />
        <style type="text/css">
            div { margin-bottom: 20px; }
            #wrapper { margin-left: auto; margin-right: auto; width: 800px; text-align: center; font-family: sans-serif;}
            table { margin-left: auto; margin-right: auto; }
            <?php if ($webkit) { ?> td { padding: 0px 10px 0px 10px; } <?php } ?>
        </style>
    </head>
    <body>
        <div id="wrapper">
            <h1>Raidtage-Vote</h1>
            <h2>Gammelig, und ohne Style!</h2>
            <div id="help">
            <?php if (!$webkit) { ?>Gib bitte eine Zahl zwischen 0 und 2 ein, je nach dem, wie gut dir der jeweilige Tag passt.<br />0 = passt schlecht, 1 = akzeptabel, 2 = passt gut.<?php } ?>
            </div>
            <div id="results">
            <?php

            # get the maximum number of votes possible (users' votes can range from 0 to 2, so the maximum is 2 * number of users)
            $rows = $dbh->query('SELECT count(id) AS num_users FROM users');
            $row = $rows->fetch();
            $num_users = 2*$row['num_users'];

            # for each day (by id) get the sum of the votes and then get the votes per day (by name) from that
            # call the image create script for each one, passing in: number of votes, maximum number of votes, name of the day
            foreach($dbh->query("SELECT a.day AS dayname, b.vote FROM days a JOIN (SELECT day, sum(vote) AS vote FROM votes GROUP BY day) b ON b.day=a.id") as $row) { ?>
            <img alt="<?php print($row['dayname']) ?>" title="<?php print($row['dayname']) ?>" src="image.php?v=<?php print($row['vote']) ?>&m=<?php print($num_users) ?>&d=<?php print($row['dayname']) ?>" />
            <?php } ?>

            </div>
            <?php if (isset($id)) { ?>
            <form action="<?php print("/vote/$name/$k") ?>" method="POST">
                <input type="hidden" name="name" value="<?php if (isset($name)) print($name); ?>" />
                <input type="hidden" name="k"    value="<?php if (isset($name)) print($k); ?>" />
                <input type="hidden" name="vote" value="true" />
                <table>
                <?php foreach($dbh->query('SELECT id,day FROM days ORDER BY id ASC') as $row) { ?>
                <tr><td><?php print($row['day']) ?>:</td>
                <?php if ($webkit) { ?>
                <td>schlechter</td><td><input type="range" name="<?php print($row['id']) ?>" min="0" max="2" default="1" /></td><td>besser</td></tr>
                <?php } else { ?>
                <td><input type="text" name="<?php print($row['id']) ?>" pattern="[012]" required="required" placeholder="1" /></td></tr>
                <?php }} ?>
                </table>
                <input type="submit" value="Absenden!" />
            </form>
            Ihr könnt beliebig oft eure Auswahl verändern und wieder auf "absenden" klicken, eure neue Auswahl überschreibt dann eure vorherige.
            <?php } ?>
        </div>
    </body>
</html> 
<?php $dbh = null; ?>
