# Changelog

All notable changes to `biurad/biurad-flysystem` will be documented in this file.

## 0.1.7 2020-06-17

- Added ability to add custom adapter in BiuradPHP and Nette Framework
- Added `FlysystemMap` class for multiple filesystems mounting
- Added phpunit tests
- Updated php files header doc
- Updated README.md file
- Renamed `ConnectorInterface` to `FlyAdapterInterface`
- Renamed `CloudConnectionInterface` to `FlysystemMapInterface`
- Renamed `FileManagerInterface` to `FlysystemInterface`
- Deleted `FileConfig` class, as it's unused
- Moved `ConnectionFactory` class to base namespace
- Moved `StreamWrapper` class under **\Streams** sub-namespace
- Improved code complexity and performance using cs fixtures
- Fixed errors with missing functions from biurad/biurad-helpers

## 0.1.0 - 2019-12-17

- First release
