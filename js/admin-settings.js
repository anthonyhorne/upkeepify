$(function() {
    // Function to toggle SMTP settings visibility
    function toggleSmtpSettings() {
        // Toggle the visibility of SMTP settings based on the checkbox state
        $('.smtp_setting').toggle(!$('#upkeepify_smtp_option').is(':checked'));
    }

    // Function to toggle the visibility of the Thank You Page setting based on the checkbox state
    function toggleThankYouPageSetting() {
        const isChecked = $('#upkeepify_enable_thank_you_page').is(':checked');
        // Toggle the visibility of the input field
        $('.upkeepify_row.upkeepify_thank_you_page_url').toggle(isChecked);
    }

    // Run on document ready to apply the correct initial state
    toggleSmtpSettings();
    // Initial check to set correct visibility on page load
    toggleThankYouPageSetting();

    // Setup change event listener for the SMTP checkbox
    $('#upkeepify_smtp_option').change(toggleSmtpSettings);

    // Setup change event listener for the Thank You Page checkbox
    $('#upkeepify_enable_thank_you_page').change(toggleThankYouPageSetting);
    
});