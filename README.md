# FileManager for handling filesytem on local disk and cloud storage

FileManager provides a powerful filesystem abstraction thanks to the wonderful [Flysystem](https://github.com/thephpleague/flysystem). PHP package by Frank de Jonge. The Flysystem integration provides simple to use drivers for working with local filesystems and Amazon S3. Even better, it's amazingly simple to switch between these storage options as the API remains the same for each system.

## Installation

The recommended way to install FileManager is via Composer:

```bash
composer require biurad/biurad-flysystem
```

It requires PHP version 7.1 and supports PHP up to 7.4. The dev-master version requires PHP 7.2.

## How To Use

Filemanager's Flysystem integration provides drivers for several "drivers" usage. However, Flysystem is not limited to these and has adapters for many other storage systems. You can create a custom driver if you want to use one of these additional adapters with FileManager.

In order to set up the custom filesystem you will need a Flysystem adapter:

```php
// Let's use the local filesystem for this example.
$driver = new League\Flysystem\Adapter\Local(getcwd());
```

When using the `local` driver, all file operations are relative to the `root` directory defined in your driver's construct. Let's say if the value is set to the `storage/` directory. Therefore, the following method would store a file in `storage/file.txt`:

```php
use BiuradPHP\FileManager\FileManager;
use BiuradPHP\FileManager\Config\FileConfig;

$filesystem = new FileManager($driver, new FileConfig()); // $driver from the previous

return $filesystem->put('file.txt', 'Contents');
```

To enable caching for a given disk, you may wrap driver in the `League\Flysystem\Cached\CachedAdapter` directive.

```php
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Cached\Storage\Memory;
use BiuradPHP\FileManager\Config\FileConfig;

$caching = new CachedAdapter($driver, new Memory());
$filesystem = new FileManager($caching, new FileConfig()); // $driver from the previous

return $filesystem->put('file.txt', 'Contents');
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Testing

To run the tests you'll have to start the included node based server if any first in a separate terminal window.

With the server running, you can start testing.

```bash
vendor/bin/phpunit
```

## Security

If you discover any security related issues, please report using the issue tracker.
use our example [Issue Report](.github/ISSUE_TEMPLATE/Bug_report.md) template.

## Want to be listed on our projects website

You're free to use this package, but if it makes it to your production environment we highly appreciate you sending us a message on our website, mentioning which of our package(s) you are using.

Post Here: [Project Patreons - https://patreons.biurad.com](https://patreons.biurad.com)

We publish all received request's on our website.

## Credits

- [Divine Niiquaye](https://github.com/divineniiquaye)
- [All Contributors](https://biurad.com/projects/biurad-flysystem/contributers)

## Support us

`Biurad Lap` is a technology agency in Accra, Ghana. You'll find an overview of all our open source projects [on our website](https://biurad.com/opensource).

Does your business depend on our contributions? Reach out and support us on to build more project's. We want to build over one hundred project's in two years. [Support Us](https://biurad.com/donate) achieve our goal.

Reach out and support us on [Patreon](https://www.patreon.com/biurad). All pledges will be dedicated to allocating workforce on maintenance and new awesome stuff.

[Thanks to all who made Donations and Pledges to Us.](.github/ISSUE_TEMPLATE/Support_us.md)

## License

The BSD-3-Clause . Please see [License File](LICENSE.md) for more information.
