FROM node:20-slim

# Baileys needs git for some transitive deps; ca-certificates for HTTPS
RUN apt-get update && apt-get install -y git ca-certificates --no-install-recommends \
    && rm -rf /var/lib/apt/lists/* \
    && update-ca-certificates \
    && git config --global url."https://github.com/".insteadOf "ssh://git@github.com/"

WORKDIR /app

COPY package.json ./
RUN npm install --omit=dev

COPY server.js ./

# Persistent auth data
VOLUME /data

EXPOSE 8602

CMD ["node", "server.js"]
