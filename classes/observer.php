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
 * event-oberserver class 
 *
 * @package    block_closed_loop_support
 * @copyright  2022 Rene Hilgemann
 * @author     Rene Hilgemann <rene.hilgemann@gmx.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_closed_loop_support_observer{
    
    public static function viewed(\block_closed_loop_support\event\course_requests_viewed $event){
        require_once(__DIR__ . '/../locallib.php');
        
        block_closed_loop_support_set_requests_viewed($event->userid, $event->courseid);
        
    }
    
    public static function module_added(\core\event\course_module_created $event){
        require_once(__DIR__ . '/../locallib.php');
        block_closed_loop_support_create_response($event->courseid, [$event->objectid]);
        
    }
    
    public static function module_deleted(\core\event\course_module_deleted $event){
        require_once(__DIR__ . '/../locallib.php');
        block_closed_loop_support_delete_response($event->courseid, [$event->objectid]);
        
    }
}