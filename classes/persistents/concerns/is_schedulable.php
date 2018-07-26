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

namespace block_quickmail\persistents\concerns;

use block_quickmail\persistents\schedule;

trait is_schedulable {

    // last_run_at
        // timestamp of last time this schedulable was run
        // defaults to NULL
        // gets set/updated after schedulable is run
    // next_run_at
        // timestamp of next time this schedulable should run
        // defaults to NULL
        // gets set after persistent is created
        // if null, schedulable will not run
    // is_running
        // boolean that indicates whether or not this schedulable is running
        // defaults to false,
        // gets set to true when schedulable fires according to schedule
        // gets set to false after schedulable has fired

    ///////////////////////////////////////////////
    ///
    ///  RELATIONSHIPS
    /// 
    ///////////////////////////////////////////////

    /**
     * Returns the schedule object for this schedulable persistent, if any
     *
     * @return stdClass
     */
    public function get_schedule() 
    {
        return schedule::find_or_null($this->get('schedule_id'));
    }

    ///////////////////////////////////////////////
    ///
    ///  PERSISTENT HOOKS
    /// 
    ///////////////////////////////////////////////

    /**
     * Take appropriate actions after creating a new schedulable, including:
     *   
     *   - calculate and set next run time
     * 
     * @return void
     */
    protected function after_create()
    {
        $this->set_next_run_time();
    }

    ///////////////////////////////////////////////
    ///
    ///  GETTERS
    /// 
    ///////////////////////////////////////////////

    /**
     * Returns the last_run_at time as an int
     * 
     * @return mixed  (returns int, or null if not set)
     */
    public function get_last_run_time()
    {
        return empty($this->get('last_run_at'))
            ? null
            : (int) $this->get('last_run_at');
    }

    /**
     * Returns the next_run_at time as an int
     * 
     * @return mixed  (returns int, or null if not set)
     */
    public function get_next_run_time()
    {
        return empty($this->get('next_run_at'))
            ? null
            : (int) $this->get('next_run_at');
    }

    /**
     * Reports whether or not this schedulable is being run
     * 
     * @return bool
     */
    public function is_running()
    {
        return (bool) $this->get('is_running');
    }

    ///////////////////////////////////////////////
    ///
    ///  METHODS
    /// 
    ///////////////////////////////////////////////

    /**
     * Sets the next_run_at for this schedulable
     *
     * If schedulable has not run yet, sets to begin time of schedule
     * If schedulable has been run already, calculates next time according to schedule
     *
     * @return void
     */
    public function set_next_run_time()
    {
        $schedule = $this->get_schedule();

        $next_run_time = empty($this->get_last_run_time())
            ? $schedule->get_begin_time()
            : $schedule->calculate_next_time_from($this->get_next_run_time());

        $this->set('next_run_at', $next_run_time);
        $this->update();
    }

    /**
     * Sets the last_run_at for this schedulable to the current time
     *
     * @return void
     */
    public function set_last_run_time()
    {
        $this->set('last_run_at', time());
        $this->update();
    }

    /**
     * Updates the sending status of this schedulable
     * 
     * @param  bool  $is_running  running status
     * @return void
     */
    public function toggle_running_status($is_running)
    {
        $this->set('is_running', (int) $is_running);
        $this->update();
    }

    /**
     * Executes standard "pre-run" actions for a schedulable
     * 
     * @return void
     */
    public function handle_schedule_pre_run_actions()
    {
        $this->toggle_running_status(true);
    }

    /**
     * Executes standard "post-run" actions for a schedulable
     * 
     * @return void
     */
    public function handle_schedule_post_run_actions()
    {
        $this->set_last_run_time();
        
        $this->set_next_run_time();

        $this->toggle_running_status(false);
    }

}