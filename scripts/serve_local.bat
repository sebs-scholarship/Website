docker run -p 4000:4000 -it --rm ^
-v "./":/srv/jekyll -v "./_vendor/bundle":/usr/local/bundle -v "./_vendor/gem":/usr/gem ^
-e JEKYLL_UID=1001 -e JEKYLL_GID=1001 ^
jekyll/builder:4.2.2 /bin/bash -c ^
"chmod 777 /srv/jekyll ^
&& chmod 777 /srv/jekyll/assets/images ^
&& jekyll serve --future --drafts"