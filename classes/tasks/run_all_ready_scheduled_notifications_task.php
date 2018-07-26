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

namespace block_quickmail\tasks;

use core\task\scheduled_task;
use block_quickmail\persistents\notification;
use block_quickmail\tasks\run_schedulable_notification_adhoc_task;
use core\task\manager as task_manager;

class run_all_ready_scheduled_notifications_task extends scheduled_task {
    
    public function get_name()
    {
        // Shown in admin screens
        return 'run all scheduled notifications'; // convert to lang string
    }

    /*
     * This tasks queries for all schedulable notifications that should be fired at the current time
     * and initiates queueing of each
     *
     * Required custom data: none
     */
    public function execute()
    {
        // fetch all schedulables that should be fired right now
        foreach (notification::get_all_ready_schedulables() as $notification) {

            // fire a task for each
            $task = new run_schedulable_notification_adhoc_task();

            $task->set_custom_data([
                'notification_id' => $notification->get('id')
            ]);

            // queue job
            task_manager::queue_adhoc_task($task);
        }
    }

}