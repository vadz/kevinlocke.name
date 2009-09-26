<?php

require_once 'HTTP_Accept.php';

# mimetype.php - Script for choosing the appropriate MIME Media type and
#		 presenting the page in that type.

define('XHTML_1_1', 0);
define('XHTML_1_0_STRICT', 1);
define('XHTML_1_0_TRANSITIONAL', 2);
define('XHTML_1_0_FRAMESET', 3);
define('HTML_4_0_1_STRICT', 4);
define('HTML_4_0_1_TRANSITIONAL', 5);
define('HTML_4_0_1_FRAMESET', 6);

$mimetypes = array(XHTML_1_1 => 'application/xhtml+xml',
		   XHTML_1_0_STRICT => 'application/xhtml+xml',
		   XHTML_1_0_TRANSITIONAL => 'application/xhtml+xml',
		   XHTML_1_0_FRAMESET => 'application/xhtml+xml',
		   HTML_4_0_1_STRICT => 'text/html',
		   HTML_4_0_1_TRANSITIONAL => 'text/html',
		   HTML_4_0_1_FRAMESET => 'text/html');

$htmltype = XHTML_1_0_STRICT;
$mime = $mimetypes[$htmltype];
$charset = "utf-8";

function translate_xhtml_to_html($buffer)
{
    // This could cause problems if '>' is not entity encoded or if
    // XML data is supposed to be embedded...
    return preg_replace("/\s*\/>/i", ">", $buffer);

    // New host doesn't have XSLT processing...
    // Real solution is to use XSLT, but is a lot more expensive
    // Note:  Have to turn off magic quotes during XML loading
    $magicquotes = get_magic_quotes_runtime();
    set_magic_quotes_runtime(0);

    $xsl = new DOMDocument();
    $xsl->load($_SERVER['DOCUMENT_ROOT'].'/include/xhtmltohtml.xsl');
    $xsltproc = new XSLTProcessor();
    $xsltproc->importStylesheet($xsl);

    $xhtmldoc = DOMDocument::loadXML($buffer);
    return $xsltproc->transformToXML($xhtmldoc);

    /*
    // PHP4 version
    $xsltransform = domxml_xslt_stylesheet_file($_SERVER['DOCUMENT_ROOT'].
						'/include/xhtmltohtml.xsl');
    $xhtmldoc = domxml_open_mem($buffer);

    set_magic_quotes_runtime($magicquotes);

    if (!$xsltransform || !$xhtmldoc) {
       	return preg_replace("/\s*\/>/i", ">", $buffer);
    } else {
       	$htmldoc = $xsltransform->process($xhtmldoc);
       	if (!$htmldoc)
	    return preg_replace("/\s*\/>/i", ">", $buffer);
       	else
	    return $xsltransform->result_dump_mem($htmldoc);
    }
    */
}

/* Return the best MIME-type for serving this page as determined by the
 * client's HTTP Accept: header
 *
 * Currently chooses between application/xhtml+xml and text/html
 */
function best_mimetype()
{
    $accept = new HTTP_Accept($_SERVER['HTTP_ACCEPT']);
    $xhtmlq = $accept->getQuality("application/xhtml+xml");
    $htmlq  = $accept->getQuality("text/html");

    // Need to check for exact match to accomodate IE which "accepts" everything
    if ($xhtmlq >= $htmlq && $accept->isMatchExact("application/xhtml+xml")) {
        return "application/xhtml+xml";
    } else {
	return "text/html";
    }
}

/* Choose between presenting XHTML or HTML (based on the http Accept header)
 * Write an XML declaration, DOCTYPE, and opening html element as appropriate
 * If producing HTML, apply output conversion from XHTML to HTML.
 * Also set $mime variable to indicate the type being presented.
 *
 * htmlflavor can be one of "strict", "frameset", or "transitional"
 *
 * Adapted from http://www.workingwith.me.uk/articles/scripting/mimetypes/
 */
function write_html_open($htmlflavor = "strict", $encoding = "utf-8",
			 $language = "en-US")
{
    global $charset, $mime;

    $charset = $encoding;
    if (stristr($_SERVER["HTTP_USER_AGENT"], "W3C_Validator")) {
	$mime = "application/xhtml+xml";
	//$mime = 'text/html';
    } else {
       	$mime = best_mimetype();
    }

    // Set appropriate headers for our choice of MIME type
    header(gmstrftime("Last-Modified: %a, %d %b %Y %T GMT",
		      filemtime($_SERVER['SCRIPT_FILENAME'])));
    header("Content-Type: $mime;charset=$charset");
    header("Vary: Accept");

    // Do compression for clients that support it
    //ob_start('ob_gzhandler');

    if ($mime == "text/html")
	ob_start("translate_xhtml_to_html");
    else
       	print "<?xml version=\"1.0\" encoding=\"$charset\" ?>\n";

    /*
    print '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">'."\n";
    */
    if ($mime == "text/html")
	print '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" 
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'."\n";
    else
	print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
    "http://www.w3.org/TR/html4/strict.dtd">'."\n";
    /*
    print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML+RDFa 1.0//EN\"
    \"http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd\" [
    <!ENTITY nbsp  \"&#160;\"> <!-- non-breaking space, U+00A0 -->
    <!ENTITY copy  \"&#169;\"> <!-- copyright sign, U+00A9 -->
    <!ENTITY reg   \"&#174;\"> <!-- registered sign, U+00AE -->
]>\n";
    */
    print "<html xmlns=\"http://www.w3.org/1999/xhtml\" ".
	  "xml:lang=\"$language\">\n";
}

/* Complement function to write_open_html() */
function write_html_close($htmlflavor = "strict")
{
    print "</html>\n";
}

?>
