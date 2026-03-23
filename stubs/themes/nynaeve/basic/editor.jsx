import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';

/**
 * Define your block's default content structure.
 * Use native WordPress blocks (core/heading, core/paragraph, core/image, etc.)
 * Import SVG assets from resources/images/ and pass as `url` to core/image blocks.
 *
 * Example:
 *   import myIcon from '../../../images/icons/my-icon.svg';
 *   ['core/image', { url: myIcon, alt: 'My Icon', width: 28, height: 28, sizeSlug: 'full', linkDestination: 'none' }],
 */
const TEMPLATE = [
  ['core/heading', {
    level: 2,
    content: 'Block Heading',
    fontFamily: 'montserrat',
    style: { typography: { fontWeight: '800' } },
  }],
  ['core/paragraph', {
    content: 'Block content goes here.',
  }],
];

export default function Edit() {
  const blockProps = useBlockProps({
    className: '{{BLOCK_CLASS_NAME}}',
  });

  return (
    <div {...blockProps}>
      <InnerBlocks template={TEMPLATE} templateLock={false} />
    </div>
  );
}
