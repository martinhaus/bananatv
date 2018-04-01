jQuery(document).ready(function($) {

	// Add new schedule AJAX

	$( "#add_schedule_form" ).on('submit', function( event ) {

		event.preventDefault();

		var data = {
			action: 'acm_add_schedule',
			interval: $('#interval').val(),
			name: $('#name').val(),
			display: $('#nice_name').val(),
			noonce: $('#add-schedule').data('noonce')
		};

		$('#add-schedule-indicator').show();

		$.post(ajaxurl, data, function(response) {

			$('#add-schedule-indicator').hide();

			var ret = $.parseJSON(response);  

			if (ret.status == 'success') {

				// response.li is prepared ul > li item for schedules list
				var list = $('#schedules-list').html();
				$('#schedules-list').html(ret.li + list);

				// Update schedules list for Add Task form
				$('#select-schedule').prepend(ret.select);

				// Show notification
				$("#notif-schedule-added").slideDown('slow').delay(3000).slideUp('slow');

				$('#add_schedule_form').slideUp('slow', function() {

					// Reset fields
					$('#interval').val('1');
					$('#name').val('');
					$('#nice_name').val('');

					$('#enable_schedule_form').slideDown('slow');
				});

			} else if (ret.status == 'error') {

				// Show error
				$("#notif-flex > p >strong").html(ret.details);
				$("#notif-flex").slideDown('slow').delay(3000).slideUp('slow');

			}

		});

	});



	// Save settings

	$( "#acm-save-settings-form" ).on('submit', function( event ) {

		event.preventDefault();

		var data = {
			action: 'acm_save_settings',
			fields: $(this).serialize(),
			nonce: $(this).data('nonce')
		};

		$.post(ajaxurl, data, function(response) {
			
			var ret = $.parseJSON(response);  

			if (ret.status == 'success') {

				// Show notification
				$("#notif-settings-saved").slideDown('slow').delay(3000).slideUp('slow');

				if (ret.refresh == true) {
					location.reload();
				}

			} else if (ret.status == 'error') {

				// Show error
				$("#notif-flex > p >strong").html(ret.details);
				$("#notif-flex").slideDown('slow').delay(3000).slideUp('slow');

			}

		});

	});



	// Deactivate license

	$( "#acm-deactivate-license" ).on('click', function( event ) {

		event.preventDefault();

		var data = {
			action: 'acm_deactivate_license',
			nonce: $(this).data('nonce')
		};

		$.post(ajaxurl, data, function(response) {
			
			var ret = $.parseJSON(response);  

			if (ret.status == 'success') {

				// Show notification
				$("#notif-license-deactivated").slideDown('slow').delay(3000).slideUp('slow');

				if (ret.refresh == true) {
					location.reload();
				}

			} else if (ret.status == 'error') {

				// Show error
				$("#notif-flex > p >strong").html(ret.details);
				$("#notif-flex").slideDown('slow').delay(3000).slideUp('slow');

			}

		});

	});



	// Remove schedule AJAX

	$( ".remove-schedule" ).live('click', function() {

		if ( !confirm($(this).data('confirm')) ) {
			return false;
		};

		var data = {
			action: 'acm_remove_schedule',
			name: $(this).data('schedule'),
			noonce: $(this).data('noonce')
		};

		$.post(ajaxurl, data, function(response) {
			
			var ret = $.parseJSON(response);  

			if (ret.status == 'success') {

				// Show notification
				$("#notif-schedule-removed").slideDown('slow').delay(3000).slideUp('slow');

				// Remove schedule from schedules list
				$('#single-schedule-' + ret.details).slideUp('slow');

				// Remove schedule from Add Task form
				$("#select-schedule option[value='"+ret.details+"']").remove();

			} else if (ret.status == 'error') {

				// Show error
				$("#notif-flex > p >strong").html(ret.details);
				$("#notif-flex").slideDown('slow').delay(3000).slideUp('slow');

			}

		});

	});



	// Add task AJAX

	$( "#add-task" ).live('click', function() {

		var args = '';
		$('.argument-field').each(function() {

			if ( $(this).val() ) {
				args = args + ',' + $(this).val();
			};
			
		});
		args = args.slice(1);

		var data = {
			action: 'acm_add_task',
			hook: $('#schedule_hook').val(),
			offset: $('#timestamp_offset').val(),
			schedule: $('#select-schedule').val(),
			args: args,
			noonce: $(this).data('noonce')
		};

		$.post(ajaxurl, data, function(response) {

			var ret = $.parseJSON(response);  

			if (ret.status == 'success') {

				// Show notification
				$("#notif-task-added").slideDown('slow').delay(3000).slideUp('slow');

				// Reset Add Task form
				$('#add_task_form_row').hide('slow', function() {

					$('#add_task_row').show('slow');

					// Reset values
					$('#schedule_hook').val('');
					$('#timestamp_offset').val('0');
					$('#arguments-list').empty();

				});

				// Place new row
				$('tr.single-cron').last().after(ret.table);

			} else if (ret.status == 'error') {

				// Show error
				$("#notif-flex > p >strong").html(ret.details);
				$("#notif-flex").slideDown('slow').delay(3000).slideUp('slow');

			}

		});

	});



	// Remove task AJAX

	$( '.remove-task' ).on('click', function() {

		if ( !confirm($(this).data('confirm')) ) {
			return false;
		};

		var remove_button = $(this);

		var data = {
			action: 'acm_remove_task',
			task: $(this).data('task'),
			interval: $(this).data('interval'),
			args: $(this).data('args'),
			noonce: $(this).data('noonce')
		};

		$.post(ajaxurl, data, function(response) {
			
			var ret = $.parseJSON(response);  

			if (ret.status == 'success') {

				// Show notification
				$("#notif-task-removed").slideDown('slow').delay(3000).slideUp('slow');

				// Remove schedule from tasks list
				$('tr.cron-' + ret.hash + ' > td').slideUp('slow', function() {
					$('tr.cron-' + ret.hash).html(ret.info);
				});

			} else if (ret.status == 'error') {

				// Show error
				$("#notif-flex > p >strong").html(ret.details);
				$("#notif-flex").slideDown('slow').delay(3000).slideUp('slow');

			}

		});

	});



	// Execute task AJAX

	$( ".execute-task" ).live('click', function() {

		var data = {
			action: 'acm_execute_task',
			task: $(this).data('task'),
			args: $(this).data('args'),
			noonce: $(this).data('noonce')
		};

		console.log(data);

		$.post(ajaxurl, data, function(response) {
			
			var ret = $.parseJSON(response);  

			if (ret.status == 'success') {

				// Show notification
				$("#notif-task-executed").slideDown('slow').delay(3000).slideUp('slow');

			} else if (ret.status == 'error') {

				// Show error
				$("#notif-flex > p >strong").html(ret.details);
				$("#notif-flex").slideDown('slow').delay(3000).slideUp('slow');

			}

		});

	});


});