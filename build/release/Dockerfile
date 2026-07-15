FROM node:24.13.1-bookworm-slim@sha256:85a395c77b811fa7f5b5e4aa69cd6eb4c3b80c7f1a8e34704dc0ce061e5b404e AS git-builder

ARG GIT_VERSION=2.43.0
ARG GIT_SHA256=5446603e73d911781d259e565750dcd277a42836c8e392cac91cf137aa9b76ec

RUN apt-get update \
 && apt-get install -y --no-install-recommends build-essential ca-certificates curl libcurl4-gnutls-dev libexpat1-dev libpcre2-dev libssl-dev xz-utils zlib1g-dev \
 && curl -fsSL "https://mirrors.edge.kernel.org/pub/software/scm/git/git-${GIT_VERSION}.tar.xz" -o /tmp/git.tar.xz \
 && echo "${GIT_SHA256}  /tmp/git.tar.xz" | sha256sum -c - \
 && tar -xf /tmp/git.tar.xz -C /tmp \
 && make -C "/tmp/git-${GIT_VERSION}" -j2 prefix=/usr/local NO_GETTEXT=YesPlease NO_TCLTK=YesPlease all \
 && make -C "/tmp/git-${GIT_VERSION}" prefix=/usr/local DESTDIR=/git-root NO_GETTEXT=YesPlease NO_TCLTK=YesPlease install

FROM node:24.13.1-bookworm-slim@sha256:85a395c77b811fa7f5b5e4aa69cd6eb4c3b80c7f1a8e34704dc0ce061e5b404e

RUN apt-get update \
 && apt-get install -y --no-install-recommends bash ca-certificates coreutils diffutils findutils grep libcurl3-gnutls libexpat1 libpcre2-8-0 libssl3 mawk sed tar unzip zip=3.0-13 zlib1g \
 && rm -rf /var/lib/apt/lists/*

COPY --from=git-builder /git-root/usr/local/ /usr/local/
COPY image-contract.sh /usr/local/bin/release-image-contract
RUN chmod 0755 /usr/local/bin/release-image-contract \
 && /usr/local/bin/release-image-contract

ENTRYPOINT ["/bin/bash"]
