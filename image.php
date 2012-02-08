<?php
if (isset($_GET['m']) && isset($_GET['v']) && isset($_GET['d'])) {
    $users      = $_GET['m'];
    $votes      = $_GET['v'];
    $letter     = substr($_GET['d'],0,1);
}
if (empty($users)) die();

$alphastep = 127-(127/$users);
$steps = $votes;
$votepercent = round($votes * (100/$users));

$font = '/usr/share/fonts/truetype/ttf-dejavu/DejaVuSans-Bold.ttf';
$img = imagecreatetruecolor(100,50);
$green = imagecolorallocatealpha($img,0,200,0,$alphastep);
$white = imagecolorallocate($img,255,255,255);
$gray = imagecolorallocate($img,245,245,245);

imagealphablending($img, true);
imagefill($img, 0, 0, $white);


if ($steps > 0) {
    for ($i=0; $i < $steps; $i++) {
        imagefilledrectangle($img, 0, 0, 100, 50, $green);
    }
}

if ($steps > 0) {
    imagettftext($img, 20, 0, 38, 35, $white, $font, "$letter");
    imagettftext($img, 12, 0, 05, 45, $white, $font, "$votepercent");
} else {
    imagettftext($img, 20, 0, 38, 35, $gray, $font, "$letter");
    imagettftext($img, 12, 0, 05, 45, $gray, $font, "$votepercent");
}

header('Content-type: image/png');
imagepng($img);
imagedestroy($img);
?>
