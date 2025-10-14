# Acorn Sage Native Block Package

This package helps you create and manage native Gutenberg blocks in your [Sage](https://roots.io/sage/) theme. It provides a convenient command to scaffold block files and automatically register them with WordPress.

## Features

- **Multiple Block Templates** - Choose from pre-configured templates for common block patterns
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

**NB** You can drop `--dev` but then it will be included in your production build.


## Configuration

You can publish the config file with:

```shell
wp acorn vendor:publish --provider="Imagewize\SageNativeBlockPackage\Providers\SageNativeBlockServiceProvider"
```

**NB**: This is recommended to customize template settings. The package includes default configuration with 5 block templates and typography/spacing presets.

## Usage

### Creating a new block (Interactive Mode - Recommended)

Simply run the command and follow the prompts:

```shell
wp acorn sage-native-block:create
```

You'll be guided through an interactive setup:
1. **Block name**: Enter your block name (e.g., "my-stats")
2. **Vendor prefix**: Optionally specify a vendor (defaults to "vendor")
3. **Template selection**: Choose from available block templates
4. **Confirmation**: Review and confirm your choices

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

The package includes templates in two categories:

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

**With block name (still prompts for template):**
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

After publishing the config file (`wp acorn vendor:publish`), you can customize typography and spacing presets in `config/sage-native-block.php` to match your theme's design system.

### Creating Custom Templates

Want to create your own block templates? See the [Developer Documentation](docs/DEV.md#creating-custom-templates) for detailed instructions on:
- Template file requirements
- Placeholder system
- Registration process
- Best practices

### Contributing Templates

Have templates from your production theme? We welcome community contributions! Check the [Theme Templates Guide](stubs/themes/README.md) for guidelines on contributing theme-specific templates.

## Benefits of Using Templates

- **80% faster development** - Start with pre-configured templates instead of building from scratch
- **Consistent patterns** - All blocks follow established structure and best practices
- **Theme integration** - Templates use theme.json values for typography and colors
- **Proper InnerBlocks setup** - Avoid common mistakes with InnerBlocks configuration
- **Reduced errors** - Well-tested templates reduce debugging time
- **Learning tool** - See WordPress block best practices in generated code

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for detailed version history and release notes.
