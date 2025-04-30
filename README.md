# Acorn Sage Native Block Package

This package helps you create and manage native Gutenberg blocks in your [Sage](https://roots.io/sage/) theme. It provides a convenient command to scaffold block files and automatically register them with WordPress.

## Features

- Scaffolds a complete native block structure in your Sage theme
- Automatically adds block registration code to your theme's setup file
- Creates all necessary block files (JS, JSX, CSS) with proper configuration
- Handles proper block naming with vendor prefixes
- Creates editor and frontend styles for your blocks
- Ensures proper imports of block JS files

## Installation

You can install this package with Composer:

```bash
composer require imagewize/sage-native-block
```

You can publish the config file with:

```shell
wp acorn vendor:publish --provider="Imagewize\SageNativeBlockPackage\Providers\SageNativeBlockServiceProvider" --tag="config"
```
**NB** Currently not necessary as we don't have any config options yet.

## Usage

### Creating a new block

Run the sage-native-block command to create a block with default settings:

```shell
wp acorn sage-native-block:add-setup
```

This will:
1. Create an `example-block` in your theme's `resources/js/blocks` directory
2. Add block registration code to your theme's `app/setup.php` if not already present
3. Update `resources/js/editor.js` to import the block files

### Creating a custom block

To create a block with a custom name:

```shell
wp acorn sage-native-block:add-setup my-cool-block
```

This will create a block named `vendor/my-cool-block` with all the necessary files.

### Creating a block with custom vendor prefix

To create a block with a specific vendor prefix:

```shell
$ wp acorn sage-native-block:add-setup imagewize/my-cool-block
```

This creates a block with proper namespace `imagewize/my-cool-block`.

### Skipping confirmation

Use the `--force` flag to skip the confirmation prompt:

```shell
$ wp acorn sage-native-block:add-setup --force
$ wp acorn sage-native-block:add-setup my-block-name --force
$ wp acorn sage-native-block:add-setup imagewize/custom-block --force
```

## Block Structure

The command creates the following files in your `resources/js/blocks/<block-name>/` directory:

- `block.json` - Block metadata and configuration
- `index.js` - Main block entry point
- `editor.jsx` - React component for the editor
- `save.jsx` - React component for the frontend
- `editor.css` - Styles for the block in the editor
- `style.css` - Styles for the block on the frontend
- `view.js` - Frontend JavaScript for the block

## Notes

- The block.json's `name` will always include a vendor prefix (e.g., `vendor/cool-block` or `imagewize/my-cool-block`)
- The block.json's `textdomain` will match the vendor prefix
- The default CSS class will be based on the full block name (e.g., `wp-block-vendor-cool-block`)
- Block styles and scripts are automatically registered via the `block.json` file
- The command will also ensure your theme's `editor.js` file imports all block index.js files

## File Structure Example

For a block named `imagewize/testimonial`, the command will create:

```
resources/js/blocks/testimonial/
├── block.json
├── editor.css
├── editor.jsx
├── index.js
├── save.jsx
├── style.css
└── view.js
```
