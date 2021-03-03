<?php
if (str_ends_with($_SERVER["REQUEST_URI"], ".jpg")) {
	return false;
}
else {
	include 'index.php';
}
