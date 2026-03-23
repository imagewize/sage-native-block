/**
 * View script for the block.
 *
 * This file handles any client-side functionality needed for the block
 * on the frontend of the site.
 */

(function() {
    // Select all instances of this block on the page
    const blocks = document.querySelectorAll('.wp-block-vendor-example-block');

    // Function to initialize a block
    function initBlock(block) {
      // Example: Add counter animation for statistics
      const stats = block.querySelectorAll('.statistics-row .wp-block-heading');

      stats.forEach(function(stat) {
        // Add your counter animation logic here
        // Example: Animate numbers when scrolling into view
      });
    }

    // Initialize each block found
    if (blocks.length) {
      blocks.forEach(function(block) {
        initBlock(block);
      });
    }
  })();
