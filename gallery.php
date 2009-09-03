<?php
require(dirname(__FILE__).'/../active-php/active-php/index.php');

define('MIME_GALLERY_JSON', 'application/vnd.genezys.gallery+json');
define('MAX_SIZE', 210);
define('DIR_ALBUMS', 'albums');
define('DIR_THUMBNAILS', 'thumbnails');
define('DIR_VIEWS', 'views');

/////////////////////////////////////////////////////////////////////
ActiveController::route('get', '/', function()
{
	ActiveController::views(__FILE__, DIR_VIEWS.'/view');
	ActiveController::respondWithView('html', 'text/html');
	ActiveController::respond();
});

/////////////////////////////////////////////////////////////////////
ActiveController::route('get', '/events', function()
{
	$events = glob(dirname(__FILE__).'/'.DIR_ALBUMS.'/*',  GLOB_ONLYDIR);
	
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
	$pathEvent = dirname(__FILE__).'/'.DIR_ALBUMS.'/'.$event;
	
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
			$result[$image] = ActiveRequest::scriptUri().'/events/'.urlencode($event).'/'.urlencode($image);
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
	$pathImage = dirname(__FILE__).'/'.DIR_ALBUMS.'/'.$idImage;
	$uriImage = ActiveRequest::relativeUri(DIR_ALBUMS.'/'.$idImage);
	
	
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
	
	$pathThumbnail = dirname(__FILE__).'/'.DIR_THUMBNAILS.'/'.$idImage;
	$uriThumbnail = ActiveRequest::relativeUri(DIR_THUMBNAILS.'/'.$idImage);
	
	if( !is_file($pathThumbnail) ) 
	{
		$img = imagecreatefromjpeg($pathImage);
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