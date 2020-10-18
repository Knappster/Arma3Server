FROM debian:buster-slim

LABEL maintainer="Carl Knapp - github.com/knappster"

ARG PUID=1000

ENV USER=steam
ENV HOME_DIR="/home/${USER}"
ENV ARMA3_DIR="${HOME_DIR}/arma3"
ENV STEAMCMD_DIR="${HOME_DIR}/steamcmd"
ENV ARMA_CONFIG=main.cfg
ENV ARMA_PROFILE=main
ENV ARMA_WORLD=empty
ENV HEADLESS_CLIENTS=0

RUN set -x \
    && dpkg --add-architecture i386 \
    && apt-get update \
    && apt-get install -y --no-install-recommends --no-install-suggests \
        php-cli \
        lib32stdc++6 \
        lib32gcc1 \
        wget \
        ca-certificates \
        libsdl2-2.0-0:i386 \
        libtbb2 \
        libtbb2:i386 \
        zlib1g:i386 \
    && useradd -u "${PUID}" -m "${USER}" \
    && su "${USER}" -c \
        "mkdir -p \"${ARMA3_DIR}\" \
        && mkdir -p \"${STEAMCMD_DIR}\" \
        && wget -qO- 'https://steamcdn-a.akamaihd.net/client/installer/steamcmd_linux.tar.gz' | tar xvzf - -C \"${STEAMCMD_DIR}\"" \
    && apt-get remove --purge -y \
    && apt-get clean autoclean \
    && apt-get autoremove -y \
    && rm -rf /var/lib/apt/lists/*

USER ${USER}
WORKDIR ${HOME_DIR}

COPY --chown=${USER}:${USER} ./launch.php ./launch.php

EXPOSE 2301/udp
EXPOSE 2302/udp
EXPOSE 2303/udp
EXPOSE 2304/udp
EXPOSE 2305/udp
EXPOSE 2306/udp
EXPOSE 2344/tcp
EXPOSE 2344/udp
EXPOSE 2345/tcp

VOLUME ${HOME_DIR}

STOPSIGNAL SIGINT

CMD ["php", "./launch.php"]
