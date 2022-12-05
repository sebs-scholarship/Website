#!/bin/bash

docker run -v "$(pwd)":/srv/jekyll -p 4000:4000 --rm jekyll/builder:4.2.2 \
/bin/bash -c "chmod 777 /srv/jekyll && jekyll serve --future --drafts"