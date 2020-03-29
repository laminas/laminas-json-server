# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 3.2.3 - TBD

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 3.2.2 - 2020-03-29

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#8](https://github.com/laminas/laminas-json-server/pull/8) fixes fluent interface on `Smd::setDescription()`.

- Fixed `replace` version constraint in composer.json so repository can be used as replacement of `zendframework/zend-json-server:^3.2.0`.

## 3.2.1 - 2020-01-16

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#1](https://github.com/laminas/laminas-json-server/pull/1) Allows optional `data` field in Error object to be omitted

## 3.2.0 - 2019-10-17

### Added

- [zendframework/zend-json-server#14](https://github.com/zendframework/zend-json-server/pull/14) adds support for PHP 7.3.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- [zendframework/zend-json-server#14](https://github.com/zendframework/zend-json-server/pull/14) removes support for laminas-stdlib v2 releases.

### Fixed

- Nothing.

## 3.1.0 - 2018-04-25

### Added

- [zendframework/zend-json-server#13](https://github.com/zendframework/zend-json-server/pull/13) adds support for PHP 7.1 and 7.2.

### Changed

- [zendframework/zend-json-server#6](https://github.com/zendframework/zend-json-server/pull/6) updates the default `Accept` and `Content-Type` header values issued
  by the `Client` to `application/json-rpc`, which is more correct per the JSON-RPC spec.

### Deprecated

- Nothing.

### Removed

- [zendframework/zend-json-server#13](https://github.com/zendframework/zend-json-server/pull/13) removes support for PHP 5.5.

- [zendframework/zend-json-server#13](https://github.com/zendframework/zend-json-server/pull/13) removes support for HHVM.

### Fixed

- [zendframework/zend-json-server#6](https://github.com/zendframework/zend-json-server/pull/6) fixes how the `Client` handles the `Accept` and `Content-Type` headers,
  honoring those already present in the request, and providing defaults if not.

- [zendframework/zend-json-server#4](https://github.com/zendframework/zend-json-server/pull/4) provides a fix to how parameters are validated, ensuring default values
  are provided when known (and only when named parameters are provided), and an error
  is raised when not enough parameters are provided.

- [zendframework/zend-json-server#2](https://github.com/zendframework/zend-json-server/pull/2) fixes an issue with how the `Response::setOptions()` method would handle a
  key of `0`; previously, it would (incorrectly) set the JSON-RPC version of the response;
  now it does not.

## 3.0.0 - 2015-03-31

First release as a standalone component. Previous releases were as part of
[laminas-json](https://github.com/laminas/laminas-json).

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.
