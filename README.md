## Alibaba

A simple PHP user authentication system. There is no support for roles/rights.

### Usage

#### Configuration

Copy `alibaba_config.sample.php` to `alibaba_config.php` and edit it to match your app.

#### General

	<?php

	include_once("Alibaba.class.php");

	Alibaba::ensureAuthentication();

	// The rest of your page

	?>

#### Login page

	<?php

	include_once("Alibaba.class.php");

	$username = $_POST["username"];
	$password = $_POST["password"];

	if (Alibaba::login($username, $password)) {
		header("Location: index.php");
	} else {
		Alibaba::redirectToLogin("Login failed");
	}

	?>

#### Logout page

	<?php
	
	include_once("Alibaba.class.php");

	Alibaba::logout();

	?>

### License

Public domain.