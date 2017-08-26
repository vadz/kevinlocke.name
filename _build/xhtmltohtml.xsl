<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xhtml="http://www.w3.org/1999/xhtml"
    exclude-result-prefixes="xhtml" version="1.0">

    <!-- Configure type/flavor/version of HTML output.
         Choices: 5, 4strict, 4transitional, 4frameset -->
    <xsl:param name="htmlver" select="'5'"/>

    <!-- Note:  encoding="us-ascii" forces entities in output for compat. -->
    <xsl:output method="html" indent="yes" encoding="us-ascii"/>

    <!-- Note:  Can't be wrapped in if/choose since outside template.
                Instead written manually below (required for HTML5 anyway).
    <xsl:output method="html" indent="yes" encoding="us-ascii"
        doctype-public="-//W3C//DTD HTML 4.01//EN"
        doctype-system="http://www.w3.org/TR/html4/strict.dtd"/>
    -->

    <!-- Should the contents of <script> be commented out? -->
    <xsl:param name="commentscripts" select="boolean(0)"/>
    
    <!--
        Although we don't usually care about spaces outside of the script tag,
        this script preserves spaces so that the converted file looks like the
        original.
    <xsl:strip-space elements="html *"/>
    <xsl:preserve-space elements="script"/>
    -->
    <xsl:preserve-space elements="html *"/>

    <!-- Add DOCTYPE.  See https://stackoverflow.com/q/3387127 -->
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="$htmlver='4strict'">
                <xsl:text disable-output-escaping='yes'>&lt;!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"
    "http://www.w3.org/TR/html4/strict.dtd"&gt;
</xsl:text>
            </xsl:when>
            <xsl:when test="$htmlver='4transitional'">
                <xsl:text disable-output-escaping='yes'>&lt;!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
    "http://www.w3.org/TR/html4/loose.dtd"&gt;
</xsl:text>
            </xsl:when>
            <xsl:when test="$htmlver='4frameset'">
                <xsl:text disable-output-escaping='yes'>&lt;!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN"
    "http://www.w3.org/TR/html4/frameset.dtd"&gt;
</xsl:text>
            </xsl:when>
            <xsl:otherwise>
                <xsl:text disable-output-escaping='yes'>&lt;!DOCTYPE html&gt;
</xsl:text>
            </xsl:otherwise>
        </xsl:choose>
        <xsl:apply-templates/>
    </xsl:template>

    <!-- Convert xml:lang attribute to lang if no other lang attribute exists
            and element can have a lang attribute -->
    <xsl:template match="@xml:lang">
        <xsl:if test="(count(../@lang) = 0)
                           and (local-name(..) != 'base')
                           and (local-name(..) != 'br')
                           and (local-name(..) != 'frame')
                           and (local-name(..) != 'frameset')
                           and (local-name(..) != 'hr')
                           and (local-name(..) != 'iframe')
                           and (local-name(..) != 'param')
                           and (local-name(..) != 'script')">
            <xsl:attribute name="lang">
                <xsl:value-of select="."/>
            </xsl:attribute>
        </xsl:if>
    </xsl:template>
    
    <!-- Convert xml:base on <html> to <base> -->
    <xsl:template match="xhtml:head">
        <head>
            <xsl:apply-templates select="@*"/>
            <xsl:if test="/xhtml:html/@xml:base">
                <base>
                    <xsl:attribute name="href"><xsl:value-of select="/xhtml:html/@xml:base"/></xsl:attribute>
                </base>
            </xsl:if>
            <xsl:apply-templates/>
        </head>
    </xsl:template>

    <!-- Ignore type/charset meta element, since xsltproc always outputs one
        with the output type and charset automatically.
        See https://mail.gnome.org/archives/xslt/2007-January/msg00004.html -->
    <xsl:template match="xhtml:meta[@http-equiv='Content-Type']|xhtml:meta[@charset]"/>

    <!-- Provide name attributes for elements with id attributes 
            and no name attributes provided -->
    <xsl:template match="@id">
        <xsl:if test="(count(../@name) = 0)
                           and ((local-name(..) = 'a')
                                   or (local-name(..) = 'applet')
                                   or (local-name(..) = 'form')
                                   or (local-name(..) = 'frame')
                                   or (local-name(..) = 'img')
                                   or (local-name(..) = 'map'))">
            <xsl:attribute name="name"><xsl:value-of select="."/></xsl:attribute>
        </xsl:if>
        <xsl:copy/>
    </xsl:template>
    
    <!-- Discard xmlns attributes -->
    <xsl:template match="@version"/>
    
    <!-- Wrap inline script in HTML comment when requested -->
    <xsl:template match="xhtml:script">
        <xsl:element name="script">
            <xsl:apply-templates select="@*"/>
            <xsl:choose>
                <xsl:when test="$commentscripts">
                    <xsl:comment>
                        <xsl:apply-templates/>//</xsl:comment>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:apply-templates/>
                </xsl:otherwise>
            </xsl:choose>
        </xsl:element>
    </xsl:template>

    <!-- Discard the xml:* attributes, with no HTML equivalents -->
    <xsl:template match="@xml:*"/>
    
    <!-- Copy all other XHTML tags verbatim -->
    <xsl:template match="xhtml:*">
        <xsl:element name="{local-name(.)}">
            <xsl:apply-templates select="@*|node()"/>
        </xsl:element>
    </xsl:template>
    
    <xsl:template match="@*">
        <xsl:copy/>
    </xsl:template>

</xsl:stylesheet>
<!-- vim: set sts=4 sw=4 et : -->
