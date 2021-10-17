# Laravel IP Camera

Manage images from IP cameras in Laravel.

This tool registers and tracks incoming imagery from IP cameras.
Actually the IP camera is a bit mis-leading, as this tool so far
listens for incoming image files in specific folders.

It uses the PHP inotify extension to look for file system changes, so
it's highly recommended to install it. If this is not available, a
poor man's inotify tool is available, which is poll-based using
cron.

When new imagery pops up, the camera model will be updated with recent
events and using model broadcasting you can catch these events client
side using Laravel's Echo:

```javascript
Echo.channel(`TromsFylkestrafikk.Camera.Models.Camera.${camera.id}`)
    .listen('.CameraUpdated', (data) => {
        commit('setCamera', data.model);
    });
```

The model has a few computed accessors, among them 'currentUrl' which
can be utilized directly in `<img src="" />`.

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

