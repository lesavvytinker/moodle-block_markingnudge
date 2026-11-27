# Marking nudge (block_markingnudge)

A small block for assessors: what's waiting to be marked, with a button to Evidence Reporter's marking page. What it
counts depends on where it sits:

- **Dashboard:** all the assessor's courses.
- **Course page:** that course only.
- **Course category page:** the assessor's courses in that category and its sub-categories.

- **Totals only:** to mark, waiting past the amber and red thresholds (7 and 14 days by default, set in Evidence
  Reporter), need feedback, marked but not released, RA and LLN still to record, Moodle overrides to check, units
  ready to sign off.
- **Go to marking** opens the matching marking page: *All my courses*, the course's grid, or the category's courses.
- **All caught up** when there's nothing to do; nothing at all for users who don't mark there (learners on a course
  page see no block).
- The totals are cached for each user and page for up to five minutes.

It does no work of its own: the numbers come from Evidence Reporter (`report_evidencereporter`, 0.35.0 or later), which
it requires.

## Install

Install Evidence Reporter first, then this block (Site administration > Plugins > Install plugins, or unzip into
`blocks/markingnudge`). Then, with Edit mode on, *Add a block > Marking nudge*:

- **Dashboard:** assessors add it themselves, or an administrator adds it to the default Dashboard for everyone (Site
  administration > Appearance > Default Dashboard page).
- **Course page:** editing teachers and managers can add it. To have it in every new course, add it to the course
  you copy or restore from.
- **Category page:** managers (or administrators) can add it from the category's page (Courses > the category).

Upgrading from 0.2.0: editing teachers get permission to add it on new installs only. On an existing site, allow
*block/markingnudge:addinstance* for the Teacher role (Site administration > Users > Permissions > Define roles), or
add the block to courses as a manager.

## Licence

GNU GPL v3 or later. Copyright 2026 Daniel Maloney.
