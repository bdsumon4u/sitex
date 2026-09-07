#!/usr/bin/env bash

set -Eeuo pipefail

APP_DIR="$(pwd)"
LOG_FILE="$APP_DIR/storage/logs/site-update.log"
STATE_DIR="$APP_DIR/storage/app/site-updater"
STATUS_FILE="$STATE_DIR/status.json"
LOCK_FILE="$STATE_DIR/update.lock"
MAX_RETRIES=2
RETRY_WAIT_SECONDS=5
FORCE_RESET="${SITE_UPDATER_FORCE_RESET:-1}"

mkdir -p "$STATE_DIR"
mkdir -p "$APP_DIR/storage/logs"

exec >>"$LOG_FILE" 2>&1

PHP_BIN="./php"
if [ ! -x "$PHP_BIN" ]; then
  PHP_BIN="$(command -v php || true)"
fi

GIT_BIN="$(command -v git || true)"

COMPOSER_BIN="./composer"
if [ ! -x "$COMPOSER_BIN" ]; then
  COMPOSER_BIN="$(command -v composer || true)"
fi

update_status() {
  local state="$1"
  local message="$2"
  local local_commit="${3:-}"
  local remote_commit="${4:-}"
  local sync_state="${5:-unknown}"
  local requires_force_reset="${6:-false}"
  local force_reset_used="${7:-false}"
  local escaped_message
  escaped_message=$(printf '%s' "$message" | sed 's/"/\\"/g')

  cat >"$STATUS_FILE" <<EOF
{
  "state": "$state",
  "message": "$escaped_message",
  "updated_at": "$(date '+%Y-%m-%d %H:%M:%S')",
  "local_commit": "${local_commit:-}",
  "remote_commit": "${remote_commit:-}",
  "sync_state": "${sync_state}",
  "requires_force_reset": ${requires_force_reset},
  "force_reset_used": ${force_reset_used}
}
EOF
}

retry_command() {
  local attempt=0
  local command="$1"
  local label="$2"

  while true; do
    attempt=$((attempt + 1))
    if bash -lc "$command"; then
      return 0
    fi

    if [ "$attempt" -gt "$MAX_RETRIES" ]; then
      echo "[$(date '+%Y-%m-%d %H:%M:%S')] ${label} failed after retries."
      return 1
    fi

    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ${label} failed. Retrying in ${RETRY_WAIT_SECONDS}s (attempt ${attempt}/${MAX_RETRIES})."
    sleep "$RETRY_WAIT_SECONDS"
  done
}

bring_site_up() {
  if [ -n "$PHP_BIN" ]; then
    "$PHP_BIN" artisan up || true
  fi
}

cleanup() {
  if [ -n "$PHP_BIN" ]; then
    "$PHP_BIN" artisan optimize:clear || true
  fi
  bring_site_up
  rm -f "$LOCK_FILE" || true
}

trap cleanup EXIT

cd "$APP_DIR"

if ! (set -o noclobber; echo "$$" >"$LOCK_FILE") 2>/dev/null; then
  update_status "failed" "Another update process is already running." "" "" "unknown" "false" "false"
  exit 1
fi

echo "=============================="
echo "Deploy started at $(date '+%Y-%m-%d %H:%M:%S')"
echo "Current user: $(whoami)"
echo "Present working directory: $(pwd)"

"$GIT_BIN" config --global --add safe.directory "$APP_DIR" || true

update_status "running" "Checking git status..." "" "" "checking" "false" "false"

missing_binaries=()
if [ -z "$PHP_BIN" ]; then
  missing_binaries+=("php")
fi
if [ -z "$GIT_BIN" ]; then
  missing_binaries+=("git")
fi
if [ -f "$APP_DIR/composer.json" ] && [ -z "$COMPOSER_BIN" ]; then
  missing_binaries+=("composer")
fi

if [ "${#missing_binaries[@]}" -gt 0 ]; then
  update_status "failed" "Missing required binaries: ${missing_binaries[*]}." "" "" "unknown" "false" "false"
  exit 1
fi

if [ -z "${HOME:-}" ]; then
  export HOME="$APP_DIR"
fi
export COMPOSER_HOME="${COMPOSER_HOME:-$APP_DIR/storage/app/.composer}"
export COMPOSER_ALLOW_SUPERUSER=1
mkdir -p "$COMPOSER_HOME"

remote_name=$("$GIT_BIN" remote | head -n 1 2>/dev/null || echo "origin")
if [ -z "$remote_name" ]; then
  remote_name="origin"
fi

branch_name=$("$GIT_BIN" rev-parse --abbrev-ref HEAD 2>/dev/null || echo "main")
if [ -z "$branch_name" ] || [ "$branch_name" = "HEAD" ]; then
  branch_name="main"
fi

remote_url=$("$GIT_BIN" remote get-url "$remote_name" 2>/dev/null || true)
echo "Remote: $remote_name (${remote_url:-unknown})"
echo "Branch: $branch_name"

