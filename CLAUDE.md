# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

`sage-native-block` is a Composer package that provides interactive CLI scaffolding for WordPress Gutenberg blocks in [Roots Sage](https://roots.io/sage/) themes. It integrates with Acorn (the Laravel-based framework for Sage) and is invoked via `wp acorn sage-native-block:create` inside a Sage theme.

## Commands

**Code formatting (Laravel Pint):**
```bash
composer run pint
```

**Manual testing inside a Sage theme:**
```bash
# Interactive mode
wp acorn sage-native-block:create

# Non-interactive with template
wp acorn sage-native-block:create imagewize/my-block --template=basic
wp acorn sage-native-block:create imagewize/my-block --template=nynaeve-cta --force

# Deprecated alias (still works)
wp acorn sage-native-block:add-setup imagewize/my-block --template=statistics
```

**Release tagging:**
```bash
git tag -a v2.x.x -m "Release v2.x.x"
git push origin v2.x.x
```

## Architecture

### Package Entry Point

`src/Providers/SageNativeBlockServiceProvider.php` registers the Artisan commands and publishes the config. Acorn auto-discovers this provider via `extra.acorn.providers` in `composer.json`.

### Main Command: `SageNativeBlockCommand`

`src/Console/SageNativeBlockCommand.php` is the core of the package. Key execution flow:

1. **Parse input** — block name like `imagewize/my-block` is split into vendor (`imagewize`) and block slug (`my-block`). If no vendor is given, it falls back to the configured default.
2. **Template selection** — interactive two-step picker (Category → Template) or direct `--template` flag. Auto-discovers custom theme templates from `block-templates/` directories.
3. **Update `app/setup.php`** — injects block auto-registration code (scans `resources/js/blocks/` for `block.json` files).
4. **Update `resources/js/editor.js`** — ensures `import.meta.glob('./blocks/**/index.js', { eager: true })` exists.
5. **Copy and process stubs** — copies 7 files from the selected stub directory, applying placeholder replacements.

The deprecated `SageNativeBlockAddSetupCommand` extends the main command and just shows a deprecation warning before delegating.

### Template System

Templates live in `stubs/` and are registered in `config/sage-native-block.php`:

```
stubs/
├── block/              # Basic template (key: "basic")
├── generic/            # Theme-agnostic templates (innerblocks, two-column, statistics, cta)
└── themes/
    └── nynaeve/        # Nynaeve theme-specific templates (keys: "nynaeve-*")
```

Each template contains exactly 7 files: `block.json`, `index.js`, `editor.jsx`, `save.jsx`, `editor.css`, `style.css`, `view.js`.

**Adding a new template:** create the stub directory with 7 files, then register it in `config/sage-native-block.php` under `templates`.

**Theme-specific templates** follow the key pattern `{theme-name}-{type}` and have a `README.md` documenting required `theme.json` settings (font families, colors, etc.).

### Placeholder Replacement

When stubs are copied, these replacements are applied:

| Placeholder | Replaced with | Files affected |
|---|---|---|
| `vendor/example-block` | Full block name (e.g. `imagewize/my-block`) | `block.json` |
| `'vendor'` (textdomain) | Vendor slug | `block.json` |
| `{{BLOCK_CLASS_NAME}}` | CSS class (e.g. `wp-block-imagewize-my-block`) | `editor.jsx` |
| `.wp-block-vendor-example-block` | CSS selector | `style.css`, `editor.css` |

Block output directories use only the block slug (no vendor): `imagewize/my-block` → `resources/js/blocks/my-block/`.

### Configuration (`config/sage-native-block.php`)

- `templates` — map of template key → `name`, `description`, `stub_path`
- `default_template` — fallback when no `--template` flag is given
- `typography_presets` — Montserrat/Open Sans font settings used in Nynaeve templates
- `spacing_presets` — reusable spacing values

### Nynaeve Theme Templates

Nynaeve templates (`stubs/themes/nynaeve/`) are production-ready and differ from generic ones:
- `block.json` uses `"category": "imagewize"`, full `supports` (align wide/full, anchor, spacing, color), and `"align": "full"` default with margin-reset attribute
- `editor.jsx` uses `{{BLOCK_CLASS_NAME}}` placeholder (not the legacy CSS class)
- Require `theme.json` font families (`montserrat`, `open-sans`) and color palette to be defined in the consuming theme

## Git Conventions

- **Atomic commits** — each commit should represent one logical change. Don't bundle unrelated fixes or features.
- **No Claude references** — commit messages must never mention Claude, AI, or be co-authored by Claude.

## Release Process

1. Update `CHANGELOG.md` with version and changes
2. Tag: `git tag -a vX.Y.Z -m "Release vX.Y.Z"` and push the tag
3. Create GitHub release using the CHANGELOG section
