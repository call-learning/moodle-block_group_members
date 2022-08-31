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
namespace block_group_members;
use advanced_testcase;
use block_group_members\output\group_members;
use coding_exception;

/**
 * Class block_group_members
 *
 * @package     block_group_members
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class group_members_test extends advanced_testcase {
    /**
     * Group member list
     *
     * @throws coding_exception
     * @covers \block_group_members\output\group_members
     */
    public function test_get_group_member_list() {
        global $OUTPUT, $PAGE;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $group = [];
        $group[0] = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $group[1] = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        // 10 users in each groups.
        for ($i = 0; $i < 20; $i++) {
            $user = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->enrol_user($user->id, $course->id);
            groups_add_member($group[rand(0, 1000) % 2]->id, $user);
        }
        $gm = new group_members($course->id, $group[0]->id);
        $context = $gm->export_for_template($PAGE->get_renderer('core'));
        $this->assertCount(5, $context->members);
        $gm = new group_members($course->id, $group[0]->id, 3);
        $context = $gm->export_for_template($OUTPUT);
        $this->assertCount(3, $context->members);
    }

}



