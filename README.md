# Marking nudge (block_markingnudge)

A small Dashboard block for assessors: what's waiting across their courses, with a button to Evidence Reporter's
marking page.

- **Totals only:** to mark, waiting past the amber and red thresholds (7 and 14 days by default, set in Evidence
  Reporter), need feedback, marked but not released, RA and LLN still to record, reasonable adjustments to confirm, units ready
  to sign off.
- **Go to marking** opens the marking page's *All my courses* view; each course opens its grid.
- **All caught up** when there's nothing to do; nothing at all for users who don't mark anywhere.
- The totals are cached for each user for up to five minutes.

It does no work of its own: the numbers come from Evidence Reporter (`report_evidencereporter`, 0.26.0 or later), which
it requires.

## Install

Install Evidence Reporter first, then this block (Site administration > Plugins > Install plugins, or unzip into
`blocks/markingnudge`). Assessors add it to their Dashboard (Edit mode > Add a block > Marking nudge), or an
administrator adds it to the default Dashboard for everyone (Site administration > Appearance > Default Dashboard
page).

## Licence

GNU GPL v3 or later. Copyright 2026 Daniel Maloney.
