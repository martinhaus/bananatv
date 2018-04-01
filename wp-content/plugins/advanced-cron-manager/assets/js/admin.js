jQuery(document).ready(function($) {
    
    // Show Add Task form in the table
	$('#show_task_form').click(function() {

		$('#add_task_row').hide('slow', function() {
			$('#add_task_form_row').show('slow');
		});

	});

	// Add another argument input in the Add Task form
	$('#add_argument_input').on('click', function() {

		// $('#arguments-list').append('<input type="text" name="arguments[]" class="argument-field widefat" />');
		$('<input type="text" name="arguments[]" class="argument-field widefat" />').appendTo('#arguments-list');

	});

	// Show Add Schedule form in the aside
	$('#enable_schedule_form').click(function() {

		$(this).slideUp('slow', function() {
			$('#add_schedule_form').slideDown('slow');
		});

	});

});