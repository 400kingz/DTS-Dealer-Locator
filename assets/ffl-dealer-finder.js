jQuery(document).ready(function($) {
    // Show modal on button click
    $('#find-a-dealer-btn').on('click', function() {
        $('#ffl-dealer-finder-modal').show();
    });

    // Hide modal on clicking the close button or outside of modal content
    $('#ffl-dealer-finder-modal').on('click', function(e) {
        if (e.target === this || $(e.target).hasClass('close')) {
            $(this).hide();
        }
    });

    // Update radius value display
    $('#ffl-radius').on('input', function() {
        $('#radius-value').text($(this).val());
    });

    // Handle form submission via AJAX
    $('#ffl-find-dealer-btn').on('click', function(e) {
        e.preventDefault(); // Prevent default form submission behavior
        var zipCode = $('#ffl-zip-code').val();
        var radius = $('#ffl-radius').val();

        $.ajax({
            url: fflAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'search_ffl_dealers',
                security: fflAjax.security,
                zip_code: zipCode,
                radius: radius
            },
            success: function(response) {
                if(response.success) {
                    var results = response.data;
                    var resultsContainer = $('#ffl-dealer-results');
                    resultsContainer.empty();
                    $.each(results, function(index, dealer) {
                        resultsContainer.append('<div><strong>' + dealer.business_name + '</strong><br>' + dealer.address + ', ' + dealer.city + ', ' + dealer.state + ' ' + dealer.zip_code + '<br><button class="add-dealer-btn" data-dealer=\'' + JSON.stringify(dealer) + '\'>Add</button></div><hr>');
                    });

                    // Add click event for "Add" buttons
                    $('.add-dealer-btn').on('click', function() {
                        var dealerInfo = $(this).data('dealer');
                        $.ajax({
                            url: fflAjax.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'add_ffl_dealer_to_shipping',
                                security: fflAjax.security,
                                dealer_info: dealerInfo
                            },
                            success: function(response) {
                                if(response.success) {
                                    alert('Dealer added to shipping information.');
                                    $('#ffl-dealer-finder-modal').hide();
                                } else {
                                    alert('Failed to add dealer.');
                                }
                            }
                        });
                    });
                }
            }
        });
    });
});