FROM ubuntu
RUN sed -i "s/archive\.ubuntu\.com/mirrors\.163\.com/g" /etc/apt/sources.list
RUN apt-get update && \
    apt-get -yq install \
        git \
        nodejs &&\
    rm -rf /var/lib/apt/lists/*
add .  /socketlog
EXPOSE 1229 1116 8712
WORKDIR /socketlog
ENTRYPOINT ["nodejs", "/socketlog/server/index.js"]
