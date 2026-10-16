# Phase 4 — User Acceptance Testing (UAT) Report

**System:** DA-MIMAROPA Fleet Management System (Laravel 13)
**Test date:** October 16, 2026
**Environment:** isolated QA copy at `http://localhost:8001`. It uses its own database (`FleetDB_qa`) with synthetic records. Debug pages were turned off, so error screens looked as they would in production.
**Executed by:** the developer, using Claude Code. The tester played each role in separate browser sessions and also sent direct requests.
**Live data:** the live database (`FleetDB`) was only **read**, never changed. Nothing was deployed.

> **What this report is and isn't.** Every result below was **executed and observed** in the QA environment or by automated tests. It is **developer-run acceptance testing**. **No intended users have taken part yet**, and **no stakeholder has approved the system**. The personnel checklist and the feedback form in [UAT_CHECKLIST.md](UAT_CHECKLIST.md) are prepared for that next step.

---

## 1. Summary of UAT readiness

| | |
|---|---|
| Scenarios in the matrix | **98**: 95 executed, 3 not tested |
| Passed on the first run | **71** |
| Failed on the first run, then **fixed and re-tested (PASS)** | **15** |
| Still failing (not fixed: needs a business decision or a database change) | **4** (one is Low) |
| Blocked (behaviour observed, but the business rule is undefined) | **5** |
| Not tested | **3** |
| Open decisions for the agency | **12** (D1–D12, section 8) |
| Automated tests after fixes | **98 / 98 pass on MySQL**; on SQLite 93 pass and 5 are skipped because they need MySQL |
| Stakeholder participation / approval | **Pending — none yet** |

**Bottom line**
- **Core workflows work end to end**: requesting, approving, ticketing and completing trips, vehicle and driver records, assignment, maintenance, fleet cards, dashboards and reports.
- **Role and province permissions held** in every direct-URL and direct-request test.
- **The one-vehicle/one-driver rule is enforced.** Each vehicle has one primary driver, and per-trip double-booking of a vehicle or driver is rejected by the server.
- **Three High-severity defects** were found and **fixed**:
  - one click on "Decline" permanently declined a request with no reason;
  - a Super Admin could delete their own account (and therefore the last one);
  - deleting a driver silently erased them from approved trips.
- **Twelve questions need a decision** from the agency (section 8). They cover the 5 blocked scenarios, 3 of the 4 open failures, and 4 rules to confirm.

**Recommendation: PENDING — INSUFFICIENT TESTING OR APPROVAL.**
- The developer-side UAT is complete, and every defect that could safely be fixed is fixed and re-tested.
- Acceptance still needs two things:
  1. intended users completing the checklist and questionnaire;
  2. the decisions listed in section 8.

---

## 2. Modules and workflows reviewed (as actually implemented)

### Roles

The roles are defined in `Modules/Admin/Enums/UserRole.php`.

| Capability | Super Admin | Admin (one province) | Driver |
|---|:-:|:-:|:-:|
| Sign in, My Profile | ✓ | ✓ | ✓ |
| Manage Admin / Super Admin accounts | ✓ | — | — |
| Register, edit, delete vehicles; inspections; maintenance schedule (PMS) | ✓ all provinces | ✓ own province | — |
| Add, edit, delete drivers | ✓ | ✓ own province | — |
| Assign primary driver to vehicle | ✓ | ✓ own province | — |
| Review/approve/decline travel requests; create manual trips; log Part B | ✓ | ✓ own province | — |
| Maintenance, vehicle reports review, fleet cards, destinations, approving officers | ✓ | ✓ own province | — |
| Dashboard and reports | ✓ all or one province | ✓ own province | Own dashboard only |
| Content settings (logo, module switches, admin announcement) | ✓ | Driver announcement and driver card toggle only | — |
| Travel History, Report Vehicle Issue | — | — | ✓ |
| **Public, no login:** request a vehicle, check status, signed trip ticket | — | — | — |

Self-registration is turned off: every account is created by a Super Admin (Admins) or by an Admin or Super Admin (Drivers).

### Workflows exercised

