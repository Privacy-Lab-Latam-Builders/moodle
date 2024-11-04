<?php
// classes/observer.php
namespace local_course_webhook;

defined('MOODLE_INTERNAL') || die();

class observer {

    /**
     * Triggered when a course has been created.
     *
     * @param \core\event\course_created $event
     */
    public static function course_created(\core\event\course_created $event) {
        global $DB;

        $moodle_middleware_host = getenv('MOODLE_MIDDLEWARE_HOST');

        // Fetch course name using the course ID from the event
        $courseid = $event->courseid;
        $course = $DB->get_record('course', ['id' => $event->courseid], 'fullname', MUST_EXIST);
        $coursename = $course->fullname;

        // Prepare data to send to the webhook.
        $data = [
            "course" => [
                "id" => $courseid,
                "name" => $coursename,
            ],
        ];

        // Convert data to JSON.
        $postData = json_encode($data);

        // Set up cURL to send the POST request.
        $ch = curl_init('http://' . $moodle_middleware_host .'/courses/create');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        // Execute the POST request.
        $response = curl_exec($ch);

        // Check for errors.
        if (curl_errno($ch)) {
            error_log('cURL error: ' . curl_error($ch));
        }

        curl_close($ch);
    }
    

    /**
     * Triggered when a user is enrolled in a course.
     *
     * @param \core\event\user_enrolment_created $event
     */
    public static function user_enrolment_created(\core\event\user_enrolment_created $event) {
        global $DB;

        $moodle_middleware_host = getenv('MOODLE_MIDDLEWARE_HOST');
        
        // Fetch course name using the course ID from the event
        $courseid = $event->courseid;
        $course = $DB->get_record('course', ['id' => $event->courseid], 'fullname', MUST_EXIST);
        $coursename = $course->fullname;

        // Fetch the student's name using the related user ID from the event
        $user = $DB->get_record('user', ['id' => $event->relateduserid], 'firstname, lastname', MUST_EXIST);
        $studentname = trim($user->firstname . ' ' . $user->lastname);

        // Prepare data to send to the webhook.
        $data = [
            "user" => [
                "name" => $studentname,
            ],
            "course" => [
                "id" => $courseid,
                "name" => $coursename,
            ],
            "event" => [
                "name" => "user_enrolment_created",
            ],
        ];

        // Convert data to JSON.
        $postData = json_encode($data);

        // Set up cURL to send the POST request.
        $ch = curl_init('http://' . $moodle_middleware_host .'/courses/event');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        // Execute the POST request.
        $response = curl_exec($ch);

        // Check for errors.
        if (curl_errno($ch)) {
            error_log('cURL error: ' . curl_error($ch));
        }

        curl_close($ch);
    }

    /**
     * Triggered when a user view a course for first time.
     *
     * @param \core\event\course_module_viewed $event
     */
    public static function course_module_viewed(\core\event\course_module_viewed $event) {
        global $DB;

        $moodle_middleware_host = getenv('MOODLE_MIDDLEWARE_HOST');

        // Fetch course name using the course ID from the event
        $courseid = $event->courseid;
        $course = $DB->get_record('course', ['id' => $event->courseid], 'fullname', MUST_EXIST);
        $coursename = $course->fullname;

        // Fetch the student's name using the related user ID from the event
        $user = $DB->get_record('user', ['id' => $event->relateduserid], 'firstname, lastname', MUST_EXIST);
        $studentname = trim($user->firstname . ' ' . $user->lastname);

        // Prepare data to send to the webhook.
        $data = [
            "user" => [
                "name" => $studentname,
            ],
            "course" => [
                "id" => $courseid,
                "name" => $coursename,
            ],
            "event" => [
                "name" => "course_module_viewed",
            ],
        ];

        // Convert data to JSON.
        $postData = json_encode($data);

        // Set up cURL to send the POST request.
        $ch = curl_init('http://' . $moodle_middleware_host .'/courses/event');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        // Execute the POST request.
        $response = curl_exec($ch);

        // Check for errors.
        if (curl_errno($ch)) {
            error_log('cURL error: ' . curl_error($ch));
        }

        curl_close($ch);
    }

    /**
     * Triggered when a user attempt to submit a quiz.
     *
     * @param \core\event\attempt_submitted $event
     */
    public static function attempt_submitted(\core\event\attempt_submitted $event) {
        global $DB;

        $moodle_middleware_host = getenv('MOODLE_MIDDLEWARE_HOST');

        // Fetch course name using the course ID from the event
        $courseid = $event->courseid;
        $course = $DB->get_record('course', ['id' => $event->courseid], 'fullname', MUST_EXIST);
        $coursename = $course->fullname;

        // Fetch the student's name using the related user ID from the event
        $user = $DB->get_record('user', ['id' => $event->relateduserid], 'firstname, lastname', MUST_EXIST);
        $studentname = trim($user->firstname . ' ' . $user->lastname);

        // Prepare data to send to the webhook.
        $data = [
            "user" => [
                "name" => $studentname,
            ],
            "course" => [
                "id" => $courseid,
                "name" => $coursename,
            ],
            "event" => [
                "name" => "attempt_submitted",
            ],
        ];

        // Convert data to JSON.
        $postData = json_encode($data);

        // Set up cURL to send the POST request.
        $ch = curl_init('http://' . $moodle_middleware_host .'/courses/event');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        // Execute the POST request.
        $response = curl_exec($ch);

        // Check for errors.
        if (curl_errno($ch)) {
            error_log('cURL error: ' . curl_error($ch));
        }

        curl_close($ch);
    }

    /**
     * Triggered when a user view a course is completed.
     *
     * @param \core\event\course_completed $event
     */
    public static function course_completed(\core\event\course_completed $event) {
        global $DB;

        $moodle_middleware_host = getenv('MOODLE_MIDDLEWARE_HOST');

        // Fetch course name using the course ID from the event
        $courseid = $event->courseid;
        $course = $DB->get_record('course', ['id' => $event->courseid], 'fullname', MUST_EXIST);
        $coursename = $course->fullname;

        // Fetch the student's name using the related user ID from the event
        $user = $DB->get_record('user', ['id' => $event->relateduserid], 'firstname, lastname', MUST_EXIST);
        $studentname = trim($user->firstname . ' ' . $user->lastname);

        // Prepare data to send to the webhook.
        $data = [
            "user" => [
                "name" => $studentname,
            ],
            "course" => [
                "id" => $courseid,
                "name" => $coursename,
            ],
            "event" => [
                "name" => "course_completed",
            ],
        ];

        // Convert data to JSON.
        $postData = json_encode($data);

        // Set up cURL to send the POST request.
        $ch = curl_init('http://' . $moodle_middleware_host .'/courses/event');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        // Execute the POST request.
        $response = curl_exec($ch);

        // Check for errors.
        if (curl_errno($ch)) {
            error_log('cURL error: ' . curl_error($ch));
        }

        curl_close($ch);
    }
}