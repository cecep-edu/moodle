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
 * Defines the class for the Message My Teacher block
 *
 * @package    block_messageteacher
 * @author     Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @copyright  2010 onwards Tauntons College, UK
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 *  Class definition for the Message My Teacher block
 */

function get_course_name() {
    global $DB, $COURSE;
    $course = $DB->get_record_sql(
        'SELECT fullname FROM mdl_course WHERE id = ?', array($COURSE->id)
    );

    return $course->fullname;
}

function get_people($id,$fullname,$img){
	$url = new moodle_url('/message/discussion.php', array('id' => $id, 'cname' => get_course_name()));
	$picture = "<img class='userpicture defaultuserpic' width='50' height='50' title='Imagen de $fullname ' alt='Imagen de $fullname' src='$img'>";
	$name = html_writer::tag('span', $fullname);
	$button = html_writer::tag('a', "Enviar incidencia", array('href' => $url, 'class' => 'btn'));
	return html_writer::tag('a', $picture.$name.$button, array('href' => $url));
}
class block_messageteacher extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_messageteacher');
    }

    /**
     * Gets a list of "teachers" with the defined role, and displays a link to message each
     *
     * @access public
     * @return void
     */
    public function has_config(){
        return true;
    }
    public function get_content() {
        global $COURSE, $CFG, $USER, $DB, $OUTPUT;

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        $usegroups = get_config('block_messageteacher', 'groups');
        $coursehasgroups = groups_get_all_groups($COURSE->id);

        $roles = explode(',', get_config('block_messageteacher', 'roles'));
        list($usql, $uparams) = $DB->get_in_or_equal($roles);
        $params = array($COURSE->id, CONTEXT_COURSE);
        $select = 'SELECT DISTINCT u.id, u.firstname, u.lastname, u.picture, u.imagealt, u.email ';
        $from = 'FROM {role_assignments} ra
            JOIN {context} AS c ON ra.contextid = c.id
            JOIN {user} AS u ON u.id = ra.userid ';
        $where = 'WHERE ((c.instanceid = ? AND c.contextlevel = ?)';
        if (get_config('block_messageteacher', 'includecoursecat')) {
            $params = array_merge($params, array($COURSE->category, CONTEXT_COURSECAT));
            $where .= ' OR (c.instanceid = ? AND c.contextlevel = ?))';
        } else {
            $where .= ')';
        }
        $params = array_merge($params, array($USER->id), $uparams);
        $where .= 'AND userid != ? AND roleid '.$usql;
        $order = ' ORDER BY u.firstname ASC, u.lastname';

        if ($teachers = $DB->get_records_sql($select.$from.$where.$order, $params)) {
            if ($usegroups && $coursehasgroups) {
                try {
                    $groupteachers = array();
                    $usergroupings = groups_get_user_groups($COURSE->id, $USER->id);
                    if (empty($usergroupings)) {
                        throw new Exception('nogroupmembership');
                    } else {
                        foreach ($usergroupings as $usergroups) {
                            if (empty($usergroups)) {
                                throw new Exception('nogroupmembership');
                            } else {
                                foreach($usergroups as $usergroup) {
                                    foreach ($teachers as $teacher) {
                                        if (groups_is_member($usergroup, $teacher->id)) {
                                            $groupteachers[$teacher->id] = $teacher;
                                        }
                                    }
                                }
                            }
                        }
                        if (empty($groupteachers)) {
                            throw new Exception('nogroupteachers');
                        } else {
                            $teachers = $groupteachers;
                        }
                    }
                } catch (Exception $e) {
                    $this->content->text = get_string($e->getMessage(), 'block_messageteacher');
                    return $this->content;
                }
            }

            $items = array();
            foreach ($teachers as $teacher) {
                $url = new moodle_url('/message/discussion.php', array('id' => $teacher->id, 'cname' => get_course_name()));
                $picture = '';
                if (get_config('block_messageteacher', 'showuserpictures')) {
                    $picture = new user_picture($teacher);
                    $picture->link = false;
                    $picture->size = 50;
                    $picture = $OUTPUT->render($picture);
                }
                $name = html_writer::tag('span', fullname($teacher) . " <b>(Docente)</b>");
                $button = html_writer::tag('a', "Enviar mensaje", array('href' => $url, 'class' => 'btn'));
                $items[] = html_writer::tag('a', $picture.$name.$button, array('href' => $url));
            }
            $items[] = get_people(2, "<b>Soporte Técnico</b>", "http://virtual.iaen.edu.ec/theme/image.php?theme=iaen&component=core&image=i%2Fsettings");

            $this->content->text = html_writer::alist($items);
        }
        
        return $this->content;
    }
}
