# Laravel IP Camera

Manage images from IP cameras in Laravel.

- Broadcast to subscribers when new images are posted

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

Publish the camera's config to your site:
```shell
php artisan vendor:publish --provider="TromsFylkestrafikk\Camera\CameraServiceprovider" --tag=config
```
