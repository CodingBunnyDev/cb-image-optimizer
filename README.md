# CodingBunny Image Optimizer

![License: GPL v3](https://img.shields.io/badge/license-GPL%20v3-blue.svg)
![WordPress Version](https://img.shields.io/badge/WordPress-%3E%3D%206.0-blue.svg)
![PHP Version](https://img.shields.io/badge/PHP-%3E%3D%208.0-orange.svg)
![Version](https://img.shields.io/badge/version-1.2.3-green.svg)

**CodingBunny Image Optimizer** is a WordPress plugin designed to enhance website performance by automatically compressing and optimizing images upon upload. This plugin makes it easy to maintain fast loading speeds and provides options for a PRO version with advanced features.

## Features

- **Automatic Image Optimization**: Compress images automatically when you upload them to your WordPress site.
- **Custom Admin Menu**: Easily manage plugin settings via an intuitive interface in the WordPress dashboard.
- **PRO Version**: Offers additional features for professional users.
- **Multi-language Support**: Translate the plugin into other languages (with translation files located in the `/languages` folder).

## Installation

1. Download the plugin and unzip it.
2. Upload the `coding-bunny-image-optimizer` folder to the `/wp-content/plugins/` directory.
3. Activate the plugin via the 'Plugins' menu in WordPress.
4. Access the **Settings** page through the WordPress admin menu to configure the plugin.

## Usage

Once activated, the plugin automatically compresses images on upload. The **Settings** page in the admin menu allows for configuration and access to advanced options if you upgrade to the PRO version.

## PRO Version

To access advanced features in the PRO version, click on the **Get CodingBunny Image Optimizer PRO!** link in the plugins list.

## Actions & Filters

- **`plugin_action_links_coding-bunny-image-optimizer`**: Adds "Settings" and "Get PRO" links to the plugin's row on the WordPress plugins page.
- **`plugins_loaded`**: Loads the text domain for translations.

## Development

For developers who want to customize or contribute:

1. Clone this repository: `git clone https://github.com/CodingBunny/image-optimizer.git`
2. Navigate to the plugin's folder: `cd coding-bunny-image-optimizer`
3. Customize or contribute as needed. Pull requests are welcome!

### File Structure

- `inc/admin-menu.php` - Admin menu configuration.
- `inc/licence-validation.php` - License validation for the PRO version.
- `inc/updates-check.php` - Checks for plugin updates.
- `inc/settings-page.php` - Settings page definitions.
- `inc/enqueue-scripts.php` - Enqueue necessary CSS and JS files.

## Text Domain & Translations

The plugin is translation-ready. The text domain `coding-bunny-image-optimizer` is used to translate strings across the plugin. Translation files should be stored in the `/languages` folder.

## License

This plugin is licensed under the [GNU General Public License v3.0](https://www.gnu.org/licenses/gpl-3.0.html).

## Author

**CodingBunny**  
[Website](https://coding-bunny.com)  
[Support](https://coding-bunny.com/support)

## Changelog

### 1.2.3
- New - Added Thai language support.
- Improvement - General code improvement.
- Fix - Fixed Bulk Resize translation problem.

### 1.2.2
- Fix - Fixed the error that allowed images to be enlarged with Imagick.

### 1.2.1
- New - Added conversion status bar.
- Improvement - General code safety improvement.

### 1.2.0
- New - Added conversion quality setting.
- New - Added button to convert all library images without selecting them.
- Improvement - Intermediate sizes of images in library are now removed after conversion.
- Fix - During bulk optimization, images were being resized even with the option turned off.

### 1.1.0
- New - Added bulk optimization of images in library.
- Improvement - Changed interface style.

### 1.1.0
- New - First release.
