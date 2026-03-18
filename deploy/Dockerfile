FROM alpine:3.19

# Install required tools
RUN apk add --no-cache \
    bash \
    curl \
    git \
    rsync \
    jq

# Copy polling script
COPY poll.sh /usr/local/bin/poll.sh
RUN chmod +x /usr/local/bin/poll.sh

# Run the poller
CMD ["/usr/local/bin/poll.sh"]
