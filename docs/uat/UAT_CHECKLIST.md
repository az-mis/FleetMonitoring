# Fleet Management System — User Acceptance Test (UAT) Checklist

**For:** DA-MIMAROPA personnel who will use the Fleet Management System.
**Purpose:** confirm that the system supports your daily work before it goes live.
No programming knowledge is needed.

## Before you start

- Use only the **test system address** and the **test account** given to you by the MIS office.
- **Never type real personal information.** Use made-up names (for example "Test Requester One"), test phone numbers such as `09170000001`, and test plate numbers such as `UAT 0001`.
- Work through the tasks for **your role**. For each task:
  - follow the steps;
  - compare what you see with the **Expected result**;
  - tick **Yes** or **No**;
  - write anything unusual in **Comments**.
- If something goes wrong:
  - take a screenshot;
  - fill in the problem report in Part 3.
- Try at least one task on a **mobile phone** (Part 1, section E).

| Tester name | Position / Office | Role tested | Device and browser | Date |
|---|---|---|---|---|
| | | | | |

---

## Part 1 — Tasks

### A. Requesting staff (no account needed)

**P1. Request a vehicle**

- **Steps:**
  1. Open the test system and click **Request a Vehicle**.
  2. Enter your (test) name and choose your office.
  3. Under **From (Station)**, choose your province.
  4. Under **To (Destination)**, pick one or two places.
  5. Click your travel date on the calendar. Click it again for a one-day trip, or click an end date.
  6. Choose a vehicle marked **✓ Available**.
  7. Type the purpose and a test contact number. Click **Submit Vehicle Request**.
- **Expected result:** a 7-character **reference code** is shown. The status page says **Pending Review** and lists your vehicle, destinations and dates.
- ☐ Yes ☐ No — Comments: ______________________________

**P2. Check your request later**

- **Steps:**
  1. Click **Check Request Status**.
  2. Enter your reference code.
- **Expected result:** the current status of your request (Pending, Approved or Declined) is shown.
- ☐ Yes ☐ No — Comments: ______________________________

**P3. Make a mistake on purpose**

- **Steps:** on the request form, try a travel date in the past or a contact number with only 10 digits.
- **Expected result:** the form refuses and explains what to correct.
- ☐ Yes ☐ No — Comments: ______________________________

**P4. Open your trip ticket** (after an Admin approves your request)

- **Steps:**
  1. Check your status.
  2. Click **Open Printable Trip Ticket**.
- **Expected result:** the Driver's Trip Ticket shows the ticket number, driver, vehicle, passengers, dates, places and purpose. It can be printed or saved as PDF.
- ☐ Yes ☐ No — Comments: ______________________________

**P5. See why a request was declined** (after an Admin declines it)

- **Steps:** check the status of a declined request.
- **Expected result:** the page says **Request Declined** and shows the Admin's reason.
- ☐ Yes ☐ No — Comments: ______________________________

### B. Admin (province)

**A1. Sign in and check your dashboard**

- **Steps:** sign in with your Admin test account.
- **Expected result:** the dashboard shows your name and **your province only** (pending requests, approved trips, vehicles, drivers, fleet card budget, calendar).
- ☐ Yes ☐ No — Comments: ______________________________

**A2. Register a vehicle with a photo**

- **Steps:**
  1. Go to **Vehicles → Vehicles List** and click **Register Vehicle**.
  2. Add a photo and fill in the required fields (marked *).
  3. Click **Save Vehicle**, check the summary, and click **Save Vehicle** again.
- **Expected result:** "Vehicle registered successfully." The vehicle and its photo appear in the list, under your province.
- ☐ Yes ☐ No — Comments: ______________________________

**A3. Try to register the same plate twice**

- **Steps:** register another vehicle using the same plate written differently (for example without the space, or in small letters).
- **Expected result:** refused with "This plate number is already registered as …".
- ☐ Yes ☐ No — Comments: ______________________________

**A4. Find and edit a vehicle**

- **Steps:**
  1. Type part of a brand or plate in the search box.
  2. Switch between list and grid view.
  3. Open **Edit Vehicle**, change the colour, and save.
- **Expected result:** search shows only matching vehicles. The change is saved. You cannot change the plate number or province.
- ☐ Yes ☐ No — Comments: ______________________________

**A5. Add a driver**

- **Steps:**
  1. Go to **Drivers → Add Driver**.
  2. Fill in the details, using a test licence number and a phone number starting with 09.
  3. Confirm.
- **Expected result:** "Driver created successfully." The driver appears with your province and licence expiry.
- ☐ Yes ☐ No — Comments: ______________________________

**A6. Try a duplicate licence number**

- **Steps:** add another driver with the same licence number as an existing driver.
- **Expected result:** refused with "This driver's license number is already registered as …".
- ☐ Yes ☐ No — Comments: ______________________________

