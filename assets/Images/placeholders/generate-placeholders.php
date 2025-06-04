<?php
/**
 * Placeholder Image Generator for E-Stem Suriname
 * 
 * This script generates placeholder images for the Surinamese visual identity
 * with patterns, flags, and nature motifs.
 */

// Set content type to plain text for console output
header('Content-Type: text/plain');

/**
 * Create a simple colored image with text
 */
function createPlaceholderImage($filename, $width, $height, $bgColor, $textColor, $text, $pattern = null) {
    // Create the image
    $image = imagecreatetruecolor($width, $height);
    
    // Allocate colors
    $bgColorRGB = sscanf($bgColor, "#%02x%02x%02x");
    $bg = imagecolorallocate($image, $bgColorRGB[0], $bgColorRGB[1], $bgColorRGB[2]);
    
    $textColorRGB = sscanf($textColor, "#%02x%02x%02x");
    $color = imagecolorallocate($image, $textColorRGB[0], $textColorRGB[1], $textColorRGB[2]);
    
    // Fill the background
    imagefill($image, 0, 0, $bg);
    
    // Apply pattern if specified
    if ($pattern == 'dots') {
        // Create dot pattern
        $dotColor = imagecolorallocate($image, 
            $bgColorRGB[0] * 0.9, 
            $bgColorRGB[1] * 0.9, 
            $bgColorRGB[2] * 0.9
        );
        
        for ($x = 0; $x < $width; $x += 20) {
            for ($y = 0; $y < $height; $y += 20) {
                imagefilledellipse($image, $x, $y, 4, 4, $dotColor);
            }
        }
    } elseif ($pattern == 'stripes') {
        // Create diagonal stripes
        $stripeColor = imagecolorallocate($image, 
            $bgColorRGB[0] * 0.9, 
            $bgColorRGB[1] * 0.9, 
            $bgColorRGB[2] * 0.9
        );
        
        for ($i = -$height; $i < $width; $i += 20) {
            imageline($image, $i, 0, $i + $height, $height, $stripeColor);
        }
    } elseif ($pattern == 'flag') {
        // Create Suriname flag
        $red = imagecolorallocate($image, 200, 16, 46);
        $green = imagecolorallocate($image, 0, 120, 71);
        $white = imagecolorallocate($image, 255, 255, 255);
        $yellow = imagecolorallocate($image, 255, 209, 0);
        
        $band_height = $height / 5;
        
        // Green top band
        imagefilledrectangle($image, 0, 0, $width, $band_height, green);
        
        // White band
        imagefilledrectangle($image, 0, $band_height, $width, 2 * $band_height, $white);
        
        // Red center band
        imagefilledrectangle($image, 0, 2 * $band_height, $width, 3 * $band_height, $red);
        
        // White band
        imagefilledrectangle($image, 0, 3 * $band_height, $width, 4 * $band_height, $white);
        
        // Green bottom band
        imagefilledrectangle($image, 0, 4 * $band_height, $width, 5 * $band_height, $green);
        
        // Yellow star in center
        $star_size = $height / 4;
        $center_x = $width / 2;
        $center_y = $height / 2;
        
        $points = [];
        for ($i = 0; $i < 5; $i++) {
            $points[] = $center_x + $star_size * cos(2 * M_PI * $i / 5 - M_PI / 2);
            $points[] = $center_y + $star_size * sin(2 * M_PI * $i / 5 - M_PI / 2);
        }
        
        imagefilledpolygon($image, $points, 5, $yellow);
    }
    
    // Add the text
    $font_size = 5;
    $text_box = imagettfbbox($font_size, 0, 'arial.ttf', $text);
    
    // If font doesn't load, use built-in font
    if (!$text_box) {
        $font = 5; // Built-in font size (1-5)
        $text_width = imagefontwidth($font) * strlen($text);
        $text_height = imagefontheight($font);
        
        $x = ($width - $text_width) / 2;
        $y = ($height - $text_height) / 2 + $text_height;
        
        imagestring($image, $font, $x, $y, $text, $color);
    } else {
        $text_width = $text_box[2] - $text_box[0];
        $text_height = $text_box[1] - $text_box[7];
        
        $x = ($width - $text_width) / 2;
        $y = ($height - $text_height) / 2 + $text_height;
        
        imagettftext($image, $font_size, 0, $x, $y, $color, 'arial.ttf', $text);
    }
    
    // Save the image
    imagepng($image, $filename);
    imagedestroy($image);
    
    echo "Created $filename ($width x $height)\n";
}

// Create directory if it doesn't exist
$dir = __DIR__;
if (!file_exists($dir)) {
    mkdir($dir, 0777, true);
    echo "Created directory: $dir\n";
}

// Create Suriname pattern image
createPlaceholderImage("$dir/suriname-pattern.png", 800, 800, "#007847", "#FFFFFF", "Suriname Pattern", "dots");

// Create Suriname flag pattern
createPlaceholderImage("$dir/suriname-flag.png", 800, 400, "#007847", "#FFFFFF", "Suriname Flag", "flag");

// Create Suriname nature-inspired pattern
createPlaceholderImage("$dir/suriname-nature.png", 800, 600, "#007847", "#FFFFFF", "Suriname Nature", "stripes");

// Create a background pattern for login pages
createPlaceholderImage("$dir/login-bg.png", 1200, 800, "#007847", "#FFFFFF", "Login Background", "dots");

// Create a background for admin dashboard
createPlaceholderImage("$dir/admin-bg.png", 1200, 800, "#007847", "#FFFFFF", "Admin Dashboard", "stripes");

echo "All placeholder images generated successfully!";
?> 