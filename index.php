<?php
header('Content-Type:text/html; charset=utf-8');
#ini_set('error_reporting',-1);
#ini_set('display_errors',1);

require_once 'dbdata.inc.php';
$dbh = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
$dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

if (isset($_GET['name'])  && !empty($_GET['name'])  && isset($_GET['k'])  && !empty($_GET['k']))  {
    $name   =   $_GET['name'];
    $k      =   $_GET['k'];
}
if (isset($_POST['name']) && !empty($_POST['name']) && isset($_POST['k']) && !empty($_POST['k']) && isset($_POST['vote']) && !empty($_POST['vote'])) {
    $name   =   $_POST['name'];
    $k      =   $_POST['k'];
    $vote   =   $_POST['vote'];
}

if (isset($name) && isset($k)) {
    $stmt = $dbh->prepare("SELECT STRCMP(SHA1(CONCAT(salt,:name,:k,'\n')), hash) AS cmp, id FROM users WHERE name=:name");
    //$stmt->bindParam(':name', $name, PDO::PARAM_STR, 13);
    //$stmt->bindParam(':k', $k, PDO::PARAM_STR, 13);
    $stmt->execute(array('name' => $name, 'k' => $k));

    while($row = $stmt->fetch()) {
        if (isset($row['cmp']) && $row['cmp'] != 0) {
            die("Ungültige Benutzerdaten!");
        } else {
            $id     =   $row['id'];
        }
    }
}

if (isset($id) && isset($vote)) {
    $votes = array();
    for ($i = 1; $i <= 7; $i++) {
        if (isset($_POST[$i]) && ($_POST[$i] >= 0) && ($_POST[$i] <=2 )) {
            $votes[$i] = $_POST[$i];
        }
    }
    
    $stmt = $dbh->prepare("INSERT INTO votes (day, user, vote) VALUES (:i, :id, :vote) ON DUPLICATE KEY UPDATE day=:i, user=:id, vote=:vote");
    for ($i = 1; $i <= 7; $i++) {
        $stmt->execute(array('i' => $i, 'id' => $id, 'vote' => $votes[$i]));
    }
}

$gecko = strpos($_SERVER['HTTP_USER_AGENT'],"Gecko/");
$webkit= strpos($_SERVER['HTTP_USER_AGENT'],"AppleWebKit");

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
                
            $rows = $dbh->query('SELECT count(id) AS num_users FROM users');
            $row = $rows->fetch();
            $num_users = 2*$row['num_users'];

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
            <?php } ?>
        </div>
    </body>
</html> 
<?php $dbh = null; ?>
