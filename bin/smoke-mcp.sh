#!/bin/bash
set -e
: "${NP_MCP_URL:?Set NP_MCP_URL to your MCP endpoint, e.g. https://example.com/wp-json/mcp/mcp-adapter-default-server}"
: "${NP_MCP_AUTH:?Set NP_MCP_AUTH to a Basic auth header value, e.g. NP_MCP_AUTH=\"Basic \$(printf user:apppass | base64)\"}"
URL="$NP_MCP_URL"
AUTH="$NP_MCP_AUTH"

cat > /tmp/init.json <<'JSON'
{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-06-18","clientInfo":{"name":"smoke","version":"1"},"capabilities":{}}}
JSON

SID=$(curl -sD - -X POST -H "Authorization: $AUTH" -H "Content-Type: application/json" -H "Accept: application/json, text/event-stream" --data-binary @/tmp/init.json "$URL" -o /tmp/init.out | grep -i '^mcp-session-id' | awk '{print $2}' | tr -d '\r')
echo "Session: $SID"
echo "--- init response ---"
head -c 300 /tmp/init.out

cat > /tmp/notif.json <<'JSON'
{"jsonrpc":"2.0","method":"notifications/initialized"}
JSON
curl -s -X POST -H "Authorization: $AUTH" -H "Content-Type: application/json" -H "Accept: application/json, text/event-stream" -H "Mcp-Session-Id: $SID" --data-binary @/tmp/notif.json "$URL" -o /dev/null

cat > /tmp/list.json <<'JSON'
{"jsonrpc":"2.0","id":2,"method":"tools/list"}
JSON
curl -s -X POST -H "Authorization: $AUTH" -H "Content-Type: application/json" -H "Accept: application/json, text/event-stream" -H "Mcp-Session-Id: $SID" --data-binary @/tmp/list.json "$URL" -o /tmp/tools.out -w "HTTP %{http_code} bytes %{size_download}\n"

echo "--- np/* tools advertised ---"
grep -oE '"np/[a-z-]+"' /tmp/tools.out | sort -u | tee /tmp/np-tools.list | wc -l
