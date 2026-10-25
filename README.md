# AGRI-SED

## Database migration

Before deploying the review timestamp feature, apply
[`system1/migrations/20261025_add_reviewed_at.sql`](system1/migrations/20261025_add_reviewed_at.sql)
to the `agrised` database. It adds a nullable `reviewed_at` timestamp to
`farmer_submissions`; it is populated when an admin accepts or rejects a
pending submission.

Apply
[`system1/migrations/20261025_create_submission_appeals.sql`](system1/migrations/20261025_create_submission_appeals.sql)
as well to enable farmer appeals. Appeals are linked to rejected submissions,
and admins can accept an appeal to return the submission to pending review or
deny it.

Apply
[`system1/migrations/20261025_create_user_reports.sql`](system1/migrations/20261025_create_user_reports.sql)
to enable the separate user bug/UX feedback report queue. Admins can mark
reports resolved; rejected-submission appeals remain a separate workflow.

Apply
[`system1/migrations/20261025_add_report_attachments_and_admin_comments.sql`](system1/migrations/20261025_add_report_attachments_and_admin_comments.sql)
to enable report images and admin comments on both reports and appeals. Both
admins and farmers can submit bug/UX reports with an optional JPG, PNG, GIF, or
WebP image (up to 5 MB). Admin comments are shown to the report or appeal
submitter.

Apply
[`system1/migrations/20261025_add_submission_pause.sql`](system1/migrations/20261025_add_submission_pause.sql)
to enable pausing approved submissions. Paused submissions are omitted from
benchmarks; admins can resume them or permanently delete a submission and its
appeal history.