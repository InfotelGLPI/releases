# Documentation — Releases Plugin for GLPI

## Overview

The **Releases** plugin adds release management to GLPI, following the ITIL change management process. It lets you track production deployments by linking changes, knowledge base articles, inventory items, and actors to each release.

**Version**: 2.1.9  
**Compatibility**: GLPI 11.0 – 11.x  
**License**: GPLv2+  
**Authors**: Xavier Caillaud, Alban Lesellier, Infotel  
**Repository**: https://github.com/InfotelGLPI/releases

---

## Features

- Create and track production releases linked to GLPI changes
- Associate changes, KB articles, documents, and inventory items
- Manage risks, rollback plans, deployment tasks, and tests
- Validation through a review and finalization step
- Email notifications (creation, update, closure, deletion)
- Reusable release templates
- GLPI planning integration (scheduled deployment tasks)
- Integration with the **Metademands** and **Mydashboard** plugins
- Profile-based rights management
- Full history tracking

---

## Requirements

| Component | Minimum version |
|-----------|----------------|
| GLPI      | 11.0           |
| PHP       | ≥ 8.1          |

---

## Installation

1. Download the archive from https://github.com/InfotelGLPI/releases/releases
2. Extract it into the `marketplace/` folder of your GLPI instance
3. Go to **Setup > Plugins** in GLPI
4. Click **Install** then **Enable** for the Releases plugin

---

## Rights and Profiles

Rights are configured under **Administration > Profiles**, **Releases** tab.

| Right | Internal key |
|-------|-------------|
| Releases | `plugin_releases_releases` |
| Risks | `plugin_releases_risks` |
| Tasks | `plugin_releases_tasks` |

---

## Release Lifecycle

A release progresses through the following statuses in order:

| Status | Description |
|--------|-------------|
| **New** | Release created, no information filled in yet |
| **Release area defined** | The scope description has been entered |
| **Dates defined** | Pre-production and production dates are set |
| **Changes defined** | Associated changes have been linked |
| **Risks defined** | Risks have been entered |
| **Rollbacks defined** | Rollback plans have been entered |
| **Deployment tasks in progress** | Deployment tasks are underway |
| **Tests in progress** | Tests are underway |
| **To finalize** | Ready for finalization |
| **Reviewed** | Post-deployment review has been completed |
| **End** | Release successfully closed |
| **Failed** | The release failed |

The status advances automatically based on the data entered (dates, description, etc.).

---

## Creating a Release

### From the main menu

1. Go to **Assistance > Releases** (central interface)
2. Click **+ Add**
3. Fill in the required fields:
   - **Title**: name of the release
   - **Release area**: description of what is being deployed
4. Save

### From a change

1. Open an existing change
2. Go to the **Releases** tab
3. Click **+ Add a release**

### From a template

When creating a release, select a **release template** in the form to pre-fill the risks, tests, tasks, and rollbacks defined in that template.

---

## Main Form Fields

| Field | Description |
|-------|-------------|
| **Title** | Name of the release |
| **Status** | Current position in the lifecycle |
| **Release area** | Description of what is being deployed (rich text editor) |
| **Pre-production run date** | Planned date for the pre-production pass |
| **Production run date** | Planned date for the production pass |
| **Service shutdown** | Whether a service interruption is required |
| **Service shutdown details** | Description of the shutdown (hours, impact…) |
| **Communication** | Whether users need to be notified |
| **Communication type** | Target type: Entity, Group, Profile, User, Location |
| **Targets** | Selection of communication recipients |
| **Location** | Physical location affected |
| **Entity** | GLPI entity for this release |

---

## Available Tabs

### Changes
Links the release to existing GLPI changes.

### Documents
Attaches documentary files to the release.

### Knowledge base
Associates relevant KB articles.

### Items
Associates GLPI inventory items (hardware, software…).

### Finalization
Allows entry of the deployment report (result, observations).

### Review
Allows completion of the post-deployment review (summary, corrective actions).

### Notes
Free-form notepad attached to the release.

### Log
Full history of changes.

---

## Timeline

The timeline displays all release elements in chronological order. From the timeline you can add:

### Risks
- **Title**: name of the risk
- **Type**: risk classification
- **Description**: detail of the identified risk
- **State**: To do / Done

### Rollbacks
- **Title**: name of the rollback plan
- **Type**: classification
- **Description**: rollback procedure
- **State**: To do / Done

### Deployment Tasks
- **Title**: name of the task
- **Type**: classification
- **Description**: procedure to execute
- **Level**: execution order (priority)
- **Associated risk**: the risk this task addresses
- **Planning**: start/end dates, assigned technician
- **State**: To do / Done / Failed

### Tests
- **Title**: name of the test
- **Type**: test classification
- **Description**: test procedure
- **Associated risk**: the risk this test validates
- **State**: To do / Done / Failed

### Followups
Free-form comments on the release progress.

---

## Release Templates

Templates allow you to pre-configure a reusable release structure.

**Access**: **Assistance > Release templates**

A template defines:
- General information (title, scope, communication settings)
- Pre-defined risks
- Pre-defined rollbacks
- Pre-defined deployment tasks
- Pre-defined tests
- Pre-assigned actors (users, groups, suppliers)
- Associated inventory items

When a release is created from a template, all these elements are automatically copied.

---

## Finalization

The **Finalization** tab allows you to officially close a release:

- Enter the deployment report
- Mark the release as **Successful** (status → End) or **Failed** (status → Failed)

---

## Post-deployment Review

The **Review** tab allows you to conduct the post-deployment review:

- Observations on how the deployment went
- Corrective actions to take
- Completing the review moves the status to **Reviewed**

---

## Notifications

The plugin sends email notifications for the following events:

| Event | Description |
|-------|-------------|
| **New release** | Sent on creation |
| **Update** | Sent on each modification |
| **Closure** | Sent on closure |
| **Deletion** | Sent on deletion |

Configuration is done under **Setup > Notifications**, filtering on the **Release** type.

---

## Integrations

### GLPI Changes
A **Releases** tab appears on each Change record to list and create associated releases.

### GLPI Planning
Deployment tasks with a planned date appear in the GLPI central and personal planning views.

### Metademands
If the Metademands plugin is installed, the `Release` type can be used in meta-demands.

### Mydashboard
If the Mydashboard plugin is installed, a Release alert widget is available in dashboards.

---

## Available Translations

| Language | Code |
|----------|------|
| French | fr_FR |
| English | en_GB |
| German | de_DE |
| Spanish (Ecuador) | es_EC |
| Portuguese (Brazil) | pt_BR |
| Czech | cs_CZ |
| Turkish | tr_TR |

Contribute translations: https://explore.transifex.com/infotelGLPI/GLPI_releases/

---

## Support and Contribution

- **Report a bug**: https://github.com/InfotelGLPI/releases/issues
- **Source repository**: https://github.com/InfotelGLPI/releases
- **Infotel blog**: https://blogglpi.infotel.com