# Explicitly fetch remote branch and prune
retry_command "\"$GIT_BIN\" fetch \"$remote_name\" \"$branch_name\" --prune 2>/dev/null || \"$GIT_BIN\" fetch \"$remote_name\" --prune" "git fetch" || true

# Determine target commit SHA
target_commit=""
if "$GIT_BIN" rev-parse --verify "${remote_name}/${branch_name}" >/dev/null 2>&1; then
  target_commit=$("$GIT_BIN" rev-parse "${remote_name}/${branch_name}")
elif "$GIT_BIN" rev-parse --verify "FETCH_HEAD" >/dev/null 2>&1; then
  target_commit=$("$GIT_BIN" rev-parse "FETCH_HEAD")
elif "$GIT_BIN" rev-parse --verify "${remote_name}/main" >/dev/null 2>&1; then
  target_commit=$("$GIT_BIN" rev-parse "${remote_name}/main")
  branch_name="main"
elif "$GIT_BIN" rev-parse --verify "${remote_name}/master" >/dev/null 2>&1; then
  target_commit=$("$GIT_BIN" rev-parse "${remote_name}/master")
  branch_name="master"
else
  target_commit=$("$GIT_BIN" rev-parse HEAD 2>/dev/null || echo "")
fi

if [ -z "$target_commit" ]; then
  update_status "failed" "Could not determine a valid target commit SHA to reset to." "" "" "unknown" "false" "false"
  exit 1
fi

short_target=$(echo "$target_commit" | cut -c1-7)
echo "Target Commit SHA: $target_commit ($short_target) on branch: $branch_name"

local_commit=$("$GIT_BIN" rev-parse HEAD 2>/dev/null || echo "")
remote_commit="$target_commit"

sync_state="force_reset"
requires_force_reset="true"
force_reset_used="true"

update_status "running" "Starting deployment and resetting to $short_target..." "$local_commit" "$remote_commit" "$sync_state" "$requires_force_reset" "$force_reset_used"

"$PHP_BIN" artisan down || true

backup_branch="backup/pre-reset-$(date '+%Y%m%d%H%M%S')"
"$GIT_BIN" branch "$backup_branch" HEAD 2>/dev/null || true

# Attach HEAD to branch and force reset to the exact commit SHA
"$GIT_BIN" checkout -B "$branch_name" "$target_commit" 2>/dev/null || true

if ! retry_command "\"$GIT_BIN\" reset --hard \"$target_commit\"" "git hard reset"; then
  update_status "failed" "git reset --hard failed to $short_target." "$local_commit" "$remote_commit" "$sync_state" "$requires_force_reset" "$force_reset_used"
  exit 1
fi

"$GIT_BIN" clean -fd -e .env -e storage/ 2>/dev/null || true

if [ -f "$APP_DIR/composer.json" ] && [ -n "$COMPOSER_BIN" ]; then
  if ! retry_command "\"$COMPOSER_BIN\" install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress" "composer install"; then
    update_status "failed" "composer install failed." "$local_commit" "$remote_commit" "$sync_state" "$requires_force_reset" "$force_reset_used"
    exit 1
  fi
fi

retry_command "\"$PHP_BIN\" artisan db:convert-innodb" "db convert innodb" || true

if ! retry_command "\"$PHP_BIN\" artisan migrate --force" "database migration"; then
  update_status "failed" "Database migration failed." "$local_commit" "$remote_commit" "$sync_state" "$requires_force_reset" "$force_reset_used"
  exit 1
fi

retry_command "\"$PHP_BIN\" artisan responsecache:clear" "responsecache clear" || true

if ! retry_command "\"$PHP_BIN\" artisan optimize:clear" "optimize clear"; then
  update_status "failed" "Could not clear optimization cache." "$local_commit" "$remote_commit" "$sync_state" "$requires_force_reset" "$force_reset_used"
  exit 1
fi

if ! retry_command "\"$PHP_BIN\" artisan config:cache" "config cache"; then
  update_status "failed" "Could not build config cache." "$local_commit" "$remote_commit" "$sync_state" "$requires_force_reset" "$force_reset_used"
  exit 1
fi

if ! retry_command "\"$PHP_BIN\" artisan route:cache" "route cache"; then
  update_status "failed" "Could not build route cache." "$local_commit" "$remote_commit" "$sync_state" "$requires_force_reset" "$force_reset_used"
  exit 1
fi

if ! retry_command "\"$PHP_BIN\" artisan view:cache" "view cache"; then
  update_status "failed" "Could not build view cache." "$local_commit" "$remote_commit" "$sync_state" "$requires_force_reset" "$force_reset_used"
  exit 1
fi

local_commit=$("$GIT_BIN" rev-parse HEAD 2>/dev/null || true)
update_status "completed" "Update completed successfully to $short_target." "$local_commit" "$remote_commit" "$sync_state" "$requires_force_reset" "$force_reset_used"
echo "Deploy completed at $(date '+%Y-%m-%d %H:%M:%S')"

