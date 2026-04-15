# Aurae Backend — Admin Panel Workflow Documentation

This document covers every module available in the Aurae admin panel (`/admin/*`). It is written for admin users who manage the system day-to-day and does not require development knowledge.

---

## Table of Contents

1. [Access & Authentication](#1-access--authentication)
2. [Dashboard](#2-dashboard)
3. [User Management](#3-user-management)
4. [Machine Management](#4-machine-management)
5. [Device Maintenance](#5-device-maintenance)
6. [Device Locations](#6-device-locations)
7. [Health Reports](#7-health-reports)
8. [Subscription Plans](#8-subscription-plans)
9. [User Subscriptions](#9-user-subscriptions)
10. [E-commerce (Lunar PHP)](#10-e-commerce-lunar-php)
    - [Products](#101-products)
    - [Collection Groups](#102-collection-groups)
    - [Orders](#103-orders)
11. [News & Advertisements](#11-news--advertisements)
12. [Knowledge Center (Tutorials)](#12-knowledge-center-tutorials)
13. [Music Library](#13-music-library)
14. [Therapy Modes](#14-therapy-modes)
15. [FAQ](#15-faq)
16. [Feedbacks](#16-feedbacks)
17. [Banners](#17-banners)
    - [Maintenance Banners](#171-maintenance-banners)
    - [Marketplace Banners](#172-marketplace-banners)
18. [Settings](#18-settings)
    - [General Settings](#181-general-settings)
    - [Social Media Links](#182-social-media-links)
    - [Legal Pages](#183-legal-pages)

---

## 1. Access & Authentication

The admin panel is accessible at `/admin/dashboard`. You must be logged in with an account that has admin privileges (`is_admin = true`). Attempting to access any `/admin/*` route without admin credentials will redirect you to the login page.

---

## 2. Dashboard

**URL:** `/admin/dashboard`

The dashboard gives a quick health-check overview of the system:

| Metric | Description |
|--------|-------------|
| Total Users | Count of all non-admin registered users |
| Total Devices | Count of all registered IoT devices |
| Online Devices | Count of devices with an active status |
| Top Subscriptions | Bar chart showing most-subscribed plans |

**Chart filter:** The subscriptions chart can be filtered by a preset time range (7 days, 30 days, 90 days) or a custom date range using the date pickers. The filter updates the chart without a full page reload.

---

## 3. User Management

**URL:** `/admin/users`

Manage all registered app users (non-admin accounts are listed; the currently logged-in admin is excluded from the list).

### Creating a User

1. Click **Create User**.
2. Fill in: Name, Email, Password, and Type (Regular User or Admin).
3. Submit — a Lunar PHP customer record is automatically created and linked to the user.

### Editing a User

1. From the user list, click the user's row or the edit action.
2. Update Name, Email, or any available fields.
3. Save — the linked Lunar customer record (first name / last name) is kept in sync automatically.

### Deleting a User

Click the delete action on the user list. This is a permanent action.

### User Detail Sub-pages

From the **User Detail** page (`/admin/users/{id}`) you can navigate to:

- **Login Activity** (`/admin/users/{id}/login-activities`) — A chronological list of every time the user logged in, including timestamps. Useful for verifying account usage or investigating suspicious access.
- **Program Logs** (`/admin/users/{id}/program-logs`) — A log of the user's therapy/program usage history.

---

## 4. Machine Management

**URL:** `/admin/machines`

Machines are the physical Aurae IoT devices that are manufactured, serialised, and eventually bound to a user's account.

### Machine List Filters

The list supports three filters:
- **Search** — by serial number or machine name.
- **Status** — Active (1) or Inactive (0).
- **Binding** — Bound (assigned to a user) or Unbound.

### Creating a Machine

Two creation modes are available on the create form:

**Single Machine**
1. Enter a Name. The serial number is auto-suggested based on the configured serial format (see [General Settings](#181-general-settings)) — you can override it.
2. Set Status (Active/Inactive).
3. Optionally upload a Thumbnail and Detail Image (max 5 MB each, stored on S3).
4. Submit.

**Bulk Generation**
1. Enter a Name (used as the base name for all machines), Model code (4 chars), Year (4 chars), Start Product Code, Variation Code, Quantity (up to 1,000), and Status.
2. Optionally upload shared Thumbnail and Detail Image.
3. Submit — serial numbers are auto-generated sequentially.

### Machine Actions

| Action | Description |
|--------|-------------|
| View | See full detail including linked user, device, and subscription. |
| Edit | Change name, serial number, status, or images. |
| Activate | Set status to Active. |
| Deactivate | Set status to Inactive. |
| Unbind | Detach the machine from its current user (also clears the linked device and subscription). A machine bound to a user cannot be deleted until unbound. |
| Delete | Permanently remove an unbound machine. |

---

## 5. Device Maintenance

**URL:** `/admin/device-maintenances`

This module handles maintenance requests submitted by users through the app. Admins review requests, update statuses, and coordinate with the factory when physical servicing is needed.

### Maintenance Status Values

| Value | Meaning |
|-------|---------|
| 0 — Pending | Newly submitted or awaiting user approval for a time proposal. |
| 1 — Approved | Admin/factory has approved the request. |
| 2 — In Progress | Maintenance is actively being worked on. |
| 3 — Completed | Maintenance is done. |

### Reviewing a Request

1. Click a maintenance record to open its detail page.
2. The detail page shows: user info, device info, current status, original requested date/time, and a change history log.
3. Use the **Update Status** action to move the request through the workflow.

### Factory Scheduling

When you propose a new date from the factory side:
- Set `Factory Maintenance Requested At` to the proposed date.
- The system automatically sets the status back to **Pending (0)** and records the change in the history log, waiting for the user to approve the new time.
- Tick `Is Factory Approved` when the factory side is confirmed.

### Filtering

The list can be filtered by status and by a search term (user name, email, device name, or device UUID).

---

## 6. Device Locations

**URL:** `/admin/device-locations`

View the GPS location history reported by IoT devices. Each entry represents a location ping from a device.

### Filtering

- **Device** — Filter to a specific device using the dropdown (shows device name and UUID).
- **Date Range** — Filter by From and To dates.

Results are paginated (50 per page, newest first).

### Per-Device History

Clicking a device on the list goes to `/admin/device-locations/{device}`, which shows the location history for that specific device only.

---

## 7. Health Reports

**URL:** `/admin/health-reports`

Admins upload health analysis PDF reports on behalf of users. There are three report types per record:

| Report Type | Field |
|-------------|-------|
| Full Body Analysis | `full_body_file` |
| Meridian Analysis | `meridian_file` |
| Multidimensional Analysis | `multidimensional_file` |

### Uploading a Report

1. Click **Upload Report**.
2. Select the **User** from the dropdown (non-admin users only).
3. Upload one or more PDF files for the applicable report types.
4. Submit.

### Viewing a Report

Click the view icon next to the file name on the list — the PDF opens inline in the browser.

### Deleting a Report

Clicking delete removes the database record **and** deletes all associated PDF files from storage.

> **Note:** Reports are stored in private storage and are not directly accessible via a public URL. They are served securely through the application.

---

## 8. Subscription Plans

**URL:** `/admin/subscriptions`

Manage the subscription tier definitions available to users in the app.

### Fields

| Field | Description |
|-------|-------------|
| Title | Internal/display name of the plan (e.g. "Monthly Plan") |
| Pricing Title | Short label shown on the pricing screen (e.g. "RM 29.90/month") |
| Description | Optional description of what's included |
| Price | Numeric price in the base currency |
| Is Active | Whether this plan is visible and selectable by users |
| Icon | Optional image icon for the plan (max 2 MB) |
| SenangPay Recurring ID | The recurring billing ID from the SenangPay dashboard. Required for automatic recurring billing. |

### Actions

- **Create / Edit** — Standard form; icon image can be replaced on edit.
- **Delete** — Permanently removes the plan. Do not delete plans that have active user subscriptions.

---

## 9. User Subscriptions

**URL:** `/admin/user-subscriptions`

View and manage all subscription records that belong to users.

### Filters

- **Search** — by user name or email.
- **Status** — active, cancelled, expired, etc.
- **Payment Status** — filter by payment state.

Results are paginated (20 per page).

### Subscription Detail

Click a record to open the detail view, which shows the user's name, linked subscription plan, subscription dates (starts_at, ends_at), payment status, and linked machines.

### Admin Actions

**Cancel Subscription**
- Sets the subscription status to `cancelled` and records the cancellation timestamp.
- If the subscription is a **recurring** one, a warning will appear reminding you to **also cancel it manually in the SenangPay dashboard** — the system alone cannot stop future payment charges.

**Extend Subscription**
- Enter the number of months to extend (1–12).
- The system adds the specified months to the current `ends_at` date and reactivates the subscription if it was expired.

---

## 10. E-commerce (Lunar PHP)

The shop is powered by [Lunar PHP](https://lunarphp.io/). Products, collections, and orders all live within Lunar's data model.

---

### 10.1 Products

**URL:** `/admin/products`

#### Quick-Creating a Product

Products are always created as **drafts** first via a quick-create form:
1. Enter **Name**, **SKU**, and **Base Price**.
2. Submit — the product is created in `draft` status with a single default variant.

#### Full Product Edit

After creation, open the product edit page (`/admin/products/{id}/edit`) to complete the setup. The edit form covers:
- **Name** — displayed product name.
- **Description** — rich text (HTML), edited via the Lexical editor.
- **Tags** — assign or remove tags for searchability/filtering.
- **Status** — toggle between `draft` (hidden) and `published` (visible in store).

The product edit page also links out to dedicated sub-pages for the sections below.

#### Product Media (`/admin/products/{id}/media`)

- Upload images (supports multiple uploads).
- **Reorder** — drag and drop images into the desired display order, then click **Save Order**.
- Delete individual images.

#### Product Variants (`/admin/products/{id}/variants`)

- **Configure** — define option types and their values (e.g. Size: S, M, L; Color: Red, Blue). Options are set on the configure sub-page.
- Once options are set, variants are auto-generated.
- **Update All** — bulk-edit variant details (SKU, stock policy, etc.) in a single save.
- **Delete** — remove individual variants.

#### Product Pricing (`/admin/products/{id}/pricing`)

Set pricing per variant, currency, and minimum quantity tier. Submit the pricing form to save.

#### Product Identifiers (`/admin/products/{id}/product-identifiers`)

Assign product identifiers such as barcode (EAN/UPC) or custom internal codes per variant.

#### Product Inventory (`/admin/products/{id}/inventory`)

Manage stock quantity, backorder policy, and purchase limits per variant.

#### Product Collections (`/admin/products/{id}/collections`)

Assign the product to one or more collections. Collections are managed under [Collection Groups](#102-collection-groups).
- Select a collection from the dropdown and click **Add**.
- Click the remove action next to a collection to detach it.

---

### 10.2 Collection Groups

**URL:** `/admin/collection-groups`

Collection Groups are top-level organisers for product collections (similar to categories).

- **Create a Group** — provide a name and handle. The group is created immediately.
- **View/Edit a Group** — opens the group detail page showing the collections within it.
- **Manage Collections within a Group** — on the group edit page, add new collections (sub-categories) or remove existing ones.

> **Note:** Collection Group editing (update/delete at the group level) is partially implemented; creating groups and managing their collections is fully functional.

---

### 10.3 Orders

**URL:** `/admin/orders`

All orders placed through the storefront appear here, pulled from Lunar's order model.

#### Order List

Shows order number, customer, total, currency, and current status. Click any row to open the full order detail.

#### Order Detail

Displays:
- Line items (product name, variant, quantity, price)
- Shipping and billing address
- Customer details
- Current status and history

#### Updating Order Status

Use the **Update Status** action on the order detail page. Available statuses:

| Status | Meaning |
|--------|---------|
| `awaiting-payment` | Order placed but payment not yet confirmed |
| `payment-received` | Payment confirmed |
| `dispatched` | Order has shipped — a **Tracking Link** field becomes required |
| `delivered` | Order received by customer |

When setting status to `dispatched`, paste the courier tracking URL into the Tracking Link field. It is saved in the order metadata and visible to the customer.

---

## 11. News & Advertisements

**URL:** `/admin/news`

Manage news articles and advertisements displayed in the app.

### Creating a News Item

1. Click **Create News**.
2. Fill in: **Title**, **Content** (rich text editor), and optionally upload a **Cover Image**.
3. Set the **Status**:
   - `published` — goes live immediately (published_at is set to now).
   - `unpublished` — saved as a draft.
4. Optionally set a **Published Date and Time** to schedule future publishing.
5. Submit.

### Editing a News Item

All fields including the image can be updated. Changing the status or the scheduled date follows the same logic as creation.

### Unpublishing

Use the **Unpublish** action directly from the news list or the detail page. This clears `published_at` and marks the item as unpublished without deleting it.

### Deleting

Permanently removes the record. (Note: file cleanup on delete is not yet implemented for news images.)

---

## 12. Knowledge Center (Tutorials)

**URL:** `/admin/tutorial`

The Knowledge Center hosts tutorial and educational content shown in the app.

### Creating a Tutorial

1. Click **Create Tutorial**.
2. Fill in: **Title**, **Content** (rich text editor), and optionally upload a **Cover Image**.
3. Set a **Published Date and Time**:
   - If the date is in the past or now → published immediately.
   - If the date is in the future → saved as unpublished; the app will show it once the scheduled time passes.
4. Submit.

### Reordering Tutorials

On the tutorial list page, drag-and-drop rows into the desired display order and save. The `order` field controls the sequence shown to users in the app.

### Unpublishing

Use the **Unpublish** action to hide a tutorial from the app without deleting it.

### Deleting

Permanently removes the record and deletes the associated cover image from storage.

---

## 13. Music Library

**URL:** `/admin/music`

The music library stores audio files used by the therapy mode system.

### Uploading Music

1. Click **Add Music**.
2. Enter a **Title**.
3. Upload the **Audio File** — supported formats: MP3, WAV, OGG, M4A (up to 1 GB).
4. Optionally upload a **Thumbnail** image (up to 10 MB).
5. Set **Is Active** — only active tracks are selectable when configuring therapy modes.
6. Submit.

> **Production note:** In the production environment, large audio files are uploaded directly to AWS S3 using a presigned URL flow to avoid server memory limits. The UI handles this automatically.

### Editing Music

Title, thumbnail, and active status can be updated. The audio file can also be replaced. Old files are automatically deleted when replaced.

### Deleting Music

Permanently deletes the record and removes the audio and thumbnail files from storage (both local and S3).

> **Warning:** Deleting music that is assigned to an active therapy mode will break that therapy. Unassign or update the therapy first.

---

## 14. Therapy Modes

**URL:** `/admin/therapies`

Therapy modes are pre-configured sessions that the Aurae device runs. Each mode defines the device parameters and optionally plays background music.

### Creating a Therapy Mode

1. Click **Create Therapy**.
2. Fill in:
   - **Name** — display name for the mode.
   - **Image** — cover image for the mode card.
   - **Music** — select from active tracks in the [Music Library](#13-music-library) (optional).
   - **Duration** — session length (in minutes or seconds, as configured).
   - **Temperature** — target device temperature setting.
   - **Light** — light intensity setting.
   - **Color LED** — LED colour setting.
   - **Status** (Is Active) — whether the mode is visible in the app.
3. Submit.

### Reordering Therapy Modes

Drag-and-drop rows on the therapy list page into the desired order, then save. The order is reflected in the app's mode selection screen.

### Editing and Deleting

All fields can be updated on the edit page. Deleting permanently removes the mode and its associated image.

> **Note:** Only non-custom therapies are managed here. Custom therapies created by users are managed separately through the User panel.

---

## 15. FAQ

**URL:** `/admin/faqs`

Manage the Frequently Asked Questions displayed in the app.

### Creating a FAQ

1. Click **Create FAQ**.
2. Enter a **Question** and write the **Answer** using the rich text editor.
3. Submit.

### Editing and Deleting

- All fields can be edited freely.
- Deleting is permanent.

---

## 16. Feedbacks

**URL:** `/admin/feedbacks`

A read-only inbox for feedback submitted by users through the app.

- The list shows the user's name, email, and submission timestamp.
- Click any row to open the full feedback detail including the message body.
- No reply or moderation actions are available from this panel — feedback is for internal review only.

---

## 17. Banners

Two separate banner systems exist for different parts of the app.

---

### 17.1 Maintenance Banners

**URL:** `/admin/maintenance-banners`

Banners displayed in the app's maintenance/servicing section.

| Field | Notes |
|-------|-------|
| Image | Required. Max 2 MB. |
| Title | Optional label. |
| Is Active | Controls visibility. |
| Order | Display order (lower number = higher position). |

Full CRUD is available (Create, View, Edit, Delete). Old images are automatically removed when replaced.

---

### 17.2 Marketplace Banners

**URL:** `/admin/marketplace-banners`

Banners displayed in the in-app marketplace/shop section.

Fields and behaviour are identical to Maintenance Banners above.

---

## 18. Settings

---

### 18.1 General Settings

**URL:** `/admin/general-settings`

Configure application-wide settings.

| Setting | Description |
|---------|-------------|
| Contact Number | Support/contact phone number displayed in the app. |
| APK File (Phone) | Upload the latest Android APK for the phone app (max 500 MB). |
| APK Version (Phone) | Version string (e.g. `1.2.3`). |
| APK Release Notes (Phone) | Changelog text shown to users on update. |
| APK File (Tablet) | Upload the latest Android APK for the tablet variant. |
| APK Version (Tablet) | Version string for tablet APK. |
| APK Release Notes (Tablet) | Changelog for tablet APK. |
| Machine Serial Format | Template string that controls how serial numbers are auto-generated. |
| Machine Serial Prefix | Prefix characters prepended to every serial number. |
| Machine Serial Length | Total character length of the serial number. |

A **Serial Number Preview** is shown live on the page reflecting the current format settings — useful to verify the format before saving.

> **File size reminder:** To upload APK files larger than your PHP default, ensure the server's `upload_max_filesize` and `post_max_size` are set to at least 600 MB / 650 MB respectively. See the project README for server configuration details.

---

### 18.2 Social Media Links

**URL:** `/admin/social-media`

Configure the social media URLs linked from the app.

| Platform | Field |
|----------|-------|
| Facebook | URL to the Facebook page |
| XHS (Xiaohongshu / RedNote) | URL to the XHS profile |
| Instagram | URL to the Instagram profile |

Leave a field blank to remove the link. Save to apply changes immediately.

---

### 18.3 Legal Pages

**URL:** `/admin/legal-settings`

Manage the legal content displayed in the app and on the public website.

- **Terms & Conditions** — edited via the rich text editor.
- **Privacy Policy** — edited via the rich text editor.

Both pages are publicly accessible at `/terms-and-conditions` and `/privacy-policy` respectively. Changes take effect immediately upon saving.
