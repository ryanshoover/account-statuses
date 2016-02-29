<?php

require_once( './csv-processing.php' );

$uploaddir = '/tmp/';

$errors = [];

if ( ! empty( $_FILES ) && isset( $_FILES['accounts-csv'] ) ) {

	$upload_file = $uploaddir . basename($_FILES['accounts-csv']['name']);

	if ( move_uploaded_file( $_FILES['accounts-csv']['tmp_name'], $upload_file ) ) {
		$csv_processor = WPEProcessCsv::get_instance();
		$csv_processor->process_csv( $upload_file );
		die();
	} else {
		$errors[] = 'The file could not be uploaded';
	}
}

?>

<!DOCTYPE html>
<html>
<head>

<title>Get Account Statuses</title>

<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css" integrity="sha384-fLW2N01lMqjakBkx3l/M9EahuwpSfeNvV63J5ezn3uZzapT0u7EYsXMjQV+0En5r" crossorigin="anonymous">

<style>
/* Sticky footer styles
-------------------------------------------------- */
html {
  position: relative;
  min-height: 100%;
}
body {
  margin-bottom: 75px;
}
.footer {
  position: absolute;
  bottom: 0;
  width: 100%;
  height: 75px;
  padding: 30px 0;
  background-color: #f5f5f5;
}
</style>
</head>

<body>
	<header class="jumbotron">
		<div class="container">
			<h1>Get Account Status</h1>
			<p>Upload a CSV file of account information to get the account's current status</p>
		</div>
	</header>
	<main class="container" role="main">

		<?php
		foreach ( $errors as $error ) {
				$close = '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';

			echo '<p class="alert alert-danger">' . $close . $error . '</p>';
		}
		?>

		<form action="#" method="post" enctype="multipart/form-data" name="csv-upload">
			<div class="row">
				<div class="col-sm-5 form-group">
					<label for="accounts-csv">CSV File</label>
					<input type="file" name="accounts-csv" class="form-control">
					<p class="help-block">Make sure the file is in the correct format*</p>
				</div>

				<div class="col-sm-4 col-sm-offset-2 form-group">
					<input type="submit" class="btn btn-lg btn-primary btn-block">
				</div>
			</div>
		</form>

		<div class="row text-muted">
			<div class="col-sm-4 col-sm-offset-1">
				<p>* Your CSV needs to have the following features</p>
				<ul>
					<li>A header row with labels for all the columns</li>
					<li>The account ID as the first column</li>
				</ul>
			</div>
		</div>
	</main>

	<footer class="footer text-right">
		<div class="container">
			<p class="text-muted">&copy; 2016 Ryan Hoover</p>
		</div>
	</footer>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js" async></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous" async></script>
</body>
</html>
