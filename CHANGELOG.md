# Bulk Edit Changelog

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