**A7. Assign and reassign a vehicle**

- **Steps:**
  1. Go to **Vehicles → Vehicle Assigning** and choose a driver for an unassigned vehicle.
  2. Then choose a different driver for the same vehicle.
- **Expected result:**
  - "Vehicle assignment updated."
  - Each vehicle shows **one** primary driver.
  - The counters (assigned / unassigned) change.
  - The new driver sees the vehicle on their dashboard.
- ☐ Yes ☐ No — Comments: ______________________________

**A8. Approve a request**

- **Steps:**
  1. Go to **Travel Requests**, open a pending request, and click **Review**.
  2. Answer whether the default driver is available (or pick a driver).
  3. Click **Approve Request**, then confirm.
- **Expected result:** "Request … approved." A **trip ticket number** is assigned, and the requester can open the ticket.
- ☐ Yes ☐ No — Comments: ______________________________

**A9. Try to double-book**

- **Steps:** approve a second request for the **same vehicle on the same date**, or the same driver on overlapping dates.
- **Expected result:** refused with a message naming the trip already booked.
- ☐ Yes ☐ No — Comments: ______________________________

**A10. Decline a request with a reason**

- **Steps:**
  1. Review a pending request and click **Decline** once.
  2. Type the reason.
  3. Click **Confirm Decline**.
- **Expected result:** the first click only opens the reason box. After confirming: "Request … declined." The requester sees your reason.
- ☐ Yes ☐ No — Comments: ______________________________

**A11. Create a trip yourself**

- **Steps:**
  1. Click **New Request** and fill in the requester, vehicle, driver, dates and destination.
  2. Click **Create & Approve Trip Ticket**, then confirm.
- **Expected result:** the trip is created as approved, with a ticket number.
- ☐ Yes ☐ No — Comments: ______________________________

**A12. Record a finished trip (Part B)**

- **Steps:**
  1. Click **Post-Trip Log** and enter the ticket number of a trip that already happened.
  2. Fill in the times, the odometer at beginning and end, and the fuel.
  3. First try an ending odometer **lower** than the beginning. Then enter the correct value and save.
- **Expected result:**
  - The lower reading is refused with a clear message.
  - The correct entry saves, with distance and fuel computed automatically.
  - Trips dated in the future cannot be logged yet.
- ☐ Yes ☐ No — Comments: ______________________________

**A13. Handle a driver's vehicle report**

- **Steps:**
  1. Go to **Vehicles → Vehicle Reports** and click **Review / Update** on a report.
  2. Link or create maintenance, and add a note.
  3. Later, mark the maintenance **completed**.
- **Expected result:** the report status moves to Maintenance Scheduled and then **Resolved**. The driver sees each update.
- ☐ Yes ☐ No — Comments: ______________________________

**A14. Log a fleet card expense**

- **Steps:**
  1. Go to **Vehicles → Fleet Cards** and click **⛽ Compute** on a card.
  2. Choose a completed trip ticket and enter the fuel price.
  3. Save. Then try the same ticket again.
- **Expected result:**
  - The amount is computed and the balance goes down.
  - Charging the same ticket twice is refused.
- ☐ Yes ☐ No — Comments: ______________________________

**A15. Check reports**

- **Steps:**
  1. Open **Reports** for **This Month**.
  2. Compare the totals with your own records.
  3. Click **Excel** and open the file.
- **Expected result:** the numbers are correct for your province only. The Excel file lists the same trips.
- ☐ Yes ☐ No — Comments: ______________________________

**A16. Check what you cannot do**

- **Steps:** paste the address of the Admins page (ending in `/admin`) into the browser.
- **Expected result:** an "Access not allowed" page with a **Go to Dashboard** button. No technical text.
- ☐ Yes ☐ No — Comments: ______________________________

**A17. Log out**

- **Steps:** click your profile picture (top right) and choose **Log out**, then confirm.
- **Expected result:** you are signed out. Opening a page again asks you to sign in.
- ☐ Yes ☐ No — Comments: ______________________________

### C. Driver

**D1. Sign in and check your dashboard**

- **Steps:** sign in with your Driver test account.
- **Expected result:** you see your assigned vehicle and your upcoming and past trips. You do not see admin menus.
- ☐ Yes ☐ No — Comments: ______________________________

**D2. View your travel history and a ticket**

- **Steps:**
  1. Open **Travel History**.
  2. Click **Ticket** on a trip.
- **Expected result:** your trips are listed. The ticket opens and can be printed.
- ☐ Yes ☐ No — Comments: ______________________________

**D3. Report a vehicle problem**

- **Steps:**
  1. Open **Report Vehicle Issue** and click **Report an Issue**.
  2. Choose the type and priority, and describe the problem.
  3. Submit.
