# Laravel IP Camera

Manage images from IP cameras in Laravel.

This tool registers and tracks incoming imagery from IP cameras.
Actually the IP camera is a bit mis-leading, as this tool so far
listens for incoming image files in specific folders.

It uses the PHP inotify extension to look for file system changes, so
it's highly recommended to install it. If this is not available, a
poor man's inotify tool is available, which is poll-based using
cron.

Client side you can use Laravel's Echo.js to listen for new/updated
pictures by listening on the camera channel:

```javascript
Echo.channel(`TromsFylkestrafikk.Camera.Models.Camera.${camera.id}`)
    .listen('.PictureUpdated', (data) => {
        commit('setPicture', data.model);
    });
```

The model has a few computed accessors, among them 'url' which can be
utilized directly in `<img src="" />`.

Events are broadcasted only when pictures are in a 'published' state,
and if you utilize the separation between incoming and processed imagery, only
`.PictureUpdated` events will be broadcasted.

## Install

Add repository to your `composer.json`
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/TromsFylkestrafikk/ipcamera"
        }
    ]
}
```

Add require entry:
```shell
composer require tromsfylkestrafikk/ipcamera
```

Publish the camera's config to your site and modify to your liking. It
is highly recommended to read the comments in this file to get a
better understanding of the inner working mechanisms:
```shell
php artisan vendor:publish --provider="TromsFylkestrafikk\Camera\CameraServiceprovider" --tag=config
```

Run migrations
```shell
php artisan migrate
```

The inotify listener is supposed to run as a service, and a
rudimentary supervisor config for this is provided in the `misc/`
directory.

NOTE! This will only listen on incoming files for the cameras
available when executed, so any cameras added after this service is
started will not be listened to.  Restart supervisor process to fix.

## Inner workings

As mentioned, this tool relies on inotify to detect incoming images.
When new incoming images arrive, it will try to match the full file
path of the image to your camera config. If each camera has a unique
folder there is a 1:1 mapping between file and camera and the new
image is announced to the camera directly.  If several cameras share
the same folder a more complex algorithm takes over and will try to
get a single camera out of it.

### Infile, outfile

Images can be modified before publishing, and this requires the
configuration to have a working disk for incoming files which is
different from published disk. If this is enabled, a Laravel pipeline
is set up for interested parties to act upon. See config/camera.php
and the section `Camera image manipulation` for further implementation
details.

## Usage

Camera CRUD management is done by artisan (CLI) using the `artisan
camera:` name space. See `php artisan list camera` for available
commands.

Create a new camera and note its created ID:
```shell
php artisan camera:add
```

Run the inotify watcher manually (-vv for debug purposes)
```shell
php artisan camera:watch -vv
```

Make your IP camera(s) dump images to your configured folders.

