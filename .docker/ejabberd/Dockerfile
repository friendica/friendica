FROM ghcr.io/processone/ejabberd:latest

# netcat-openbsd gives a well-behaved /usr/bin/nc that handles EOF correctly.
# BusyBox nc (already in Alpine) works for basic cases, but openBSD nc is more
# predictable as a STDIN/STDOUT relay for the extauth bridge.
USER root
RUN apk add --no-cache netcat-openbsd
USER ejabberd
