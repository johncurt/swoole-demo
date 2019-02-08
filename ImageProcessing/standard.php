<?php

$images = [
	'1',
	'2',
	'3',
];

foreach ($images as $image) {
	print "starting {$image}...";
	$filename = "./images/{$image}.jpg";
	$newFilename = "./standardOut/{$image}.png";
	$im = imagecreatefromjpeg($filename);
	$textColor =
		( get_avg_luminance($im) > 127 ) ?
			imagecolorallocatealpha($im, 0, 0, 0, 0)
			: imagecolorallocatealpha($im, 254, 254, 254, 0);
	imagettftext($im, 100, 0, 100, 150, $textColor, './Hero.otf', 'Some Great Text');
	imagepng($im, $newFilename, 0);
	imagedestroy($im);
	print "done. \n";
}

print "finished with everything.\n";


function get_avg_luminance($img, $num_samples = 10) {

	$width = imagesx($img);
	$height = imagesy($img);

	$x_step = intval($width / $num_samples);
	$y_step = intval($height / $num_samples);

	$total_lum = 0;

	$sample_no = 1;

	for ($x = 0; $x < $width; $x += $x_step) {
		for ($y = 0; $y < $height; $y += $y_step) {

			$rgb = imagecolorat($img, $x, $y);
			$r = ( $rgb >> 16 ) & 0xFF;
			$g = ( $rgb >> 8 ) & 0xFF;
			$b = $rgb & 0xFF;

			$lum = ( $r + $r + $b + $g + $g + $g ) / 6;

			$total_lum += $lum;

			$sample_no++;
		}
	}

	// work out the average
	$avg_lum = $total_lum / $sample_no;

	return $avg_lum;
}