# Vacation Tracker (WordPress plugin)

A self-contained WordPress plugin implementing employee vacation requests,
approval routing, balances, notifications, and a privacy-safe team calendar,
built to run on your existing WordPress site (e.g. bkifg.com) with no new
hosting or licensing required.

This maps the original blueprint's structure directly onto WordPress:

| Blueprint concept | This plugin |
|---|---|
| Administrative back end (HR) | `wp-admin` → **Vacation Tracker** menu |
| Employee/Manager front-end portal | The `[vt_app]` shortcode on any page |
| Employee accounts | Native WordPress users, admin-created, roles `vt_employee` / `vt_manager` / `vt_hr_admin` |
| System Administrator | Whoever manages the WordPress site itself (the `Administrator` role already gets full Vacation Tracker access automatically) |
| SharePoint Lists / Dataverse | Custom MySQL tables in your WordPress database (`wp_vt_*`) |
| Power Automate | WP-Cron scheduled tasks + PHP business logic in `includes/` |
| Outlook/Teams notifications | `wp_mail()` (see the SMTP note below - this is the one step you shouldn't skip) |
| Outlook shared calendar | The in-portal "Team Calendar" tab (privacy-safe: name + dates only) |

## 1. Install

1. Zip the `wp-vacation-tracker` folder (the folder itself, so the zip contains `wp-vacation-tracker/vacation-tracker.php` etc.).
2. In WordPress: **Plugins → Add New → Upload Plugin**, choose the zip, install, then **Activate**.
   - Alternative: upload the `wp-vacation-tracker` folder directly into `wp-content/plugins/` via FTP or your host's file manager, then activate it from the Plugins list.
3. Activation automatically creates the plugin's database tables and three new roles (`Vacation Employee`, `Vacation Manager`, `Vacation HR Admin`). Nothing else is touched in your existing site.

## 2. Set up outgoing email (do this before relying on the system)

By default WordPress sends mail through PHP's built-in `mail()` function, which is unreliable on most hosting (frequently marked as spam, sometimes silently dropped). Since this whole system is built around email notifications, install a free SMTP plugin and connect it to a real mailbox before go-live:

1. Install **WP Mail SMTP** (or similar) from Plugins → Add New.
2. Connect it to a mailbox you control - Gmail/Google Workspace, Microsoft 365, or a transactional provider (Brevo, SendGrid, Mailgun all have free tiers that comfortably cover ~100 employees' worth of notifications).
3. Send a test email from that plugin's settings page to confirm delivery.

## 3. Make sure the scheduled jobs actually run

WordPress's default "cron" only fires when someone visits the site, which is fine for a busy public site but unreliable for a job that must run every day regardless of traffic (reminders, escalations, nightly balance reconciliation). Ask your host to add a real server-side cron job hitting:

```
wget -q -O /dev/null "https://your-site.com/wp-cron.php?doing_wp_cron" >/dev/null 2>&1
```

...once every 15–60 minutes. Most hosts have a "Cron Jobs" panel for this. (If you're not sure how, ask your host's support - this is a very standard request.)

## 4. Create the employee/manager portal page

1. Create a new WordPress Page (e.g. titled "Vacation Tracker" or "My Vacation").
2. Add the shortcode `[vt_app]` as the page content.
3. Publish it, and add it to your site's navigation menu so employees can find it.

The first time this page loads, the plugin remembers its Page ID so email links point back to it correctly.

## 5. Add your employees

Go to **wp-admin → Vacation Tracker → Employees → Add Employee**. For each person:

- Enter their name, work email, department, entitlement, work schedule, etc.
- Leave "Existing WP User ID" blank to have the plugin create a brand-new WordPress account for them - they'll immediately receive WordPress's standard "set your password" email, so nobody's password is ever sent in plain text.
- Check "Manager" if they should approve their team's requests, and/or "HR Admin" if they need full back-end access.
- Set **Primary Team Lead** to the *Employee ID* (shown in the Employees list) of their approver - this is what drives routing, so get this right for everyone before go-live.

Also set up, in this order, before employees start submitting requests:

1. **Departments** (Vacation Tracker → Departments) - name, manager, backup approver, max-employees-away.
2. **Holidays** (Vacation Tracker → Holidays) - your company holiday list, so those days aren't charged against vacation.
3. **Settings** (Vacation Tracker → Settings) - reminder/escalation timing, long-request threshold, whether over-balance requests are allowed, etc. Sensible defaults are pre-filled.

## 6. What employees/managers see

The `[vt_app]` page shows tabs based on the logged-in user's role:

- **My Vacation** - balance tiles, pending requests, upcoming approved vacation, request history.
- **New Request** - date pickers with a live working-days/balance preview, submit or save as draft.
- **Team Calendar** - privacy-safe "Name - Away" entries only (no leave type, no comments, no balances).
- **Approvals** (Managers/HR only) - pending approvals with Approve/Reject/Request-Info, plus a self-service delegation form for when they're away.

## 7. Simplifications vs. the original design blueprint

This is a genuine, working implementation of the core system, but a few things were deliberately simplified to keep the first version buildable in one pass. None of these are hard to add later - flag them if you need them sooner:

- **Half-day granularity only** - no hourly leave requests yet.
- **Escalation reassigns rather than "expires"** - an unresponsive approval reassigns to a backup/department manager/HR after the configured window, rather than the request separately expiring.
- **One shared calendar view**, not per-department calendars.
- **Executive-approver routing** works (set the "Executive" checkbox on an employee and the Employee ID of the designated approver in Settings → `executive_approver_employee_id`), but a few of the rarer combined-condition routing edge cases from the blueprint are simplified to the core rules (long request → department, over-balance/blackout → HR, a manager's own request → their department manager). The routing engine (`includes/class-vt-requests.php`) is centralized in one place, so refining these rules later is a contained change.
- **No calendar-event ID / external calendar sync** - since the calendar is rendered live from the requests table itself, there's no separate event object to keep in sync or clean up (this removes an entire class of bugs the Outlook-calendar version of this design had to guard against).

## 8. Where things live (for future changes)

- `includes/class-vt-activator.php` - database schema, roles, default settings.
- `includes/class-vt-calc.php` - chargeable-day calculation (weekends, holidays, half-days, cross-year splitting).
- `includes/class-vt-balances.php` - stored balances, idempotent updates, nightly reconciliation, annual rollover.
- `includes/class-vt-requests.php` - the request state machine and approval-routing engine. Start here for any routing-rule change.
- `includes/class-vt-notifications.php` - every email template.
- `includes/class-vt-cron.php` - reminders, escalation, reconciliation scheduling.
- `includes/class-vt-ajax.php` - front-end actions.
- `includes/class-vt-shortcodes.php` - the `[vt_app]` portal markup.
- `includes/class-vt-admin.php` - the wp-admin HR/back-end console.
