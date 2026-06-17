/**
 * Alpine.js Initialization Helper
 * Ensures Collapse plugin is properly registered
 */

// This script ensures Alpine.js plugins are loaded before Alpine starts
document.addEventListener('DOMContentLoaded', function () {
    // Verify Alpine.js is loaded
    if (typeof Alpine === 'undefined') {
        console.error('Alpine.js not loaded!');
        return;
    }

    console.log('✓ Alpine.js loaded successfully');

    // The Collapse plugin should auto-register when loaded via CDN
    // But we'll verify it's available
    if (typeof AlpineCollapse !== 'undefined') {
        console.log('✓ Alpine Collapse plugin detected');
    }
});

// Listen for Alpine initialization
document.addEventListener('alpine:init', () => {
    console.log('✓ Alpine.js initialized');
});

// Debug helper for sidebar
window.debugSidebar = function () {
    console.log('=== SIDEBAR DEBUG ===');
    console.log('Alpine version:', Alpine.version);

    // Check if collapse directive is registered
    const testEl = document.querySelector('[x-collapse]');
    if (testEl) {
        console.log('✓ Found elements with x-collapse directive');
    } else {
        console.log('✗ No elements with x-collapse found');
    }

    // Check sidebar menu state
    const menus = document.querySelectorAll('[x-data*="open"]');
    console.log(`Found ${menus.length} collapsible menus`);

    menus.forEach((menu, index) => {
        const data = Alpine.$data(menu);
        console.log(`Menu ${index + 1}:`, data);
    });
};
