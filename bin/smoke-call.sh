#!/bin/bash
set -e
: "${NP_MCP_URL:?Set NP_MCP_URL to your MCP endpoint, e.g. https://example.com/wp-json/mcp/mcp-adapter-default-server}"
: "${NP_MCP_AUTH:?Set NP_MCP_AUTH to a Basic auth header value, e.g. NP_MCP_AUTH=\"Basic \$(printf user:apppass | base64)\"}"
URL="$NP_MCP_URL"
AUTH="$NP_MCP_AUTH"

cat > /tmp/init.json <<'JSON'
{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-06-18","clientInfo":{"name":"smoke","version":"1"},"capabilities":{}}}
JSON
SID=$(curl -sD - -X POST -H "Authorization: $AUTH" -H "Content-Type: application/json" -H "Accept: application/json, text/event-stream" --data-binary @/tmp/init.json "$URL" -o /dev/null | grep -i '^mcp-session-id' | awk '{print $2}' | tr -d '\r')
echo "Session: $SID"

cat > /tmp/notif.json <<'JSON'
{"jsonrpc":"2.0","method":"notifications/initialized"}
JSON
curl -s -X POST -H "Authorization: $AUTH" -H "Content-Type: application/json" -H "Accept: application/json, text/event-stream" -H "Mcp-Session-Id: $SID" --data-binary @/tmp/notif.json "$URL" -o /dev/null

call() {
  local NAME="$1"
  local ARGS="$2"
  cat > /tmp/call.json <<JSON
{"jsonrpc":"2.0","id":99,"method":"tools/call","params":{"name":"$NAME","arguments":$ARGS}}
JSON
  echo "--- $NAME ---"
  curl -s -X POST -H "Authorization: $AUTH" -H "Content-Type: application/json" -H "Accept: application/json, text/event-stream" -H "Mcp-Session-Id: $SID" --data-binary @/tmp/call.json "$URL" | head -c 500
  echo
}

call np-system-info '{}'
call np-audit-seo '{"limit":3}'
call np-elementor-list-templates '{}'
