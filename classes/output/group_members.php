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
 * @copyright   2023 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_group_members\output;

use coding_exception;
use context_course;
use core_user\fields;
use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;
use user_picture;

/**
 * Block group_members is defined here.
 *
 * @package     block_group_members
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class group_members implements renderable, templatable {
    /**
     * Default number of maximum members displayed.
     */
    const DEFAULT_MAX_MEMBERS = 5;

    /**
     * @var $groupid
     */
    protected $groupid = null;

    /**
     * @var $maxmembers
     */
    protected $maxmembers = 0;

    /**
     * @var $courseid
     */
    protected $courseid = null;

    /**
     * group_members constructor.
     * Retrieve matching forum posts sorted in reverse order
     *
     * @param int $courseid
     * @param int $groupid
     * @param int $maxmembers
     */
    public function __construct(int $courseid, int $groupid, int $maxmembers = self::DEFAULT_MAX_MEMBERS) {
        $this->groupid = $groupid;
        $this->courseid = $courseid;
        $this->maxmembers = $maxmembers ?: self::DEFAULT_MAX_MEMBERS;
    }

    /**
     * Export featured course data
     *
     * @param renderer_base $output
     * @return object
     * @throws coding_exception
     */
    public function export_for_template(renderer_base $output): object {
        global $PAGE;
        $extrafields = fields::for_identity($PAGE->context, false)->get_required_fields();
        $extrafields[] = 'picture';
        $extrafields[] = 'imagealt';
        // Use of core\user API instead of user_picture::fields().
        $userfields = fields::for_userpic();
        $userfields->including(...$extrafields);

        $allfields = $userfields->get_sql('u', false, '', 'id', false)->selects;
        // Maintain legacy behaviour where the field list was done with 'implode' and no spaces.
        $allfields = str_replace(', ', ',', $allfields);

        $groupmembers = get_enrolled_users(context_course::instance($this->courseid), '', $this->groupid, $allfields);
        $context = new stdClass();
        $context->members = [];
        foreach ($groupmembers as $member) {
            $context->members[] = [
                'picture' => $output->user_picture($member),
                'fullname' => fullname($member),
            ];
        }
        uasort($context->members, function($m1, $m2) {
            return strnatcmp($m1['fullname'], $m2['fullname']);
        });
        $context->memberscount = count($context->members);
        $context->members = array_slice($context->members, 0, $this->maxmembers);
        if ($PAGE->course) {
            global $USER;
            $ccourse = context_course::instance($PAGE->course->id);
            $canaccessallgroups = has_capability('moodle/site:accessallgroups', $ccourse);
            $isingroup = array_intersect_key([$this->groupid => 1], groups_get_all_groups($PAGE->course->id, $USER->id));
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
