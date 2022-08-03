# Changelog for Laravel IP Camera

## [0.1.1] – 2022-04-06
### Changed
- Modification of images is now done using pipelines, not .inc files.
  The event `ProcessImage` that this was intended for is also removed.
- Pictures are now models. This simplified the logic around keeping
  the latest image up to date a lot. This new model is now the source
  of broadcasted events, though the channel name is the same. Picture
  models are created on incoming images, but gets a 'published'
  boolean flag set when processing is done and picture copied to
  destination folder.
- Routes were split in api and web groups, and camera config updated.
- Added support for Laravel 9.x

## [0.1.0] – 2021-11-17

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
- Image handling is split in incoming and outgoing folders/disks.
  Between these, imagery can be manipulated using the Intervention
  image API, either through event listeners or as a per-camera PHP
  include file.
