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
define('SG_PRIMARY_COLOR', '#44ff55');


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
	
	sg_show_album($existing_album, $photos_in_album);
}


function sg_get_untrusted_requested_album() {
	$uri = $_SERVER['REQUEST_URI'];

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


function sg_show_album($album, $all_photos) {
	$album_directory = SG_ALBUM_DIRECTORY.'/'.$album;
	// Yeah, I see your point, putting html here is dirty... or pragmatic? :D
?><!DOCTYPE html>
<html>
	<head>
		<title><?= SG_TITLE ?></title>
		<style>
			header {
				color: <?= SG_SECONDARY_COLOR ?>;
				background-color: <?= SG_PRIMARY_COLOR ?>;
			}
		</style>
	</head>
	<body>
		<header><?= SG_TITLE ?></header>
		<main>
			All photos: <ul>
			<?php foreach ($all_photos as $photo): ?>
				<li><a href="<?= $album_directory ?>/<?= $photo ?>"><img src="<?= $album_directory ?>/thumb-<?= $photo ?>"/></a></li>
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

