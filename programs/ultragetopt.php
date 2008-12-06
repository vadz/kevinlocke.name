<?php
  include $_SERVER['DOCUMENT_ROOT'].'/include/mimetype.php';
  write_html_open();

  include $_SERVER['DOCUMENT_ROOT'].'/include/head.php';
  write_head_open();
  write_head_metadata("UltraGetopt",
		      "Webpage for the UltraGetopt library");
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

<h2>UltraGetopt</h2>

<h3>Description</h3>
<p>UltraGetopt is a versatile and customizable implementation of getopt() with
support for many common extensions, MS-DOS formatted option strings, and much
more.&nbsp; It can function as a drop-in replacement for getopt() on systems
with or without existing vendor-provided implementations and also as a separate
co-existing function.</p>

<!-- Screenshot, if appropriate -->
<h3>Features</h3>
<ol>
<li>Supports MS-DOS formatted option strings (e.g. <code>/option:arg</code>)
</li>
<li>Supports permuting command-line arguments to shift all non-option arguments
as appropriate</li>
<li>Supports first-longest-matching for options</li>
<li>Supports the BSD <code>optreset</code> functionality</li>
<li>Supports many runtime-configurable behaviors (described below)</li>
</ol>

<h3>Development Status</h3>
<p>Actively being developed.&nbsp; UltraGetopt supports nearly all of the
functionality that I am looking for in a getopt implementation, so development
is mostly minor bug fixes, tweaks, and minor additions.&nbsp; If you have
suggestions for a feature (or the desire to implement it), don't hesitate to
let me know about it.</p>

<h3>License</h3>
<p><a href="http://www.opensource.org/licenses/mit-license.php">MIT</a></p>

<h3>Supported Systems</h3>
<p>Known to work on Linux, {Net,Free}BSD, and Windows.&nbsp;  Should work on
any system (bug reports welcome).</p>

<h3>Dependencies</h3>
<ul>
<li>Depends on the <code>strcasecmp</code> and <code>strncasecmp</code>
functions.&nbsp; For systems which provide <code>_strnicmp</code> define
<code>HAVE__STRICMP</code> and <code>HAVE__STRNICMP</code> and these functions
will be used as replacements.&nbsp; Otherwise, replacements will need to be
included into the build system for projects UltraGetopt being targeted at
systems lacking them.</li>
</ul>

<h3>Installing</h3>
<ol class="sectioned" style="counter-reset: sectioneditem">
<li><a href="ultragetopt-0.6.0.tar.gz">Download it</a></li>
</ol>
<h4>To build into an existing project</h4>
<ol class="sectioned">
<li>Include <span class="filename">ultragetopt.c</span> in the build system for
your project</li>
<li>Include <span class="filename">ultragetopt.h</span> after any
vendor-provided getopt headers and define <code>ULTRAGETOPT_REPLACE_GETOPT</code>
if you would like the ultragetopt*() functions to replace vendor-provided
getopt*() functions.</li>
</ol>
<h4>To create a library for UltraGetopt with *nix build tools</h4>
<ol class="sectioned" style="counter-reset: sectioneditem 1">
<li>Run <code>./configure</code></li>
<li>Run <code>make</code></li>
<li>Run <code>make install</code> with appropriate privelages</li>
</ol>
<h4>To create a library for UltraGetopt with Visual Studio</h4>
<ol class="sectioned" style="counter-reset: sectioneditem 1">
<li>Open <span class="filename">ultragetopt.sln</span> in Visual Studio</li>
<li>Set the type to Release Static or Release Shared as appropriate</li>
<li>Run Build Solution</li>
</ol>

<h3>Configuration</h3>
<p>Configuration of UltraGetopt is accomplished by both compiletime defines
when building <span class="filename">ultragetopt.c</span> and by runtime
options passed to the <code>ultragetopt_tunable()</code> function.&nbsp; These
options are documented below.</p>

<h4>Meta-options</h4>
<p>These options will define the options below to appropriate values to mimic
the functionality of other existing getopt suites.</p>
<dl class="defineslist">
<dt>ULTRAGETOPT_LIKE_BSD</dt>
<dd>Behave like BSD getopt()</dd>
<dt>ULTRAGETOPT_LIKE_DARWIN</dt>
<dd>Behave like Darwin (Mac OS) getopt()</dd>
<dt>ULTRAGETOPT_LIKE_GNU</dt>
<dd>Behave like GNU getopt()</dd>
<dt>ULTRAGETOPT_LIKE_POSIX</dt>
<dd>Behave like POSIX definition of getopt()</dd>
</dl>

