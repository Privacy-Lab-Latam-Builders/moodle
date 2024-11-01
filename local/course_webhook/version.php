<?php
/**
 * Plugin description or file purpose.
 *
 * @package    local_webhook_observer
 * @autor      Paul R
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @var stdClass $plugin
 */

 defined('MOODLE_INTERNAL') || die();

 $plugin->component = 'local_course_webhook'; // Plugin name.
 $plugin->version = 2024110100;               // Version of the plugin (YYYYMMDDXX).
 $plugin->requires = 2022112800;              // Moodle 4.1 or later.
 $plugin->maturity = MATURITY_STABLE;
 $plugin->release = '1.0';