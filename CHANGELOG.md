# Changelog

## [v0.1.1-beta](https://github.com/nickwelsh/fennel/releases/tag/v0.1.1-beta) - 2025-04-16
### Fixed
- `anim` flag was previously ignored when rendering image URLs. It now correctly applies the animation parameter.

## [v0.1.0-beta](https://github.com/nickwelsh/fennel/releases/tag/v0.1.0-beta) – Initial Beta Release

This is the first beta release of **Fennel**,
a self-hostable image optimization layer that closely mirrors Cloudflare's Image Resizing API.

### ✅ What's Supported
- Nearly **1:1 support** for Cloudflare's image transformation parameters
- Full support for:
    - Resizing
    - Format conversion
    - Fit modes
    - Quality control
    - Blur, sharpen, saturation, brightness, contrast, gamma
    - Rotation, flip, trim, gravity, dpr
- URL-based transforms via `/{endpoint}/{options}/{path}` routing (images is the default endpoint)
- Blade component for use in Laravel views
- Ability to serve images from the public path or a filesystem disk

### 🚫 Not Implemented
- `compression` option
    - This simply saves a fraction of a second by using a slightly faster format, but a larger file size.
    - The time savings of the compression could be offset by the time it takes to download the slightly larger file.
    - This option isn't recommended by Cloudflare anyway, and I'm not sure how much of a benefit it would provide.
- `onerror` fallback behavior
    - This just happens automatically.

### ⚠️ Known Differences
- Some slight rendering differences may occur compared to Cloudflare
