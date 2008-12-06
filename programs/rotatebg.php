<?php
  include $_SERVER['DOCUMENT_ROOT'].'/include/mimetype.php';
  write_html_open();

  include $_SERVER['DOCUMENT_ROOT'].'/include/head.php';
  write_head_open();
  write_head_metadata("RotateBG",
		      "Webpage for the RotateBG script");
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

<h2>RotateBG: &nbsp;A script to rotate the desktop wallpaper (background)</h2>

<h3>Description</h3>
<p>RotateBG is a shell script which builds a list of files
from the files and directories (searched recursively) listed on the
command-line (or from stdin if none are listed).&nbsp; It then sorts this
list and changes the current background to the next in the list, or
changes to the first in the list if the current background is not in
the list, or to a random file if the -r switch is specified.</p>
<p>RotateBG is based on <a href="http://users.skynet.be/bk221183/">grotbckgd</a>
created by Damien Merenne.&nbsp; Unfortunately, when I last contacted Mr.
Merenne he did not have access to that website any longer and therefore could
not post patches to the program.&nbsp; Since then I have made several changes
to the program to the point that the similarity to the original program is 
somewhat vague and I would feel bad sending a patch this large, which means
that it is time to post it here.</p>

<h3>Features</h3>
<ol>
<li>Not Bash-dependent (should run in any POSIX-compatible shell)</li>
<li>Handles filenames with spaces (and all characters except '\n' and '#')</li>
<li>The command to set the background can be specified on the command-line
which allows it to work with any window manager.</li>
<li>In combination with a periodic scheduler (like cron) RotateBG can be used
to periodically change the desktop background, either randomly or in
progression.</li>
</ol>

<h3>Development Status</h3>
<p>Stable.&nbsp; I don't have any other features planned and I haven't come
across any bugs for a while.&nbsp; Bugs and proposed improvements would be
considered.</p>

<h3>License</h3>
<p>Expat Style - The original author requires that the copyright statement
remain intact, no additional conditions have been added.</p>

<h3>Dependencies</h3>
<ul>
<li>Bash-like shell (dash, posh, sh, etc.)</li>
<li>Unix core utilities (awk, sed, grep)</li>
</ul>

<h3>Installing</h3>
<ol>
<li><a href="rotatebg.sh">Download it</a>, possibly into your PATH (e.g. ~/bin)</li>
<li>Make it executable (e.g. <code>chmod +x rotatebg.sh</code>)</li>
<li>Run the script to change your background (use <code>./rotatebg.sh -h</code>
to see the usage information).</li>
<li>Optionally, schedule it to run periodically.&nbsp; This can be accomplished
by adding it to your crontab by typing <code>crontab -e</code> and adding a line
like the following to your crontab:<br />
<code>*/5 * * * * $HOME/bin/rotatebg.sh -r $HOME/wallpapers</code><br />
which will run rotatebg.sh every 5 minutes and set the wallpaper to a random
image in a subdirectory of $HOME/wallpapers</li>
</ol>

<h3>Example Usage</h3>
<ul>
<li><code>./rotatebg.sh ~/wallpaper</code> - Set the desktop wallpaper to the
next image from a subdirectory of ~/wallpaper.</li>
<li><code>./rotatebg.sh -r -t jpg,png,gif,bmp ~/wallpaper</code> - Set the 
desktop wallpaper to a random image with a .jpg .png .gif or .bmp extension
from a subdirectory of ~/wallpaper.</li>
<li><code>./rotatebg.sh -t xbm --setbgcmd "xsetroot -bitmap" -f ~/.bgimage
~/wallpaper</code> - Set the desktop wallpaper using xsetroot to an X Bitmap
(xbm) image in a subdirectory of ~/wallpaper and keep track of the current 
wallpaper in the file ~/.bgimage (to determine which image is next, if this
option were not specified -r would be assumed).</li>
</ul>

<h3>Configuration</h3>
<p>It is possible to change the default values by editing the script, however
this should be unnecessary.&nbsp; Determining the proper command-line arguments
should be all of the configuration that is required.</p>

<h3>Known Bugs</h3>
<p>The script should have improved default behavior, such as testing for more
alternatives to set the desktop wallpaper than gconftool if none is specified
on the command-line.&nbsp; Suggestions for appropriate commands would be 
appreciated.</p>

</div>

<?php include $_SERVER['DOCUMENT_ROOT'].'/include/footer.html'; ?>

</div>
</body>
</html>
