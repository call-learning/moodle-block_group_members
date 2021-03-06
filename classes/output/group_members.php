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
 * Block group_members is defined here.
 *
 * @package     block_group_members
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_group_members\output;
defined('MOODLE_INTERNAL') || die();

use context_course;
use context_helper;
use context_module;
use core_course\external\course_summary_exporter;
use moodle_url;
use renderable;
use renderer_base;
use templatable;
use user_picture;

/**
 * Block group_members is defined here.
 *
 * @package     block_group_members
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class group_members implements renderable, templatable {
    /**
     * @var $groupid
     */
    protected $groupid = null;

    /**
     * @var $maxmembers
     */
    protected $maxmembers = 0;

    /**
     * @var $context
     */
    protected $context = null;

    /**
     * Default number of maximum members displayed.
     */
    const DEFAULT_MAX_MEMBERS = 5;

    /**
     * group_members constructor.
     * Retrieve matching forum posts sorted in reverse order
     *
     * @param int $groupid
     * @param bool $maxmembers
     */
    public function __construct($groupid, $maxmembers = false) {
        $this->groupid = $groupid;
        $this->maxmembers = $maxmembers ? $maxmembers : self::DEFAULT_MAX_MEMBERS;
    }

    /**
     * Export featured course data
     *
     * @param renderer_base $renderer
     * @return object
     * @throws \coding_exception
     */
    public function export_for_template(renderer_base $renderer) {
        global $PAGE;
        $extrafields = get_extra_user_fields($PAGE->context);
        $extrafields[] = 'picture';
        $extrafields[] = 'imagealt';
        $allfields = 'u.id, ' . user_picture::fields('u', $extrafields);

        $groupmembers = groups_get_members($this->groupid, $allfields);
        $context = new \stdClass();
        $context->members = [];
        foreach ($groupmembers as $member) {
            $context->members[] = [
                'picture' => $renderer->user_picture($member),
                'fullname' => fullname($member),
            ];
        }
        uasort($context->members, function($m1, $m2) {
            return strnatcmp($m1['fullname'], $m2['fullname']);
        });
        $context->members = array_slice($context->members, 0, $this->maxmembers);
        if ($PAGE->course) {
            global $USER;
            $ccourse = context_course::instance($PAGE->course->id);
            $canaccessallgroups = has_capability('moodle/site:accessallgroups', $ccourse);
            $isingroup = array_intersect_key([$this->groupid], groups_get_all_groups($PAGE->course->id, $USER->id));
            if (!empty($isingroup) || $canaccessallgroups) {
                $group = groups_get_group($this->groupid);
                $context->morelink = (new moodle_url('/user/index.php', array(
                    'id' => $group->courseid,
                    'group' => $this->groupid
                )))->out(false);
            }
        }
        return $context;
    }
}