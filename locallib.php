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
 * Point of View external lib
 *
 * @package    block_closed_loop_support
 * @copyright  2022 Rene Hilgemann
 * @author     Rene Hilgemann <rene.hilgemann@gmx.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Check if new requests exists for teacher in respect to teacher last request check
 * and return them
 * 
 * @return boolean Description
 *
*/

defined('MOODLE_INTERNAL') || die();


function block_closed_loop_support_get_new_requests_teacher_ids(int $userid, int $courseid = -1){
    global $DB;
    
    $tableTimestamp = 'block_closed_loop_teacher';
    $conditions = array('userid' => $userid, 'courseid' => $courseid);
    $counter = 0;
    if($courseid === -1){
        
        return $DB->get_records($tableTimestamp, ['userid' => $userid]);
    }
    else{
        return $DB->get_records($tableTimestamp, $conditions);
    }
}


function block_closed_loop_support_get_new_requests_teacher(int $userid, int $courseid = -1){
    global $DB, $CFG;
    
    $tableTimestamp = 'block_closed_loop_teacher';
    $conditions = array('userid' => $userid, 'courseid' => $courseid);
    $counter = 0;
    if($courseid === -1){
        
        $counter = count($DB->get_records($tableTimestamp, ['userid' => $userid]));
    }
    else{
        $counter = count($DB->get_records($tableTimestamp, $conditions));
    }
    if($counter > 1){
        $text = get_string('newRequests', 'block_closed_loop_support', $counter);
        $btnClass = "btn-warning";
    }
    else if ($counter === 1){
        $text = get_string('newRequest', 'block_closed_loop_support');
        $btnClass = "btn-warning";
    }
    else{
        $text = get_string('noRequest', 'block_closed_loop_support');
        $btnClass = "btn-info";
    }
    $url = new moodle_url("{$CFG->wwwroot}/blocks/closed_loop_support/request_overview.php", 
                    array('courseid'=> $courseid));
    
    return ['InfoText' => $text,
        'BtnClass' => $btnClass,
        'Url' => $url->out()];
}



function block_closed_loop_support_set_requests_viewed(int $userid, int $courseid = -1){
    global $DB;
    $tableTeacher = 'block_closed_loop_teacher';
    $conditions = array('userid' => $userid, 'courseid' => $courseid);
    
    
    if($courseid === -1){
        
        $DB->delete_records($tableTeacher, ['userid' => $userid]);
    }
    else{
        $DB->delete_records($tableTeacher, $conditions);
    }
}


function block_closed_loop_support_write_request(int $userid, int $courseid, int $cmid){
    global $DB;
    $table = 'block_closed_loop_support';
    $tableTeacher = 'block_closed_loop_teacher';
    $conditions = array('courseid' => $courseid, 'moduleid' => $cmid, 'userid' => $userid);
    $time = new DateTime("now", core_date::get_user_timezone_object());
    $timeStamp = $time->getTimestamp();

    if (!$DB->record_exists($table, $conditions)) {
        $counter = 1;
    } else {
        $counter = $DB->get_field($table, 'counter', $conditions) + 1;
    }

    $dataobject = array(
        'userid' => $userid,
        'courseid' => $courseid,
        'moduleid' => $cmid,
        'counter' => $counter,
        'timestamp' => $timeStamp
    );

    $requestid = $DB->insert_record($table, $dataobject);

    //Update unread for course-teacher
    $role = $DB->get_record('role', array('shortname' => 'editingteacher'));
    $context = get_context_instance(CONTEXT_COURSE, $courseid);
    $teachers = get_role_users($role->id, $context);
    $data = [];
    foreach ($teachers as $teacher) {
        $data[] = ['courseid' => $courseid, 'requestid' => $requestid, 'userid' => $teacher->id];
    }

    $DB->insert_records($tableTeacher, $data);
    
    // Trigger event, request generated (for log)
    $contextModule = get_context_instance(CONTEXT_MODULE, $cmid);
    $eventparams = array('courseid' => $courseid, 'userid' =>$userid, 'contextid' => $contextModule->id);
    $event = \block_closed_loop_support\event\module_request_generated::create($eventparams);
    $event->trigger();
}


function block_closed_loop_support_delete_response($courseid, $moduleids = NULL){
    global $DB;
    $tableResponse = 'block_closed_loop_response';
    $requestids = [];
    if($moduleids === NULL){
        $requestids = $DB->get_records('block_closed_loop_support', ['courseid' => $courseid]);
        $DB->delete_records($tableResponse, ['courseid' => $courseid]);
    }
    else{
        foreach ($moduleids as $moduleid){
            $requestids = array_merge($requestids, 
                    $DB->get_records('block_closed_loop_support', 
                    ['courseid' => $courseid, 'moduleid' => $moduleid]));
            $DB->delete_records($tableResponse, ['courseid' => $courseid, 'moduleid' => $moduleid]);
        }

    }
    
    foreach($requestids as $rid){
        $DB->delete_records('block_closed_loop_teacher', ['requestid' => $rid->id]);
        $DB->delete_records('block_closed_loop_support', ['id' => $rid->id]);
    }
}


function block_closed_loop_support_set_response($courseid, $moduleid = -1, $value = 0){
    global $DB;
    $tableResponse = 'block_closed_loop_response';
    if($moduleid === -1){
        $DB->set_field($tableResponse, 'setresponse', $value, ['courseid' => $courseid]);
    }
    else{
        $DB->set_field($tableResponse, 'setresponse', $value, ['courseid' => $courseid, 'moduleid' => $moduleid]);
    }
}


