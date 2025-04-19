/**
 * WordPress dependencies
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';

/**
 * Save function that defines output on the frontend
 */
export default function Save() {
  const blockProps = useBlockProps.save();
  
  return (
    <div { ...blockProps }>
      <InnerBlocks.Content />
    </div>
  );
}