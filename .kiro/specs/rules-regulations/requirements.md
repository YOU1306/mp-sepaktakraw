# Requirements Document

## Introduction

The Sepaktakraw Association of Madhya Pradesh website needs a publicly accessible
**Rules & Regulations** section that serves as the authoritative source for Laws of
the Game, score sheets, and other official documents published by ISTAF / STFI.

All infrastructure (model, controller, routes, Filament resource, public view, and
artisan import command) already exists in the codebase. This specification covers:

1. Ensuring all existing pieces are correctly connected and production-ready.
2. Admin / Super Admin full CRUD management through the Filament panel.
3. Public read-only access — any visitor can browse titles and view PDFs inline.
4. Seeding five initial regulation documents so the section is populated on first
   deployment.
5. Consistent UI following the existing Indian tricolour theme.

---

## Glossary

- **Regulation_Page**: The public-facing page at `/rules-regulations` that lists
  all active regulation documents.
- **PDF_Viewer**: The browser's native PDF renderer invoked when a visitor opens a
  regulation via `/rules-regulations/{regulation}/view`.
- **Filament_Panel**: The Filament v4 admin panel used by privileged users to manage
  site content.
- **Regulation_Resource**: The Filament resource class `RegulationResource` and its
  associated form, table, and page classes inside
  `app/Filament/Resources/Regulations/`.
- **Admin**: A user carrying the Spatie role `admin`.
- **Super_Admin**: A user carrying the Spatie role `super-admin`.
- **Regulation**: An Eloquent model record representing one uploaded PDF document
  with fields: `title`, `description`, `path`, `original_name`, `size`,
  `sort_order`, `is_active`, and `uploaded_by`.
- **Regulation_Seeder**: A Laravel database seeder class that creates the five
  initial regulation records and copies their PDF files to
  `storage/app/regulations/`.
- **AuditService**: The existing `App\Services\AuditService` used to log create,
  update, and delete actions on Regulation records.
- **Local_Disk**: Laravel's `local` filesystem disk; PDF files are stored under
  `storage/app/regulations/` and never exposed via a public URL directly.

---

## Requirements

### Requirement 1: Public Listing of Active Regulations

**User Story:** As a website visitor, I want to see a list of published rules and
regulations documents, so that I can find the official Laws of the Game and other
ISTAF / STFI documents quickly.

#### Acceptance Criteria

1. WHEN a visitor navigates to `/rules-regulations`, THE Regulation_Page SHALL
   display all Regulation records where `is_active` is `true`, ordered first by
   `sort_order` ascending and then by `title` ascending.

2. WHEN the Regulation_Page renders a Regulation record, THE Regulation_Page SHALL
   display the `title` as a clickable link that opens the PDF_Viewer URL in a new
   browser tab.

3. WHERE a Regulation record has a non-null `description`, THE Regulation_Page
   SHALL display the `description` beneath the `title` on the same card.

4. WHERE a Regulation record has a non-null `size`, THE Regulation_Page SHALL
   display the human-readable file size (e.g., "1.2 MB") alongside the "View PDF"
   call-to-action on the card.

5. WHEN the Regulation_Page is requested and no active Regulation records exist,
   THE Regulation_Page SHALL display the message "No rules or regulations have been
   published yet." instead of an empty grid.

6. THE Regulation_Page SHALL render within the `layouts.public` master template,
   using the existing Indian tricolour header strip, navigation bar, and footer
   without modification.

7. THE Regulation_Page SHALL include a `<title>` element reading
   `Rules & Regulations — {app.name}`.

---

### Requirement 2: Inline PDF Viewing

**User Story:** As a website visitor, I want to click on a regulation title and read
the PDF directly in my browser, so that I do not have to download a file to read the
document.

#### Acceptance Criteria

1. WHEN a visitor requests `/rules-regulations/{regulation}/view` for an active
   Regulation, THE PDF_Viewer SHALL respond with the PDF file stream using
   `Content-Type: application/pdf` and `Content-Disposition: inline`.

2. WHEN a visitor requests `/rules-regulations/{regulation}/view` for an inactive
   Regulation and the visitor does not hold the `admin` or `super-admin` role,
   THE PDF_Viewer SHALL respond with HTTP 404.

3. WHEN an Admin or Super_Admin requests `/rules-regulations/{regulation}/view` for
   an inactive Regulation, THE PDF_Viewer SHALL respond with the PDF file stream so
   that the document can be previewed before publishing.

4. IF the file referenced by `Regulation.path` does not exist on the Local_Disk,
   THEN THE PDF_Viewer SHALL respond with HTTP 404 regardless of the requester's
   role.

5. THE PDF_Viewer SHALL serve the file using a sanitised filename derived from the
   regulation's `title` (URL-slug form with `.pdf` extension) in the
   `Content-Disposition` header, so that if the browser saves the file it receives
   a meaningful name.

---

### Requirement 3: Admin and Super Admin Regulation Management via Filament

**User Story:** As an Admin or Super Admin, I want to create, edit, reorder, and
delete regulation documents through the Filament admin panel, so that the published
document library stays accurate without requiring developer intervention.

#### Acceptance Criteria

1. THE Regulation_Resource SHALL be visible in the Filament_Panel navigation only
   for users holding the `admin` or `super-admin` role.

2. WHEN an authenticated user without the `admin` or `super-admin` role attempts to
   access any Regulation_Resource route inside the Filament_Panel, THE
   Filament_Panel SHALL deny access (HTTP 403 or equivalent Filament
   unauthorised response).

