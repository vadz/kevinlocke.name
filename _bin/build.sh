#!/bin/sh
# build.sh - Build the website

set -ex

jekyll

# Rename blog posts from .html to .xhtml
for FILE in $(find _site/bits -name '*.html') ; do
    mv "$FILE" "${FILE%.html}.xhtml"
done

# Generate .html versions of .xhtml pages
for FILE in $(find _site -name '*.xhtml') ; do
    xsltproc --nodtdattr -o "${FILE%.xhtml}.html" _bin/xhtmltohtml.xsl "$FILE"
done

# Remove .xhtml extension from URLs in the sitemap
sed -i 's/\.xhtml<\/loc>/<\/loc>/' _site/sitemap.xml
