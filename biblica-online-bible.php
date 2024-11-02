<?php

/*
 * Copyright © 2022 by Biblica, Inc.
 */

/*
 * Plugin Name: Biblica Bible Reader
 * Plugin URI:
 * Description: Easily add the text of any Bible translation to your website
 * Version: 1.0.5.0
 * Requires PHP: 8.0.2
 * Requires at least: 6.0
 * Author: Biblica
 * Author URI: http://www.biblica.com
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: biblica-online-bible
 * Domain Path: /languages
 */

declare(strict_types=1);

use Biblica\WordPress\Plugin\OnlineBible\OnlineBiblePlugin;
use Psr\Log\LogLevel;

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

require_once(dirname(__FILE__) . '/vendor/autoload.php');

const BIBLICA_OB_VERSION = '1.0.5.0';
// emergency, alert, critical, error, warning, notice, info, debug
const BIBLICA_OB_LOG_LEVEL = LogLevel::DEBUG;

$biblicaOnlineBiblePluginPath = plugin_dir_path(__FILE__);

$biblicaOnlineBiblePlugin = new OnlineBiblePlugin($biblicaOnlineBiblePluginPath);
$biblicaOnlineBiblePlugin->initialize();
