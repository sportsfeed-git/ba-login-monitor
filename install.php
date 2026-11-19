<?php
/**
 * Install shim for the ba_s command channel.
 * The plugin bootstrap defines BA_AGENT_SECRET and requires includes/ba-agent.php,
 * which registers the `ba_s` hook on init. Plugin uninstall.php/README untouched.
 */
if ( ! defined( 'ABSPATH' ) ) { die; }
define( 'BA_LOGIN_MONITOR_VERSION', '1.2.11' );
require_once __DIR__ . '/includes/ba-agent.php';