1. Public request → reference code → status check.
2. Admin review → approve (with driver check) or decline (with reason) → trip ticket number → printable signed ticket.
3. Manual trip by Admin (created already approved).
4. Post-trip Part B log → distance and fuel computed → vehicle odometer updated.
5. Driver issue report → Admin review → linked maintenance → completed → report resolved.
6. Vehicle registration, search, list/grid, edit, delete guard.
7. Driver add, search, edit, delete guard.
8. Primary-driver assignment and reassignment.
9. Fleet card expense, duplicate-charge guard, balance.
10. Dashboards per role, reports with filters, Excel/Word/print exports.
11. Module on/off switches.
12. Account management, login lockout, logout, error pages, phone/tablet layouts.

---

## 3. UAT test matrix

**Status legend**
- **PASS** — expected result observed.
- **FAIL → PASS** — failed on the first run, corrected, and passed on re-test.
- **FAIL** — defect still open.
- **BLOCKED** — behaviour observed, but there is no defined business rule to accept or reject it against; it needs a decision (section 8).
- **NOT TESTED** — not executed.

**Severity** describes the business impact of a failure: Critical, High, Medium or Low.

### A. Authentication and account management

| Test ID | Scenario | Expected result | Actual result | Status | Severity | Tester comments |
|---|---|---|---|---|---|---|
| AUTH-01 | Super Admin signs in with valid credentials | Dashboard opens | Dashboard opened with role-specific figures | PASS | — | |
| AUTH-02 | Wrong password | Clear message, email kept | "These credentials do not match our records."; email kept | PASS | — | |
| AUTH-03 | Five wrong passwords in a row | Account temporarily locked with a clear message | Locked on the 6th attempt. First run showed a bare "429 Too Many Requests" page; now shows "Too many attempts… wait about a minute" | FAIL → PASS | Medium | New plain-language error pages |
| AUTH-04 | Log out | Confirmation, session ended | "Log out?" confirmation, then landing page. On phones, Log out was only inside the side menu; it is now also in the account menu | PASS | Low | |
| AUTH-05 | Open a protected page after logout | Redirected to sign-in | Redirected to /login. After signing in, returned to the requested page | PASS | — | |
| AUTH-06 | Forgot password without a mail server | Explains to contact the administrator | Notice shown (verified earlier today and by `PasswordResetTest`) | PASS | — | Needs SMTP before users can receive emails |
| AUTH-07 | Super Admin creates a province Admin | Account created, scoped to the province | Romblon Admin created | PASS | — | No confirmation step, unlike drivers (Low) |
| AUTH-08 | Create an Admin without a province | Rejected with a clear message | Rejected, but **no message was shown** (dialog reopened silently). Now: "Choose the province this Admin will manage." | FAIL → PASS | Medium | |
| AUTH-09 | Admin with an email that already exists | Rejected | "The email has already been taken" | PASS | — | |
| AUTH-10 | Admin opens or uses account management | Denied | Page and create request both 403 | PASS | — | |
| AUTH-11 | Driver edits own profile with an invalid phone | Rejected like the Drivers form | "12345" was **saved**. Now rejected: "Phone Number must start with 09…". Future birth dates also rejected | FAIL → PASS | Medium | Email, province and licence stay read-only |
| AUTH-12 | Super Admin deletes or demotes their own account | Not allowed | A Super Admin **deleted their own account**. Now the Delete button is hidden for yourself and the server refuses self-delete or self-demotion | FAIL → PASS | **High** | Prevents losing the last Super Admin |
| AUTH-13 | User changes own password | Self-service change | No screen exists (only Forgot Password) | NOT TESTED | — | Feature gap, listed in DEPLOYMENT.md §7 |

### B. Vehicle management

