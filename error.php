<?php 
  include $_SERVER['DOCUMENT_ROOT'].'/include/mimetype.php';
  write_html_open();

  include $_SERVER['DOCUMENT_ROOT'].'/include/head.php';
  write_head_open();
  write_head_metadata("Error Handling Request");
  write_head_stylesheets();
  write_head_close();
?>
<body>
<div id="container">

<?php
include $_SERVER['DOCUMENT_ROOT'].'/include/title.html';
?>

<div id="sidebar">
<?php
include $_SERVER['DOCUMENT_ROOT'].'/include/sidenavbar.html';
echo "\n";
?>
</div>

<div id="content">

<?php
$errormsg = array(0   => 'Unexpected Error Encountered',
		  400 => 'Error:  Bad Request',
		  401 => 'Error:  Unauthorized',
		  403 => 'Error:  Forbidden',
		  404 => 'Error:  Not Found',
		  500 => 'Error:  Internal Server Error');

$errordesc = array(0 => <<<EOT
<p>The error you have encountered is so strange, bad, or rare that this error
page is unable to recognize the error encountered.  Perhaps something is very
wrong.  I recommend returning to the page you came from and not repeating the
actions you took to get here.  (You may also contact the
<a href="mailto:webmaster@kevinlocke.name">webmaster</a> and inform them of the
error and how it occurred.)</p>
EOT
, 400 => <<<EOT
<p>The server was unable to understand your request.  This is most likely due to
a problem in your web browser software and/or the web server software running
this website, both of which are outside of my (the webmaster's) direct control.
Sorry 'bout that.</p>
EOT
, 401 => <<<EOT
<p>You do not have permission to access this part of the website.  If you think
this is in error, please contact the
<a href="mailto:webmaster@kevinlocke.name">webmaster</a>.  For the rest of you,
better luck next time.</p>
EOT
, 403 => <<<EOT
<p>This part of the website is off limits.  You are encouraged to visit another
portion of the site and to forget the path by which you reached this page.</p>
EOT
, 404 => <<<EOT
<p>The requested page was not found.  This may have resulted due to a page being
moved and links from other pages to that page not being updated.  If this is
the case, the <a href="mailto:webmaster@kevinlocke.name">webmaster</a> will
correct this situation shortly.  This may have resulted due to incorrectly
written links from other websites, in which case the situation is likely
hopeless.  This may also have resulted from operator error (that means you),
in which case you are encouraged to be careful.  Computers have been known to
become angry due to repeated operator error.</p>
EOT
, 500 => <<<EOT
<p>An error in the software that serves these pages has occurred.  You are off
the hook, the problem is completely out of your hands.  Squads of software
engineers have been alerted and are currently jumping into action to remedy
this error.  Have a nice day.</p>
EOT
);

if (isset($_SERVER['REDIRECT_STATUS'])) {
  $errornum = $_SERVER['REDIRECT_STATUS'];
} else if (isset($_GET['error'])) {
  $errornum = $_GET['error'];
} else {
  $errornum = 0;
}

if (!isset($errormsg[$errornum]) || !isset($errordesc[$errornum])) {
  $errornum = 0;
}

echo "<h2>$errormsg[$errornum]</h2>\n";
#if (!empty($_SERVER['REDIRECT_ERROR_NOTES'])) {
#  echo '<h3>'.$_SERVER['REDIRECT_ERROR_NOTES']."</h3>\n";
#}
echo $errordesc[$errornum];

?>

</div>

<?php include $_SERVER['DOCUMENT_ROOT'].'/include/footer.html'; ?>

</div>
</body>
</html>
<!-- vim: set ts=8 sts=2 sw=2 noet: -->
