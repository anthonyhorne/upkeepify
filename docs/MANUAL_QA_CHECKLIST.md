# Manual QA Checklist

## Resident Submission To Contractor Invite

Use this checklist before tagging or deploying changes that touch public task submission, trustee approval, provider invitations, or resident confirmation.

- Enable **Allow Public Task Logging** in Upkeepify settings.
- Visit a page containing `[upkeepify_task_form]` as a logged-out visitor.
- Submit a task with title, description, nearest unit, category, type, math captcha, optional GPS fields, optional photo, and optional resident email.
- Confirm the new maintenance task is created as `pending`, not `publish`.
- Confirm the task has only resident-facing taxonomy values from the form: `task_category` and `task_type`.
- Confirm the task status is automatically set to `Open`.
- Confirm no provider response posts or contractor emails are created while the task is still `pending`.
- Publish the task from the WordPress admin.
- Confirm one provider response post is created per matching service provider.
- Confirm contractor invite emails are sent when matching providers have valid email addresses and the provider response page setting is configured.
- Edit and update the already-published task.
- Confirm provider response posts are not duplicated on later updates.
- Submit a contractor acceptance and ballpark estimate.
- In the task edit screen, confirm the **Trustee Lifecycle** panel shows the estimate and an **Approve estimate** action.
- Approve the estimate and confirm the contractor receives an email that unlocks the formal quote step.
- Submit a contractor formal quote.
- In the **Trustee Lifecycle** panel, approve the quote and confirm the contractor receives an email that unlocks completion proof.
- If resident email was supplied, complete the contractor flow through completion proof and confirm the resident confirmation email is sent.
- Submit a resident "No, there is an issue" response and confirm the trustee receives a review email instead of a lifecycle-closed email.
- With "Notify Contractor When Resident Reports an Issue" enabled, confirm the contractor receives the follow-up email and their token link opens a follow-up note/photo form.
- Submit the contractor follow-up and confirm the trustee receives the follow-up review email; the contractor should not be able to close the lifecycle from that link.
