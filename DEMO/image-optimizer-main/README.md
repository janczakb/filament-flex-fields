> This package is a fork of [joshembling/image-optimizer](https://github.com/joshembling/image-optimizer), updated to support Filament v4 & v5 and Laravel 12 & 13.

# Optimize your Filament images before they reach your database.

[![Downloads](https://img.shields.io/packagist/dt/danihidayatx/image-optimizer.svg?style=flat-square)](https://packagist.org/packages/danihidayatx/image-optimizer)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/danihidayatx/image-optimizer.svg?style=flat-square)](https://packagist.org/packages/danihidayatx/image-optimizer)

When you currently upload an image using the native Filament component `FileUpload`, the original file is saved without any compression or conversion.

Additionally, if you upload an image and use conversions with `SpatieMediaLibraryFileUpload`, the original file is saved with its corresponding versions provided on your model. 

What if you'd rather convert and reduce the image(s) before reaching your database/S3 bucket? Especially in the case where you know you'll never need to save the original image sizes the user has uploaded.

🤳 **This is where Filament Image Optimizer comes in**. 

You use the same components as you have been doing and have access to two additional methods for maximum optimization, saving you a lot of disk space in the process. 🎉

## Contents

- [Contents](#contents)
- [Installation](#installation)
- [Usage](#usage)
	- [Optimizing Images](#optimizing-images)
	- [Dynamic Values (Closures)](#dynamic-values-closures)
	- [Resizing Images](#resizing-images)
	- [Combining Methods](#combining-methods)
	- [Multiple Images](#multiple-images)
	- [Examples](#examples)
	- [Debugging](#debugging)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [Security Vulnerabilities](#security-vulnerabilities)
- [Credits](#credits)
- [Licence](#license)

## Installation

You can install the package via composer, which works with Filament v3.x, v4.x & v5.x, and Laravel 10, 11, 12 & 13:

```bash
composer require danihidayatx/image-optimizer
```

## Usage

### Filament version

You must be using [Filament v3.x, v4.x or v5.x](https://filamentphp.com/docs/panels/installation) to have access to this plugin.

| PHP | Laravel version | Filament version | Image Optimizer version |
| ----- | ----- | -----| ----- |
| ^8.2 | ^10.0, ^11.0, ^12.0, ^13.0 | ^3.2, ^4.0, ^5.0 | ^2.2 |

### Server

[GD Library](https://www.php.net/manual/en/image.installation.php) must be installed on your server to compress images.

To ensure WebP images are previewed correctly in the admin panel, you may need to add the correct mime types to your server configuration.

**Apache Users (.htaccess):**

```apache
AddType image/webp .webp
```

**Nginx Users:**

Ensure your `mime.types` file includes:

```nginx
image/webp webp;
```

### Optimizing images

Before uploading your image, you may choose to optimize it by converting to your chosen format. The file saved to your disk will be the converted version only.

E.g. I want to convert my image to 'webp': 

`````php
use Filament\Forms\Components\FileUpload;

FileUpload::make('attachment')
    ->image()
    ->optimize('webp'),
`````

You can also pass a second parameter to `optimize` to set the quality of the image (0-100). This is useful if you want to compress the image further.

`````php
use Filament\Forms\Components\FileUpload;

FileUpload::make('attachment')
    ->image()
    ->optimize('webp', 85),
`````

You can do exactly the same using `SpatieMediaLibraryFileUpload`:

`````php
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

SpatieMediaLibraryFileUpload::make('attachment')
    ->image()
    ->optimize('webp', 85),
`````

### Dynamic values (Closures)

You may pass a **Closure** to almost any parameter (like `quality`, `resize`, `maxImageWidth`, etc.) to dynamically set values based on form state or logic.

For example, adjusting quality based on a toggle:

`````php
use Filament\Forms\Components\FileUpload;

FileUpload::make('attachment')
    ->image()
    ->optimize(
        format: 'webp', 
        quality: fn ($get) => $get('high_quality') ? 100 : 50
    ),
`````

This also works with `SpatieMediaLibraryFileUpload`:

`````php
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

SpatieMediaLibraryFileUpload::make('attachment')
    ->image()
    ->optimize(
        format: 'webp', 
        quality: fn ($get) => $get('is_premium') ? 100 : 80
    ),
`````

### Resizing images

You may also want to resize an image by passing in a percentage you would like to reduce the image by. This will also maintain aspect ratio.

E.g. I'd like to reduce my image (1280px x 720px) by 50%:

`````php
use Filament\Forms\Components\FileUpload;

FileUpload::make('attachment')
    ->image()
    ->resize(50),
`````

Uploaded image size is 640px x 360px.

You can do the same using `SpatieMediaLibraryFileUpload`:

`````php
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

SpatieMediaLibraryFileUpload::make('attachment')
    ->image()
    ->resize(50),
`````

### Add maximum width and/or height

You can also add a maximum width and/or height to the image. This will resize the image to the maximum width and/or height, maintaining the aspect ratio.

`````php
use Filament\Forms\Components\FileUpload;

FileUpload::make('attachment')
    ->image()
    ->maxImageWidth(1024)
    ->maxImageHeight(768),
`````

### Combining methods

You can combine these two methods for maximum optimization.

> [!TIP]
> Most methods like `resize()`, `maxImageWidth()`, and `maxImageHeight()` also support **Closures**, allowing you to dynamically adjust settings based on form state.

`````php
use Filament\Forms\Components\FileUpload;

FileUpload::make('attachment')
	->image()
	->optimize('webp')
	->maxImageWidth(1024)
	->maxImageHeight(768)
	->resize(50),
`````

`````php
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

SpatieMediaLibraryFileUpload::make('attachment')
    ->image()
	->optimize('webp')
	->maxImageWidth(1024)
	->maxImageHeight(768)
    ->resize(50),
`````

### Multiple images

You can also do this with multiple images - all images will be converted to the same format and reduced with the same percentage passed in. Just chain on `multiple()` to your upload:

`````php
use Filament\Forms\Components\FileUpload;

FileUpload::make('attachment')
    ->image()
	->multiple()
	->optimize('jpg')
    ->resize(50),
`````

`````php
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

SpatieMediaLibraryFileUpload::make('attachment')
    ->image()
	->multiple()
	->optimize('jpg')
    ->resize(50),
`````

### Examples 

![Before](images/before.jpg) 

![After](images/after.jpg)

### Debugging

- If you see a 'not found' exception, including "Method `optimize`" or "Method `resize`", ensure you run `composer update` so that your lock file is in sync with your `composer.json`. 

- You might see a 'Waiting for size' message and an infinite loading state on the component and the likely cause of this is a CORS issue. This can be quickly be resolved by ensuring you are serving and upload images from the same domain. Check your Javascript console for more information.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Dani Hidayat](https://github.com/danihidayatx)
- [Josh Embling](https://github.com/joshembling)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
