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

# Derive image tags from Dockerfile
FRIENDICA_VERSION=$(grep '^FROM friendica:' Dockerfile | sed 's/FROM friendica:\(.*\)-fpm/\1/')
LARPNET_VERSION=$(grep '^ARG LARPNET_VERSION=' Dockerfile | sed 's/ARG LARPNET_VERSION=//')
TAG="${FRIENDICA_VERSION}-${LARPNET_VERSION}"
IMAGE="${REGISTRY_URL}/friendica-larpnet"

echo "Building ${IMAGE}:${TAG}"

docker login "$REGISTRY_URL" -u "$REGISTRY_USER" -p "$REGISTRY_PASSWORD"

docker build \
  -t "${IMAGE}:${TAG}" \
  -t "${IMAGE}:latest" \
  "$SCRIPT_DIR"

docker push "${IMAGE}:${TAG}"
docker push "${IMAGE}:latest"

echo "Done — pushed ${IMAGE}:${TAG} and ${IMAGE}:latest"
