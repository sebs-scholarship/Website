# syntax=docker/dockerfile:1
#
# Tooling-only Jekyll build image for the SEBS Scholarship website.
#
# The site source is bind-mounted at runtime (see compose.yaml) and every gem
# is installed from Gemfile.lock by `bundle install` into the mounted bundle
# cache volume. Jekyll is deliberately NOT installed here so that Dependabot's
# daily Bundler updates take effect without rebuilding this image.

FROM ruby:3.4-alpine

# Build toolchain, native gem build deps, and runtime helpers. build-base,
# cmake, linux-headers, openssl-dev, zlib-dev, yaml-dev and libffi-dev compile
# the source gems (openssl, bigdecimal, json, ...); shadow + su-exec drive the
# UID/GID remapping in entrypoint.sh.
RUN apk add --no-cache \
      bash \
      build-base \
      cmake \
      linux-headers \
      git \
      nodejs \
      npm \
      yarn \
      openssl-dev \
      zlib-dev \
      yaml-dev \
      libffi-dev \
      libxml2-dev \
      libxslt-dev \
      tzdata \
      shadow \
      su-exec \
      libstdc++

# Preserve the upstream jekyll-docker environment contract. Everything Bundler
# installs lands in BUNDLE_HOME (= GEM_HOME), which is the single cache volume
# mounted by compose; there is no separate /usr/gem gem store.
ENV BUNDLE_HOME=/usr/local/bundle \
    BUNDLE_APP_CONFIG=/usr/local/bundle \
    BUNDLE_BIN=/usr/local/bundle/bin \
    GEM_HOME=/usr/local/bundle \
    JEKYLL_DATA_DIR=/srv/jekyll \
    JEKYLL_VAR_DIR=/var/jekyll \
    JEKYLL_ENV=development \
    RUBYOPT=-EUTF-8
ENV PATH=$BUNDLE_BIN:$PATH

# Create the unprivileged build user. Its UID/GID can be remapped at runtime
# via JEKYLL_UID/JEKYLL_GID so generated files are owned by the host user.
RUN addgroup -g 1000 jekyll \
 && adduser -u 1000 -G jekyll -s /bin/bash -D jekyll \
 && mkdir -p "$JEKYLL_DATA_DIR" "$JEKYLL_VAR_DIR" "$BUNDLE_HOME" \
 && chown -R jekyll:jekyll "$JEKYLL_DATA_DIR" "$JEKYLL_VAR_DIR" "$BUNDLE_HOME"

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /srv/jekyll
EXPOSE 4000 35729

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["bash"]
