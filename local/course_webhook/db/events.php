<?php
/**
 * Plugin description or file purpose.
 *
 * @package    local_webhook_observer
 * @copyright  2024 Privacy Labs Latam
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname'   => '\core\event\course_created', // Event for user enrollment in a course.
        'callback'    => '\local_course_webhook\observer::course_created', // Method to call.
        'includefile' => '/local/course_webhook/classes/observer.php', // File containing the observer class.
        'internal'    => false,
    ],
    [
        'eventname'   => '\core\event\user_enrolment_created', // Event for user enrollment in a course.
        'callback'    => '\local_course_webhook\observer::user_enrolment_created', // Method to call.
        'includefile' => '/local/course_webhook/classes/observer.php', // File containing the observer class.
        'internal'    => false,
    ],
    [
        'eventname'   => '\mod_survey\event\course_module_viewed', // Event for user view at first time a course.
        'callback'    => '\local_course_webhook\observer::course_module_viewed', // Method to call.
        'includefile' => '/local/course_webhook/classes/observer.php', // File containing the observer class.
        'internal'    => false,
    ],
    [
        'eventname'   => '\mod_survey\event\attempt_submitted', // Event for user view at first time a course.
        'callback'    => '\local_course_webhook\observer::attempt_submitted', // Method to call.
        'includefile' => '/local/course_webhook/classes/observer.php', // File containing the observer class.
        'internal'    => false,
    ],
    [
        'eventname'   => '\mod_survey\event\course_completed', // Event for user view at first time a course.
        'callback'    => '\local_course_webhook\observer::course_completed', // Method to call.
        'includefile' => '/local/course_webhook/classes/observer.php', // File containing the observer class.
        'internal'    => false,
    ],
];