<?php
  include $_SERVER['DOCUMENT_ROOT'].'/include/mimetype.php';
  write_html_open();

  include $_SERVER['DOCUMENT_ROOT'].'/include/head.php';
  write_head_open();
  write_head_metadata("HTTP_Accept",
		      "Webpage for the HTTP_Accept PHP library");
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

include $_SERVER['DOCUMENT_ROOT'].'/include/news.html';
?>
</div>

<div id="content">

<h2>HTTP_Accept</h2>

<h3>Description</h3>
<p>HTTP_Accept is a PHP script for dealing with both parsing and constructing
of <a href="http://tools.ietf.org/html/rfc2616#section-14.1">HTTP 1.1 Accept
headers</a> according to <a href="http://tools.ietf.org/html/rfc2616">RFC
2616</a>.&nbsp; It is primarily designed to make it easy for web developers
to determine what content to send to clients while following the behavior
detailed in the RFC.
</p>

<h3>Features</h3>
<ul>
<li>Supports quoted strings in parameter values</li>
<li>Supports MIME Types with parameters, quality values, and extension
key/value pairs as well as extension tokens without values</li>
<li>Support for determining the quality factors by best matching (e.g.
<code>text/html</code> will match <code>text/*</code> and
<code>text/html;level=4</code> will match <code>text/html</code>)</li>
<li>Support for determining if a match was exact (useful for browsers which
do not report unsupported subtypes)</li>
<li>Tested under PHP 4 and 5</li>
</ul>

<h3>Development Status</h3>
<p>Idle.&nbsp; HTTP_Accept currently supports all of the features that I
need.&nbsp; I have considered attempting to get it into
<a href="http://pear.php.net">PEAR</a>, and I believe it meets all of the
requirements (with the exception of exceptions, which are not supported in
PHP 4), but I have not been able to muster enough courage to do it.&nbsp; If
you have places to take the software or features to add, please do!&nbsp; I
will also happily accept bug reports and feature requests.</p>

<h3>License</h3>
<p><a href="http://www.opensource.org/licenses/mit-license.php">MIT</a></p>

<h3>Supported Systems</h3>
<p>Should work on any system with PHP 4 or 5.&nbsp; It has not yet been tested
with PHP 6.</p>

<h3>Dependencies</h3>
<ul>
<li><a href="http://php.net">PHP</a></li>
</ul>

<h3>Installing</h3>
<ol>
<li><a href="HTTP_Accept-1.0.0.tar.gz">Download it</a></li>
<li>Extract it and put HTTP_Accept.php into an accessible directory, then
include it from the client scripts</li>
</ol>

<h3>Documentation</h3>
<p>The documentation is generated using
<a href="http://phpdoc.org">phpDocumentor</a>, and is available
<a href="http_accept-phpdoc/">online</a>.</p>

<h3>Known Bugs</h3>
<ul>
<li>None reported, yet...</li>
</ul>

</div>

<?php include $_SERVER['DOCUMENT_ROOT'].'/include/footer.html'; ?>

</div>
</body>
</html>
