		//Ready Function
		jQuery(function() {

			jQuery('#analytics-page .table-nav').after('<span class="loader"></span>');

			get_analytics_query_entry_pages( per_page, offset, 'titles' );

		});
		//-------------------------------------AJAX FUNCTIONS-------------------------------------
		var query_entry_pages = [];
		
		function get_analytics_query_entry_pages( perPage, pageOffset, dataView ) {

			var start_date = '';
			var end_date = "2020-04-10 00:00:00";

			jQuery.ajax({
				url: global_ajax_url,
				type: "POST",
				data: {
					action: 'ajax_analytics_query_entry_pages',
					security: global_admin_nonce,
					start_date: start_date,
					end_date: end_date,
					per_page: perPage,
					offset: pageOffset,
					type: dataView
				},
				success: function (data) {

					analytics_query_entry_pages = JSON.parse(data)
					jQuery('#entry-pages .loader').remove();
					jQuery('#entry-pages table').removeClass('table-loading');
					jQuery('#entry-pages .table-footer').attr('data-page-results', pageOffset);
					jQuery('#analytics-page .total-result-view').text(data.amount);
					jQuery('.current-result-view').text('1 - ' + per_page + ' ');
					output_analytics_query_entry_pages_table();

				},
				error: function (xhr, ajaxOptions, thrownError) {
					console.log(thrownError);
				}
			});
		}

		//Output analytics for top bar
		// function output_top_bar_stats() {
		// 	let data = query_top_stats;
		// }



		//-------------------------------------QUERY FUNCTIONS-------------------------------------
		function output_analytics_query_entry_pages_table() {
			let data = query_entry_pages;	
			let dataView = jQuery('#' + data.selector + ' .view-list').find('.active').data('view');
			let iteration = 1;
			var classType = 'parent';
			var titleQuery;
			if( dataView == 'paths' ) {
				jQuery.each(data.paths, function(key, value) {
					//Parent
					classType = 'parent';
					jQuery("." + data.selector + " tbody").append("<tr data-id='" + iteration + "' class='entry-" + classType + "' data-type='" + classType + "'><td><span class='expand-row'><i class='mdi mdi-plus' aria-hidden='true'></i></span></td><td data-type='title'>" + data.paths[key].path + "</td><td>" + data.paths[key].total_visitors + "</td><td>" + data.paths[key].bounces +"</td></tr>");	
					classType = 'child';	
					//Child
					jQuery.each(data.paths[key].query, function(secondkey, value) {		
						titleQuery = data.paths[key].query[secondkey].query;
						jQuery("." + data.selector + " tbody").append("<tr data-related='" + iteration + "' class='entry-" + classType + "' data-type='" + classType + "'><td></td><td>" + titleQuery + "</td><td>" + data.paths[key].query[secondkey].total_visitors + "</td><td>" + data.paths[key].query[secondkey].bounces +"</td></tr>");
					});
					iteration++;
				});
			} else {
				jQuery.each(data.titles, function(key, value) {					
					titleQuery = data.titles[key].title;
					jQuery("." + data.selector + " tbody").append("<tr class='entry-" + classType + "' data-type='" + classType + "'><td colspan='2'>" + titleQuery + "</td><td>" + data.titles[key].total_visitors + "</td><td>" + data.titles[key].bounces +"</td></tr>");

				});
			}
			jQuery('#analytics-page .total-result-view').text(data.amount);
		}

		//-------------------------------------UPDATE UI FUNCTIONS-------------------------------------
		//Loading Function for Ajax
		function updateTableLoading() {
			jQuery('#entry-pages .entry-pages tbody').html('');
			jQuery('#analytics-page .table-nav').after('<span class="loader"></span>');
		}

		//Change Per Page on Table
		jQuery(document).on( 'change', '#entry-pages .table-per-page', function(){
			updateTableLoading();
			per_page = jQuery(this).val();
			let dataView = jQuery('#entry-pages .view-list').find('.active').data('view');
			get_analytics_query_entry_pages( per_page, 0, dataView );
		} );

		//Switch View(s) of Table
		jQuery(document).on( 'click', '#entry-pages .view-list li', function(e){
			jQuery('#entry-pages .entry-pages tbody').html('');
			jQuery('#entry-pages .view-list li').removeClass('active');
			jQuery(this).addClass('active');

			let data = query_entry_pages;	
			let dataView = jQuery('#entry-pages .view-list').find('.active').data('view');
			console.log(dataView);

			if( dataView == 'titles' ){
				jQuery("#entry-pages thead").html('<tr class="table-header"><th class="view-title" colspan="2">Title</th><th>Visitors</th><th>Bounces</th></tr>');
				jQuery('#entry-pages .view-title').text('Title');
			}else if( dataView == 'paths' ){
				jQuery("#entry-pages thead").html('<tr class="table-header"><th width="30" class="view-open-close">#</th><th class="view-title">Title</th><th>Visitors</th><th>Bounces</th></tr>');
				jQuery('#entry-pages .view-open-close').text('+ / -');
				jQuery('#entry-pages .view-title').text('Path');
			}

			//output_analytics_query_entry_pages_table();
			per_page = jQuery('#entry-pages .table-per-page').val();
			get_analytics_query_entry_pages( per_page, offset, dataView )

		} );

		//Expand nested Parent/Child rows
		// jQuery(document).on( 'click', '#analytics-page .expand-row', function(){
		// 	let buttonText = '<i class="mdi mdi-plus" aria-hidden="true"></i>';
		// 	let parentID = jQuery(this).closest('[data-id]').data('id');
		// 	jQuery(this).toggleClass('expanded');
		// 	if( jQuery(this).hasClass('expanded') ) {
		// 		buttonText = '<i class="mdi mdi-minus" aria-hidden="true"></i>';
		// 	}
		// 	jQuery(this).html(buttonText);
		// 	jQuery('tr[data-related="'+ parentID +'"').toggle();
		// });

		//Next Page function
		// jQuery(document).on( 'click', '#analytics-page .table-footer .next-btn', function(){
		// 	console.log(currentOffset);
		// 	currentOffset++;

		// 	updateTableLoading();
		// 	jQuery('#analytics-page .table-footer').attr('data-page-results', currentOffset);
		// 	let dataView = jQuery('#entry-pages .view-list').find('.active').data('view');
		// 	get_analytics_query_entry_pages( per_page, currentOffset, dataView );
		// 	jQuery('.current-result-view').text( per_page + ' - ' + (per_page + per_page) );
			
		// } );

		//Previous Page function
		// jQuery(document).on( 'click', '#analytics-page .table-footer .prev-btn', function(){
		// if( !currentOffset <= 0 ){
		// 	currentOffset--;

		// 		updateTableLoading();
		// 		jQuery('#analytics-page .table-footer').attr('data-page-results', currentOffset);
		// 		let dataView = jQuery('#entry-pages .view-list').find('.active').data('view');
		// 		get_analytics_query_entry_pages( per_page, currentOffset, dataView );
		// 		jQuery('.current-result-view').text( per_page + ' - ' + (per_page - per_page) );
		// 	}
			
		// } );

