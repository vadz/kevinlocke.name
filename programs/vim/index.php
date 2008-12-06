<?php
  include $_SERVER['DOCUMENT_ROOT'].'/include/mimetype.php';
  write_html_open();

  include $_SERVER['DOCUMENT_ROOT'].'/include/head.php';
  write_head_open();
  write_head_metadata("Vim Scripts",
		      "Webpage for my scripts for the Vim editor");
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

<h2>Vim Scripts</h2>

<h3>Description</h3>
<p>This is a collection of some of my scripts for the
<a href="http://vim.org">Vim</a> text editor.&nbsp; Hopefully, scripts will
be added and removed to this page as they are created and integrated
upstream.&nbsp; Files which are already integrated will be marked appropriately
(if there is an updated version or bugfix).</p>

<h3>Microsoft Message File Support</h3>
<p>Support syntax highlighting and some format-specific settings for the
<a href="http://msdn2.microsoft.com/en-us/library/aa385646.aspx">Microsoft
Message File format</a>.</p>

<h4>Integration Status</h4>
<p>Submitted to upstream for inclusion.</p>

<h4>Installing</h4>
<ol>
<li>Place the <a href="syntax/msmessages.vim">msmessages.vim syntax file</a>
into the syntax folder of your Vim installation (or Vim user directory) and the
<a href="ftplugin/msmessages.vim">msmessages.vim ftplugin file</a> into the
<code>ftplugin</code> directory.</li>
<li>Either add <code>au BufRead,BufNewFile *.mc set filetype=msmessages</code>
into your <code>vimrc</code> file or into a newly created file in an
<code>ftdetect</code> folder adjacent to <code>ftplugin</code> and
<code>syntax</code> (or any other method described in the instructions in
<a href="http://vimdoc.sourceforge.net/htmldoc/filetype.html">filetype.txt</a>).</li>
</ol>

<h4>License</h4>
<p><a href="http://www.opensource.org/licenses/mit-license.php">MIT</a> or
under the same terms as the
<a href="http://vimdoc.sourceforge.net/htmldoc/uganda.html#license">Vim License</a>.</p>

</div>

<?php include $_SERVER['DOCUMENT_ROOT'].'/include/footer.html'; ?>

</div>
</body>
</html>
