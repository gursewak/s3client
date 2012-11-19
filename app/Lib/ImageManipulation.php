<?php

class ImageManipulation {
  
  public function resizeToThumb($fileIn, $fileOut = null, $desiredWidth, $desiredHeight,$options = array()) {
    $defaults = array('quality' => 80, 'autocrop' => false, 'dontUpscale' => false);
    $options = array_merge($defaults, $options);

    // pr($this->settings[$this->model->name]['autocrop']); 
    // Used for AutoCropping (if set)
    $canvasWidth = $desiredWidth;
    $canvasHeight = $desiredHeight;

    // Get Image to Work With (based on attachement location passed through from model)
    // This is the original un-altered image
    $img = $fileIn;

    // Get file information to work with
    $info = pathinfo($img);
    $filename = $info['basename'];
    $ext = strtolower($info['extension']);

    // Get Width and Height of Original Image to determine the current dimension ratio
    list($imgWidth, $imgHeight) = getimagesize($img);
    // Set Original Ratio
    $ratioOrig = $imgWidth / $imgHeight;

    // In the event that the image canvasWidth is actually larger than the original image width
    // shrink the canvasWidth and Height to match the original image.  This is used to avoid
    // scaling the image up IF upscale is not desired.
    if ($options['dontUpscale']) {
      $canvasWidth = $imgWidth;
      $canvasHeight = $imgHeight;
    }

    // If the original ratio is larger then the desired ratio, shrink the canvasWidth so that the
    // the canvasWidth and canvasHeight match the ratio of the original un-altered image.
    if ($ratioOrig > ($canvasWidth / $canvasHeight)) {
      $canvasWidth = $canvasHeight * $ratioOrig;
    } else {
      $canvasHeight = $canvasWidth / $ratioOrig;
    }

    if(in_array($ext, array('jpg', 'jpeg'))) {
      $original = imagecreatefromjpeg($img);
    } else if ($ext == 'png') {
      $original = @imagecreatefrompng($img);

      if ($original == false) {
        $original = imagecreatefromjpeg($img);
      }
    } else {
      $original = imagecreatefromgif($img);
    }

    if ($options['autocrop'] == true) {
      $temp = imagecreatetruecolor($canvasWidth, $canvasHeight);
      imagecopyresampled($temp, $original, 0, 0, 0, 0, $canvasWidth, $canvasHeight, $imgWidth, $imgHeight);

      // Copy cropped region from temporary image into the desired GD image
      $x0 = ($canvasWidth - $desiredWidth) / 2;
      $y0 = ($canvasHeight - $desiredHeight) / 2;

      $canvas = imagecreatetruecolor($desiredWidth, $desiredHeight);
      imagecopy($canvas, $temp, 0, 0, $x0, $y0, $desiredWidth, $desiredHeight);
      imagedestroy($temp);
    } else {
      $canvas = imagecreatetruecolor($canvasWidth, $canvasHeight);
      imagecopyresampled($canvas, $original, 0, 0, 0, 0, $canvasWidth, $canvasHeight, $imgWidth, $imgHeight);
    }

    $thumbQuality = $options['quality'];

    if ($fileOut == null) {
      $fileOut = $fileIn;
    }

    if(in_array($ext, array('jpg', 'jpeg'))) {
      $createJpg = imagejpeg($canvas, $fileOut, $thumbQuality);
    } else if ($ext == 'png') {
      $createJpg = imagepng($canvas, $fileOut, ceil($thumbQuality / 10));
    } elseif($ext == 'gif') {
      $createJpg = imagegif($canvas, $fileOut, $thumbQuality);
    } else {
      // TODO: what to do if we cannot handle the image?
      die("Dont know how to handle this file type");
    }

    imagedestroy($canvas);
    imagedestroy($original);

    return($createJpg ? true : false);
  }


  public function cropImage($fileIn, $fileOut = null, $w, $h, $x1, $y1, $x2, $y2) {
    $mediaRoot = WWW_ROOT."/files/folders/";
    $thumbQuality = 80;

    /**
     * Create image instances
     */
    $image_width = $x2 - $x1;
    $image_height = $y2 - $y1;

    $source_x = $x1;
    $source_y = $y1;

    $img = $fileIn;

    /**
     * Get file information to work with
     */
    $info = pathinfo($img);
    $ext = strtolower($info['extension']);

    if (($ext == 'jpg') || ($ext == 'jpeg')) {
      $src = imagecreatefromjpeg($img);
    } else if ($ext == 'png') {
      $src = @imagecreatefrompng($img);

      if ($src == false) {
        $src = imagecreatefromjpeg($img);
      }
    } else {
      $src = imagecreatefromgif($img);
    }


    $dest = imagecreatetruecolor($image_width, $image_height);

    imagecopy($dest, $src, 0, 0, $source_x, $source_y, $image_width, $image_height);

    // Small Image
    $smallImageWidth = $w;
    $smallImageHeight = $h;
    $smallImage = imagecreatetruecolor($smallImageWidth, $smallImageHeight);
    imagecopyresampled($smallImage, $dest, 0, 0, 0, 0, $smallImageWidth, $smallImageHeight, $image_width, $image_height);

    if ($fileOut == null) {
      $fileOut = $fileIn;
    }

    // Create New Files
    if (($ext == 'jpg') || ($ext == 'jpeg')) {
      $createSmallJpg = imagejpeg($smallImage, $fileOut, $thumbQuality);
    } else if ($ext == 'png') {
      $createSmallJpg = imagepng($smallImage, $fileOut, ceil($thumbQuality / 10));
    } else {
      $createSmallJpg = imagegif($smallImage, $fileOut, $thumbQuality);
    }

    // Clear Memory
    imagedestroy($dest);
    imagedestroy($src);
    imagedestroy($smallImage);

    if ($createSmallJpg) {
      return true;
    }
    return false;
}

}

?>