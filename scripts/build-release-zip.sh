#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_SLUG="upkeepify"

VERSION="$(node -p "require('${ROOT_DIR}/package.json').version")"
SHORT_SHA="$(git -C "${ROOT_DIR}" rev-parse --short HEAD)"
ARCHIVE_NAME="${PLUGIN_SLUG}-${VERSION}-${SHORT_SHA}-slim.zip"
STAGING_DIR="$(mktemp -d)"
PACKAGE_DIR="${STAGING_DIR}/${PLUGIN_SLUG}"

cleanup() {
  rm -rf "${STAGING_DIR}"
}

trap cleanup EXIT

mkdir -p "${PACKAGE_DIR}"
mkdir -p "${PACKAGE_DIR}/includes" "${PACKAGE_DIR}/js" "${PACKAGE_DIR}/languages"

cp "${ROOT_DIR}/upkeepify.php" "${PACKAGE_DIR}/"
cp "${ROOT_DIR}/uninstall.php" "${PACKAGE_DIR}/"
cp "${ROOT_DIR}/readme.txt" "${PACKAGE_DIR}/"
cp "${ROOT_DIR}/LICENSE.md" "${PACKAGE_DIR}/"
cp "${ROOT_DIR}/upkeepify-styles.css" "${PACKAGE_DIR}/"
cp "${ROOT_DIR}/favicon.png" "${PACKAGE_DIR}/"
cp "${ROOT_DIR}/icon-192x192.png" "${PACKAGE_DIR}/"
cp "${ROOT_DIR}/icon-512x512.png" "${PACKAGE_DIR}/"

cp -R "${ROOT_DIR}/includes/." "${PACKAGE_DIR}/includes/"
cp "${ROOT_DIR}/languages/upkeepify.pot" "${PACKAGE_DIR}/languages/"

cp "${ROOT_DIR}/js/utils.min.js" "${PACKAGE_DIR}/js/"
cp "${ROOT_DIR}/js/notifications.min.js" "${PACKAGE_DIR}/js/"
cp "${ROOT_DIR}/js/admin-settings.min.js" "${PACKAGE_DIR}/js/"
cp "${ROOT_DIR}/js/form-validation.min.js" "${PACKAGE_DIR}/js/"
cp "${ROOT_DIR}/js/upload-handler.min.js" "${PACKAGE_DIR}/js/"
cp "${ROOT_DIR}/js/task-filters.min.js" "${PACKAGE_DIR}/js/"
cp "${ROOT_DIR}/js/calendar-interactions.min.js" "${PACKAGE_DIR}/js/"
cp "${ROOT_DIR}/js/service-worker.js" "${PACKAGE_DIR}/js/"

(
  cd "${STAGING_DIR}"
  zip -rq "${ROOT_DIR}/${ARCHIVE_NAME}" "${PLUGIN_SLUG}"
)

echo "Created ${ARCHIVE_NAME}"
