<?php
require(dirname(__FILE__).'/../active-php/active-php/index.php');

define('MIME_GALLERY_JSON', 'application/vnd.genezys.gallery+json');
define('MAX_SIZE', 210);
define('PATH_ALBUMS', dirname(__FILE__).'/albums');
define('PATH_THUMBNAILS', dirname(__FILE__).'/thumbnails');
define('PATH_VIEWS', dirname(__FILE__).'/views');

/////////////////////////////////////////////////////////////////////
ActiveController::route('get', '/', function()
{
	ActiveController::views(PATH_VIEWS.'/view');
	ActiveController::respondWithView('html', 'text/html');
	ActiveController::respond();
});

/////////////////////////////////////////////////////////////////////
ActiveController::route('get', '/events', function()
{
	$events = glob(PATH_ALBUMS.'/*',  GLOB_ONLYDIR);
	
	ActiveController::respondWith('js', MIME_GALLERY_JSON, function()use($events)
	{
		$format = function($result, $event)
		{
			$name = basename($event);
			$result[$name] = ActiveRequest::scriptUri().'/events/'.urlencode($name);
			return $result;
		};
		echo json_encode(array_reduce($events, $format, array()));
	});
	ActiveController::respond();
});

/////////////////////////////////////////////////////////////////////
ActiveController::route('get', '/events/:event', function($params)
{
	$event = ActiveUtils::arrayGet($params, 'event');
	$pathEvent = PATH_ALBUMS.'/'.$event;
	
	if( !is_dir($pathEvent) )
	{
		ActiveController::status(404);
		return;
	}
	
	$directoryEntries = glob($pathEvent.'/*');
	$imagePaths = array_filter($directoryEntries, function($entry) { return is_file($entry); });
	$images = array_map(function($path){ return basename($path); }, $imagePaths);
	
	ActiveController::respondWith('js', MIME_GALLERY_JSON, function()use($event, $images)
	{
		$format = function($result, $image)use($event)
		{
			$result[] = array(
				'name'=> $image,
				'href'=> ActiveRequest::scriptUri().'/events/'.urlencode($event).'/'.urlencode($image)
			);
			return $result;
		};
		echo json_encode(array_reduce($images, $format, array()));
	});
	ActiveController::respond();
});

/////////////////////////////////////////////////////////////////////
ActiveController::route('get', '/events/:event/:image', function($params)
{
	$event = ActiveUtils::arrayGet($params, 'event');
	$image = ActiveUtils::arrayGet($params, 'image');
	$idImage = $event.'/'.$image;
	$pathImage = PATH_ALBUMS.'/'.$idImage;
	$uriImage = ActiveRequest::relativeUri(ActiveUtils::relativePath(__FILE__, $pathImage));
	
	
	if( !is_file($pathImage) )
	{
		ActiveController::status(404);
		return;
	}
	
	$size = getimagesize($pathImage);
	$exif = exif_read_data($pathImage, 'IFD0');

	$width = $size[0];
	$height = $size[1];
	$ratio = ($width * $height * 4) / (MAX_SIZE * MAX_SIZE * 3);
	$ratioSize = sqrt($ratio);
	$widthThumbnail = intval($width / $ratioSize);
	$heightThumbnail = intval($height / $ratioSize);
	
	$pathThumbnail = PATH_THUMBNAILS.'/'.$idImage;
	$uriThumbnail = ActiveRequest::relativeUri(ActiveUtils::relativePath(__FILE__, $pathThumbnail));
	
	if( !is_file($pathThumbnail) ) 
	{
		$img = imagecreatefromjpeg($pathThumbnail);
		$thumbnail = imagecreatetruecolor($widthThumbnail, $heightThumbnail);
		imagecopyresampled($thumbnail, $img, 0, 0, 0, 0, $widthThumbnail, $heightThumbnail, $width, $height);
		mkdir(dirname($pathThumbnail), 0777, true);
		imagejpeg($thumbnail, $pathThumbnail);
	}
	
	$imageInfo = array(
		'name'=> $image,
		'href'=> $uriImage,
		'width'=> $width,
		'height'=> $height,
		'thumbnail'=> $uriThumbnail,
		'thumbnailwidth'=> $widthThumbnail,
		'thumbnailheight'=> $heightThumbnail,
		'orientation'=> ActiveUtils::arrayGet($exif, 'Orientation'),
	);
	echo json_encode($imageInfo);
});

/////////////////////////////////////////////////////////////////////
ActiveController::dispatch();

?>