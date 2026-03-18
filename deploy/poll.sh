#!/bin/bash
##
# Upkeepify QNAP Polling Deployer
#
# Polls GitHub Actions for successful builds on main branch.
# When a new commit with passing CI is detected, pulls code and deploys to local WordPress.
#
# Environment Variables:
#   PLUGIN_DIR    - Path to plugin folder (default: /plugin)
#   POLL_INTERVAL - Seconds between polls (default: 300 / 5 minutes)
#   REPO          - GitHub repo (default: anthonyhorne/upkeepify)
#   BRANCH        - Git branch to track (default: main)
##

set -euo pipefail

REPO="${REPO:-anthonyhorne/upkeepify}"
BRANCH="${BRANCH:-main}"
PLUGIN_DIR="${PLUGIN_DIR:-/plugin}"
SHA_FILE="${PLUGIN_DIR}/.deployed-sha"
POLL_INTERVAL="${POLL_INTERVAL:-300}"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

log "Starting Upkeepify polling deployer"
log "Repository: $REPO"
log "Branch: $BRANCH"
log "Plugin directory: $PLUGIN_DIR"
log "Poll interval: ${POLL_INTERVAL}s"

# Main polling loop
while true; do
    log "Checking for updates..."

    # Fetch latest commit on main branch
    RESPONSE=$(curl -sf "https://api.github.com/repos/$REPO/branches/$BRANCH" 2>/dev/null || echo "{}")

    if [ "$RESPONSE" = "{}" ]; then
        log "ERROR: Failed to reach GitHub API"
        sleep "$POLL_INTERVAL"
        continue
    fi

    # Extract SHA
    LATEST_SHA=$(echo "$RESPONSE" | jq -r '.commit.sha // empty' 2>/dev/null || echo "")

    if [ -z "$LATEST_SHA" ]; then
        log "ERROR: Could not parse SHA from API response"
        sleep "$POLL_INTERVAL"
        continue
    fi

    # Check currently deployed SHA
    DEPLOYED_SHA=""
    if [ -f "$SHA_FILE" ]; then
        DEPLOYED_SHA=$(cat "$SHA_FILE" 2>/dev/null || echo "")
    fi

    # If no new commit, wait
    if [ "$LATEST_SHA" = "$DEPLOYED_SHA" ]; then
        log "Already at $LATEST_SHA — no update needed"
        sleep "$POLL_INTERVAL"
        continue
    fi

    log "New commit detected: $LATEST_SHA (deployed: ${DEPLOYED_SHA:-none})"

    # Fetch check-run status for this commit
    CHECK_RESPONSE=$(curl -sf "https://api.github.com/repos/$REPO/commits/$LATEST_SHA/check-runs" 2>/dev/null || echo "{}")

    if [ "$CHECK_RESPONSE" = "{}" ]; then
        log "ERROR: Failed to fetch check-runs from GitHub API"
        sleep "$POLL_INTERVAL"
        continue
    fi

    # Parse check-run results
    TOTAL=$(echo "$CHECK_RESPONSE" | jq '.total_count // 0' 2>/dev/null || echo "0")
    FAILURES=$(echo "$CHECK_RESPONSE" | jq '[.check_runs[] | select(.conclusion == "failure" or .conclusion == "cancelled" or .conclusion == "timed_out" or .conclusion == "action_required")] | length' 2>/dev/null || echo "0")
    IN_PROGRESS=$(echo "$CHECK_RESPONSE" | jq '[.check_runs[] | select(.status == "in_progress" or .status == "queued")] | length' 2>/dev/null || echo "0")
    SUCCESS=$(echo "$CHECK_RESPONSE" | jq '[.check_runs[] | select(.conclusion == "success")] | length' 2>/dev/null || echo "0")

    log "Check status: Total=$TOTAL, Success=$SUCCESS, Failures=$FAILURES, In-Progress=$IN_PROGRESS"

    # Handle no checks yet
    if [ "$TOTAL" = "0" ] || [ "$TOTAL" = "null" ]; then
        log "No checks found for $LATEST_SHA — waiting for CI to start"
        sleep 30
        continue
    fi

    # Handle failed checks
    if [ "$FAILURES" -gt "0" ]; then
        log "❌ CI FAILED for $LATEST_SHA ($FAILURES failures) — skipping deploy"
        # Mark as seen so we don't retry this failure
        echo "$LATEST_SHA" > "$SHA_FILE"
        sleep "$POLL_INTERVAL"
        continue
    fi

    # Handle in-progress checks
    if [ "$IN_PROGRESS" -gt "0" ]; then
        log "⏳ CI still running ($IN_PROGRESS in progress) — waiting..."
        sleep 60  # Check more frequently while CI is running
        continue
    fi

    # All checks passed
    log "✅ CI passed ($SUCCESS checks) — deploying $LATEST_SHA..."

    # Clone fresh copy to temp directory
    TMPDIR=$(mktemp -d)
    log "Cloning to $TMPDIR..."

    if ! git clone --depth 1 --branch "$BRANCH" "https://github.com/$REPO.git" "$TMPDIR/upkeepify" 2>/dev/null; then
        log "ERROR: git clone failed"
        rm -rf "$TMPDIR"
        sleep "$POLL_INTERVAL"
        continue
    fi

    # Sync plugin files to plugin directory (exclude dev files)
    log "Syncing files to $PLUGIN_DIR..."
    rsync -av --delete \
        --exclude='.git' \
        --exclude='.github' \
        --exclude='tests/' \
        --exclude='composer.json' \
        --exclude='composer.lock' \
        --exclude='.phpcs.xml' \
        --exclude='deploy/' \
        --exclude='.deployed-sha' \
        "$TMPDIR/upkeepify/" "$PLUGIN_DIR/" 2>&1 | grep -E '^(building|deleting|[<>cf])' || true

    rm -rf "$TMPDIR"

    # Record deployed SHA
    echo "$LATEST_SHA" > "$SHA_FILE"

    log "✅ Successfully deployed $LATEST_SHA"
    sleep "$POLL_INTERVAL"

done
