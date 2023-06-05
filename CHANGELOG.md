# Changelog

### [1.0.4]
- (fix) cache not working because of doctrine proxy object.

### [1.0.3]
- (fix) dont create alias of abstract service cache.adapter.array

### [1.0.2]
- (improvement) Add metadata caching for more efficient deleting.
- (upgrade) Use the new symfony ^6.1 bundle system.

### [1.0.1]
- (fix) Out of memory error when soft deleting large entities.
- (fix) symfony/doctrine-bridge 6.3 using doctrine subscribers as is deprecated. Use listeners instead.

### [1.0.0]
- Initial release
