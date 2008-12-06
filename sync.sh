#!/bin/sh

lftp -e "mirror -R --no-perms --delete -x testing -x template\.php -x sync\.sh -x \.git -v && exit" -u kevinlo,25462e2 \
	ftp://kevinlocke.name/public_html
