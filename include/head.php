<?php

function write_head_open()
{
    // Note to self:  add http://www.w3.org/2005/11/profile for favicon
    // see http://www.w3.org/2005/10/howto-favicon#profile
    print "<head profile=\"http://dublincore.org/documents/dcq-html/\">\n";
}

function write_head_metadata($title = "", $description = "")
{
    global $mime;

    print <<<METADATA
  <!-- See http://dublincore.org for information about this metadata.
       Note:  Old-style tags kept for compatibility -->
  <link rel="schema.DC" href="http://purl.org/dc/elements/1.1/" />
  <link rel="schema.DCTERMS" href="http://purl.org/dc/terms/" />
  <meta name="DC.language" content="en-US" />
  <meta name="DC.type" scheme="DCTERMS.DCMIType" content="Text" />
  <meta name="DC.creator" content="Kevin Locke &lt;kwl7@cornell.edu&gt;" />
  <meta name="author" content="Kevin Locke &lt;kwl7@cornell.edu&gt;" />
  <meta name="generator" content="Vi IMproved 6.3" />
  <meta name="DC.rights" content="http://kevinlocke.name/license.php" />
  <meta name="license" content="Adaptation of the FreeBSD Documentation License - see license.php" />
  <meta name="copyright" content="&copy; 2005 Kevin Locke &lt;kwl7@cornell.edu&gt;" />

METADATA;

    if (isset($mime))
	print "  <link rel=\"copyright\" href=\"/license.php\" " . 
	      "type=\"$mime\" />\n";
    else
	print "  <link rel=\"copyright\" href=\"/license.php\" />";

    if (isset($mime))
	print "  <meta name=\"DC.format\" scheme=\"DCTERMS.IMT\" ".
	      "content=\"$mime\" />\n";

    $mtime = filemtime($_SERVER['SCRIPT_FILENAME']);
    print "  <meta name=\"DC.date\" scheme=\"DCTERMS.W3CDTF\" ".
	  "content=\"" . date("Y-m-d", $mtime) . "\" />\n";
    print "  <meta name=\"date\" content=\"" .  date("Y-m-d\TH:i:sO", $mtime) .
          "\" />\n";

    if ($description != "") {
	print "  <meta name=\"DC.description\" content=\"$description\" />\n";
        print "  <meta name=\"description\" content=\"$description\" />\n";
    }

    if ($title != "") {
	print "  <meta name=\"DC.title\" content=\"$title\" />\n";
	print "  <title>$title</title>\n";
    }
}

function write_head_stylesheets()
{
    print <<<STYLESHEETS
  <link rel="stylesheet" media="screen" type="text/css" href="/styles/screen1.css" />
  <link rel="stylesheet" media="print" type="text/css" href="/styles/print1.css" />

STYLESHEETS;
}

function write_head_close()
{
    print "</head>\n";
}
