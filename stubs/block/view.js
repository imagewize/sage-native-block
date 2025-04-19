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
      // Example: Add click event listener
      block.addEventListener('click', function(e) {
        // Example functionality - uncomment to use
        // console.log('Block clicked:', this);
      });
    }
  
    // Initialize each block found
    if (blocks.length) {
      blocks.forEach(function(block) {
        initBlock(block);
      });
    }
  })();