<h4>Error Message Options</h4>
<p>These options change the formatting of the error messages produced by
ultragetopt.</p>
<dl class="defineslist">
<dt>ULTRAGETOPT_BSD_ERRORS</dt>
<dd>Print error messages matching BSD getopt</dd>
<dt>ULTRAGETOPT_DARWIN_ERRORS</dt>
<dd>Print error messages matching Darwin getopt</dd>
<dt>ULTRAGETOPT_GNU_ERRORS</dt>
<dd>Print error messages matching GNU getopt</dd>
</dl>

<h4>Compiletime-only Behavior Options</h4>
<p>These options change the default behavior of getopt() and do not have a
corresponding runtime flag (although they may be affected by other
arguments).</p>
<dl class="defineslist">
<dt>ULTRAGETOPT_ASSIGNSPACE</dt>
<dd>Parse "-o value" as "value" rather than " value"<br />Note:&nbsp; Only
applicable when argv[x] == "-o value", not for argv[x] == "-o" [x+1] ==
"value"</dd>
<dt>ULTRAGETOPT_NO_OPTIONALARG</dt>
<dd>Do not support GNU "::" optional argument.<br />Note:&nbsp; Always
supported in *_long*() functions.</dd>
<dt>ULTRAGETOPT_NO_OPTIONASSIGN</dt>
<dd>Do not support --option=value syntax</dd>
</dl>

<h4>Runtime-selectable Options</h4>
<p>These options can all be selected by passing their value as a flag to the
ultragetopt_tunable() function, where ULTRAGETOPT_ is replaced by UGO_ for
compactness of the source.&nbsp; Defining these values sets the default state of
the flag when invoked from ultragetopt{_long,_dos}().</p>
<dl class="defineslist">
<dt>ULTRAGETOPT_DEFAULTOPTOPT</dt>
<dd>Set optopt to this value by default on each call to getopt()</dd>
<dt>ULTRAGETOPT_HYPHENARG</dt>
<dd>Accept -option -arg as -option with argument "-arg" rather than -option
missing argument</dd>
<dt>ULTRAGETOPT_LONGOPTADJACENT</dt>
<dd>Accept adjacent arguments to long options (e.g. --optionarg) based on first
longest-match</dd>
<dt>ULTRAGETOPT_OPTIONPERMUTE</dt>
<dd>Permute options, do not stop at first non-option.&nbsp; A leading '+' in
shortopts or when the $POSIXLY_CORRECT environmental variable are set,
permuting will be stopped @ runtime</dd>
<dt>ULTRAGETOPT_SHORTOPTASSIGN</dt>
<dd>Support -o=file syntax for short options</dd>
<dt>ULTRAGETOPT_SEPARATEDOPTIONAL</dt>
<dd>Accept separated optional arguments.&nbsp; Parse -o file as -o with
argument file rather than -o without an argument and non-option argument
"file"</dd>
<dt>ULTRAGETOPT_DOS_DASH</dt>
<dd>Support - and -- options in ultragetopt*_dos() functions</dd>
<dt>ULTRAGETOPT_NO_EATDASHDASH</dt>
<dd>Do not increment optind when argv[optind] is -- as required by
SUS/POSIX (results in "--" being one of the non-option arguments)</dd>
</dl>

<h3>Known Bugs</h3>
<ul>
<li>The option permuting done by UltraGetopt differs from other getopt
implementations that permute arguments on the next call after they were
returned.&nbsp; UltraGetopt currently moves an option forward and then returns
it.&nbsp; Therefore, its values of <code>optind</code> will necessarily differ
from other implementations, although <code>argv[optind]</code> will be the
same.</li>
</ul>

<h3>Planned Features</h3>
<ul>
<li>More testing for incompatibilities with existing getopt implementations</li>
<li>More features, as they are thought up</li>
</ul>

</div>

<?php include $_SERVER['DOCUMENT_ROOT'].'/include/footer.html'; ?>

</div>
</body>
</html>
