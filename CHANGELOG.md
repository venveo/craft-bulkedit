# Bulk Edit Changelog

## 2.0.5
### Fixed
- Bug where multi-site bulk editing was not working properly (Thanks @monachilada)

## 2.0.4 - 2020-02-25
### Fixed
- Bug where bulk editing assets with could yield no fields

## 2.0.3 - 2020-02-24
### Fixed
- Bug where bulk editing a section with a field layout that had been deleted could yield no fields

## 2.0.2.1 - 2020-02-20
### Fixed
- Error that can occur if trying to bulkedit fields on a soft-deleted layout

## 2.0.2 - 2020-02-20
### Added
- All field types (including custom ones and Matrix) now support bulk replacement!!!

## 2.0.1 - 2020-02-13
### Fixed
- Fixed problem with saving bulk edit jobs in Firefox

## 2.0.0 - 2020-01-24
{warning} The FieldProcessorInterface has slightly changed to better
support old versions of PHP. If you wrote your own FieldProcessor,
ensure it has been updates prior to updating to 2.0.0

### Added
- Add support for new strategies: ADD, MULTIPLY, DIVIDE
- Number fields now have the following strategies: REPLACE, ADD, SUBTRACT, MULTIPLY, DIVIDE

### Changed
- Changed FieldProcessorInterface to remove void declaration

### Fixed
- Improved compatibility with older versions of php

## 1.1.1 - 2019-07-22
### Fixed
- Fixed potential issues with merge strategies defaulting to replace

### Changed
- Abstracted field handling

### Added
- `EVENT_REGISTER_FIELD_PROCESSORS`

### Removed
- `EVENT_REGISTER_SUPPORTED_FIELDS` use `EVENT_REGISTER_FIELD_PROCESSORS` instead

## 1.1.0.1 - 2019-07-21
### Fixed
- Fixed an issue with bulkEdit component not being set

## 1.1.0 - 2019-07-21
### Added
- Strategy selection: replace, merge, or subtract
- Added support for bulk editing custom fields on Categories
- Added support for bulk editing custom fields on Users
- Added support for bulk editing custom fields on Assets
- Added support for bulk editing custom fields on Commerce Products
- Added `EVENT_REGISTER_ELEMENT_PROCESSORS` to allow modules/plugins an opportunity to register an element processor
- Added `EVENT_REGISTER_SUPPORTED_FIELDS` to allow modules/plugins an opportunity to register a supported field
- Added a progress message to queue job
- Added permissions for each element type
- Edit contexts now ensure the element types match that of the original request

### Changed
- Refactored much of the modal form structure to accommodate strategies
- Code cleaning & abstraction
- The queue now runs automatically after saving a job
- The queue jobs now batch elements properly

### Removed
- Don't save revisions of entries anymore

## 1.0.5 - 2019-04-08
### Fixed
- Fixed issue with unescaped HTML in field instructions breaking editor

## 1.0.4 - 2019-04-08
### Fixed
- Fixed install migration issues
- Fixed potential compatibility issues with PostgreSQL (Thanks, @boboldehampsink)

## 1.0.3 - 2018-11-17
### Fixed
- Potential error from field values that are too long to store in history

### Changed
- Set element saving scenario to SCENARIO_ESSENTIALS

## 1.0.2 - 2018-10-08
### Fixed
- Added scrollbar to modal to allow for larger field layouts

## 1.0.1 - 2018-10-01
### Changed
- Adjusted plugin handle

## 1.0.0 - 2018-10-01
### Added
- Initial release
