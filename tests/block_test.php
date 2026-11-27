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

namespace block_markingnudge;

#[\PHPUnit\Framework\Attributes\CoversClass(\block_markingnudge::class)]
/**
 * Tests for the Marking nudge block.
 *
 * @package    block_markingnudge
 * @category   test
 * @copyright  2026 Daniel Maloney
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_markingnudge
 */
final class block_test extends \advanced_testcase {
    /**
     * Load the block class.
     */
    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
        require_once($CFG->dirroot . '/blocks/markingnudge/block_markingnudge.php');
        parent::setUpBeforeClass();
    }

    /**
     * Totals for a teacher with marking waiting; nothing for a learner; "all caught up" when there's nothing to do.
     */
    public function test_totals(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('enabled', 1, 'core_competency');
        $gen = $this->getDataGenerator();
        $cgen = $gen->get_plugin_generator('core_competency');
        $course = $gen->create_course();
        $framework = $cgen->create_framework();
        $unit = $cgen->create_competency(['competencyframeworkid' => $framework->get('id'), 'idnumber' => 'U1']);
        $cgen->create_course_competency(['courseid' => $course->id, 'competencyid' => $unit->get('id')]);
        $assign = $gen->create_module('assign', ['course' => $course->id]);
        $cgen->create_course_module_competency(['cmid' => $assign->cmid, 'competencyid' => $unit->get('id')]);
        $teacher = $gen->create_and_enrol($course, 'teacher');
        $learner = $gen->create_and_enrol($course, 'student');

        $this->setUser($teacher);
        // RA and LLN not yet recorded for the learner.
        $data = \block_markingnudge::export(\block_markingnudge::get_totals());
        $this->assertSame(['torecord'], array_column($data['items'], 'key'));
        $this->assertSame('RA/LLN to record', $data['items'][0]['label']);
        $this->assertStringEndsWith('/report/evidencereporter/marking.php', $data['url']);

        // Recorded: all caught up.
        \report_evidencereporter\local\declarations::save(get_fast_modinfo($course)->get_cm($assign->cmid), [
            $learner->id => ['ra' => false, 'radetails' => '', 'lln' => false, 'llndetails' => '']]);
        \cache::make('block_markingnudge', 'totals')->purge();
        $data = \block_markingnudge::export(\block_markingnudge::get_totals());
        $this->assertFalse($data['hasitems'], 'Nothing to do: all caught up');

        $DB->insert_record('assign_submission', ['assignment' => $assign->id, 'userid' => $learner->id,
            'status' => 'submitted', 'latest' => 1, 'attemptnumber' => 0, 'groupid' => 0,
            'timecreated' => time() - 20 * DAYSECS, 'timemodified' => time() - 20 * DAYSECS]);
        \cache::make('block_markingnudge', 'totals')->purge();
        $totals = \block_markingnudge::get_totals();
        $this->assertSame(1, $totals->tomark);
        $this->assertSame(1, $totals->red);
        $data = \block_markingnudge::export($totals);
        $this->assertSame('1', (string) $data['items'][0]['count']);
        $this->assertSame('to mark', $data['items'][0]['label']);
        $this->assertSame('waiting 14+ days', $data['overdue'][0]['label']);

        $this->setUser($learner);
        \cache::make('block_markingnudge', 'totals')->purge();
        $this->assertNull(\block_markingnudge::get_totals(), 'Learners mark nowhere');
        $block = block_instance('markingnudge');
        $this->assertSame('', $block->get_content()->text);
    }

    /**
     * On a course page: that course only, linking to its marking page; on a category page: the courses in it (and
     * its sub-categories); nothing for someone who doesn't mark there.
     */
    public function test_course_and_category(): void {
        $this->resetAfterTest();
        set_config('enabled', 1, 'core_competency');
        $gen = $this->getDataGenerator();
        $cgen = $gen->get_plugin_generator('core_competency');
        $framework = $cgen->create_framework();
        $unit = $cgen->create_competency(['competencyframeworkid' => $framework->get('id'), 'idnumber' => 'U1']);
        $parent = $gen->create_category(['name' => 'Design']);
        $child = $gen->create_category(['name' => 'Term 1', 'parent' => $parent->id]);
        $other = $gen->create_category(['name' => 'Other']);
        $teacher = $gen->create_user();
        $courses = [];
        foreach ([$child, $child, $other] as $i => $category) {
            $course = $gen->create_course(['category' => $category->id]);
            $cgen->create_course_competency(['courseid' => $course->id, 'competencyid' => $unit->get('id')]);
            $assign = $gen->create_module('assign', ['course' => $course->id]);
            $cgen->create_course_module_competency(['cmid' => $assign->cmid, 'competencyid' => $unit->get('id')]);
            $gen->enrol_user($teacher->id, $course->id, 'teacher');
            $gen->create_and_enrol($course, 'student');
            $courses[] = $course;
        }
        $this->setUser($teacher);

        // Each course has one learner with RA and LLN to record.
        $this->assertSame(1, \block_markingnudge::get_totals('course', (int) $courses[0]->id)->torecord);
        $this->assertSame(
            2,
            \block_markingnudge::get_totals('category', (int) $parent->id)->torecord,
            'Both courses in the sub-category'
        );
        $this->assertSame(1, \block_markingnudge::get_totals('category', (int) $other->id)->torecord);
        $this->assertSame(3, \block_markingnudge::get_totals()->torecord, 'Dashboard: every course');
        $data = \block_markingnudge::export(
            \block_markingnudge::get_totals('course', (int) $courses[0]->id),
            'course',
            (int) $courses[0]->id
        );
        $this->assertStringEndsWith('/report/evidencereporter/marking.php?courseid=' . $courses[0]->id, $data['url']);
        $data = \block_markingnudge::export(
            \block_markingnudge::get_totals('category', (int) $parent->id),
            'category',
            (int) $parent->id
        );
        $this->assertStringEndsWith('/report/evidencereporter/marking.php?categoryid=' . $parent->id, $data['url']);

        // Where the block sits decides what it counts.
        $page = new \moodle_page();
        $page->set_course($courses[0]);
        $page->set_pagetype('course-view-topics');
        $this->assertSame(['course', (int) $courses[0]->id], \block_markingnudge::get_scope($page));
        $page = new \moodle_page();
        $page->set_context(\context_coursecat::instance($parent->id));
        $this->assertSame(['category', (int) $parent->id], \block_markingnudge::get_scope($page));
        $page = new \moodle_page();
        $page->set_context(\context_user::instance($teacher->id));
        $page->set_pagetype('my-index');
        $this->assertSame(['my', 0], \block_markingnudge::get_scope($page));

        // A learner on a course page: nothing.
        $learner = $gen->create_and_enrol($courses[0], 'student');
        $this->setUser($learner);
        $this->assertNull(\block_markingnudge::get_totals('course', (int) $courses[0]->id));
        $this->assertNull(\block_markingnudge::get_totals('category', (int) $parent->id));
    }
}
