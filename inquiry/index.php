<?php
  include $_SERVER['DOCUMENT_ROOT'].'/include/mimetype.php';
  write_html_open();

  include $_SERVER['DOCUMENT_ROOT'].'/include/head.php';
  write_head_open();
  write_head_metadata("Inquiries",
		      "Index page for all inquiries on Kevin Locke's website");
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
<h2>Inquiry</h2>
<p>Although it has been claimed that the Internet is
<q cite="http://en.wikipedia.org/wiki/Series_of_tubes">not something [that] you
just dump something on</q>, I respectfully disagree.&nbsp; In fact, this section
of the website will serve as a place on the Internet where there will likely be
a significant amount of dumping.&nbsp; Specifically, I hope to dump lots of the
data that I collect as part of analyses for questions that have piqued my
interest and which may be interesting to a larger audience.&nbsp; Although
there is currently only a small amount of data, I expect that more dumping will
occur in the near future.</p>

<h3><abbr title="Simple Directmedia Layer">SDL</abbr></h3>
<ul>
  <li><a href="sdlblitspeed/sdlblitspeed.php">SDL Blit-speed Test</a> -
  In this page I examine the effect that different surface
(image) and screen creation parameters have on the blit (drawing) speed of a
program.</li>
</ul>
</div>

<?php include $_SERVER['DOCUMENT_ROOT'].'/include/footer.html'; ?>

</div>
</body>
</html>
<!-- vim: set ts=8 sts=2 sw=2 noet: -->
