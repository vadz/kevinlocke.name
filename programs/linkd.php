<?php
  include $_SERVER['DOCUMENT_ROOT'].'/include/mimetype.php';
  write_html_open();

  include $_SERVER['DOCUMENT_ROOT'].'/include/head.php';
  write_head_open();
  write_head_metadata("Linkd - Motorola SB4100 Link Daemon",
		      "Webpage for the linkd program");
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

<h2>Linkd: Motorola SB4100 Link Daemon</h2>

<h3>Description</h3>
<p>Linkd is a small daemon which will cause the SB4100 to restart when it
stops working.  Linkd monitors the network rate on a specified interface and
when it drops below a given threshold linkd will attempt to ping a given
address.  If the ping fails, an HTTP POST packet is sent to the SB4100 which
is equivalent to pressing the "Restart Cable Modem" button in its
web-interface.  This will cause the modem to restart and (for me) is sufficient
to get traffic flowing over the link again.</p>
<p>For the less pedantic and more practical, contacting your cable company and
replacing your cable modem are probably better solutions. &nbsp;My efforts in
this respect were somewhat less useful and rather than spending the money out
of my own pocket to buy a new cable modem, I wrote this program.</p>

<h3>Supported Systems</h3>
<ul>
<li>Linux 2.4/2.6</li>
</ul>

<h3>Development Status</h3>
<p>Works for me. &nbsp;I am planning to add a few more command-switches to ease
use of the program and add a startup script to the package. &nbsp;If this 
program finds an audience, more features could be added...</p>

<h3>License</h3>
<p><a href="http://www.opensource.org/licenses/bsd-license.php">BSD</a></p>

<h3>Installing</h3>
<ol>
<li><a href="linkd.c">Download it</a></li>
<li>Change any definitions appropriately in linkd.c using a text editor</li>
<li>Compile it (e.x. <code>cc -o linkd linkd.c</code>)</li>
<li>Run it</li>
<li>Optionally add it to startup scripts with the -d command line option to
continually monitor your network link</li>
</ol>

<h3>Configuration</h3>
<p>Almost all of the linkd configuration is done by changing defined macros
in the C source file to appropriate values. &nbsp;The purpose of each definition
is commented in the source file. &nbsp;Definitions of particular interest are
MODEM_IP, PING_ARGS, and possibly NET_RATE_THRESH. &nbsp;At a very minimum, 
please change the IP in PING_ARGS so that all users of this program won't be
pinging the same address to check connectivity. &nbsp;Note that the network
rate threshold is non-zero because (at least for my setup) there is a
significant number of ARP packets which are delivered when the modem is
down.</p>

</div>

<?php include $_SERVER['DOCUMENT_ROOT'].'/include/footer.html'; ?>

</div>
</body>
</html>
