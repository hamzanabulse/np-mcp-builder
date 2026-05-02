#!/bin/bash
# Security audit harness for np-mcp-builder.
# Runs WordPress-specific static checks and prints a numeric summary.

set -e
cd "$(dirname "$0")/.."
PLUGIN_DIR=$(pwd)

echo "=========================================="
echo " np-mcp-builder security audit"
echo " path: $PLUGIN_DIR"
echo " date: $(date -u +%Y-%m-%dT%H:%M:%SZ)"
echo "=========================================="

# Composer + PHPCS bootstrap
if [ ! -d /tmp/np-audit ]; then
    mkdir -p /tmp/np-audit
    cd /tmp/np-audit
    composer require --dev squizlabs/php_codesniffer:"^3.10" wp-coding-standards/wpcs:"^3" phpcompatibility/phpcompatibility-wp:"^2" dealerdirect/phpcodesniffer-composer-installer:"^1" --no-interaction --quiet 2>&1 | tail -5
    cd "$PLUGIN_DIR"
fi
PHPCS=/tmp/np-audit/vendor/bin/phpcs

echo
echo "--- 1. WordPress-Extra + Security ruleset ----------"
$PHPCS --standard=WordPress-Extra,WordPress-Docs --extensions=php --report=summary --runtime-set ignore_warnings_on_exit 1 -p . | tail -20 || true

echo
echo "--- 2. WordPress-VIP-Go (security focused) ---------"
$PHPCS --standard=WordPress-Extra --sniffs=WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput,WordPress.Security.EscapeOutput,WordPress.DB.PreparedSQL,WordPress.DB.PreparedSQLPlaceholders,WordPress.WP.AlternativeFunctions --extensions=php --report=summary . | tail -20 || true

echo
echo "--- 3. PHP 8.0+ compatibility ----------------------"
$PHPCS --standard=PHPCompatibilityWP --runtime-set testVersion 8.0- --extensions=php --report=summary . | tail -10 || true

echo
echo "--- 4. Hand-rolled grep checks ---------------------"

count() { local label="$1"; shift; local n; n=$(grep -RInE "$@" --include="*.php" . 2>/dev/null | grep -v -E '/(vendor|node_modules|bin/audit-)' | wc -l); printf "%-45s %s\n" "$label" "$n"; }

count "eval(...)"                                          'eval\s*\('
count "exec/shell_exec/passthru/system"                    '\b(exec|shell_exec|passthru|system|popen|proc_open)\s*\('
count "file_get_contents on superglobal"                   'file_get_contents\s*\(\s*\$_'
count "include/require with variable"                      '(include|require)(_once)?\s*\(\s*\$'
count "unserialize() (insecure if user data)"              '\bunserialize\s*\('
count "raw \$_GET/\$_POST/\$_REQUEST without sanitize"     '\$_(GET|POST|REQUEST|COOKIE|SERVER)\['
count "echo of \$_..."                                     'echo\s+\$_'
count "direct \$wpdb->query without prepare"               '\$wpdb->query\s*\(\s*"[^"]*\$'
count "missing ABSPATH guard (PHP files)"                  '^<\?php' && true
echo "  files lacking ABSPATH guard:"
for f in $(find . -name "*.php" -not -path "./vendor/*" -not -path "./bin/*"); do
    if ! grep -q "defined.*ABSPATH" "$f" && [ "$(basename $f)" != "uninstall.php" ]; then
        head -1 "$f" | grep -q "<?php" && echo "    - $f (no ABSPATH check)"
    fi
done
count "wp_unslash usage (good)"                            '\bwp_unslash\s*\('
count "esc_html/esc_attr/esc_url usage"                    '\b(esc_html|esc_attr|esc_url|esc_textarea)\s*\('
count "current_user_can() checks"                          '\bcurrent_user_can\s*\('
count "wp_verify_nonce / check_admin_referer"              '\b(wp_verify_nonce|check_admin_referer|wp_nonce_field|wp_create_nonce)\s*\('
count "permission_callback (Abilities API)"                'permission_callback'
count "sanitize_*() calls"                                 '\bsanitize_(text_field|email|url|key|title|user|file_name|hex_color|html_class)\s*\('

echo
echo "--- 5. Secret scan ---------------------------------"
echo "  Looking for hard-coded keys/passwords/tokens..."
grep -RInE "(api[_-]?key|secret|password|token)\s*=\s*['\"][A-Za-z0-9]{20,}" --include="*.php" . 2>/dev/null | grep -vE '(vendor|sample|example|YOUR_|placeholder)' || echo "  none found"

echo
echo "--- 6. Permission audit (every register call) ------"
echo "  abilities WITHOUT permission_callback:"
for f in includes/abilities/*.php; do
    awk '
        /wp_register_ability\s*\(/ { in_block=1; brace=0; buf=""; tool="" }
        in_block { buf = buf $0 "\n"; for(i=1;i<=length($0);i++){c=substr($0,i,1); if(c=="(")brace++; else if(c==")"){brace--; if(brace==0){in_block=0; break}} }
            if (match($0, /'\''(np\/[a-z-]+)'\''/, m)) tool=m[1]
            if (in_block==0) {
                if (buf !~ /permission_callback/) print "    - " tool " (" FILENAME ")"
                buf=""
            }
        }
    ' "$f"
done

echo
echo "--- 7. Capability map ------------------------------"
grep -hE "current_user_can\s*\(\s*['\"][a-z_]+['\"]" --include="*.php" -r includes/ | grep -oE "current_user_can\s*\(\s*['\"][a-z_]+" | sort | uniq -c | sort -rn

echo
echo "=========================================="
echo " audit finished"
echo "=========================================="
