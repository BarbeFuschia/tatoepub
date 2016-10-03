<?php

require "tatoepub.php";

$path = $_POST['urlta'];

echo "voyons voir si ça marche<br>";

if (extractHtml($path) === false) {
	echo "erreur quelque part<br>";
} else {
	echo "a première vue ça a marché<br>";
}

/*
 * pour le xhtml strict nécessaire aux epub
 * <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
  * */
?>
