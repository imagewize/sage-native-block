<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Vendor Prefix
    |--------------------------------------------------------------------------
    |
    | The default vendor prefix to use when creating blocks without a specified
    | vendor prefix. This helps maintain consistent naming across your blocks.
    |
    */
    'default_vendor_prefix' => 'vendor',

    /*
    |--------------------------------------------------------------------------
    | Block Directory Path
    |--------------------------------------------------------------------------
    |
    | The relative path where block files will be created within your theme.
    | Default: resources/js/blocks
    |
    */
    'block_directory' => 'resources/js/blocks',

    /*
    |--------------------------------------------------------------------------
    | Template Paths
    |--------------------------------------------------------------------------
    |
    | Customize the stub templates used for generating block files.
    | Set to null to use package defaults.
    |
    */
    'templates' => [
        'block_json' => null,    // Path to custom block.json template
        'index_js' => null,      // Path to custom index.js template
        'editor_jsx' => null,    // Path to custom editor.jsx template
        'save_jsx' => null,      // Path to custom save.jsx template
        'editor_css' => null,    // Path to custom editor.css template
        'style_css' => null,     // Path to custom style.css template
        'view_js' => null,       // Path to custom view.js template
    ],

];
