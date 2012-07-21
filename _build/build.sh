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
    xsltproc --nodtdattr -o "${FILE%.xhtml}.html" _build/xhtmltohtml.xsl "$FILE"
done

# Check other XML for well-formedness
xmllint --nonet --noout $(find _site -iname '*.atom' -o -iname '*.xml')

# Remove .xhtml extension from URLs in the sitemap
sed -i 's/\.xhtml<\/loc>/<\/loc>/' _site/sitemap.xml
