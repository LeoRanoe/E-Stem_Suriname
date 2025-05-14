# Admin Page Plan

## Overview

This document outlines the plan for designing a new, private admin page that combines voter import, QR code generation, and voter monitoring.

## Features

The page will include the following functionalities:

*   **Voter Import:**
    *   A CSV import form with fields mapping.
    *   CSV validation for required identification fields, data formats, district/region validation, and duplicate checks.
    *   Bulk insert to the voters table.
    *   Error handling and import summary reporting.
    *   Progress bar during the CSV import process.

*   **QR Code Generation:**
    *   QR code generation for all imported voters.
    *   Ability for admins to re-generate QR codes.
    *   Options to download/print QR codes (ZIP archive containing individual PNG images, grouped by region).

*   **Voter Monitoring:**
    *   Display voter data (Full Name, National ID, Voting district/region).
    *   Filtering and search capabilities.

## Constraints

The page must also adhere to the following constraints:

*   **Privacy:** The admin must not be able to see who a voter has voted for.
*   **Data Handling:** Must handle special characters in names/addresses and support multiple CSV formats.
*   **QR Codes:** QR codes must be readable by common mobile scanners and should contain the voter's unique voting code.

## Mermaid Diagram

```mermaid
graph LR
    subgraph Admin Page
        A[Voter Import] --> B(CSV Import Form);
        B --> C{CSV Validation};
        C -- Required Fields --> D[Error Handling];
        C -- Data Formats --> D;
        C -- District/Region --> D;
        C -- Duplicate Checks --> D;
        C -- Update Existing Record --> E[Bulk Insert];
        E --> F(Import Summary);
        B --> O[Progress Bar];
        E --> O;
        
        G[QR Code Generation] --> H{Generate QR Codes};
        H --> I{Re-generate QR Codes};
        I --> J[Download/Print (ZIP by Region)];
        
        K[Voter Monitoring] --> L(Display Voter Data);
        L -- Full Name, National ID, Voting district/region --> M(Filtering);
        L --> N(Search);
    end
    
    style A fill:#f9f,stroke:#333,stroke-width:2px
    style G fill:#f9f,stroke:#333,stroke-width:2px
    style K fill:#f9f,stroke:#333,stroke-width:2px
    style O fill:#ccf,stroke:#333,stroke-width:2px
```

## Detailed Plan

1.  **Voter Import Section:**
    *   Create a new page `admin/import_voters.php` with a CSV import form.
    *   Implement the import logic in `src/controllers/ImportController.php`.
    *   Implement CSV validation:
        *   Required identification fields (Full Name, National ID, Date of Birth, Address).
        *   Data formats (dates, IDs).
        *   District/region validation (using `config/valid_districts.php`).
        *   Duplicate checks (National ID as the unique identifier).
        *   If a duplicate is found, update the existing record.
    *   Implement bulk insert to the voters table.
    *   Add error handling and import summary reporting.
    *   Add a progress bar to visually track the import process.
    *   Handle special characters in names/addresses.
    *   Support multiple CSV formats.

2.  **QR Code Generation Section:**
    *   Implement QR code generation using a library like phpqrcode.
    *   Create a QR generation endpoint in `src/controllers/QrCodeController.php`.
    *   Add an option to generate QR codes for all imported voters.
    *   Allow admins to re-generate QR codes.
    *   Implement download/print options:
        *   Generate a ZIP archive containing individual PNG images, grouped by region.
    *   Ensure QR codes are readable by common mobile scanners and contain the voter's unique voting code.

3.  **Voter Monitoring Section:**
    *   Display voter data (Full Name, National ID, Voting district/region).
    *   Implement filtering and search capabilities.
    *   Ensure voter privacy by excluding sensitive information like voting history.

4.  **Integration and UI:**
    *   Create a user-friendly interface that integrates all three functionalities into a single page.
    *   Use appropriate styling and layout to ensure a seamless user experience.
    *   Add navigation elements to easily switch between the different sections.

## Files to Modify/Create

*   `admin/import_voters.php` (new)
*   `src/controllers/ImportController.php` (update)
*   `src/controllers/QrCodeController.php` (update)
*   `src/views/qrcodes.php` (new)
*   `admin/views/voters.php` (add QR column)
*   `database/e-stem_suriname.sql` (ensure complete schema)
*   `config/valid_districts.php` (new)
*   `composer.json` (add QR library if needed)