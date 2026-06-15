#!/usr/bin/env bash
# larpnet-wifi-provision.sh
# Processes spool files written by the larpnet_wifi Friendica addon.
# For each file: provisions/resets the user in MikroTik User Manager,
# emails the new password via Mailgun, then deletes the spool file.
#
# Deploy: cp larpnet-wifi-provision.sh /usr/local/sbin/ && chmod 700 /usr/local/sbin/larpnet-wifi-provision.sh
# Config: /etc/larpnet-wifi.conf (chmod 600)
set -euo pipefail

CONFIG_FILE="/etc/larpnet-wifi.conf"

if [[ ! -f "$CONFIG_FILE" ]]; then
    echo "[ERROR] Config file not found: $CONFIG_FILE" >&2
    exit 1
fi

# shellcheck source=/dev/null
source "$CONFIG_FILE"

: "${MIKROTIK_URL:?not set in $CONFIG_FILE}"
: "${MIKROTIK_USER:?not set in $CONFIG_FILE}"
: "${MIKROTIK_PASS:?not set in $CONFIG_FILE}"
: "${MIKROTIK_PROFILE:?not set in $CONFIG_FILE}"
: "${MIKROTIK_TLS_VERIFY:=yes}"
: "${SPOOL_DIR:=/var/spool/portalprov}"
: "${MAILGUN_API_KEY:?not set in $CONFIG_FILE}"
: "${MAILGUN_DOMAIN:?not set in $CONFIG_FILE}"
: "${MAILGUN_FROM:?not set in $CONFIG_FILE}"
: "${EMAIL_SUBJECT:=Twoje hasło do sieci WIFI LARPnet}"

TLS_FLAG=""
[[ "$MIKROTIK_TLS_VERIFY" == "no" ]] && TLS_FLAG="-k"

log() { echo "[$(date -u '+%Y-%m-%dT%H:%M:%SZ')] $*"; }

mt_curl() {
    # Wrapper for authenticated MikroTik REST calls.
    # Usage: mt_curl <method> <path> [extra curl args...]
    local method="$1" path="$2"
    shift 2
    curl -sf $TLS_FLAG \
        -u "${MIKROTIK_USER}:${MIKROTIK_PASS}" \
        -H "Content-Type: application/json" \
        -X "$method" \
        "${MIKROTIK_URL}/rest${path}" \
        "$@"
}

provision_user() {
    local portal_user="$1" password="$2"

    # Look up existing user by name
    local lookup
    lookup=$(mt_curl GET "/user-manager/user" --get --data-urlencode "name=${portal_user}" 2>&1) || {
        log "[ERROR] MikroTik lookup failed for '${portal_user}': ${lookup}"
        return 1
    }

    local user_id
    user_id=$(echo "$lookup" | jq -r '.[0][".id"] // empty' 2>/dev/null)

    if [[ -n "$user_id" ]]; then
        # User exists — reset password
        local result
        result=$(mt_curl PATCH "/user-manager/user/${user_id}" \
            -d "{\"password\":\"${password}\"}" 2>&1) || {
            log "[ERROR] MikroTik password reset failed for '${portal_user}' (id=${user_id}): ${result}"
            return 1
        }
        log "[INFO] Password reset for existing user '${portal_user}' (id=${user_id})"
    else
        # New user — create account
        local result
        result=$(mt_curl PUT "/user-manager/user" \
            -d "{\"name\":\"${portal_user}\",\"password\":\"${password}\",\"profile\":\"${MIKROTIK_PROFILE}\"}" 2>&1) || {
            log "[ERROR] MikroTik user creation failed for '${portal_user}': ${result}"
            return 1
        }
        log "[INFO] Created new user '${portal_user}'"
    fi
}

send_email() {
    local to="$1" realname="$2" portal_user="$3" password="$4"

    local body
    body="$(cat <<EOF
Cześć ${realname},

Twoje hasło do sieci WIFI LARPnet zostało zresetowane.

Login: ${portal_user}
Hasło: ${password}

Jeśli nie prosiłeś/aś o reset, skontaktuj się z administratorem.

-- LARPnet
EOF
)"

    local result
    result=$(curl -sf \
        --user "api:${MAILGUN_API_KEY}" \
        "https://api.mailgun.net/v3/${MAILGUN_DOMAIN}/messages" \
        -F "from=${MAILGUN_FROM}" \
        -F "to=${to}" \
        -F "subject=${EMAIL_SUBJECT}" \
        -F "text=${body}" 2>&1) || {
        log "[WARN] Mailgun delivery failed for '${to}': ${result}"
        return 1
    }
    log "[INFO] Email sent to '${to}'"
}

process_file() {
    local spool_file="$1"

    local uid portal_user email realname
    uid=$(jq -r '.uid' "$spool_file")
    portal_user=$(jq -r '.portal_user' "$spool_file")
    email=$(jq -r '.email' "$spool_file")
    realname=$(jq -r '.realname' "$spool_file")

    if [[ -z "$portal_user" || -z "$email" ]]; then
        log "[ERROR] Missing fields in ${spool_file} — skipping"
        return 1
    fi

    local password
    password=$(tr -dc 'A-Za-z0-9' < /dev/urandom | head -c 12)

    log "[INFO] Processing uid=${uid} portal_user=${portal_user} file=$(basename "$spool_file")"

    # MikroTik provisioning must succeed before we delete the file
    provision_user "$portal_user" "$password" || return 1

    # Email failure is non-fatal — don't block deletion or retry provisioning
    send_email "$email" "$realname" "$portal_user" "$password" || true

    rm -f "$spool_file"
    log "[INFO] Deleted ${spool_file}"
}

# Main loop
shopt -s nullglob
files=("${SPOOL_DIR}"/*.json)

if [[ ${#files[@]} -eq 0 ]]; then
    exit 0
fi

log "[INFO] Processing ${#files[@]} spool file(s)"

for spool_file in "${files[@]}"; do
    [[ -f "$spool_file" ]] || continue
    # Run each file in a subshell so one failure doesn't abort the batch
    (process_file "$spool_file") || log "[ERROR] Failed to process $(basename "$spool_file") — will retry next run"
done
