jQuery(document).ready(function($) {
    // Function to toggle SMTP settings visibility
    function toggleSmtpSettings() {
        $('.smtp_setting').toggle(!$('#upkeepify_smtp_option').is(':checked'));
    }

    // Run on document ready to apply the correct initial state
    toggleSmtpSettings();

    // Attach a change event listener to the checkbox
    $('#upkeepify_smtp_option').change(toggleSmtpSettings);
});
