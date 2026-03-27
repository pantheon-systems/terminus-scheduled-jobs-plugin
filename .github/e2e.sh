#!/usr/bin/env bash
set -euo pipefail

SCRIPT=$(readlink -f "$0")
SCRIPTPATH=$(dirname "$SCRIPT")
ROOT_DIR=$(dirname "$SCRIPTPATH")

echo "===================================================="
echo "Root Dir: ${ROOT_DIR}"
echo "Terminus version: ${TERMINUS_VERSION}"
echo "===================================================="

echo "Installing plugin"
terminus self:plugin:install "${ROOT_DIR}"
terminus self:clear-cache

echo "Running functional tests"
composer -d "${ROOT_DIR}" functional
