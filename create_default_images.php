<?php
// Create default profile image
$profileImage = imagecreatetruecolor(200, 200);
$bgColor = imagecolorallocate($profileImage, 59, 130, 246); // Blue color
imagefill($profileImage, 0, 0, $bgColor);

// Add a simple pattern
$patternColor = imagecolorallocate($profileImage, 255, 255, 255);
for ($i = 0; $i < 200; $i += 20) {
    imageline($profileImage, $i, 0, $i, 200, $patternColor);
    imageline($profileImage, 0, $i, 200, $i, $patternColor);
}

// Save profile image
if (!file_exists('assets/images/profiles')) {
    mkdir('assets/images/profiles', 0777, true);
}
imagepng($profileImage, 'assets/images/profiles/default-profile.jpg');
imagedestroy($profileImage);

// Create default cover image
$coverImage = imagecreatetruecolor(1200, 300);
$gradientStart = imagecolorallocate($coverImage, 59, 130, 246); // Blue
$gradientEnd = imagecolorallocate($coverImage, 37, 99, 235); // Darker blue

// Create gradient
for ($i = 0; $i < 300; $i++) {
    $ratio = $i / 300;
    $r = 59 + (37 - 59) * $ratio;
    $g = 130 + (99 - 130) * $ratio;
    $b = 246 + (235 - 246) * $ratio;
    $color = imagecolorallocate($coverImage, $r, $g, $b);
    imageline($coverImage, 0, $i, 1200, $i, $color);
}

// Add some pattern
$patternColor = imagecolorallocate($coverImage, 255, 255, 255);
for ($i = 0; $i < 1200; $i += 40) {
    imageline($coverImage, $i, 0, $i, 300, $patternColor);
}

// Save cover image
if (!file_exists('assets/images/covers')) {
    mkdir('assets/images/covers', 0777, true);
}
imagepng($coverImage, 'assets/images/covers/default-cover.jpg');
imagedestroy($coverImage);

echo "Default images created successfully!";
?> 