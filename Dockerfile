FROM debian:buster-slim

LABEL maintainer="Carl - github.com/knappster"

RUN dpkg --add-architecture i386
RUN apt-get update
RUN apt-get install -y --no-install-recommends --no-install-suggests \
        python3 \
        lib32stdc++6 \
        lib32gcc1 \
        wget \
        ca-certificates \
        libsdl2-2.0-0:i386 \
        libtbb2 \
        libtbb2:i386 \
        zlib1g:i386
RUN apt-get remove --purge -y
RUN apt-get clean autoclean
RUN apt-get autoremove -y
RUN rm /var/lib/apt/lists/* -r
RUN mkdir -p /steamcmd \
        && cd /steamcmd \
        && wget -qO- 'https://steamcdn-a.akamaihd.net/client/installer/steamcmd_linux.tar.gz' | tar zxf -

ENV ARMA_CONFIG=main.cfg
ENV ARMA_PROFILE=main
ENV ARMA_WORLD=empty
ENV HEADLESS_CLIENTS=0

EXPOSE 2301/udp
EXPOSE 2302/udp
EXPOSE 2303/udp
EXPOSE 2304/udp
EXPOSE 2305/udp
EXPOSE 2306/udp
EXPOSE 2344/tcp
EXPOSE 2344/udp
EXPOSE 2345/tcp

ADD launch.py /launch.py

WORKDIR /arma3

VOLUME /steamcmd

STOPSIGNAL SIGINT

CMD ["python3","/launch.py"]
