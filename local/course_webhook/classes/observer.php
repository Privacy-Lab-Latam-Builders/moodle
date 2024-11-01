<?php
// classes/observer.php
namespace local_course_webhook;

defined('MOODLE_INTERNAL') || die();

class observer {

    /**
     * Triggered when a user is enrolled in a course.
     *
     * @param \core\event\user_enrolment_created $event
     */
    public static function user_enrolment_created(\core\event\user_enrolment_created $event) {
        global $DB;

        // Fetch course name using the course ID from the event
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
                "name" => $coursename,
            ],
            "group" => [
                "id" => "ecd018621e8aaf7858784f7db08c1d25bf5bb51cb0611e537d0edf661c3e675f",
            ],
        ];

        // Convert data to JSON.
        $postData = json_encode($data);

        // Set up cURL to send the POST request.
        $ch = curl_init('http://44.204.180.159:3000/courses/start');
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