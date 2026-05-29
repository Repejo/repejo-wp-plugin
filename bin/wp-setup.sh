#!/usr/bin/env bash
# Provisions the local WordPress install for testing the Repejo WP Plugin.
# Idempotent: safe to re-run.
set -euo pipefail

SITE_URL="http://localhost:8080"
WP="wp --path=/var/www/html"

echo "Waiting for WordPress core files to appear..."
for _ in $(seq 1 60); do
  [ -f /var/www/html/wp-load.php ] && break
  sleep 2
done

echo "Waiting for the database..."
until $WP db check >/dev/null 2>&1; do
  $WP core is-installed >/dev/null 2>&1 && break
  sleep 2
done

if ! $WP core is-installed >/dev/null 2>&1; then
  echo "Installing WordPress..."
  $WP core install \
    --url="$SITE_URL" \
    --title="Repejo Plugin Test" \
    --admin_user=admin \
    --admin_password=admin \
    --admin_email=admin@example.com \
    --skip-email
else
  echo "WordPress already installed."
fi

echo "Enabling pretty permalinks (required by the plugin's rewrite rules)..."
$WP rewrite structure '/%postname%/' --hard

echo "Activating the plugin..."
$WP plugin activate repejo-wp-plugin

# Seed a test page at /signera and opt it into the pretty donor-id URL format.
if ! $WP post list --post_type=page --name=signera --field=ID | grep -q .; then
  echo "Creating test page /signera ..."
  PAGE_ID=$($WP post create --post_type=page --post_status=publish \
    --post_title='Signera' --post_name='signera' --porcelain)
else
  PAGE_ID=$($WP post list --post_type=page --name=signera --field=ID | head -n1)
  echo "Test page /signera already exists (ID $PAGE_ID)."
fi

echo "Opting the page into the Repejo pretty-URL format..."
$WP post meta update "$PAGE_ID" _repejo_pretty_url 1

echo "Forcing a rewrite flush so the new rule takes effect..."
$WP option update repejo_wp_plugin_needs_flush 1
$WP rewrite flush --hard

cat <<EOF

================================================================
  WordPress is ready.

  Admin:   $SITE_URL/wp-admin   (admin / admin)
  Plugin settings: Settings -> Repejo

  Test the pretty donor-id URL:
    $SITE_URL/signera/abc-123

  Expect: page loads (no 404) and the <head> contains
    <meta name="repejo-telemarketing-id" content="abc-123">

  Legacy query param still works:
    $SITE_URL/signera/?rp_hrid=abc-123
================================================================
EOF
