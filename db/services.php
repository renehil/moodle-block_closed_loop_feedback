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
 * Services for closed loop support
 *
 * @package    block_point_view
 * @copyright  2022 Rene Hilgemann
 * @author     Rene Hilgemann <rene.hilgemann@gmx.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'block_closed_loop_support_external' => array(
        'classname'   => 'block_closed_loop_support_external_data',
        'methodname'  => 'update_db',
        'classpath'   => 'blocks/closed_loop_support/externallib.php',
        'description' => 'Update Database due to a request.',
        'type'        => 'write',
        'ajax'        => true
    ),
    'block_closed_loop_support_external' => array(
        'classname'   => 'block_closed_loop_support_external_data',
        'methodname'  => 'read_requests',
        'classpath'   => 'blocks/closed_loop_support/externallib.php',
        'description' => 'Get all unread requests for teacher',
        'type'        => 'read',
        'ajax'        => true
    )
    );