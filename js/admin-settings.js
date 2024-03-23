jQuery(document).ready(function($) {
    // Function to toggle SMTP settings visibility
    function toggleSmtpSettings() {
        console.log('Toggling SMTP settings');
        $('.smtp_setting').toggle(!$('#upkeepify_smtp_option').is(':checked'));
    }

    // Function to toggle the visibility of the "Thank You Service Provider Page" URL
    function toggleThankYouPageUrl() {
        console.log('Toggling Thank You Page URL');
        var isChecked = $('#upkeepify_enable_thank_you_page').is(':checked');
        console.log('Checkbox checked:', isChecked);
        console.log($('.upkeepify_thank_you_page_url').length + " elements found.");
        $('.upkeepify_thank_you_page_url').toggle(isChecked);
    }    

    // Run on document ready to apply the correct initial state
    toggleSmtpSettings();
    toggleThankYouPageUrl(); // Apply initial state for the thank you page URL setting

    // Attach a change event listener to the checkboxes
    $('#upkeepify_smtp_option').change(toggleSmtpSettings);
    $('#upkeepify_enable_thank_you_page').change(toggleThankYouPageUrl); // Listen for changes on the thank you page checkbox

        // Attach a change event listener to the Thank You Page checkbox using delegated events
        $(document).on('change', '#upkeepify_enable_thank_you_page', toggleThankYouPageUrl);

});
