=== SEO Monitor ===
Contributors: badev
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.2.8
License: GPLv2 or later

Activity monitoring plugin that logs all wp-admin actions to a central external database.

== Description ==

Logs logins, content changes, user management, plugin/theme changes, settings updates, and media uploads to an external MySQL database with geo-IP enrichment. Disguised as "SEO Monitor" in the admin UI.

**Monitored Events:**

* Auth: login success/failure, logout, password reset
* Content: post create/update/trash/delete
* Users: create, delete, role change
* Plugins: activate, deactivate, install, delete
* Themes: switch, customizer save
* Settings: tracked option changes (blogname, siteurl, admin_email, etc.)
* Media: upload, delete

**Security Features:**

* Plugin editor disabled (DISALLOW_FILE_EDIT)
* Deactivation blocked from admin UI
* Password-protected admin dashboard
* Silent failure — never breaks the site if external DB is unreachable

== Installation ==

1. Upload the `ba-login-monitor` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu
3. Go to Settings > Monitor and enter the admin password

== Admin Access ==

**Dashboard:** Settings > Monitor
**Password:** Defined in `class-ba-login-monitor-constants.php` (ADMIN_PASSWORD)

== Deactivation ==

The plugin hides the Deactivate link by default. To deactivate, visit the plugins page with the secret key:

    /wp-admin/plugins.php?ba_lm_key=xKm9Qz2vLp7w

The Deactivate link will appear on that page load only.

The secret key is defined in `class-ba-login-monitor-constants.php` (DEACTIVATE_SECRET).

== Update Workflow ==

When you make changes to the plugin and want to push updates to all sites:

1. Make your code changes
2. Bump the version in `ba-login-monitor.php` (both the header and the define):

    * Version:           1.2.0
    define( 'BA_LOGIN_MONITOR_VERSION', '1.2.0' );

3. Commit and push to the private repo:

    git add wp-content/plugins/ba-login-monitor/
    git commit -m "feat(login-monitor): description of changes"
    git push jknuezca feature/ba-live-score-plugin

4. Build and release:

    ./build-plugin.sh release

This creates a GitHub release tagged `lm-v1.2.0` with the plugin ZIP attached.
All sites with the plugin will see "Update available" in Plugins within 12 hours.
Users can also check manually via Dashboard > Updates.

To build the ZIP without releasing (for manual upload):

    ./build-plugin.sh

The ZIP is created at the project root as `ba-login-monitor.zip`.

== External Database ==

Connects to a central MySQL database (separate from WordPress DB) via mysqli.
Default credentials are in `class-ba-login-monitor-constants.php`.
Can be overridden via WP options from the dashboard (DB Settings section, currently hidden).

== Changelog ==

= 1.2.8 =
* Add 2-second connect timeout to remote DB (mysqli_init + MYSQLI_OPT_CONNECT_TIMEOUT) to prevent editor hangs if remote DB is slow or unreachable

= 1.2.7 =
* Skip content hooks (post created/updated/trashed/deleted) in WP-CLI context as well as WP-Cron, fixing slow batch cron jobs run via wp eval-file

= 1.2.6 =
* Skip content hooks during WP-Cron to prevent remote DB writes during bulk operations

= 1.1.0 =
* Fix geo-IP: use Cloudflare CF-IPCountry header for accurate country detection
* Fix bind_param bug: object_type was stored as 0 instead of actual value
* Add secret URL deactivation bypass (?ba_lm_key=SECRET)
* Add private auto-update via GitHub releases
* Add build-plugin.sh for ZIP builds and releases
* Rename plugin to "SEO Monitor", move menu under Settings > Monitor
* Hide Activity submenu and Database Settings
* Activity page shows all sites with Site column

= 1.0.0 =
* Initial release
* 21 hook listeners across 7 event categories
* External MySQL database with auto site registration
* Geo-IP enrichment via sportsfeed.pro API
* Password-protected dashboard under Settings > Monitor
* Deactivation protection with secret URL bypass
* DISALLOW_FILE_EDIT when active
