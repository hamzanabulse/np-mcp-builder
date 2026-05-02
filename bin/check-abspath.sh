#!/bin/bash
cd /var/www/wordpress/wp-content/plugins/np-mcp-builder
echo "=== Files missing ABSPATH guard ==="
miss=0
for f in $(find . -name '*.php' -not -path './vendor/*' -not -path './bin/*' -not -path './.git/*' -not -path './.github/*'); do
    if [ "$(basename "$f")" = "uninstall.php" ]; then continue; fi
    if [ "$(basename "$f")" = "np-mcp-builder.php" ]; then continue; fi
    if ! grep -q "ABSPATH" "$f"; then
        echo "MISSING: $f"
        miss=$((miss+1))
    fi
done
echo "missing total: $miss"