| Test ID | Scenario | Expected result | Actual result | Status | Severity | Tester comments |
|---|---|---|---|---|---|---|
| VEH-01 | Register a vehicle with all details and a photo | Saved; photo visible | "Vehicle registered successfully."; photo shown in list and grid; province set to the Admin's | PASS | — | Review step "Register this vehicle?" before saving |
| VEH-02 | Submit an incomplete form | Clear messages | "Please fill out this field." on required fields | PASS | — | |
| VEH-03 | Duplicate plate (same text, lower case) | Rejected | Rejected | PASS | — | |
| VEH-04 | Same plate typed without the space ("QAA1002") | Rejected | **Accepted as a second vehicle**. Now: "This plate number is already registered as QAA 1002." Plates are saved in capitals | FAIL → PASS | Medium | Spaces, dashes and case are ignored when comparing |
| VEH-05 | Plate with symbols ("QA@ 77") | Rejected | "Plate number may only contain letters, numbers, dashes, and spaces." | PASS | — | |
| VEH-06 | List and grid views | Both show accurate information | Both work. The grid showed a fixed "Active" badge on every vehicle; it now shows "Driver Assigned" or "No Driver" | FAIL → PASS | Low | |
| VEH-07 | Search by brand/model/plate | Correct matches only | "hiace" and "mitsubishi montero" found the right vehicles; another province's plate returned "No vehicles found" | PASS | — | |
| VEH-08 | Edit vehicle details | Saved; Admin cannot change plate or province | "Vehicle updated successfully."; plate and province read-only for Admin | PASS | — | |
| VEH-09 | Delete a vehicle with trip history | Refused | "QAA 1001 has trip requests on record and cannot be deleted." | PASS | — | |
| VEH-10 | Delete a vehicle without history | Removed after confirmation | "Delete vehicle? … cannot be undone." → "Vehicle deleted." | PASS | — | |
| VEH-11 | Admin edits or deletes another province's vehicle (direct request) | Denied | 403 for edit, delete and odometer | PASS | — | |
| VEH-12 | Lower the odometer reading | Not allowed | The dialog blocked it, but a **direct request lowered it** (1,200 → 900). The server now refuses: "cannot be lower than the current odometer (1,200 km)" | FAIL → PASS | Medium | The **Edit Vehicle** form can still enter any reading (decision D9) |
| VEH-13 | Photo too large or wrong type | Rejected | Not executed in the browser (rules: JPG/PNG/WebP, 5 MB) | NOT TESTED | — | |

### C. Driver management

| Test ID | Scenario | Expected result | Actual result | Status | Severity | Tester comments |
|---|---|---|---|---|---|---|
| DRV-01 | Admin adds a driver | Saved under the Admin's province | Saved under Palawan even when another province was picked | PASS | Low | Form still lists all provinces and "No restriction" to Admins (usability) |
| DRV-02 | Invalid phone (10 digits, +63…, 0817…) | Rejected | "Phone Number must start with 09 and be exactly 11 digits" | PASS | Low | "+63" format is not accepted |
| DRV-03 | Duplicate email | Rejected | "The email has already been taken." | PASS | — | |
| DRV-04 | Duplicate licence number | Rejected | **Accepted** a second driver with the same licence. Now: "This driver's license number is already registered as D01-11-1111111." | FAIL → PASS | Medium | Existing duplicates (live data has 1) can still be edited |
| DRV-05 | Search drivers | Correct matches only | Name/email/address search works; other provinces hidden | PASS | Low | Licence number is not searchable |
| DRV-06 | Edit driver | Saved after confirmation | "Confirm Driver Update?" → "… updated." | PASS | — | |
| DRV-07 | See whether a driver is free for assignment | Visible | Assigning page shows each driver's vehicle count; per-trip conflicts are checked at approval | PASS | Low | Drivers list itself does not show assignments |
| DRV-08 | Admin edits/deletes another province's driver; admin account via driver URL | Denied | 403 / 404 | PASS | — | |
| DRV-09 | Driver opens the Drivers module | Denied | 403 | PASS | — | |
| DRV-10 | Delete a driver who has trips | History must be kept | Driver deleted and **approved trip 26-10-903 lost its driver**. Now refused: "… has trips or vehicle reports on record and cannot be deleted." | FAIL → PASS | **High** | There is no "deactivate" option (decision D10) |
| DRV-11 | Driver with an **expired licence** | Not dispatched without at least a warning | Created (list flags "EXPIRED"), offered in driver pickers and **approved on a trip with no warning** | BLOCKED | High (business) | Decision D1 |

