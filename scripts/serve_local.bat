docker run -v ${PWD}:/srv/jekyll -v ${PWD}\_site:/srv/jekyll/_site -p 4000:4000 --rm \
jekyll/builder:4.1.0 /bin/bash -c "chmod 777 /srv/jekyll && jekyll serve --future --drafts"