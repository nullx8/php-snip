#!/bin/bash

# --- Temperature (optional) ---
get_temp() {
    if command -v sensors >/dev/null 2>&1; then
        sensors 2>/dev/null | awk '/temp1/ {print $2; exit}' | tr -d '+°C'
    else
        echo ""
    fi
}

# --- Disk info ---
get_disk() {
    df -h / | awk 'NR==2 {print $4}'
}

# --- Load averages ---
load_1=$(cut -d ' ' -f1 /proc/loadavg)
load_5=$(cut -d ' ' -f2 /proc/loadavg)
load_15=$(cut -d ' ' -f3 /proc/loadavg)

# --- Uptime ---
uptime_seconds=$(cut -d ' ' -f1 < /proc/uptime)

# --- Memory ---
mem_total=$(grep MemTotal /proc/meminfo | awk '{print $2}')
mem_free=$(grep MemFree /proc/meminfo | awk '{print $2}')
mem_avail=$(grep MemAvailable /proc/meminfo | awk '{print $2}')

# --- Temperature ---
temp=$(get_temp)

# --- Disk free ---
disk_free=$(get_disk)

# --- Tailscale JSON ---
if command -v tailscale >/dev/null 2>&1; then
    tailscale_json=$(tailscale status --json 2>/dev/null)
    if [ -z "$tailscale_json" ]; then
        tailscale_json="null"
    fi
else
    tailscale_json="null"
fi

# --- Output JSON ---
cat <<EOF
{
  "hostname": "$(hostname)",
  "uptime_seconds": $uptime_seconds,
  "load_average": {
      "1min": $load_1,
      "5min": $load_5,
      "15min": $load_15
  },
  "memory_kb": {
      "total": $mem_total,
      "free": $mem_free,
      "available": $mem_avail
  },
  "disk": {
      "root_free": "$disk_free"
  },
  "temperature_celsius": ${temp:-null},
  "tailscale": $tailscale_json
}
EOF