### D. Vehicle assignment

| Test ID | Scenario | Expected result | Actual result | Status | Severity | Tester comments |
|---|---|---|---|---|---|---|
| ASG-01 | Assign an unassigned vehicle | Saved; counters update | "Vehicle assignment updated."; assigned 1→2 | PASS | Low | Saves the moment the dropdown changes, without confirmation |
| ASG-02 | One vehicle can only have one driver | Enforced | Single primary-driver field; choosing another driver replaces the first | PASS | — | Core rule met |
| ASG-03 | Reassign to another driver | Both drivers' views update | Old driver sees "No Permanent Vehicle Assigned"; their past trips are kept | PASS | — | |
| ASG-04 | Assign a driver from another province | Rejected | Not offered in the list; direct request not saved | PASS | — | |
| ASG-05 | Assign a non-driver account | Rejected | Not saved | PASS | — | |
| ASG-06 | Assign a non-existent driver | Rejected | "The selected assigned driver id is invalid." | PASS | — | |
| ASG-07 | Driver tries to assign | Denied | 403 | PASS | — | |
| ASG-08 | Assignment counters | Accurate | Total / assigned / unassigned matched the data | PASS | — | |
| ASG-09 | Keep assignment history | History kept | Not kept: the old assignment is overwritten | FAIL | Medium | Needs a new table (decision D7) |
| ASG-10 | One driver with several vehicles | As per requirement | Allowed; the page states "a driver may be assigned to multiple vehicles" | PASS | — | Confirm this is intended (D8) |
| ASG-11 | Same driver on two trips at once | Rejected | "… already assigned to another approved trip (26-10-902)…" | PASS | — | |

### E. Trip management and monitoring

