/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import Edit from './editor';
import Save from './save';

/**
 * Register the block
 */
registerBlockType(metadata.name, {
  ...metadata,
  edit: Edit,
  save: Save,
});