<?php
/*
 * SimpleGallery.
 *
 * A very pragmatic (see also "quick & dirty"?) gallery system.
 * It'll look for subfolders of the main album directory.
 * Each subfolder is considered an album.
 *
 * List of all albums is not allowed, so you can use this to privately share files.
 * Simply come up with an album name unique and random enough so that people won't find it.
 * Recommended format: YYYY-MM-DD_Name-of-your-album_GUID
 *
 * Set the configuration here.
 */

/**
 * SG_ALBUM_DIRECTORY
 *
 * The path to the directory that contains the albums, relative to this file.
 * Must be accessible from WWW
 */
define('SG_ALBUM_DIRECTORY', 'albums');

/**
 * SG_DEBUG
 *
 * Set this to true to enable error reporting.
 * Should be set to false in production.
 */
define('SG_DEBUG', true);


/**
 * SG_PRIMARY_COLOR
 *
 * A valid CSS hex color for your branding.
 * Used for the header background color.
 */
define('SG_PRIMARY_COLOR', '#229933');


/**
 * SG_SECONDARY_COLOR
 *
 * A valid CSS hex color for your branding.
 * Used for the header text color.
 */
define('SG_SECONDARY_COLOR', '#ffffff');


/**
 * SG_TITLE
 *
 * Used for the <title> tag and in the header
 */
define('SG_TITLE', 'github.com/aspyct/simplegallery');


/**
 * SG_LINK_HOME
 *
 * A link to the home of your website/store/social page/whatever.
 * Set this to false if there's no website.
 */
define('SG_LINK_HOME', 'https://github.com/aspyct/simplegallery');


/*******************************
 * Do not edit below this point.
 *******************************/

if (SG_DEBUG) {
	error_reporting(E_ALL);
}

function sg_main() {
	$untrusted_requested_album = sg_get_untrusted_requested_album();
	$existing_album = sg_get_existing_album($untrusted_requested_album);
	$photos_in_album = sg_list_photos_in_album($existing_album);
	
	$untrusted_requested_photo = sg_get_requested_download();
	if ($untrusted_requested_photo !== false) {
		sg_download($existing_album, $untrusted_requested_photo);
	}
	else {
		sg_show_album($existing_album, $photos_in_album);
	}
}


function sg_get_requested_download() {
	if (isset($_GET['download'])) {
		return $_GET['download'];
	}
	else {
		return false;
	}
}


function sg_get_untrusted_requested_album() {
	$uri = $_SERVER['PATH_INFO'];

	if (strlen($uri) > 1 && $uri[0] === '/') {
		return substr($uri, 1);
	}
	else {
		die("SimpleGallery: Invalid request");
	}
}


function sg_get_existing_album($untrusted_requested_album) {
	$all_albums = sg_list_all_albums();

	if ($all_albums !== false) {
		$key = array_search($untrusted_requested_album, $all_albums);
		if ($key !== false) {
			return $all_albums[$key];
		}
		else {
			die("SimpleGallery: Album doesn't exist or was removed.");
		}
	}
	else {
		die("SimpleGallery: Could not list albums");
	}
}


function sg_list_all_albums() {
	$album_directory = sg_get_album_directory();

	if (is_dir($album_directory)) {
		return sg_list_subdirectories($album_directory);
	}
	else {
		die("SimpleGallery: album directory doesn't exist. Check your config");
	}
}


function sg_list_photos_in_album($existing_album) {
	$album_directory = sg_get_album_directory($existing_album);

	$all_entries = scandir($album_directory);
	if ($all_entries !== false) {
		// Assumption 1: there's no subdirectory in an album, only jpg files
		// Assumption 2: thumbnail files are prefixed with "thumb-"
		return array_filter($all_entries, 'is_valid_photo_name');
	}
	else {
		die("SimpleGallery: Could not read the contents of this album.");
	}
}


function is_valid_photo_name($filename) {
	// Assumption: $filename is at least 1 char long
	return $filename[0] !== '.' && !str_starts_with($filename, "thumb-");
}


