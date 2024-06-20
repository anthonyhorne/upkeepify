jQuery(document).ready(function($) {
    // Function to toggle SMTP settings visibility
    function toggleSmtpSettings() {
        $('.smtp_setting').toggle(!$('#upkeepify_smtp_option').is(':checked'));
    }

    // Function to toggle visibility based on the checkbox
    function toggleThankYouPageSetting() {
        // Check if the checkbox is checked
        var isChecked = $('#upkeepify_enable_thank_you_page').is(':checked');
        // Toggle the visibility of the input field
        $('.upkeepify_row.upkeepify_thank_you_page_url').toggle(isChecked);
    }

    // Run on document ready to apply the correct initial state
    toggleSmtpSettings();
    // Initial check to set correct visibility on page load
    toggleThankYouPageSetting();

    // Attach a change event listener to the SMTP checkbox
    $('#upkeepify_smtp_option').change(toggleSmtpSettings);

    // Setup change event listener
    $('#upkeepify_enable_thank_you_page').change(toggleThankYouPageSetting);
    
});