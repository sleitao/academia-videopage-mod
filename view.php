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
 * Videopage module version information
 *
 * @package mod_videopage
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/mod/videopage/lib.php');
require_once($CFG->dirroot.'/mod/videopage/locallib.php');
require_once($CFG->libdir.'/completionlib.php');

$id      = optional_param('id', 0, PARAM_INT); // Course Module ID
$p       = optional_param('p', 0, PARAM_INT);  // Videopage instance ID
$inpopup = optional_param('inpopup', 0, PARAM_BOOL);

if ($p) {
    if (!$videopage = $DB->get_record('videopage', array('id'=>$p))) {
        print_error('invalidaccessparameter');
    }
    $cm = get_coursemodule_from_instance('videopage', $videopage->id, $videopage->course, false, MUST_EXIST);

} else {
    if (!$cm = get_coursemodule_from_id('videopage', $id)) {
        print_error('invalidcoursemodule');
    }
    $videopage = $DB->get_record('videopage', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/videopage:view', $context);

// Completion and trigger events.
videopage_view($videopage, $course, $cm, $context);

$PAGE->set_url('/mod/videopage/view.php', array('id' => $cm->id));

$options = empty($videopage->displayoptions) ? array() : unserialize($videopage->displayoptions);

if ($inpopup and $videopage->display == RESOURCELIB_DISPLAY_POPUP) {
    $PAGE->set_pagelayout('popup');
    $PAGE->set_title($course->shortname.': '.$videopage->name);
    $PAGE->set_heading($course->fullname);
} else {
    $PAGE->set_title($course->shortname.': '.$videopage->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($videopage);
}
echo $OUTPUT->header();
if (!isset($options['printheading']) || !empty($options['printheading'])) {
    echo $OUTPUT->heading(format_string($videopage->name), 2);
}

if (!empty($options['printintro'])) {
    if (trim(strip_tags($videopage->intro))) {
        echo $OUTPUT->box_start('mod_introbox', 'videopageintro');
        echo format_module_intro('videopage', $videopage, $cm->id);
        echo $OUTPUT->box_end();
    }
}

$content = file_rewrite_pluginfile_urls($videopage->content, 'pluginfile.php', $context->id, 'mod_videopage', 'content', $videopage->revision);
$formatoptions = new stdClass;
$formatoptions->noclean = true;
$formatoptions->overflowdiv = true;
$formatoptions->context = $context;
$content = format_text($content, $videopage->contentformat, $formatoptions);
echo $OUTPUT->box($content, "generalbox center clearfix");

$strlastmodified = get_string("lastmodified");
echo "<div class=\"modified\">$strlastmodified: ".userdate($videopage->timemodified)."</div>";

echo $OUTPUT->footer();
