<?php
/*
The following code will generate the largest possible mimic of a Facebook thumbnail for a given 
user without losing resolution. The generated thumbnail is not guaranteed to be exactly the
same as the real thumbnail on Facebook for the user, but it is close.

*** You will need to have a version of imageMagick installed that can execute the '-subimage-search' command. ***
Furthermore, be sure that any call to an imageMagick command, e.g.; identify or convert, contains the full path to it. For me that would be:
$HOME/local/bin/<command> etc.

Licensing-
If you want to use this code, I would very much appreciate you referencing 
my name, Bradley Griffith - @bradleygriffith on Twitter, and website, http://lessucettes.com,
but you do NOT have to do any such thing.

Usage, whether stated in script or not, is also subject to the ImageMagick license, which can be found at: http://www.imagemagick.org/script/license.php
*/

//Set a Facebook user id to be used.
$user = '44409482';



//Get images from Facebook.
$thumbnailImage = 'https://graph.facebook.com/' . $user . '/picture';
$fullSizeImage = 'https://graph.facebook.com/' . $user . '/picture?type=small';
$fullSizeImageLarge = 'https://graph.facebook.com/' . $user . '/picture?type=large';


//Display recieved images.
echo 'Recieved thumbnail image:<br><img src="' . $thumbnailImage . '" /><br>';
echo 'Recieved profile image:<br><img src="' . $fullSizeImage . '" /><br><br>';
echo '<br><br>';

//Get dimensions of full size profile image (without saving the image).
$fullSizeImageLargeDimensions = getimagesize($fullSizeImageLarge);
$largeProfileWidth = $fullSizeImageLargeDimensions[0];
$largeProfileHeight = $fullSizeImageLargeDimensions[1];

//Declare paths for saving the small thumb and the small version of the full profile image.
$thumbnailPath = 'path/to/images/imageThumb_' . $user . '.jpg';
$fullSizePath = 'path/to/images/imageFull_' . $user . '.jpg';


//Save both the thumbnail and the small version of the full profile image by calling the saveImage() function.
saveImage($thumbnailImage, $thumbnailPath);
saveImage($fullSizeImage, $fullSizePath);


function saveImage($img,$fullPath){
    if($handle = fopen($img, 'rb')) {
        $newfile_name = $fullPath;
        $newfile = fopen($newfile_name,'wb');
        while (!feof($handle)) { 
            $chunk = fread($handle,1024);
            fwrite($newfile,$chunk);
        }
        fclose($newfile);
        fclose($handle);
    } 
    else {
        echo 'Could\'nt find image.jpg!';
    }
}


/* 
Resize thumbnail for comparing to small version of full profile image.

This part is tricky and could definitely be improved upon. Essentially, we need to compare the recieved 
thumbnail and small version of the full profile image in as close to the same resolution as possible. 
However, facebook returns the full profile image with set widths and variable heights. The numbers you see following 
'-resize' in the imageMagick command correspond to X and Y, formatted as XxY. So, if the profile image is wider than it 
is tall, we shrink its Y value to the height of the small version of the full profile image minus 2px, and if it is not, we shrink it's width by 2px. Since the later's width is always 45 in this usage, it is hardcoded to shrink to 43px.
*/
if($largeProfileWidth > $largeProfileHeight){
    $getSizeString = '/path/to/identify -format "%h" ' . $fullSizePath;
    $fullHeight = exec($getSizeString);
    $convertString = '/path/to/mogrify -resize x' . ($fullHeight - 2) . ' ' . $thumbnailPath;
}
else{
    $convertString = '/path/to/mogrify -resize 43 ' . $thumbnailPath;
}
exec($convertString);


//Get height and width for both the thumbnail image and the small version of the full profile image.
$getSizeString = '/path/to/identify -format "%h" ' . $fullSizePath;
$fullHeight = exec($getSizeString);

$getSizeString = '/path/to/identify -format "%w" ' . $fullSizePath;
$fullWidth = exec($getSizeString);

$getSizeString = '/path/to/identify -format "%h" ' . $thumbnailPath;
$thumbHeight = exec($getSizeString);

$getSizeString = '/path/to/identify -format "%w" ' . $thumbnailPath;
$thumbWidth = exec($getSizeString);



//Locate thumbnail within small version of the full profile image.
$finderString = '/path/to/compare  -dissimilarity-threshold 1 -metric RMSE -subimage-search ' . $fullSizePath . ' ' . $thumbnailPath . ' null: 2>&1';
$coords = exec($finderString);
$coordsBoth = explode('@ ', $coords);
$coordsBoth = explode(',',$coordsBoth[1]);
$coordsX = $coordsBoth[0];
$coordsY = $coordsBoth[1];



//Convert the coords decimal offsets of the small version of the full profile image.
if($coordsX == 0){
    $offsetX = $coordsX;
}
else{
    $offsetX = $coordsX / $fullWidth;
}
if($coordsY == 0){
    $offsetY = $coordsY;
}
else{
    $offsetY = $coordsY / $fullHeight;
}

//Get pixel offsets of the largest possible thumbnail within the largest version of the full profile image.
$largeOffsetX = round($largeProfileWidth * $offsetX);
$largeOffsetY = round($largeProfileHeight * $offsetY);




//Get decimal height and width of of thumbnail within the small version of the full profile image.
$extentX = $thumbWidth / $fullWidth;
$extentY = $thumbHeight / $fullHeight;

//Find the width and height of the large version of the thumbnail within the largest version of the full profile image.
$largeThumbWidth = $largeProfileWidth * $extentX;
$largeThumbHeight = $largeProfileHeight * $extentY;



//Average and round the large thumbnail's width and height to ensure a square image.
$largeThumbWidth = round(($largeThumbWidth + $largeThumbHeight) / 2);
$largeThumbHeight = round(($largeThumbWidth + $largeThumbHeight) / 2);


//Delete both the thumbnail and the small version of the full profile image by calling the deleteImage() function.
deleteImage($thumbnailPath);
deleteImage($fullSizePath);


function deleteImage($fullPath){
    unlink($fullPath);
}


//Display relevant numbers.
echo 'Largest thumbnail size: ' . $largeThumbWidth . 'x' . $largeThumbHeight . '<br>';
echo 'Within large profile image of size: ' . $largeProfileWidth . 'x' . $largeProfileHeight . '<br>';
echo 'With offsets of: -' . $largeOffsetX . ' X  and -' . $largeOffsetY . ' Y<br/>';
echo '<br>';
?>

<html>
    <head>
        <style type="text/css">
            /*
            The values generated above are now used to MIMIC cropping with CSS. This is just an
            example usage. One could easily crop and save a thumbnail version of the user's profile 
            image by using these values in imageMagick for example.
            */
            .largeImage{
                overflow: hidden;
                width: <?php echo $largeThumbWidth;?>px;
                height: <?php echo $largeThumbHeight;?>px;
            }

            .largeImage img{
                margin: -<?php echo $largeOffsetY;?>px -<?php echo $largeOffsetX;?>px ;
            }
        </style>
    </head>
    <body>
        Generated replication of thumbnail:
        <div class='largeImage'>
            <img src='<?php echo $fullSizeImageLarge; ?>'/>
        </div>
    </body>
</html>