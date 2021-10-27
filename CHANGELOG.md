# Changelog for Laravel IP Camera

## [Unreleased]

### Added
- Package extracted from other project
- Artisan command for watching camera folder(s) which broadcasts
  updated/new images when dropped there.
- Camera model contains current/latest image.
- API call for camera model returns image, URL to it, mime and timestamp.
- Add optional scheduled check for latest images. Useful when inotify
  isn't available.
- Cron job looks for stalled cameras and deactivates them if latest
  image is older than the `max_age` configuration option.
- Image handling is split in incoming and outgoing folders/disks with
  events where interested parties can manipulate the images before
  publishing.
