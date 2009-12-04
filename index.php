<?php 
  include $_SERVER['DOCUMENT_ROOT'].'/include/mimetype.php';
  write_html_open();

  include $_SERVER['DOCUMENT_ROOT'].'/include/head.php';
  write_head_open();
  write_head_metadata("Kevin Locke's Homepage");
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
<h2>Welcome</h2>
<p>Welcome to Kevin Locke's home on the web.&nbsp; This website is being
built to provide all sorts of useful information and plenty of
humorous/insightful/eclectic tidbits.&nbsp; I hope you enjoy it.</p>

<p>This website is a work in progress and not yet fully developed.&nbsp; All
links should work and information should be accurate, however, content is
seriously lacking and the design may feel somewhat clumsy until more media is
added.&nbsp; If there is anything that should be added or if you have a
suggestion/change to the layout that would make the site better
<a href="contact.php">let me know about it</a>.</p>

<h2>Which Kevin Locke is this?</h2>
<p>As unusual as this may sound, there are more than one of me.&nbsp; Or, at
least, there is more than one person with the name Kevin Locke.&nbsp; So, 
to differentiate myself from the other Kevin Lockes of the world,
I'll provide a quick description of who I am and some information for a few
other Kevin Lockes that I have come across.&nbsp; Also, soon, I should have
some pictures posted so that you can see if I am me...</p>
<p>Who am I?&nbsp; I am the son of
<a href="http://www.homepage.montana.edu/~ueswl/">William Locke</a> and Charlene
Locke, and the brother of Brian Locke.&nbsp; I 
grew up in Bozeman, MT (in the United States), attended and graduated from
Cornell University in Ithaca, NY with a degree in Computer Science, and I am
a founder and president of <a href="http://digitalenginesoftware.com">Digital
Engine Software</a>.&nbsp; I am involved in
open-source software and the community surrounding it.&nbsp; If you are still unsure
if I am the Kevin Locke you are looking for, see if one of my
<a href="contact.php">contact points</a> looks familiar, or feel free to ask me.
</p>

<h3>Other Kevin Lockes</h3>
<ul>
<li>The Native American flutist and hoop dancer named Kevin Locke's website is 
at <a href="http://www.kevinlocke.com">kevinlocke.com</a> (He is also described in the Wikipedia entry for
<a href="http://en.wikipedia.org/wiki/Kevin_Locke">Kevin Locke</a>).</li>
<li>The photographer Kevin R. Locke, who lives in Washington DC, has a
website at <a href="http://kevinlocke.net">kevinlocke.net</a>.</li>
<li>A Texan named Kevin Locke of the Locke Family Association, is listed at
<a href="http://www.lockefamilyassociation.org/kkor.htm">lockefamilyassociation.org</a>.</li>
</ul>

</div>

<?php include $_SERVER['DOCUMENT_ROOT'].'/include/footer.html'; ?>

</div>
</body>
</html>
<!-- vim: set ts=8 sts=2 sw=2 noet: -->
