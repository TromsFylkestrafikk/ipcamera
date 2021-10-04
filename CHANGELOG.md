# Changelog for Laravel IP Camera

## [Unreleased]

### Added
- Package extracted from other project
- API call for getting the latest image, actual image, not URL to its file.
- Artisan command for watching camera folder(s) which broadcasts
  updated/new images when dropped there.
- Camera model contains current/latest image.
- Add optional scheduled check for latest images. Useful when inotify
  isn't available.
