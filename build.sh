#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Load credentials
ENV_FILE="${SCRIPT_DIR}/.env"
if [[ ! -f "$ENV_FILE" ]]; then
  echo "ERROR: .env not found — copy .env.example and fill in credentials" >&2
  exit 1
fi
source "$ENV_FILE"

: "${REGISTRY_URL:?REGISTRY_URL not set in .env}"
: "${REGISTRY_USER:?REGISTRY_USER not set in .env}"
: "${REGISTRY_PASSWORD:?REGISTRY_PASSWORD not set in .env}"

# REGISTRY_PUSH_URL: optional SSH-tunnel endpoint to bypass Cloudflare upload limits.
# If set, the image is built+tagged with REGISTRY_URL (the real name) but pushed
# via REGISTRY_PUSH_URL (e.g. localhost:5000). The registry stores the same image
# and serves it under REGISTRY_URL normally.
PUSH_URL="${REGISTRY_PUSH_URL:-$REGISTRY_URL}"

# Derive image tags from Dockerfile
FRIENDICA_VERSION=$(grep '^FROM friendica:' Dockerfile | sed 's/FROM friendica:\(.*\)-fpm/\1/')
LARPNET_VERSION=$(grep '^ARG LARPNET_VERSION=' Dockerfile | sed 's/ARG LARPNET_VERSION=//')
TAG="${FRIENDICA_VERSION}-${LARPNET_VERSION}"
IMAGE="${REGISTRY_URL}/friendica-larpnet"
PUSH_IMAGE="${PUSH_URL}/friendica-larpnet"

echo "Building ${IMAGE}:${TAG}"
[[ "$PUSH_URL" != "$REGISTRY_URL" ]] && echo "Pushing via tunnel: ${PUSH_IMAGE}"

docker login "$PUSH_URL" -u "$REGISTRY_USER" -p "$REGISTRY_PASSWORD"

docker build \
  -t "${IMAGE}:${TAG}" \
  -t "${IMAGE}:latest" \
  "$SCRIPT_DIR"

# Re-tag for push URL if using a tunnel
if [[ "$PUSH_URL" != "$REGISTRY_URL" ]]; then
  docker tag "${IMAGE}:${TAG}"   "${PUSH_IMAGE}:${TAG}"
  docker tag "${IMAGE}:latest"   "${PUSH_IMAGE}:latest"
fi

docker push "${PUSH_IMAGE}:${TAG}"
docker push "${PUSH_IMAGE}:latest"

echo "Done — pushed ${IMAGE}:${TAG} and ${IMAGE}:latest"
