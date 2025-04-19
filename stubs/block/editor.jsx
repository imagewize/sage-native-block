/**
 * WordPress dependencies
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Edit function that renders in the admin
 */
export default function Edit() {
  const blockProps = useBlockProps();
  
  return (
    <div { ...blockProps }>
      <p>{ __('Example block content. Edit this template to create your own block.', 'vendor') }</p>
      <InnerBlocks />
    </div>
  );
}