3. WHEN an Admin or Super_Admin creates a new Regulation, THE Regulation_Resource
   SHALL require a `title` (max 255 characters) and a PDF file upload.

4. WHEN an Admin or Super_Admin uploads a PDF file while creating a Regulation, THE
   Regulation_Resource SHALL store the file on the Local_Disk under the
   `regulations/` directory with a random filename, and SHALL record the file size
   in bytes in `Regulation.size`.

5. WHEN an Admin or Super_Admin edits an existing Regulation, THE Regulation_Resource
   SHALL allow updating `title`, `description`, `sort_order`, and `is_active`
   without requiring a new file upload.

6. WHEN an Admin or Super_Admin uploads a replacement PDF file while editing an
   existing Regulation, THE Regulation_Resource SHALL replace the stored file and
   update `Regulation.size` accordingly.

7. WHEN an Admin or Super_Admin saves a new or updated Regulation, THE
   Regulation_Resource SHALL record the action via AuditService with the action
   strings `"created"` and `"updated"` respectively.

8. WHEN an Admin or Super_Admin deletes a Regulation, THE Regulation_Resource SHALL
   record the action via AuditService with the action string `"deleted"`.

9. THE Regulation_Resource table view SHALL support drag-and-drop reordering via the
   `sort_order` column so that Admins can control the public display sequence.

10. WHEN an Admin or Super_Admin clicks "View PDF" in the Regulation_Resource table,
    THE Filament_Panel SHALL open the PDF_Viewer URL in a new browser tab.

11. THE Regulation_Resource SHALL accept PDF uploads of maximum 20 MB.

12. THE Regulation_Resource SHALL reject non-PDF file uploads and display a
    validation error.

---

### Requirement 4: Access Restriction for Non-Admin Roles

**User Story:** As a system security requirement, I need to ensure that only Admins
and Super Admins can manage regulations, so that unauthorised users cannot add,
remove, or replace legal documents.

#### Acceptance Criteria

1. THE Regulation_Resource SHALL return `false` for `canViewAny()`, `canCreate()`,
   `canEdit()`, and `canDelete()` when called for any user who does not hold the
   `admin` or `super-admin` role.

2. WHEN a user holding the `super-user` role is authenticated, THE Regulation_Resource
   SHALL deny all management operations, matching the behaviour for unauthenticated
   users.

3. WHEN a user holding the `user` role is authenticated, THE Regulation_Resource SHALL
   deny all management operations.

4. THE Regulation_Page public listing SHALL be accessible to all visitors,
   authenticated or not, without any authentication gate.

---

### Requirement 5: Initial Data Seeding (Five Regulation Documents)

**User Story:** As a site administrator, I want the five standard ISTAF / STFI
documents pre-loaded on first deployment, so that the Rules & Regulations page is
not empty for visitors from day one.

#### Acceptance Criteria

1. THE Regulation_Seeder SHALL create exactly five Regulation records representing the
   five initial official documents, using `updateOrCreate` keyed on `title` so the
   seeder is safe to run multiple times without creating duplicates.

2. THE Regulation_Seeder SHALL copy each corresponding seed PDF file from
   `database/seeders/data/regulations/` into `storage/app/regulations/` using a
   random filename, only if the source file is present and the destination path is
   not already occupied.

3. IF a seed PDF source file is absent, THEN THE Regulation_Seeder SHALL create the
   Regulation record with `is_active` set to `false` so that the missing file does
   not produce a broken public link.

4. THE Regulation_Seeder SHALL set `uploaded_by` to the ID of the super-admin user
   identified by the `SUPER_ADMIN_EMAIL` environment variable, or `null` if that
   user does not exist.

5. THE DatabaseSeeder SHALL call Regulation_Seeder as part of the standard
   `db:seed` run.

6. THE Regulation_Seeder SHALL assign sequential `sort_order` values starting at 1,
   matching the intended display sequence of the five documents.

---

### Requirement 6: Secure PDF Storage and Serving

**User Story:** As a security requirement, I need PDF files to be stored outside the
public web root and served through the application, so that documents cannot be
accessed by guessing file paths.

#### Acceptance Criteria

1. THE Regulation_Resource SHALL store all uploaded PDF files on the `local`
   filesystem disk (i.e., under `storage/app/`), not on the `public` disk.

2. THE PDF_Viewer SHALL serve file contents by reading from the Local_Disk path
   using `Storage::disk('local')->path()`, never by constructing a direct
   `public/` URL to the stored file.

3. THE Regulation_Resource SHALL mark all uploaded files with `visibility('private')`
   so the storage layer does not create any publicly accessible symlink or URL.

4. WHEN a Filament file upload component stores a Regulation PDF, THE
   Regulation_Resource SHALL use a randomly generated filename (not the original
   filename) to prevent path enumeration.

---

### Requirement 7: Navigation and Discoverability

**User Story:** As a visitor, I want the Rules & Regulations section to be reachable
from the main navigation, so that I can find it without knowing its URL.

#### Acceptance Criteria

1. THE `layouts.public` navigation bar SHALL include a "Rules & Regulations" link
   pointing to the `regulations.index` named route.

2. WHEN the current page matches the `regulations.*` route pattern, THE navigation
   link SHALL be rendered with the active style (font-semibold, text-stone-900)
   consistent with other active navigation links.

3. THE `layouts.public` footer "Quick Links" column SHALL include a "Rules &
   Regulations" link pointing to the `regulations.index` named route.
