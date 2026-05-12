#!/usr/bin/env bash
set -euo pipefail

SITE_ENV="${E2E_SITE_ENV}"
SCHEDULE_NAME="e2e-test-${GITHUB_RUN_ID:-$(date +%s)}"
SCHEDULE_CMD="echo e2e-test"
SCHEDULE_CRON="0 0 1 1 *"  # Jan 1st at midnight — valid but fires at most once a year
SCHEDULE_ID=""

cleanup() {
    if [ -n "$SCHEDULE_ID" ]; then
        echo "--- Cleaning up schedule $SCHEDULE_ID ---"
        terminus scheduledjobs:schedule:delete "$SITE_ENV" "$SCHEDULE_ID" || true
    fi
}
trap cleanup EXIT

get_status() {
    terminus scheduledjobs:schedule:list "$SITE_ENV" --format=json 2>/dev/null \
        | jq -r ".[] | select(.id == \"$SCHEDULE_ID\") | .status"
}

# --- Create ---
echo "--- Creating schedule '$SCHEDULE_NAME' ---"
terminus scheduledjobs:schedule:create "$SITE_ENV" "$SCHEDULE_NAME" "$SCHEDULE_CMD" "$SCHEDULE_CRON"

LIST_JSON=$(terminus scheduledjobs:schedule:list "$SITE_ENV" --format=json 2>/dev/null)
SCHEDULE_ID=$(echo "$LIST_JSON" | jq -r ".[] | select(.name == \"$SCHEDULE_NAME\") | .id")

if [ -z "$SCHEDULE_ID" ]; then
    echo "FAIL: created schedule '$SCHEDULE_NAME' not found in list"
    exit 1
fi

STATUS=$(get_status)
if [ "$STATUS" != "ENABLED" ]; then
    echo "FAIL: expected status ENABLED after create, got '$STATUS'"
    exit 1
fi
echo "OK: schedule $SCHEDULE_ID created with status ENABLED"

# --- Pause ---
echo "--- Pausing schedule ---"
terminus scheduledjobs:schedule:pause "$SITE_ENV" "$SCHEDULE_ID"

STATUS=$(get_status)
if [ "$STATUS" = "ENABLED" ]; then
    echo "FAIL: status is still ENABLED after pause"
    exit 1
fi
echo "OK: schedule paused (status: $STATUS)"

# --- Resume ---
echo "--- Resuming schedule ---"
terminus scheduledjobs:schedule:resume "$SITE_ENV" "$SCHEDULE_ID"

STATUS=$(get_status)
if [ "$STATUS" != "ENABLED" ]; then
    echo "FAIL: expected status ENABLED after resume, got '$STATUS'"
    exit 1
fi
echo "OK: schedule resumed with status ENABLED"

# --- Delete ---
echo "--- Deleting schedule ---"
terminus scheduledjobs:schedule:delete "$SITE_ENV" "$SCHEDULE_ID"
SCHEDULE_ID=""  # prevent double-delete in cleanup trap

LIST_JSON=$(terminus scheduledjobs:schedule:list "$SITE_ENV" --format=json 2>/dev/null || echo "[]")
REMAINING=$(echo "$LIST_JSON" | jq -r ".[] | select(.name == \"$SCHEDULE_NAME\") | .id")

if [ -n "$REMAINING" ]; then
    echo "FAIL: schedule '$SCHEDULE_NAME' still present after deletion"
    exit 1
fi
echo "OK: schedule no longer present"

echo "--- E2E tests passed ---"
