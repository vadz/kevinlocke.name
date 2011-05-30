#!/bin/sh

lftp -e "mirror -R --no-perms --delete -x template\.php -x sync\.sh -x \.git -v && exit" sftp://kevinlocke.name/srv/www/kevinlocke.name/testing
