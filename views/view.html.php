<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<meta http-equiv="Content-type" content="text/html; charset=utf-8">
<style type="text/css" media="screen">
@import url(<?php echo ActiveRequest::scriptDir() ?>/views/gallery.css);
</style>
</head>
<body>
<script type="text/javascript" charset="utf-8"
	src="http://ajax.googleapis.com/ajax/libs/jquery/1.3/jquery.min.js">
</script>
<script type="text/javascript" charset="utf-8"
	src="../jquery.async.js">
</script>
<script type="text/javascript" charset="utf-8">
//<![CDATA[
(function($){ 
	function rotate($query, degRotation, ieRotation)
	{
		return $query
		.addClass('rotate'+degRotation)
		.css('transform', 'rotate('+degRotation+'deg)')
		.css('WebkitTransform', 'rotate('+degRotation+'deg)')
		.css('MozTransform', 'rotate('+degRotation+'deg)')
		.css('filter', 'progid:DXImageTransform.Microsoft.BasicImage(rotation='+ieRotation+')');
	}
	$.fn.rotate90 = function() { return rotate(this, 90, 1) }
	$.fn.rotate180 = function() { return rotate(this, 180, 2) }
	$.fn.rotate270 = function() { return rotate(this, 270, 3) }
	$.fn.rotateByOrientation = function(orientation)
	{ 
		switch( orientation )
		{
		case 3: this.rotate270(); break
		case 6: this.rotate90(); break
		case 8: this.rotate180(); break
		}
		return this;
	}
})(jQuery);

var EVENTS_URI = location.pathname + "/events";

function init()
{
	$('body').empty();
	
	var match = null;
	if( (match = location.hash.match(/^#\/([^\/]+)\/([^\/]+)$/)) ) 
	{
		var event = unescape(match[1]);
		var image = unescape(match[2]);

		buildImagePage(event, image);
	}
	else if( (match = location.hash.match(/^#\/([^\/]+)$/)) ) 
	{
		var event = unescape(match[1]);
		
		buildEventPage(event);
	}
	else
	{
		buildHomePage();
	}
}

function title(parts)
{
	var text = $('<h1 id="title"></h1>').html(parts.join(' \u25B8 ')).appendTo('body').text();
	document.title = $.map(parts, function(part){ return $('<span/>').html(part).text(); }).reverse().join(' - ');
}

function buildHomePage()
{
	title(['Gallery']);
	
	var $ul = $('<ul></ul>').appendTo('body');
	httpGet(EVENTS_URI, function(events)
	{
		for( var event in events )
		{
			$('<a href="#/'+ event +'">'+ event +'</a>')
			.wrap('<li></li>')
			.parent()
			.appendTo($ul);
		}	
	})
}

function buildEventPage(event)
{
	title(['<a href="#">Gallery</a>', event]);

	var $ul = $('<ul class="event"></ul>').appendTo('body');
	
	httpGet(EVENTS_URI, function(events)
	{
		httpGet(events[event], function(images)
		{ 
			$.each(images, function(name, url)
			{ 
				var $img = $('<li></li>').appendTo($ul);

				httpGet(url, function(img)
				{
					var size = Math.max(img.thumbnailwidth, img.thumbnailheight),
						offsetTop = Math.round(Math.max(0, img.thumbnailwidth - img.thumbnailheight) / 2),
						offsetLeft = Math.round(Math.max(0, img.thumbnailheight - img.thumbnailwidth) / 2);

					$img.append('<a href="#/'+ event +'/'+ img.name +'">'
						+'<img src="'+img.thumbnail +'"'
						+' width="'+img.thumbnailwidth+'" height="'+img.thumbnailheight+'">'
						+'</a>')
						.css({ 
							width: size - offsetLeft, 
							height: size - offsetTop, 
							paddingTop: offsetTop, 
							paddingLeft: offsetLeft 
						})
						.rotateByOrientation(img.orientation);
				});
			});
		});
	});	
}

function buildImagePage(event, image)
{
	title(['<a href="#">Gallery</a>', '<a href="#/'+event+'">'+event+'</a>', image]);
	var $div = $('<div class="image"></div>').appendTo('body');

	httpGet(EVENTS_URI, function(events)
	{
		httpGet(events[event], function(images)
		{
			httpGet(images[image], function(img)
			{ 
				var size = Math.max(img.width, img.height),
					offsetTop = Math.round(Math.max(0, img.width - img.height) / 2),
					offsetLeft = Math.round(Math.max(0, img.height - img.width) / 2),
				 	
					$img = $('<div><img src="'+ img.href +'"></div>')
					.rotateByOrientation(img.orientation)
					.find('img')
						.one('load', function(){ $(window).trigger('resize') })
					.end()
					.appendTo($div);
					
				// Trigger a resize, we have an event to handled rotated images
				$(window).trigger('resize');
			});
		});
	});
}

function httpGet(url, callback)
{
	arguments.callee.cache = arguments.callee.cache || {};
	var cache = arguments.callee.cache;
	if( url in cache ) 
	{
		callback(cache[url]);
	}
	else
	{
		$.getJSON(url, function(json)
		{ 
			cache[url] = json;
			callback(cache[url]);
		});
	}
}

$(document).ready(function()
{
	init();
	var oldHash = location.hash;
	setInterval(function()
	{
		if( oldHash != location.hash ) 
		{
			init();
			oldHash = location.hash;
		}
	}, 100);
});

// On window resize, adds a paddingTop to rotated images
// to compensate the rotation
$(window).bind('resize', function()
{ 
	 $('.image .rotate90,.image .rotate270').each(function()
	 { 
		var delta = Math.abs($(this).width() - $(this).height());
		var offset = Math.round(delta / 2);
	 	$(this).parent().css({ paddingTop: offset, paddingBottom: delta - offset });
	 });
})
//]]>
</script>
</body>
</html>