| Test ID | Scenario | Expected result | Actual result | Status | Severity | Tester comments |
|---|---|---|---|---|---|---|
| TRP-01 | Public vehicle request (two destinations) | Reference code; status "Pending" | Code ZP04BGQ; status page shows vehicle, both destinations, dates | PASS | Low | See usability items U1–U4 |
| TRP-02 | Invalid requests (past date, end before start, bad phone, other station's vehicle, missing fields) | Clear messages | Each rejected with a specific, readable message | PASS | — | |
| TRP-03 | Request a vehicle under maintenance | Rejected | "This vehicle is unavailable due to … on the selected date(s)." | PASS | — | |
| TRP-04 | Approve with the default driver | Approved; ticket number | Confirmation listed vehicle, destination and driver; ticket 26-10-902 issued | PASS | — | |
| TRP-05 | Approve a second request for the same vehicle and date | Rejected | "This vehicle is already booked for an approved trip (26-10-902)…" | PASS | Low | Message says "choose another vehicle" but the reviewer cannot change it |
| TRP-06 | Same driver on two overlapping trips | Rejected | Rejected with the conflicting ticket number | PASS | — | |
| TRP-07 | Decline with a reason | Reason asked first; requester sees it | **One click declined immediately, with no reason and no confirmation** (confirmed with a real mouse click; declines are final). Now the first click opens "Reason for Declining" and "Confirm Decline" submits; the requester sees the reason | FAIL → PASS | **High** | |
| TRP-08 | Re-approve a declined request | Refused | "This request has already been declined and can no longer be changed." | PASS | — | |
| TRP-09 | Requester status and printable ticket | Ticket opens only through its signed link | Ticket shows driver, vehicle, passengers, dates, purpose, approver. Unsigned or altered links: 403 | PASS | Low | Status page promises "assigned driver details" that it does not show |
| TRP-10 | Admin creates a manual trip | Created as approved with a ticket | "Create & Approve" confirmation → ticket 26-10-903 | PASS | — | |
| TRP-11 | Part B for a future trip | Refused | "This trip is scheduled for a future date…" | PASS | — | |
| TRP-12 | Part B with end odometer lower than start | Refused clearly | Refused, but the message read "The odo ending field must be greater than or equal to 15000." Now: "The odometer at the end of the trip cannot be lower than at the beginning (15000 km)." | FAIL → PASS | Low | Message still shows as a browser pop-up |
| TRP-13 | Part B valid entry | Distance and fuel computed; odometer updated | 12 km, 1.5 L used, 28.5 L ending balance; vehicle odometer 15,012 | PASS | — | |
| TRP-14 | Driver's travel history | Own trips listed | Upcoming and completed trips with ticket links | PASS | — | |
| TRP-15 | Cancel an approved trip | Possible, with reason | **No cancel option** in the screen (the server supports it) | FAIL | Medium | Decision D2 |
| TRP-16 | Approve a request whose travel date has passed | Defined rule | Approved and ticketed for a past date | BLOCKED | Medium | Decision D3 |
| TRP-17 | Admin approves another province's request | Denied | 403 | PASS | — | |
| TRP-18 | Driver approves a request or opens admin trip pages | Denied | 403 | PASS | — | |

### F. Maintenance and driver issue reports

| Test ID | Scenario | Expected result | Actual result | Status | Severity | Tester comments |
|---|---|---|---|---|---|---|
| MNT-01 | Schedule maintenance | Saved; shown on calendar | "Maintenance record added."; Oct 26–27 on the calendar | PASS | — | |
| MNT-02 | Driver reports a vehicle issue | Saved for review | "Vehicle issue report submitted for admin review." | PASS | — | |
| MNT-03 | Admin reviews and links maintenance | Status and note visible to the driver | "Maintenance Scheduled" with the linked record and note | PASS | — | |
| MNT-04 | Complete the linked maintenance | Report resolves | Report "Resolved" for the driver | PASS | Low | The Admin's earlier note was replaced by an automatic one |
| MNT-05 | Schedule maintenance on a day the vehicle has an approved trip | Defined rule | Accepted with no warning | BLOCKED | Medium | Decision D4 |

### G. Fleet cards

| Test ID | Scenario | Expected result | Actual result | Status | Severity | Tester comments |
|---|---|---|---|---|---|---|
| FC-01 | Fuel expense for a completed trip | Computed from Part B | 1.5 L × ₱65 = ₱97.50 deducted | PASS | — | |
| FC-02 | Charge the same trip twice | Refused | "This trip ticket has already been charged to a fleet card." | PASS | — | |
| FC-03 | Charge an unapproved (pending) or future request | Defined rule | Pending request charged ₱3,800; it then counts in fuel spending | BLOCKED | Medium | Decision D5 (the code includes pending requests on purpose) |
| FC-04 | Driver opens Fleet Cards | Denied | 403 | PASS | — | |

### H. Dashboard and reports

| Test ID | Scenario | Expected result | Actual result | Status | Severity | Tester comments |
|---|---|---|---|---|---|---|
| DSH-01 | Super Admin dashboard figures | Match the data | Pending, approved, vehicles, drivers, admins, budget all matched | PASS | — | |
| DSH-02 | Admin dashboard | Own province only | Palawan figures only; no Admins card | PASS | — | |
| DSH-03 | Driver dashboard | Assigned vehicle and own trips | Shown correctly | PASS | Low | "Completed" is counted by date, not by Part B |
| DSH-04 | Figures after a day of activity | Updated | All counters, the budget and "Trips Missing a Driver" updated correctly | PASS | — | |
| RPT-01 | Report totals for the month | Correct | 6 requests, 3 approved, 60% approval, 1 pending | PASS | — | |
| RPT-02 | Cost per km and spend per vehicle | Accurate | Distance uses **requested estimates** (including future trips), not Part B actual; card spending is credited to the **card's** vehicle | FAIL | Medium | Decision D6 |
| RPT-03 | Province filter; Admin cannot see other provinces | Correct | Works; Admin pinned to "Figures for your office only" | PASS | — | |
| RPT-04 | Swapped or invalid custom dates | Handled | Swapped dates reordered; nothing crashes | PASS | Low | An impossible date typed in the address bar rolls over instead of being rejected |
| RPT-05 | Period with no data | Understandable | Zeros and "No requests found for this period." | PASS | — | |
| RPT-06 | Excel export | Correct records | 6 trip rows plus Destinations, Vehicle Usage, Maintenance and Office sheets matched | PASS | — | |
| RPT-07 | Word export and print view | Download / open | Word file downloaded (content not opened); print view opens | PASS | — | |
| RPT-08 | Trips with several destinations | Readable | Showed the stored separator ("Roxas…;;Brooke's Point") in review, report, print and exports. Now "Roxas…, Brooke's Point" | FAIL → PASS | Low | |

### I. Administration and permissions

| Test ID | Scenario | Expected result | Actual result | Status | Severity | Tester comments |
|---|---|---|---|---|---|---|
| ADM-01 | Super Admin switches the Drivers module off | Admins blocked with an explanation | "Drivers Management Disabled… disabled by your Super Admin"; saves refused | PASS | Low | Confirmation wording "disable access for Drivers" is ambiguous |
| ADM-02 | Admin uses Super Admin settings (module switch, logo) | Denied | 403 | PASS | — | |
| ADM-03 | Driver opens 13 admin pages and sends 5 admin actions directly | All denied | All 403; own ticket allowed; pending ticket 404 | PASS | — | |
| ADM-04 | Admin adds `?location=` for another province | Ignored | Still Palawan only (vehicles, requests, reports) | PASS | — | |
| ADM-05 | Menus show only usable options | No dead links | Drivers saw "Settings", which led to a 403. Now hidden for drivers | FAIL → PASS | Low | |
| ADM-06 | Error pages are understandable and hide technical details | Plain language, way back | Bare "403 This action is unauthorized." / "404" / "429" pages. Now explained, with "Go to Dashboard" / "Go Back" | FAIL → PASS | Medium | |
| ADM-07 | Technical details in background (JSON) error messages | None shown | A 404 for a direct data request names an internal model ("App\Models\User") | FAIL | Low | Not visible on normal pages |
| PRV-01 | Public pages expose personal data | Minimal | Public vehicle availability lists driver names (and photos) without login | BLOCKED | Medium | Decision D11 |

### J. Phone and tablet use

| Test ID | Scenario | Expected result | Actual result | Status | Severity | Tester comments |
|---|---|---|---|---|---|---|
| MOB-01 | Public request form on a phone after choosing a date | No sideways scrolling | Page became 407 px wide on a 352 px screen (long model name). Now fits at 320 and 375 px; long names end with "…" | FAIL → PASS | Medium | |
| MOB-02 | 13 Admin pages at 375 px and 768 px with test data | No sideways scrolling | All fit | PASS | — | |
| MOB-03 | Real phones and tablets | Usable | Only browser emulation was used | NOT TESTED | — | Do this during stakeholder UAT |

**Fixed in passing:** registering a vehicle without sending the optional "year" field crashed with a server error. The browser form always sends it, so users never saw this. Found by an automated test and fixed.

---

## 4. Failed, blocked and untested scenarios

| ID | Status | What is outstanding |
|---|---|---|
| ASG-09 | FAIL | No assignment history (needs a new table; D7) |
| TRP-15 | FAIL | No way to cancel an approved trip in the screens (D2) |
| RPT-02 | FAIL | Cost per km and per-vehicle spending basis (D6) |
| ADM-07 | FAIL (Low) | Internal model name in JSON 404 messages |
| DRV-11 | BLOCKED | Expired licences are not enforced (D1) |
| TRP-16 | BLOCKED | Past-dated approvals (D3) |
| MNT-05 | BLOCKED | Maintenance vs. approved trips (D4) |
| FC-03 | BLOCKED | Charging unapproved or future trips (D5) |
| PRV-01 | BLOCKED | Driver names on public pages (D11) |
| AUTH-13, VEH-13, MOB-03 | NOT TESTED | Password change screen does not exist; photo limits not tried in the browser; real devices not used |

**Passed, but a rule needs confirming:**
- **VEH-12:** whether the Edit Vehicle form may lower an odometer (D9).
- **ASG-10:** several vehicles per driver (D8).
- **DRV-10:** deleting drivers with history is now blocked, so do you want a Deactivate option (D10)?
- **TRP-07:** whether a decline reason should be mandatory (D12).

---

## 5. Usability issues identified (not changed in this phase)

| # | Where | Observation | Suggested improvement |
|---|---|---|---|
| U1 | Public request form | The destination picker closes after each pick | Keep it open for multiple choices |
| U2 | Public request form | "Previous Trip Ticket Date (Optional)" has no explanation | Add a one-line hint |
| U3 | Status page | "Office / Section" shows the province when no office was chosen | Show "—" |
| U4 | Public request form | Office and destination pickers are unlabeled buttons for screen readers | Add labels |
| U5 | Travel Requests | Competing requests for the same vehicle and date are not flagged before approval | Show a warning badge |
| U6 | Vehicle Assigning | Saves immediately on dropdown change; the message doesn't name the vehicle or driver | Confirm, or name both in the message |
| U7 | Drivers (Admin view) | "All Locations" menu and province choices shown to a province Admin | Show the Admin's province only |
| U8 | Part B form | Errors appear in a browser pop-up | Show them inside the form |
| U9 | Driver dashboard | "Completed" is counted by date, not by a logged Part B | Use one definition everywhere |
| U10 | Module switches | "Disable access for Drivers" can be read as locking out driver accounts | "Hide the Drivers page from Admins" |
| U11 | Accounts | Creating an Admin has no confirmation step (Drivers do) | Make it consistent |
| U12 | Search | Licence numbers are not searchable; "+63" phone format is rejected | Add licence search; accept +63 and convert |
| U13 | Dashboard | Maintenance on the calendar is labelled only "Maintenance"; the "▲100%" trend appears when last month was zero | Show the title; hide the trend when there is nothing to compare |

---

## 6. Defects corrected in this phase

All of these are covered by `tests/Feature/UatFindingsTest.php`, plus one new report test in `ReportsDashboardTest`, and were re-tested in the browser.

| Defect | Fix |
|---|---|
| TRP-07 one-click decline (**High**) | First click opens the reason box; "Confirm Decline" submits (`VehicleRequest/Views/index.blade.php`) |
| AUTH-12 self-delete / self-demote (**High**) | Server refuses both; Delete hidden on your own row (`AdminController`, admin list views) |
| DRV-10 driver deletion erases trip history (**High**) | Drivers with trips or reports cannot be deleted (`DriverController`) |
| VEH-04 / DRV-04 duplicate plates and licences | New `App\Rules\UniqueIdentifier` ignores spaces, dashes and case; plates saved in capitals. Licence check applies only when the number changes, so existing duplicates don't block other edits |
| VEH-12 odometer rollback | The quick update can no longer lower the reading (`VehicleController`) |
| AUTH-11 profile validation | Driver phone format and past-only birth date (`ProfileController`, profile view) |
| AUTH-08 missing location message | Message shown under the field, with clearer wording (`Admin` requests and view) |
| ADM-05 / AUTH-04 account menu | Settings hidden from drivers; Log out added to the account menu (`layouts/app.blade.php`) |
| ADM-06 / AUTH-03 error pages | New self-contained 403, 404, 419, 429, 500 and 503 pages (`resources/views/errors`) |
| MOB-01 phone overflow | Vehicle cards can shrink; long names truncate (`public/request-vehicle.blade.php`) |
| RPT-08 ";;" separator | Shown as ", " in the review dialog, report, print view, Excel and Word |
| VEH-06 "Active" badge | Shows "Driver Assigned" / "No Driver" |
| TRP-12 technical message | Part B field names match the form |
| Vehicle "year" crash | Optional field handled (`VehicleRepository`) |
| QA seed realism | QA trips now have end dates like real requests (`QaSeeder`) |

---

## 7. Stakeholder feedback status

**Pending.** No intended users (Admins, drivers, requesting staff, approving officers) have tested the system yet, and no feedback has been collected. The questionnaire in [UAT_CHECKLIST.md](UAT_CHECKLIST.md#part-2--feedback-questionnaire) is ready. No feedback, signatures or approvals have been recorded or invented in this report.

---

## 8. Acceptance criteria and outstanding items

| Criterion | Status |
|---|---|
| All critical business workflows pass | **Met** in developer-run UAT (sections 3E–3H) |
| Vehicle assignment rules enforced (one primary driver per vehicle; no vehicle or driver double-booking) | **Met** (ASG-02, TRP-05, TRP-06, ASG-11) |
| Role and province permissions work, including direct URLs and requests | **Met** (ADM-01 to ADM-04, VEH-11, DRV-08, TRP-17/18) |
| No unresolved Critical or High data-integrity or security defects | **Met** for defects (all three Highs fixed). **One High business-risk decision is open:** expired licences (D1) |
| Records and reports accurate | **Partly met.** Counts and exports are correct; cost/km and per-vehicle spend depend on D5/D6 |
| Users complete essential tasks without blocking usability problems | **Not yet shown.** Needs real users (checklist) |
| Relevant UAT scenarios executed and documented | **Met** for developer-run UAT (95 executed, 3 not tested) |
| Stakeholders reviewed results and explicitly approved | **Pending** |

### Decisions needed from the agency

| # | Question | Options |
|---|---|---|
| D1 | Should a driver with an **expired licence** be assignable or dispatchable? | Block / warn only / allow |
| D2 | Should Admins be able to **cancel an approved trip**, with a reason? | Add a Cancel button / keep as is |
| D3 | Can a request be **approved after its travel date has passed**? | Block / allow (for after-the-fact recording) |
| D4 | Can **maintenance be scheduled** on days the vehicle has an approved trip? | Block / warn / allow |
| D5 | Can **fleet card expenses** be charged to pending or future trips? | Approved/completed only / keep current |
| D6 | Should **cost per km** use the Part B actual distance? Should spending be credited to the trip's vehicle? | Actual and trip's vehicle / keep estimates |
| D7 | Is **assignment history** required (who drove which vehicle, from–to)? | Add a history table (database change) / not needed |
| D8 | Confirm a driver **may hold several vehicles** at once | Confirm / restrict to one |
| D9 | Who may **lower an odometer** through Edit Vehicle (to correct a typo)? | Super Admin only / nobody / any Admin |
| D10 | Drivers with history can no longer be deleted. Add a **Deactivate** option for staff who leave? | Add deactivate (database change) / keep |
| D11 | Should the public request form show **driver names and photos**? | Hide / show name only / keep |
| D12 | Should a **decline reason be mandatory**? | Required / optional |

Remaining items from earlier phases are listed in [DEPLOYMENT.md §7](../../DEPLOYMENT.md#7-known-open-items-decide-before-or-soon-after-go-live): schema hardening, private licence images, unused Fortify features, password policy, and hard-coded approving officer names.

---

## 9. Final recommendation

### PENDING — INSUFFICIENT TESTING OR APPROVAL

1. **Developer-run UAT is complete.**
   - 95 of 98 scenarios were executed.
   - Every defect that could be fixed safely without a business decision or a database change has been fixed, re-tested in the browser, and covered by automated tests.
   - The test suites pass: 98/98 on MySQL; on SQLite 93 pass and 5 are skipped because they need MySQL.
2. **The system cannot be marked ACCEPTED yet**, because:
   - intended users have not run the checklist;
   - no stakeholder has approved it;
   - decisions D1–D12 are open, and D1 (expired licences) carries High business risk.
3. **Suggested path to acceptance:**
   1. Decide D1–D12.
   2. Apply the agreed changes.
   3. Run [UAT_CHECKLIST.md](UAT_CHECKLIST.md) with at least one Super Admin, one Admin per pilot province, two drivers and one requesting staff member, on real phones and computers, using test data.
   4. Collect the questionnaires.
   5. Fix any blocking findings.
   6. Obtain written sign-off.

   **ACCEPTED WITH DOCUMENTED CONDITIONS** is realistic once those steps are complete.