function sg_list_subdirectories($directory) {
	$all_entries = scandir($directory);
	if ($all_entries !== false) {
		return array_filter($all_entries, fn ($item) => $item[0] !== '.' && is_dir($directory.'/'.$item));
	}
	else {
		return false;
	}
}


function sg_get_album_directory($album = false) {
	$absolute_path = __DIR__.'/'.SG_ALBUM_DIRECTORY;

	if ($album !== false) {
		$absolute_path .= '/'.$album;
	}

	return $absolute_path;
}


function sg_get_photo_absolute_path($album, $photo) {
	return sg_get_album_directory($album).'/'.$photo;
}


/**
 * Attempt to parse the album name based on the standard naming of
 * YYYY-MM-DD_Album-topic_GUID
 * Returns an array with the [DATE, Album-topic], or [false, false] if the format is incorrect
 */
function sg_parse_album_name($album) {
	if (preg_match('/^(\d{4}-\d{2}-\d{2})_([^_]+).*$/', $album, $matches)) {
		return [$matches[1], $matches[2]];
	}
	else {
		return [false, false];
	}
}


function sg_format_album_title($album_title) {
	return str_replace('-', ' ', $album_title);
}


function sg_download($existing_album, $untrusted_requested_photo) {
	$existing_photos = sg_list_photos_in_album($existing_album);

	$index = array_search($untrusted_requested_photo, $existing_photos);
	if ($index !== false) {
		$existing_photo = $existing_photos[$index];
		$photo_absolute_path = sg_get_photo_absolute_path($existing_album, $existing_photo);

		header('Content-Description: File Transfer');
		header('Content-Type: image/jpeg');
		header('Content-Disposition: attachment; filename="'.$existing_photo.'"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($photo_absolute_path));
		readfile($photo_absolute_path);
		exit;
	}
	else {
		die("SimpleGallery: No such photo");
	}
}


function sg_show_album($album, $all_photos) {
	$album_directory = SG_ALBUM_DIRECTORY.'/'.$album;
	[$album_date, $album_title] = sg_parse_album_name($album);

	// Yeah, I see your point, putting html here is dirty... or pragmatic? :D
?><!DOCTYPE html>
<html>
	<head>
		<title><?= SG_TITLE ?></title>
		<meta name="viewport" content="width=device-width, initial-scale=1"/>
		<style>
			html, body {
				padding: 0;	
				margin: 0;
				font-family: sans-serif;
			}

			header {
				color: <?= SG_SECONDARY_COLOR ?>;
				background-color: <?= SG_PRIMARY_COLOR ?>;
			}

			header a, header a:visited {
				color: <?= SG_SECONDARY_COLOR ?>;
			}

			header, h1, ul {
				padding: 12px;
			}

			ul {
				list-style-type: none;
				padding: 0;
				margin: 0;
			}

			li {
				display: inline-block;
			}
		</style>
	</head>
	<body>
		<header>
			<?php if (SG_LINK_HOME !== false): ?>
				<a href="<?= SG_LINK_HOME ?>"><?= SG_TITLE ?></a>
			<?php else: ?>
				<?= SG_TITLE ?>
			<?php endif; ?>
		</header>
		<main>
			<?php if ($album_title !== false): ?>
				<h1><?= sg_format_album_title($album_title) ?> - <?= $album_date ?></h1>
			<?php else: ?>
				<h1><?= $album ?></h1>
			<?php endif; ?>
			<ul>
			<?php foreach ($all_photos as $photo): ?>
				<li><a href="?download=<?= $photo ?>"><img src="<?= $album_directory ?>/thumb-<?= $photo ?>"/></a></li>
			<?php endforeach; ?>
			</ul>
		</main>
	</body>
</html>

<?php
}

/**
 * If you want to include this file for its functionnality (e.g. list an album),
 * make sure you define the SG_SKIP constant, so the gallery doesn't show.
 */
if (!defined('SG_SKIP')) {
	sg_main();
}