//-------------------------------------HELPER FUNCTIONS-------------------------------------
		//Create today's date in proper format
		// let dateArray = [];
		// function createToday() {
		// 	let getDate = new Date();
			
		// 	//Date
		// 	let year = getDate.getFullYear();
		// 	let day = getDate.getDate();
		// 	let month = getDate.getMonth();
			
		// 	let date = year + '-' + day + '-' + month;
			
		// 	//Time
		// 	let hours = getDate.getHours();
		// 	let minutes = getDate.getMinutes();
		// 	let seconds = getDate.getSeconds();
			
		// 	let time = hours + ':' + minutes + ':' + seconds;
			
		// 	dateArray.push(date, time);
		// }
		// createToday();
		// currentDate = dateArray[0] + ' ' + dateArray[1];
		// console.log(currentDate);


//-------------------------------------REFACTOR STUFF-------------------------------------
		// let currentOffset = jQuery('#analytics-page .table-footer').data('page-results');
		// var per_page = 10;
		// var offset = 0;











		
	function loop_stats() {
		let statSelectors = jQuery('.quick-stat');

		let currentStats = [ quick_stats.result.current.total_unique_sessions, quick_stats.result.current.total_visitors, quick_stats.result.current.total_page_views ];
		let previousStats = [ quick_stats.result.previous.total_unique_sessions, quick_stats.result.previous.total_visitors, quick_stats.result.previous.total_page_views ];

		//loop current stats
		for( let i = 0; i < currentStats.length; i++ ) {
			data = calculate_quick_stat( currentStats[i], previousStats[i] );
			console.log( data );
			// for( let j = 0; j < statSelectors.length; j++ ) {

			// 	jQuery( statSelectors[j] ).find('.base-num').text( data[0] );
			// }
		};
	}

	function calculate_quick_stat( currentStat, previousStat ){
		quickStatsArray = [];

		//calculate difference
		let difference = currentStat - previousStat;

		//calculate percentage
		let percentageClass;
		let percentage = (difference / currentStat) * 100;
		percentage = Math.abs( Math.round(percentage) )


		//Check if Gain or Loss
		if( Math.sign(difference) > -1 ){

			difference = difference + ' More than Last Period';
			percentage = '+' + percentage + '%';
			percentageClass = 'up';

		} else {
			difference = difference + ' Less than Last Period';
			difference = Math.abs(difference);

			percentage = '-' + percentage + '%';
			percentageClass = 'down';
		}
		quickStatsArray.push( currentStat, percentage, difference );
		
		return quickStatsArray;
	}

	function update_quick_stats(){
		//update UI
		jQuery('.total-sessions .base-num').text( currentTotalSessions );
		jQuery('.total-sessions .difference-num').text( sessionDifference + differenceString );
		jQuery('.total-sessions .percentage').text( sessionPercentage ).addClass(percentageClass);
	}
