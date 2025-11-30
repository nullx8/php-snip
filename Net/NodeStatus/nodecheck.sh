#!/bin/bash

# Load .env
if [ ! -f .env ]; then
    echo ".env file missing"
    exit 1
fi

# shellcheck source=/dev/null
source .env

# Check required variables
if [ -z "$KEYFILE" ]; then
    echo "KEYFILE not defined in .env"
    exit 1
fi

if [ -z "$OUTFILE_PREFIX" ]; then
    echo "OUTFILE_PREFIX not defined in .env"
    exit 1
fi

if [ ${#HOSTS[@]} -eq 0 ]; then
    echo "No HOSTS defined in .env"
    exit 1
fi

# Check key file
if [ ! -f "$KEYFILE" ]; then
    echo "Keyfile not found: $KEYFILE"
    exit 1
fi

# Loop through HOSTS from .env
for HOST in "${HOSTS[@]}"; do
    echo "Resolving $HOST through Tailscale..."

    # Resolve Tailscale IP (DNS-free)
    TSIP=$(tailscale ip -4 "$HOST")

    if [ -z "$TSIP" ]; then
        echo "Error: Cannot resolve $HOST via Tailscale"
        continue
    fi

    echo " -> $HOST resolved to $TSIP"

    # Determine output file name from prefix + sanitized hostname
    SANITIZED_HOST=$(echo "$HOST" | tr '/:' '_')
    OUTFILE="${OUTFILE_PREFIX}${SANITIZED_HOST}.json"

    echo "Collecting data from $HOST..."
    ssh \
        -i "$KEYFILE" \
        -o ConnectTimeout=5 \
        -o StrictHostKeyChecking=no \
        root@"$TSIP" \
        /root/nodestatus.sh \
        > "$OUTFILE"

    if [ $? -eq 0 ]; then
        echo "Saved: $OUTFILE"
    else
        echo "Failed to collect from $HOST"
    fi

    echo
done

