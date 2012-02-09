<?php
# evaluate GET parameters
if (isset($_GET['m']) && isset($_GET['v']) && isset($_GET['d'])) {
    $users      = $_GET['m'];
    $votes      = $_GET['v'];
    $letter     = substr($_GET['d'],0,1);
}
if (empty($users)) die();

# how big are the steps to cover the 0-127 alpha range
$alphastep = 127-(127/$users);

# number of votes = number of steps used
$steps = $votes;

# for text output
$votepercent = round($votes * (100/$users));
$votepercent = $votepercent . "%";

# create a new image, define colors and fonts to be used in it
$img = imagecreatetruecolor(100,50);
$green = imagecolorallocatealpha($img,0,200,0,$alphastep);
$white = imagecolorallocate($img,255,255,255);
$gray = imagecolorallocate($img,245,245,245);
$font = '/usr/share/fonts/truetype/ttf-dejavu/DejaVuSans-Bold.ttf';

# enable alpha blending and fill with white background
imagealphablending($img, true);
imagefill($img, 0, 0, $white);


# layer the appropriate number of partly-transparent colored layers on top of each other
if ($steps > 0) {
    for ($i=0; $i < $steps; $i++) {
        imagefilledrectangle($img, 0, 0, 100, 50, $green);
    }
}

# add text output, in a different color if number of colored layers was 0
if ($steps > 0) {
    imagettftext($img, 20, 0, 38, 35, $white, $font, "$letter");
    imagettftext($img, 10, 0, 05, 45, $white, $font, "$votepercent");
} else {
    imagettftext($img, 20, 0, 38, 35, $gray, $font, "$letter");
    imagettftext($img, 10, 0, 05, 45, $gray, $font, "$votepercent");
}

# send HTTP header, the image, and clean up after ourselves
header('Content-Type: image/png');
imagepng($img);
imagedestroy($img);
?>