function block_closed_loop_support_create_response($courseid, $moduleids = NULL, $value = 0){
    global $DB;
    $tableResponse = 'block_closed_loop_response';
    $data = [];
    if(is_null($moduleids)){
        $res = $DB->get_records('course_modules', ['course' => $courseid]);
        foreach($res as $r){
            $data[] = ['courseid' => $courseid, 'moduleid' => $r->id, 'set' => $value];
        }
    }
    else{
        foreach($moduleids as $modid){
            $data[] = ['courseid' => $courseid, 'moduleid' => $modid, 'set' => $value];
        }
    }

    $DB->insert_records($tableResponse, $data);
}


function block_closed_loop_support_set_response_config($courseid, $moduleid, $formdata){
        global $DB, $CFG;
        $cond = ['courseid' => $courseid, 'moduleid' => $moduleid];
        block_closed_loop_support_set_response($courseid, $moduleid, 1);
        
        $textCorrect = file_save_draft_area_files(
            $formdata->config_text['itemid'],
            context_course::instance($courseid)->id,
            'block_closed_loop_support',
            'content',
            0,
            array('subdirs' => true),
            $formdata->config_text['text']
        );

        $formdata->config_text['text'] = $textCorrect;
        $DB->set_field('block_closed_loop_response', 'config', base64_encode(serialize($formdata)) , $cond);
        
        
}


function block_closed_loop_support_get_response_config($courseid, $moduleid){
        global $DB, $CFG;
        require_once($CFG->libdir . '/filelib.php');
        $rec = ($DB->get_record('block_closed_loop_response', 
                ['courseid' => $courseid, 'moduleid' => $moduleid]))->config;
        $contentOriginal = unserialize(base64_decode($rec));
        
        if(!$contentOriginal){
            return null;
        }
        $correctText = file_rewrite_pluginfile_urls(
                $contentOriginal->config_text['text'],
                'pluginfile.php',
                context_course::instance($courseid)->id,
                'block_closed_loop_support',
                'content',
                null
        );
        $contentOriginal->config_text['text'] = $correctText;
        return $contentOriginal;
}


function block_closed_loop_support_get_response_content($courseid, $moduleid){
        global $DB;
        $content = block_closed_loop_support_get_response_config($courseid, $moduleid);
        $cms = get_fast_modinfo($courseid);
        $cm = $cms->get_cm($moduleid);
        $cm->get_formatted_name();
        $title = 'Response for helprequest<br>Course/Module:    ' . get_course($courseid)->fullname. 
                '/'.$cm->get_formatted_name();
        
        return ['title' => $title,'content' => $content->config_text['text']];
}


function block_closed_loop_support_get_responselist($courseid) {
        global $DB;
        $dataResponse = $DB->get_records('block_closed_loop_response', 
                ['courseid' => $courseid, 'setresponse' => 1], '', 'id, courseid, moduleid');
        return $dataResponse;
}


function block_closed_loop_support_get_responselist_html($courseid) {
        global $DB, $CFG;
        $dataResponse = $DB->get_records('block_closed_loop_response', ['courseid' => $courseid], '', 'moduleid, setresponse');
        $col_array = array_column($dataResponse, 'setresponse', 'moduleid');
        $coursename = get_course($courseid)->fullname;
        $cms = get_fast_modinfo($courseid);
        $iconSet = '<i class="icon fa fa-check text-success fa-fw " '
                . 'title="Set" aria-label="Set"></i>';//$OUTPUT->pix_icon('i/valid', 'Set');
        $iconNotSet = '<i class="icon fa fa-times text-danger fa-fw " '
                . 'title="Not set" aria-label="Not set"></i>';//$OUTPUT->pix_icon('i/invalid', 'Not set');
        
        //TODO: more nice!
        $output = "";
        $sectMod = [];
        foreach ($dataResponse as $response){
            $cm = $cms->get_cm($response->moduleid);
            if(!$cm->get_user_visible()){
                continue;
            }
            if(empty($sectMod[$cm->sectionnum])){
                $sectMod[$cm->sectionnum] = array($response->moduleid);
            }
            else{
                $sectMod[$cm->sectionnum][] = $response->moduleid;
            } 
        }
        ksort($sectMod);
        $keys = array_keys($sectMod);
        $counter = 0;
        $output .= html_writer::start_tag('ul', ['class' => 'scrollListUL', 'style'=>'list-style-type:none;']);
        foreach ($sectMod as $sect){
            $sectionItem = get_section_name($courseid, $keys[$counter]);
            $output .= html_writer::start_tag('li'). $sectionItem;
            $sequenceString = $DB->get_field('course_sections', 'sequence', ['course' => $courseid,'section' => $keys[$counter]]);
            $secArrayAllMod = explode(',', $sequenceString);
            $items = [];
            foreach ($secArrayAllMod as $mod){
                if(in_array($mod, $sect)){
                    $url = new moodle_url("{$CFG->wwwroot}/blocks/closed_loop_support/set_response_view.php", 
                    ['courseid' => $courseid, 
                        'sectionid' => $keys[$counter], 
                        'moduleid' => $mod]);
                    $cm = $cms->get_cm($mod);
                    $link = html_writer::link($url, $cm->get_formatted_name());
                    $icon = $col_array[$mod] == 1 ? $iconSet : $iconNotSet;
                    $items[] = $link . $icon ;
                }
            }
            $output .= html_writer::alist($items,['style'=>'list-style-type:none;']);
            $output .= html_writer::end_tag('li');
            $counter += 1;
        }
        $output .= html_writer::end_tag('ul');
        return $output;
}



