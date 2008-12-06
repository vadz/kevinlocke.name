<?php
  include $_SERVER['DOCUMENT_ROOT'].'/include/mimetype.php';
  write_html_open();

  include $_SERVER['DOCUMENT_ROOT'].'/include/head.php';
  write_head_open();
  write_head_metadata("Programs",
		      "List of programs written by Kevin Locke");
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
<h2>Programs</h2>
<p>This page is a collection of random programs and programming-related stuff
that I have helped to create which does not have a proper homepage elsewhere
(or which have uncommitted updates).&nbsp; Enjoy.</p>

<h3>Internet/Network Programs</h3>
<ul>
    <li><a href="linkd.php">Linkd</a> - A small daemon to monitor
    my SB4100 cable modem and restart it when it stops functioning.</li>
    <li><a href="http_accept.php">HTTP_Accept</a> - A PHP script to deal
    with processing and creation of the HTTP 1.1 Accept header.</li>
</ul>

<h3>Desktop Programs</h3>
<ul>
  <li><a href="piv.php">piv</a> - A no-frills image viewer written in Perl
  using GTK2-Perl.</li>
  <li><a href="rotatebg.php">RotateBG</a> - A shell script to change the
  desktop wallpaper (background) to an image selected sequentially or randomly
  from a list or directory.</li>
  <li><a href="xlaunch.php">xlaunch</a> - A program to launch a process in the
  running X server.</li>
</ul>

<h3>Libraries</h3>
<ul>
  <li><a href="ultragetopt.php">UltraGetopt</a> - A versatile and customizable
  implementation of getopt() with support for many common extensions, MS-DOS
  style option strings, and much more.</li>
  <li><a href="ultragetopt-java.php">UltraGetopt for Java</a> - The Java
  imagening of UltraGetopt.&nbsp; All the features of UltraGetopt with the
  smooth taste of Java.</li>
</ul>

<h3>Games</h3>
<ul>
  <li><a href="http://geuka.org">Geuka</a> - A 2D, top-down, real-time,
  puzzler.&nbsp; As a chameleon, the objective is to follow an elusive
  butterfly using the changes in day and night as well as the color changing
  ability of the chameleon to evade hungry predators and other dangerous
  creatures.&nbsp; Runs on Windows, Linux, and other Unix-like systems.</li>

  <li><a href="http://manojalpa.net/games/musicmonsters/index.html">Music
  Monsters</a> - A musical 2.5D side-scroller.&nbsp; As a researcher, you
  tune the abilities of a musically-sensitive creature called a Zyk, then
  send your creature out into the world in search of knowledge.&nbsp;
  Runs on Windows XP.<br />
  The GDIAC Showcase version can be downloaded from the
  <a href="http://gdiac.cis.cornell.edu/showgame.php?id=MusicMonsters">GDIAC
  website</a>.&nbsp; The new installer can be (temporarily) downloaded from
  <a href="ftp://kevinoid.homelinux.org/MusicMonsters-0.2.0.msi">here</a>.</li>

  <li><a href="http://www.red-comedy.net/symbiosis/">Symbiosis</a> - A fully 3D,
  networked, 2-player, cooperative adventure game.&nbsp; Play as either Ashva,
  a powerful and fast equine, or as Ryder, a quick and cunning human with a
  deadly bow.&nbsp; Explore the world of Limbo, gain virtues, and find the
  hidden treasures scattered across the world.&nbsp; Runs on Windows XP.</li>
</ul>

<h3>Game Mods/AddOns</h3>
<ul>
	<li><a href="dueltracker.php">DuelTracker</a> - An addon for Blizzard's
	<a href="http://www.worldofwarcraft.com/">World of Warcraft</a> which
	collects information about duels that the user observes and calculates
	statistics from that data.&nbsp; Data can also be exported in CSV 
	format using a Lua interpreter external to World of Warcraft.</li>
</ul>

<h3>Scripts/Plugins</h3>
<ul>
    <li><a href="vim/index.php">Vim Scripts</a> - Scripts and file type support
    addons for the <a href="http://vim.org">Vim</a> text editor.</li>
</ul>

</div>

<?php include $_SERVER['DOCUMENT_ROOT'].'/include/footer.html'; ?>

</div>
</body>
</html>
