# Acorn Sage Native Block Package

[![Total Downloads](https://img.shields.io/packagist/dt/imagewize/sage-native-block.svg)](https://packagist.org/packages/imagewize/sage-native-block)
[![Latest Stable Version](https://img.shields.io/packagist/v/imagewize/sage-native-block.svg)](https://packagist.org/packages/imagewize/sage-native-block)
[![License](https://img.shields.io/packagist/l/imagewize/sage-native-block.svg)](https://packagist.org/packages/imagewize/sage-native-block)

This package helps you create and manage native Gutenberg blocks in your [Sage](https://roots.io/sage/) theme. It provides a convenient command to scaffold block files and automatically register them with WordPress.

## Features

- **Hierarchical Template Selection** - Organized two-step selection process with template categories
- **Multiple Block Templates** - Choose from pre-configured templates for common block patterns
- **Dynamic Theme Detection** - Automatically discovers and displays theme-specific templates
- Scaffolds a complete native block structure in your Sage theme
- Automatically adds block registration code to your theme's setup file
- Creates all necessary block files (JS, JSX, CSS) with proper configuration
- Handles proper block naming with vendor prefixes
- Creates editor and frontend styles for your blocks
- Ensures proper imports of block JS files
- **80% faster development** - Start with pre-configured templates instead of building from scratch

## Installation

You can install this package with Composer from your Sage 11+ theme root directory (not from the Bedrock root):

```bash
composer require imagewize/sage-native-block --dev
```

**That's it!** The package is ready to use. No additional setup required.

You can drop `--dev` but then it will be included in your production build.

## Configuration (Optional)

The package works out of the box with default settings. However, you can optionally publish the config file to customize template settings:

```shell
wp acorn vendor:publish --provider="Imagewize\SageNativeBlockPackage\Providers\SageNativeBlockServiceProvider"
```

**When to publish:**
- You want to customize typography or spacing presets
- You want to add your own template definitions to the config
- You're experiencing config loading issues in your environment (rare)

**Note:** Since v2.0.1, the package automatically falls back to loading config directly if it's not published, making this step truly optional.

## Usage

### Creating a new block (Interactive Mode - Recommended)

Simply run the command and follow the prompts:

```shell
wp acorn sage-native-block:create
```

You'll be guided through an interactive setup:
1. **Block name**: Enter your block name (e.g., "my-stats")
2. **Vendor prefix**: Optionally specify a vendor (defaults to "vendor")
3. **Template category**: Choose between Basic Block, Generic Templates, or Theme-specific templates
4. **Template selection**: Choose a specific template within your selected category
5. **Confirmation**: Review and confirm your choices

The command will then:
- Create the block in your theme's `resources/js/blocks` directory
- Add block registration code to your theme's `app/setup.php` if not already present
- Update `resources/js/editor.js` to import the block files

### Non-Interactive Mode (for automation)

You can still provide all parameters via CLI arguments:

```shell
wp acorn sage-native-block:create my-block --template=statistics --force
```

## Command Output

The package provides a clean, professional terminal interface:

```
🔨 Creating block: imagewize/my-stats
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  Template:  Statistics Section (Generic)
  Location:  resources/js/blocks/my-stats

  Continue? (yes/no) [no]: yes

Setup:
  ✓ Block registration configured
  ✓ Editor imports configured

Files:
  ✓ block.json, index.js
  ✓ editor.jsx, save.jsx
  ✓ editor.css, style.css
  ✓ view.js

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✓ Success! Block ready at resources/js/blocks/my-stats
```

**Features:**
- Clear visual hierarchy with emoji header and separators
- Color-coded output for easy scanning
- Check marks (✓) for quick status updates
- Grouped file operations reduce noise
- Relative paths for better readability

### Block Templates

The package provides an organized, hierarchical template selection system. When creating a block interactively, you'll first choose a **template category**, then select a specific template within that category.

#### Template Categories

The command automatically presents available categories:

1. **Basic Block** - Default simple block (selected directly, no sub-options)
2. **Generic Templates** - Universal, theme-agnostic templates that work everywhere
3. **Theme Templates** - Production-ready templates from specific themes
4. **Custom Templates** - Your own custom templates (auto-detected)

> 💡 **Custom Templates**: Create your own templates without modifying the vendor package!
> - Create a folder in your theme: `block-templates/my-template/`
> - Add required files: `block.json`, `index.js`, `editor.jsx`, `save.jsx`, etc.
> - Optional: Add `template-meta.json` for custom name, description, and category
> - Templates automatically appear in the selection menu
> - Override package templates by using the same template name

The package includes templates in these categories:

#### 🟢 Generic Templates (Recommended)
Universal templates that work with ANY theme - no dependencies required:

| Template | Description | Use Case |
|----------|-------------|----------|
| **basic** | Simple block with InnerBlocks support | General-purpose container blocks |
| **innerblocks** | Minimal heading and content template | Section blocks (add your own styles) |
| **two-column** | Basic two-column layout structure | Feature comparisons, benefits |
| **statistics** | Simple statistics layout | Impact metrics, key numbers |
| **cta** | Basic call-to-action with button | Lead generation, conversions |

#### 🎨 Theme-Specific Templates

Real-world examples from production themes. Currently featuring templates from the **[Nynaeve theme](https://github.com/imagewize/nynaeve)** by Imagewize.

⚠️ **Nynaeve theme requirements:**
- Font families: `montserrat`, `open-sans`
- Color slugs: `main`, `secondary`, `tertiary`, `base`
- Font sizes: `3xl`, `2xl`, `xl`, `lg`, `base`, `sm`

| Template | Description | Use Case |
|----------|-------------|----------|
| **nynaeve-innerblocks** | Pre-styled with Nynaeve typography | Production-ready container |
| **nynaeve-two-column** | Card-style layout from Nynaeve | Polished two-column sections |
| **nynaeve-statistics** | Complete statistics from Nynaeve | Production-ready stats display |
| **nynaeve-cta** | Styled CTA from Nynaeve theme | Ready-to-use call-to-action |

> 💡 **Tip:** Use generic templates for universal compatibility, or Nynaeve templates if your theme matches its setup. See [`stubs/themes/nynaeve/README.md`](stubs/themes/nynaeve/README.md) for detailed requirements.

### Command Examples

**Interactive mode (easiest):**
```shell
# Follow prompts to create any block
wp acorn sage-native-block:create
```

**With block name (prompts for category and template):**
```shell
wp acorn sage-native-block:create my-stats
```

**With vendor prefix:**
```shell
wp acorn sage-native-block:create imagewize/my-stats
```

**Fully automated (no prompts):**
```shell
# Generic templates (work everywhere)
wp acorn sage-native-block:create my-stats --template=statistics --force
wp acorn sage-native-block:create my-cta --template=cta --force
wp acorn sage-native-block:create my-columns --template=two-column --force
wp acorn sage-native-block:create my-container --template=innerblocks --force

# Nynaeve theme templates (requires Nynaeve theme.json setup)
wp acorn sage-native-block:create my-stats --template=nynaeve-statistics --force
wp acorn sage-native-block:create imagewize/my-cta --template=nynaeve-cta --force
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

## How It Works

The command automatically handles:
- **Block naming** - Ensures proper vendor prefixes (e.g., `imagewize/my-block`)
- **CSS classes** - Generates block-specific classes (e.g., `wp-block-imagewize-my-block`)
- **Registration** - Adds code to `app/setup.php` to auto-register all blocks
- **Imports** - Updates `resources/js/editor.js` to load block scripts
- **File structure** - Creates organized directory with all 7 required files

**Example structure for `imagewize/testimonial`:**
```
resources/js/blocks/testimonial/
├── block.json      ← Block metadata
├── index.js        ← Registration entry point
├── editor.jsx      ← Edit component
├── save.jsx        ← Save component
├── editor.css      ← Editor-only styles
├── style.css       ← Frontend styles
└── view.js         ← Frontend JavaScript
```

> 💡 For technical details on the build process and architecture, see [Developer Documentation](docs/DEV.md)

## Customization

### Typography and Spacing Presets

**Optional:** If you want to customize global typography and spacing presets used by package templates, publish the config file:

```bash
wp acorn vendor:publish --provider="Imagewize\SageNativeBlockPackage\Providers\SageNativeBlockServiceProvider"
```

Then edit `config/sage-native-block.php` to match your theme's design system.

**Note:** This only affects package templates (basic, generic, nynaeve). Your custom templates in `block-templates/` are unaffected and use whatever styles you define in them.

### Creating Custom Templates

Want to create your own block templates? It's incredibly simple - **no configuration needed!**

#### Quick Start

1. **Create template folder** in your Sage theme root:
   ```bash
   mkdir -p block-templates/my-hero
   ```

2. **Add template files** (copy from an existing template or create from scratch):
   ```
   block-templates/my-hero/
   ├── block.json
   ├── index.js
   ├── editor.jsx
   ├── save.jsx
   ├── editor.css
   ├── style.css
   └── view.js
   ```

3. **Run the command** - Your template automatically appears in the menu:
   ```bash
   wp acorn sage-native-block:create
   ```

That's it! No config files, no vendor package modification needed.

#### Optional: Add Metadata

For better display names and organization, add `template-meta.json`:

```json
{
  "name": "Hero Section",
  "description": "Full-featured hero with background image support",
  "category": "custom"
}
```

**Metadata fields (all optional):**
- `name` - Display name in menu (defaults to folder name)
- `description` - Template description (defaults to "Custom template")
- `category` - Category name (defaults to "custom")
- `author` - Template author
- `version` - Template version

#### Override Package Templates

Create a template with the same name as a package template to override it:

```bash
# Override the generic "innerblocks" template
mkdir -p block-templates/innerblocks
# Add your custom files...
```

Your theme's version will be used instead of the package version.

#### Example: Creating a Hero Template

```bash
# 1. Copy an existing template as a starting point
cp -r vendor/imagewize/sage-native-block/stubs/generic/innerblocks block-templates/hero

# 2. Customize the files (editor.jsx, save.jsx, etc.)

# 3. Add metadata
cat > block-templates/hero/template-meta.json << 'EOF'
{
  "name": "Hero Section",
  "description": "Hero with heading, text, and background image",
  "category": "layout"
}
EOF

# 4. Use it!
wp acorn sage-native-block:create my-hero --template=hero
```

> 💡 **Tip**: Check out existing templates in `vendor/imagewize/sage-native-block/stubs/` for examples and inspiration.

For advanced template customization, see the [Custom Template Stubs Documentation](docs/CUSTOM-TEMPLATE-STUBS.md).

#### Directory Structure in Your Theme:
```
your-sage-theme/
├── block-templates/              ← Create this folder
│   ├── hero/                     ← Your custom template
│   │   ├── block.json
│   │   ├── index.js
│   │   ├── editor.jsx
│   │   ├── save.jsx
│   │   ├── editor.css
│   │   ├── style.css
│   │   ├── view.js
│   │   └── template-meta.json    ← Optional metadata
│   ├── cta/                      ← Another custom template
│   │   └── ...
│   └── stats/                    ← Yet another template
│       └── ...
├── resources/
│   └── js/
│       └── blocks/               ← Generated blocks go here
└── config/
    └── sage-native-block.php     ← Optional: published config
```

Templates in `block-templates/` are automatically discovered - no configuration needed!

### Contributing Templates

Have templates from your production theme? We welcome community contributions! Check the [Theme Templates Guide](stubs/themes/README.md) for guidelines on contributing theme-specific templates.

## Benefits of Using Templates

- **80% faster development** - Start with pre-configured templates instead of building from scratch
- **Organized selection** - Hierarchical categories make finding the right template easy
- **Extensible system** - Add your own theme templates with automatic detection
- **Consistent patterns** - All blocks follow established structure and best practices
- **Theme integration** - Templates use theme.json values for typography and colors
- **Proper InnerBlocks setup** - Avoid common mistakes with InnerBlocks configuration
- **Reduced errors** - Well-tested templates reduce debugging time
- **Learning tool** - See WordPress block best practices in generated code

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for detailed version history and release notes.
