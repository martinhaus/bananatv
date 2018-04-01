var wpPWidgets;
(function($) {

	wpPWidgets = {

		init : function() {
			var rem, sidebars = $('div.widgets-sortables'), self = this, chooser = $('.widgets-chooser'),
			selectSidebar = chooser.find('.widgets-chooser-sidebars'), the_id;

			$('#widgets-right').children('.widgets-holder-wrap').children('.sidebar-name').click(function(){
				var c = $(this).siblings('.widgets-sortables'), p = $(this).parent();
				if ( !p.hasClass('closed') ) {
					c.sortable('disable');
					p.addClass('closed');
				} else {
					p.removeClass('closed');
					c.sortable('enable').sortable('refresh');
				}
			});

			$('#widgets-left').children('.widgets-holder-wrap').children('.sidebar-name').click(function() {
				$(this).siblings('.widget-holder').parent().toggleClass('closed');
			});

			sidebars.not('#wp_inactive_widgets').each(function(){
				var h = 50, H = $(this).children('.widget').length;
				h = h + parseInt(H * 48, 10);
				//$(this).css( 'minHeight', 50 + 'px' ); // Why h? CHO changed to 50
			});
			$(".widget .widget-title").each(function(index, element) {
				if(!$("a.widget-action", this).length){
					$(this).prepend('<a href="#available-widgets" class="new-widget-action hide-if-no-js"></a>');
				}
			});
			$('.widget-liquid-right .widget-title').live('click', function(){
				var css = {}, widget = $(this).closest('div.widget'), inside = widget.children('.widget-inside'), w = parseInt( widget.find('input.widget-width').val(), 10 );

				if ( inside.is(':hidden') ) {
					if ( w > 250 && inside.closest('div.widgets-sortables').length ) {
						css['width'] = w - 75 + 'px';
						if ( inside.closest('div.widget-liquid-right').length )
							css['marginLeft'] = 340 - w + 'px';
						widget.css(css);
					}
					wpPWidgets.fixLabels(widget);
					inside.slideDown('fast');
				} else {
					inside.slideUp('fast', function() {
						widget.css({'width':'','marginLeft':''});
					});
				}
				return false;
			});

			$('input.widget-control-save').live('click', function(){
				wpPWidgets.save( $(this).closest('div.widget'), 0, 1, 0 );
				return false;
			});

			$('a.widget-control-remove').live('click', function(){
				wpPWidgets.save( $(this).closest('div.widget'), 1, 1, 0 );
				return false;
			});

			$('a.widget-control-close').live('click', function(){
				wpPWidgets.close( $(this).closest('div.widget') );
				return false;
			});

			sidebars.children('.widget').each(function() {
				wpPWidgets.appendTitle(this);
				if ( $('p.widget-error', this).length )
					$('a.widget-action', this).click();
			});

			$('#widget-list').children('.widget').draggable({
				connectToSortable: 'div.widgets-sortables',
				handle: '> .widget-top > .widget-title',
				distance: 2,
				helper: 'clone',
				zIndex: 5,
				containment: 'document',
				start: function(e,ui) {
					wpPWidgets.fixWebkit(1);
					ui.helper.find('div.widget-description').hide();
					the_id = this.id;
				},
				stop: function(e,ui) {
					if ( rem )
						$(rem).hide();
					rem = '';
					wpPWidgets.fixWebkit();
				}
			});

			sidebars.sortable({
				placeholder: 'widget-placeholder',
				items: '> .widget',
				handle: '> .widget-top > .widget-title',
				cursor: 'move',
				distance: 2,
				containment: '#pw-widgets',
				start: function(e,ui) {
					wpPWidgets.fixWebkit(1);
					ui.item.children('.widget-inside').hide();
					ui.item.css({'marginLeft':'','width':''});
				},
				stop: function(e,ui) {
					if ( ui.item.hasClass('ui-draggable') && ui.item.data('draggable') ) {
						ui.item.draggable('destroy');
					}

					// Remove style: display=block
					if ( ui.item.hasClass('ui-draggable') ) {
						ui.item.removeAttr('style');
					}

					if ( ui.item.hasClass('deleting') ) { // nay roi
						wpPWidgets.save( ui.item, 1, 0, 1 ); // delete widget
						ui.item.remove();
						return;
					}

					var add = ui.item.find('input.add_new').val(),
						n = ui.item.find('input.multi_number').val(),
						//id = ui.item.attr('id'),
						id = the_id,
						sb = $(this).attr('id');
					//console.log(ui.item);
					ui.item.css({'marginLeft':'','width':''});
					wpPWidgets.fixWebkit();
					if ( add ) {
						if ( 'multi' == add ) {
							ui.item.html( ui.item.html().replace(/<[^<>]+>/g, function(m){ return m.replace(/__i__|%i%/g, n); }) );
							ui.item.attr( 'id', id.replace(/__i__|%i%/g, n) );
							n++;
							$('div#' + id).find('input.multi_number').val(n);
						} else if ( 'single' == add ) {
							ui.item.attr( 'id', 'new-' + id );
							rem = 'div#' + id;
						}
						wpPWidgets.save( ui.item, 0, 0, 1 );
						ui.item.find('input.add_new').val('');
						ui.item.find('a.widget-action').click();
						return;
					}
					wpPWidgets.saveOrder(sb);
				},
				receive: function(e,ui) {
					if ( !$(this).is(':visible') )
						$(this).sortable('cancel');
				}
			}).sortable('option', 'connectWith', 'div.widgets-sortables').parent().filter('.closed').children('.widgets-sortables').sortable('disable');

			$('#available-widgets').droppable({
				tolerance: 'pointer',
				accept: function(o){
					return $(o).parent().attr('id') != 'widget-list';
				},
				drop: function(e,ui) {
					ui.draggable.addClass('deleting');
					//$('#removing-widget').hide().children('span').html('');
					$('#removing-widget').hide().children('span').empty();
				},
				over: function(e,ui) {
					ui.draggable.addClass('deleting');
					$('div.widget-placeholder').hide();

					if ( ui.draggable.hasClass('ui-sortable-helper') )
						$('#removing-widget').show().children('span')
							.html( ui.draggable.find('div.widget-title').children('h4').html() );
				},
				out: function(e,ui) {
					ui.draggable.removeClass('deleting');
					$('div.widget-placeholder').show();
					//$('#removing-widget').hide().children('span').html('');
					$('#removing-widget').hide().children('span').empty();
				}
			});

			// Area Chooser
			$( '#widgets-right .widgets-holder-wrap' ).each( function( index, element ) {
				var $element = $( element ),
					name = $element.find( '.sidebar-name h3' ).text(),
					id = $element.find( '.widgets-sortables' ).attr( 'id' ),
					li = $('<li tabindex="0">').text( $.trim( name ) );

				if ( index === 0 ) {
					li.addClass( 'widgets-chooser-selected' );
				}

				selectSidebar.append( li );
				li.data( 'sidebarId', id );
			});

			$( '#available-widgets .widget .widget-title' ).on( 'click.widgets-chooser', function() {
				var $widget = $(this).closest( '.widget' );

				if ( $widget.hasClass( 'widget-in-question' ) || $( '#widgets-left' ).hasClass( 'chooser' ) ) {
					self.closeChooser();
				} else {
					// Open the chooser
					self.clearWidgetSelection();
					$( '#widgets-left' ).addClass( 'chooser' );
					$widget.addClass( 'widget-in-question' ).children( '.widget-description' ).after( chooser );

					chooser.slideDown( 300, function() {
						selectSidebar.find('.widgets-chooser-selected').focus();
					});

					selectSidebar.find( 'li' ).on( 'focusin.widgets-chooser', function() {
						selectSidebar.find('.widgets-chooser-selected').removeClass( 'widgets-chooser-selected' );
						$(this).addClass( 'widgets-chooser-selected' );
					} );
				}
			});

			// Add event handlers
			chooser.on( 'click.widgets-chooser', function( event ) {
				var $target = $( event.target );

				if ( $target.hasClass('button-primary') ) {
					self.addWidget( chooser );
					self.closeChooser();
				} else if ( $target.hasClass('button-secondary') ) {
					self.closeChooser();
				}
				return false;
			}).on( 'keyup.widgets-chooser', function( event ) {
				if ( event.which === $.ui.keyCode.ENTER ) {
					if ( $( event.target ).hasClass('button-secondary') ) {
						// Close instead of adding when pressing Enter on the Cancel button
						self.closeChooser();
					} else {
						self.addWidget( chooser );
						self.closeChooser();
					}
				} else if ( event.which === $.ui.keyCode.ESCAPE ) {
					self.closeChooser();
				}
				return false;
			});

		},

		saveOrder : function(sb) {
			if ( sb )
				$('#' + sb).closest('div.widgets-holder-wrap').find('img.ajax-feedback').css('visibility', 'visible');
			// WP 3.8
			$('#' + sb).closest('div.widgets-holder-wrap').find('.spinner').css('display', 'inline-block');

			if($('#post_ID').length){
				var a = {
					action: 'pw-widgets-order',
					post_id: $('#post_ID').val(),
					savewidgets: $('#_wpnonce_widgets').val(),
					sidebars: []
				};
			}

			// For search page
			else if ( $('#pw_search_page').length ) {
				var a = {
					action: 'pw-widgets-order',
					search_page: 'yes',
					savewidgets: $('#_wpnonce_widgets').val(),
					sidebars: []
				};
			}

			else if($('#tag_ID').length){
				var a = {
					action: 'pw-widgets-order',
					tag_id: $('#tag_ID').val(),
					taxonomy: $('#taxonomy').val(),
					savewidgets: $('#_wpnonce_widgets').val(),
					sidebars: []
				};
			}

			$('div.widgets-sortables').each( function() {
				a['sidebars[' + $(this).attr('id') + ']'] = $(this).sortable('toArray').join(',');
			});

			$.post( ajaxurl, a, function() {
				$('img.ajax-feedback').css('visibility', 'hidden');
				$('.spinner').css('display', 'none');
			});

			this.resize();
		},

		save : function(widget, del, animate, order) {
			var sb = widget.closest('div.widgets-sortables').attr('id'), data = widget.find('form').serialize(), a;
			if(data == "")
			{
				wgIn = widget.find('.widget-inside');
				htmlInwpIn = wgIn.html();
				wgIn.html('');
				wgIn.append('<form method="post" action="">'+htmlInwpIn+'</form>');
				data = widget.find('form').serialize();
			}
			widget = $(widget);
			$('.ajax-feedback', widget).css('visibility', 'visible');
			$('.spinner', widget).css('display', 'inline-block');
			if($('#post_ID').length){
				a = {
					action: 'pw-save-widget',
					post_id: $('#post_ID').val(),
					savewidgets: $('#_wpnonce_widgets').val(),
					sidebar: sb
				};
			}

			// For search page
			else if ( $('#pw_search_page').length ) {
				a = {
					action: 'pw-save-widget',
					search_page: 'yes',
					savewidgets: $('#_wpnonce_widgets').val(),
					sidebar: sb
				};
			}

			// For taxonomy page
			else if($('#tag_ID').length){
				a = {
					action: 'pw-save-widget',
					tag_id: $('#tag_ID').val(),
					taxonomy: $('#taxonomy').val(),
					savewidgets: $('#_wpnonce_widgets').val(),
					sidebar: sb
				};
			}

			if ( del )
				a['delete_widget'] = 1;

			data += '&' + $.param(a);

			$.post( ajaxurl, data, function(r){
				var id;

				if ( del ) {
					if ( !$('input.widget_number', widget).val() ) {
						id = $('input.widget-id', widget).val();
						$('#available-widgets').find('input.widget-id').each(function(){
							if ( $(this).val() == id )
								$(this).closest('div.widget').show();
						});
					}

					if ( animate ) {
						order = 0;
						widget.slideUp('fast', function(){
							$(this).remove();
							wpPWidgets.saveOrder();
						});
					} else {
						widget.remove();
						wpPWidgets.resize();
					}
				} else {
					$('.ajax-feedback').css('visibility', 'hidden');
					// WP 3.8
					$('.spinner').css('display', 'none');
					if ( r && r.length > 2 ) {
						$('div.widget-content', widget).html(r);
						wpPWidgets.appendTitle(widget);
						wpPWidgets.fixLabels(widget);
					}
				}
				if ( order )
					wpPWidgets.saveOrder();
			});
		},

		appendTitle : function(widget) {
			var title = $('input[id*="-title"]', widget);
			if ( title = title.val() ) {
				title = title.replace(/<[^<>]+>/g, '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
				$(widget).children('.widget-top').children('.widget-title').children()
					.children('.in-widget-title').html(': ' + title);
			}
		},

		resize : function() {
			$('div.widgets-sortables').not('#wp_inactive_widgets').each(function(){
				var h = 50, H = $(this).children('.widget').length;
				h = h + parseInt(H * 48, 10);
				//$(this).css( 'minHeight', h + 'px' );
			});
		},

		fixWebkit : function(n) {
			n = n ? 'none' : '';
			$('body').css({
				WebkitUserSelect: n,
				KhtmlUserSelect: n
			});
		},

		fixLabels : function(widget) {
			widget.children('.widget-inside').find('label').each(function(){
				var f = $(this).attr('for');
				if ( f && f == $('input', this).attr('id') )
					$(this).removeAttr('for');
			});
		},

		addWidget: function( chooser ) {
			var widget, widgetId, add, n, viewportTop, viewportBottom, sidebarBounds,
				sidebarId = chooser.find( '.widgets-chooser-selected' ).data('sidebarId'),
				sidebar = $( '#' + sidebarId );
				// alert(sidebarId);

			widget = $('#available-widgets').find('.widget-in-question').clone();
			widgetId = widget.attr('id');
			add = widget.find( 'input.add_new' ).val();
			n = widget.find( 'input.multi_number' ).val();

			//display
			//widget.find('.widget-inside').show();
			//console.log(widget);

			// Remove the cloned chooser from the widget
			widget.find('.widgets-chooser').remove();

			if ( 'multi' === add ) {
				widget.html(
					widget.html().replace( /<[^<>]+>/g, function(m) {
						return m.replace( /__i__|%i%/g, n );
					})
				);

				widget.attr( 'id', widgetId.replace( '__i__', n ) );
				n++;
				$( '#' + widgetId ).find('input.multi_number').val(n);
			} else if ( 'single' === add ) {
				widget.attr( 'id', 'new-' + widgetId );
				$( '#' + widgetId ).hide();
			}

			// Open the widgets container
			sidebar.closest( '.widgets-holder-wrap' ).removeClass('closed');

			sidebar.append( widget );
			sidebar.sortable('refresh');

			wpPWidgets.save( widget, 0, 0, 1 );
			// No longer "new" widget
			widget.find( 'input.add_new' ).val('');

			/*
			 * Check if any part of the sidebar is visible in the viewport. If it is, don't scroll.
			 * Otherwise, scroll up to so the sidebar is in view.
			 *
			 * We do this by comparing the top and bottom, of the sidebar so see if they are within
			 * the bounds of the viewport.
			 */
			viewportTop = $(window).scrollTop();
			viewportBottom = viewportTop + $(window).height();
			sidebarBounds = sidebar.offset();

			sidebarBounds.bottom = sidebarBounds.top + sidebar.outerHeight();

			if ( viewportTop > sidebarBounds.bottom || viewportBottom < sidebarBounds.top ) {
				$( 'html, body' ).animate({
					scrollTop: sidebarBounds.top - 130
				}, 200 );
			}

			window.setTimeout( function() {
				// Cannot use a callback in the animation above as it fires twice,
				// have to queue this "by hand".
				widget.find( '.widget-title' ).trigger('click');
			}, 250 );
		},

		close : function(widget) {
			widget.children('.widget-inside').slideUp('fast', function(){
				widget.css({'width':'','marginLeft':''});
			});
		},
		closeChooser: function() {
			var self = this;

			$( '.widgets-chooser' ).slideUp( 200, function() {
				$( '#wpbody-content' ).append( this );
				self.clearWidgetSelection();
			});
		},

		clearWidgetSelection: function() {
			$( '#widgets-left' ).removeClass( 'chooser' );
			$( '.widget-in-question' ).removeClass( 'widget-in-question' );
		}
	};

	$(document).ready(function($){
		/*if($("#addtag").length){
		 var taxonomyAdd = $("input[name='taxonomy']", "#addtag").val();
		 var data = { action: 'pw-get-taxonomy-widget', taxonomy: taxonomyAdd };
		 $.ajax({
		 url: ajaxurl,
		 data: data,
		 async: false,
		 type: "POST",
		 dataType: "html",
		 success: function(data) {
		 $(data).insertBefore("#addtag .submit");
		 }
		 });
		 }*/
		wpPWidgets.init();

		$('.pw-toggle-customize').click(function(e) {
			if ( adminpage == 'post-new-php' ) return true;

			var t = this;
			if($('#post_ID').length){
				var post_id = $('#post_ID').val();

				$.post(ajaxurl, {action: 'pw-toggle-customize', post_id: post_id, 'pw-customize-sidebars': $(t).val()}, function() {

				});
			}

			// For search page
			else if ( $('#pw_search_page').length ) {
				$.post(ajaxurl, {action: 'pw-toggle-customize', search_page: 'yes', 'pw-customize-sidebars': $(t).val()}, function() {});

			}

			// For taxonomy page
			else{
				var tag_id = $('#tag_ID').val();
				var taxonomy = $('#taxonomy').val();

				$.post(ajaxurl, {action: 'pw-toggle-customize', tag_id: tag_id, taxonomy: taxonomy, 'pw-customize-sidebars': $(t).val()}, function() {

				});
			}

			return true;
		});

		if($("#edittag").length){
			$("#edittag").nextUntil().clone().appendTo("#edittag");
			$("#edittag").nextUntil().remove();
		}

		//fix bug #14078 -> create and process on button remove inactive widget
		var wp_inactive_widgets = $('#wp_inactive_widgets'),
			btn = $('<a href="#" class="button">'+wp_page_widgets.remove_inactive_widgets+'</a>')
				.insertAfter( wp_inactive_widgets );

		btn.wrap(
			'<p class="description" style="clear:both;line-height: 26px;">'+ wp_page_widgets.remove_inactive_widgets_text+'</p>'
		);

		btn
			.click(
			function(e){
				e.preventDefault();
				jQuery.post(ajaxurl, {
					action: 'pw-remove-inactive-widget',
					post_id: $('#post_ID').val(),
					savewidgets: $('#_wpnonce_widgets').val()
				}, function(data) {
					wp_inactive_widgets.empty();
				});
			}
		);
	});

	// Fix to work with ACF
	var postIDEdit = $("#post_ID").length ? $("#post_ID").val() : 0;
	if(typeof(acf) != "undefined" && postIDEdit){
		acf.o.post_id = postIDEdit;
	}

})(jQuery);