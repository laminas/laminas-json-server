# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 3.1.0 - 2018-04-25

### Added

- [#13](https://github.com/zendframework/zend-json-server/pull/13) adds support for PHP 7.1 and 7.2.

### Changed

- [#6](https://github.com/zendframework/zend-json-server/pull/6) updates the default `Accept` and `Content-Type` header values issued
  by the `Client` to `application/json-rpc`, which is more correct per the JSON-RPC spec.

### Deprecated

- Nothing.

### Removed

- [#13](https://github.com/zendframework/zend-json-server/pull/13) removes support for PHP 5.5.

- [#13](https://github.com/zendframework/zend-json-server/pull/13) removes support for HHVM.

### Fixed

- [#6](https://github.com/zendframework/zend-json-server/pull/6) fixes how the `Client` handles the `Accept` and `Content-Type` headers,
  honoring those already present in the request, and providing defaults if not.

- [#4](https://github.com/zendframework/zend-json-server/pull/4) provides a fix to how parameters are validated, ensuring default values
  are provided when known (and only when named parameters are provided), and an error
  is raised when not enough parameters are provided.

- [#2](https://github.com/zendframework/zend-json-server/pull/2) fixes an issue with how the `Response::setOptions()` method would handle a
  key of `0`; previously, it would (incorrectly) set the JSON-RPC version of the response;
  now it does not.

## 3.0.0 - 2015-03-31

First release as a standalone component. Previous releases were as part of
[zend-json](https://github.com/zendframework/zend-json).

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.
