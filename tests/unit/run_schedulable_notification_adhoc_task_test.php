<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    block_quickmail
 * @copyright  2008 onwards Louisiana State University
 * @copyright  2008 onwards Chad Mazilly, Robert Russo, Jason Peak, Dave Elliott, Adam Zapletal, Philip Cali
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
require_once(dirname(__FILE__) . '/traits/unit_testcase_traits.php');

use block_quickmail\messenger\messenger;
use block_quickmail\persistents\reminder_notification;
use block_quickmail\tasks\run_schedulable_notification_adhoc_task;
use core\task\manager as task_manager;

class block_quickmail_run_schedulable_notification_adhoc_task_testcase extends advanced_testcase {
    
    use has_general_helpers, 
        sets_up_courses, 
        sets_up_notifications,
        sends_emails, 
        sends_messages;
    
    public function test_runs_scheduled_via_adhoc_task()
    {
        // reset all changes automatically after this test
        $this->resetAfterTest(true);
        
        $sink = $this->open_email_sink();

        // set up a course with a teacher and students
        list($course, $user_teacher, $user_students) = $this->setup_course_with_teacher_and_students();

        $reminder_notification = $this->create_reminder_notification_for_course_user('non-participation', $course, $user_teacher, null, [
            'name' => 'My non participation reminder'
        ]);

        \phpunit_util::run_all_adhoc_tasks();

        // should not have run yet
        $this->assertNull($reminder_notification->get('last_run_at'));
        $this->assertNotNull($reminder_notification->get('next_run_at'));

        // should be no tasks fire yet, so no emails
        $this->assertEquals(0, $this->email_sink_email_count($sink));

        $task = new run_schedulable_notification_adhoc_task();

        $task->set_custom_data([
            'notification_id' => $reminder_notification->get_notification()->get('id')
        ]);

        // queue and run job
        task_manager::queue_adhoc_task($task);
        \phpunit_util::run_all_adhoc_tasks();

        // get the updated reminder notification for checking calculating run times
        $updated_reminder_notification = reminder_notification::find_or_null($reminder_notification->get('id'));

        // should have run
        $this->assertNotNull($updated_reminder_notification->get('last_run_at'));
        $this->assertGreaterThan((int) $reminder_notification->get('next_run_at'), (int) $updated_reminder_notification->get('next_run_at'));

        // should have executed the taks, so 4 emails
        $this->assertEquals(4, $this->email_sink_email_count($sink));

        $this->close_email_sink($sink);
    }

}