- **Expected result:** "Vehicle issue report submitted for admin review." The report appears as **Submitted**.
- ☐ Yes ☐ No — Comments: ______________________________

**D4. Update your profile**

- **Steps:**
  1. Open **My Profile** and change your phone number. Save.
  2. Then try a number that does not start with 09.
- **Expected result:**
  - The valid number is saved.
  - The invalid one is refused with "must start with 09 and be exactly 11 digits".
  - Email, province and licence cannot be changed by you.
- ☐ Yes ☐ No — Comments: ______________________________

### D. Super Admin

**S1. Create an Admin account**

- **Steps:**
  1. Go to **Admins → Add Admin**.
  2. First try role **Admin** without choosing a province. Then choose a province and save.
- **Expected result:**
  - Without a province: "Choose the province this Admin will manage."
  - With a province: the account is created.
- ☐ Yes ☐ No — Comments: ______________________________

**S2. Switch a module off and on**

- **Steps:**
  1. Open **Settings** (profile menu) and turn **Drivers** off.
  2. Ask an Admin to open Drivers.
  3. Turn it back on.
- **Expected result:** the Admin sees "Drivers Management Disabled". After you turn it on again, it works.
- ☐ Yes ☐ No — Comments: ______________________________

**S3. Protect your own account**

- **Steps:** look at your own row in **Admins**.
- **Expected result:** there is **no Delete button** for your own account.
- ☐ Yes ☐ No — Comments: ______________________________

**S4. Check all provinces**

- **Steps:**
  1. On the Dashboard choose **All Locations**.
  2. Open Reports and look at **Office Comparison**.
- **Expected result:** each province's figures match its records.
- ☐ Yes ☐ No — Comments: ______________________________

### E. Phone and tablet

**M1. Request a vehicle on a phone**

- **Steps:** repeat task P1 on a mobile phone.
- **Expected result:**
  - Everything fits on the screen without sideways scrolling.
  - Buttons are easy to tap.
  - The request is submitted.
- ☐ Yes ☐ No — Comments: ______________________________

**M2. Approve a request on a phone or tablet**

- **Steps:** repeat task A8 on a phone or tablet.
- **Expected result:** the review and confirmation dialogs fit the screen and work.
- ☐ Yes ☐ No — Comments: ______________________________

---

## Part 2 — Feedback questionnaire

Circle one number for each statement: **1 = strongly disagree, 5 = strongly agree.**

| # | Statement | Rating |
|---|---|---|
| 1 | The system is easy to use without training. | 1 2 3 4 5 |
| 2 | I can find the menu or page I need quickly. | 1 2 3 4 5 |
| 3 | Labels, buttons and instructions are clear. | 1 2 3 4 5 |
| 4 | The forms ask only for what is needed. | 1 2 3 4 5 |
| 5 | Error messages tell me what to correct. | 1 2 3 4 5 |
| 6 | Vehicle, driver and trip information shown is accurate. | 1 2 3 4 5 |
| 7 | Dashboard and report figures match our records. | 1 2 3 4 5 |
| 8 | Common tasks (request, approve, assign, log a trip) are quick to finish. | 1 2 3 4 5 |
| 9 | The system works well on a phone or tablet. | 1 2 3 4 5 |
| 10 | The system covers the tasks my office needs. | 1 2 3 4 5 |

**Open questions**

1. Which task was the most difficult or confusing, and why?
   ____________________________________________________________
2. Did anything show wrong or missing information? Describe it.
   ____________________________________________________________
3. What does your office do with vehicles, drivers or trips that the system **cannot** do yet?
   ____________________________________________________________
4. Which task took too long? How could it be faster?
   ____________________________________________________________
5. What one improvement would help you most?
   ____________________________________________________________
6. Would you be comfortable using this system for daily work? ☐ Yes ☐ Not yet — why?
   ____________________________________________________________

---

## Part 3 — Problem report (copy for each problem)

| Field | Details |
|---|---|
| Task number (e.g. A8) | |
| What I did (steps) | |
| What happened | |
| What I expected | |
| Screenshot attached? | ☐ Yes ☐ No |
| How serious? | ☐ Stops my work ☐ Wrong information ☐ Annoying but workable ☐ Suggestion |
| Device / browser | |

---

## Part 4 — Acceptance sign-off

To be completed by authorized officials **after** reviewing the test results and the UAT report (`docs/uat/UAT_REPORT.md`).

| Name | Position | Office | Decision | Conditions (if any) | Signature | Date |
|---|---|---|---|---|---|---|
| | | | ☐ Accept ☐ Accept with conditions ☐ Do not accept | | | |
| | | | ☐ Accept ☐ Accept with conditions ☐ Do not accept | | | |
| | | | ☐ Accept ☐ Accept with conditions ☐ Do not accept | | | |
