var home_url = aa_admin.url;

//-------------------------------------IMPORT DATA FUNCTIONS-------------------------------------
function import_analytics_data_from_api( start_date, end_date ) {
    jQuery('.ranges ul').hide();
    var unloaded_card = jQuery(".analytics-card:not(.loaded)").first();
    var function_name = unloaded_card.data("function");

    var data = {
        function_name : function_name,
        start_date: start_date,
        end_date: end_date,
    };
    axios.post(wpApiSettings.root + 'api/v1/analytics/loaddata', data, {
        headers: { 'X-WP-Nonce' : wpApiSettings.nonce }
    }).then(function(data) {
        var jsonParse;
        try {
            if('undefined' === typeof data || data.length == 0) {
                return;
            }
            jsonParse = JSON.parse( JSON.stringify(data.data));
        }
        catch (e) {
            console.log("Load all data error for " + function_name + " : "+e);
        };
        if('undefined' === typeof jsonParse || jsonParse.length == 0) {
            return;
        }
        let jsonResult = jsonParse.result;

        pass_api_data_to_function( function_name, jsonResult );

        unloaded_card.addClass("loaded");

        var next_card = jQuery(".analytics-card:not(.loaded)").first();

        if(next_card.length) {
            import_analytics_data_from_api( start_date, end_date );
        } else {
            jQuery("#analytics-page").removeClass('is-loading');
            jQuery('.ranges ul').show();
        }

        // Functions that include a details modal
        var detailsFunctions = ['page-visits', 'entry-pages', 'exit-pages', 'referrers', 'sessions-locations', 'track-clicks', 'blog-visits', 'blog-category-visits', 'keywords'];

        //console.log(function_name + jsonResult.total_records);
        if( detailsFunctions.includes( function_name) && jsonResult.total_records > 0 ) {
            import_analytics_data_from_api_detail( start_date, end_date, function_name );
        }
    }).catch(function(error){
        console.log(error);
    });
}
//-------------------------------------PASS DATA-------------------------------------
function pass_api_data_to_function(function_name, data) {
    switch (function_name) {
        case 'quick-stats':
            update_quick_stats( data );
            break;
        case 'entry-pages':
            output_analytics_entry_exit_tables(data);
            break;
        case 'exit-pages':
            output_analytics_entry_exit_tables(data);
            break;
        case 'page-visits':
            output_analytics_page_visits_table(data);
            break;
        case 'referrers':
            output_analytics_referrers_table(data);
            break;
        case 'keywords':
            output_analytics_keywords_table(data);
            break;
        case 'engines':
            output_analytics_engines(data);
        break;
        case 'visit-overview':
            output_analytics_visit_overview(data);
        break;
        case 'domain-metrics':
            output_domain_metrics(data);
        break;
        case 'blog-visits':
            output_analytics_blogs_visits_table(data);
        break;
        case 'single-blog-post-visits':
            output_single_blog_post_visits(data);
        break;
        case 'vists-per-day':
            output_visits_per_day_chart(data);
        break;
        case 'user-accounts':
            output_user_accounts_chart(data);
        break;
        case 'visits-per-hour':
            output_visits_per_hour(data);
        break;
        case 'campaign-mediums-sources':
            output_campaign_mediums_sources(data);
        break;
        case 'campaign-data':
            output_campaign_data(data);
        break;
        case 'exit-survey':
            output_exit_survey_data(data);
        break;
        case 'sessions-locations':
            output_sessions_locations_data(data);
        break;
        case 'user-locations':
            output_user_locations_data(data);
        break;
        case 'blog-category-visits':
            output_blog_category_data(data);
        break;
        case 'track-clicks':
            output_track_clicks_data(data);
        break;
        default:
            console.log('Sorry, didnt find ' + function_name + '.');
    }
}

//-------------------------------------UPDATE AJAX FUNCTIONS-------------------------------------
function update_analytics_data_from_api(start_date, end_date) {

    var unloaded_card = jQuery(".analytics-card:not(.loaded)").first();
    var function_name = unloaded_card.data("function");
    var per_page = jQuery('.table-per-page').val();
    var offset = 0;
    var data = {
        start_date: start_date,
        end_date: end_date,
        function_name: function_name,
        per_page: per_page,
        offset: offset
    };

    axios.post(wpApiSettings.root + 'api/v1/analytics/loaddata', data, {
        headers: { 'X-WP-Nonce' : wpApiSettings.nonce }
    }).then(function(data){
        updated_analytics = JSON.parse(JSON.stringify(data.data));
    }).catch(function(error){
        console.log(error);
    });

}

//-------------------------------------DOCUMENT READY-------------------------------------
jQuery(function() {

var start = moment().subtract(1, 'days');
var end = moment().subtract(1, 'days');

function update_analytics_datepicker(start, end) {
    jQuery('.single-table .view-list li').removeClass('active');
    jQuery('.single-table .view-list li:first-child').addClass('active');

    jQuery(".analytics-card").removeClass("loaded");
    jQuery('.daterangepicker').removeClass('loaded');
    jQuery('#reportrange').removeClass('loaded');

    import_analytics_data_from_api(start.format('MMMM D, YYYY'), end.format('MMMM D, YYYY'))

    jQuery('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
}

jQuery('#reportrange').daterangepicker({
    startDate: start,
    endDate: end,
    ranges: {
       'Today': [moment(), moment()],
       'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
       'Last 7 Days': [moment().subtract(6, 'days'), moment()],
       'Last 30 Days': [moment().subtract(29, 'days'), moment()],
       'This Month': [moment().startOf('month'), moment().endOf('month')],
       'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
    }
}, update_analytics_datepicker);

update_analytics_datepicker(start, end);

//This month statistics
get_this_month_stats(moment().startOf('month').format('MMMM D, YYYY'), moment().format('MMMM D, YYYY'));


});

//-------------------------------------QUERY FUNCTIONS-------------------------------------
function get_this_month_stats(start_date, end_date){
    var data = {
        function_name : 'quick-stats',
        start_date: start_date,
        end_date: end_date,
    };
    axios.post(wpApiSettings.root + 'api/v1/analytics/loaddata', data, {
        headers: { 'X-WP-Nonce' : wpApiSettings.nonce }
    }).then(function(data) {
        var jsonParse;
        try {
            if('undefined' === typeof data || data.length == 0) {
                return;
            }
            jsonParse = JSON.parse( JSON.stringify(data.data));
        }
        catch (e) {
            console.log("Load all data error for " + function_name + " : "+e);
        };
        if('undefined' === typeof jsonParse || jsonParse.length == 0) {
            return;
        }
        let jsonResult = jsonParse.result;

        update_tm_quick_stats( jsonResult, start_date, end_date );

    }).catch(function(error){
        console.log(error);
    });
}

function update_quick_stats( data ) {

    jQuery("#top-bar").html('<span class="loader"></span>');

    if(data === '') {
        console.log("No quick stats")
    } else {
        jQuery.each(data, function(key, value) {
            var tool_tip = "";
            (data[key].title) == "Total Sessions" ? tool_tip = "Block of time visitor is on your site, after 30 minutes session ends" : "";
            (data[key].title) == "Average Session Duration" ? tool_tip = "Average length of time on your website in minutes" : "";
            (data[key].title) == "Total Visits" ? tool_tip = "All visitors returning and unique" : "";
            (data[key].title) == "Total Page Views" ? tool_tip = "Total of pages viewed by visitors to your website" : "";
            (data[key].title) == "Average Actions" ? tool_tip = "Average visitors that interacted with your website" : "";
            (data[key].title) == "Total Page Reads" ? tool_tip = "Total visitors that read full articles" : "";
            (data[key].title) == "Average Page Reads" ? tool_tip = "Average visitors that read full articles" : "";
            jQuery("#top-bar").append("<figure class='quick-stat'><h6 class>" + data[key].title + "<span class='tooltip'>" + aa_admin.info_svg + "<span class='tooltiptext'>"+ tool_tip +"</span></span></h6><h3><span class='base-num'>" + addCommas(data[key].total) + "</span><span class='percentage " + data[key].class + "'>" + data[key].percentage + "</span></h3><p><span class='difference-num'>" + addCommas(data[key].difference) + "</span></p></figure>");
        });
    }
    jQuery('.daterangepicker').addClass('loaded');
    jQuery('#reportrange').addClass('loaded');
}

//-------------------------------------THIS MONTH STATISTICS-------------------------------------
function update_tm_quick_stats( data, start_date, end_date ) {
    if(data === '') {
        console.log("No quick stats")
    } else {
        jQuery('#tmStatistics > div > div > div.flex.justify-between.items-start.p-4.rounded-t > h3').text('This month statistics (' + start_date + ' - ' + end_date + ')');
        jQuery.each(data, function(key, value) {
            var tool_tip = "";
            (data[key].title) == "Total Sessions" ? tool_tip = "Block of time visitor is on your site, after 30 minutes session ends" : "";
            (data[key].title) == "Average Session Duration" ? tool_tip = "Average length of time on your website in minutes" : "";
            (data[key].title) == "Total Visits" ? tool_tip = "All visitors returning and unique" : "";
            (data[key].title) == "Total Page Views" ? tool_tip = "Total of pages viewed by visitors to your website" : "";
            (data[key].title) == "Average Actions" ? tool_tip = "Average visitors that interacted with your website" : "";
            (data[key].title) == "Total Page Reads" ? tool_tip = "Total visitors that read full articles" : "";
            (data[key].title) == "Average Page Reads" ? tool_tip = "Average visitors that read full articles" : "";
            jQuery("#tm-statistics").append("<figure class='quick-stat mr-9'><h6 class='text-xl font-extralight'>" + data[key].title + "<span class='tooltip'>" + aa_admin.info_svg + "<span class='tooltiptext'>"+ tool_tip +"</span></span></h6><h3 class='flex text-7xl mt-1 text-white items-center'><span class='base-num'>" + addCommas(data[key].total) + "</span><span class='percentage " + data[key].class + "'>" + data[key].percentage + "</span></h3><p><span class='difference-num'>" + addCommas(data[key].difference) + "</span></p></figure>");
        });
        //jQuery("#top-bar").after('<div class="grid justify-items-end bg-qs-gray rounded-br-lg pb-3 pr-3"><button class="block text-black bg-gray-50 hover:bg-gray-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button" data-modal-toggle="defaultModal">This month statistics</button></div>');
    }
}

function generate_bar_chart( id, range ){
    let data;
    let dateRange = [];
    let uniqueVisits = [];
    let returnVisits = [];
    let allVisits = [];
    let reads =[];

    let monthNames = ["January", "February", "March", "April", "May","June","July", "August", "September", "October", "November","December"];
    if( range === 7 ){
        data = aa_admin.bar_data_7;
    } else if( range === 30 ){
        data = aa_admin.bar_data_30;
    } else if( range === 60 ) {
        data = aa_admin.bar_data_60;
    } else if( range === 90 ) {
        data = aa_admin.bar_data_90;
    } else {
        return;
    }

    if( range === 7 ) {
            jQuery.each(data.records, function(key, value) {
            //format dates
            let oldDate = new Date( data.records[key].date );
            //let year = oldDate.getFullYear();
            let month = monthNames[oldDate.getMonth()];
            data.records[key].date.slice( 1 + data.records[key].date.lastIndexOf('-'), data.records[key].date.length );
            let day = oldDate.getDate();
            let newDate = month + ' ' + day;
            dateRange.push( newDate );
            uniqueVisits.push( data.records[key].unique_visitors );
            returnVisits.push( data.records[key].returning_visitors );
            allVisits.push(parseInt(data.records[key].unique_visitors) +  parseInt(data.records[key].returning_visitors));
            reads.push(data.records[key].page_reads);
        })
    } else {
            jQuery.each(data.records, function(key, value) {
            //format dates
            let oldDate = new Date( data.records[key].date );
            //let year = oldDate.getFullYear();
            let month = monthNames[oldDate.getMonth()];
             //data.records[key].date.slice( 1 + data.records[key].date.lastIndexOf('-'), data.records[key].date.length );
            let day = oldDate.getDate();
            let newDate = month + ' ' + day;
            dateRange.push( newDate );
            uniqueVisits.push( data.records[key].unique_visitors );
            returnVisits.push( data.records[key].returning_visitors );
            allVisits.push(parseInt(data.records[key].unique_visitors) +  parseInt(data.records[key].returning_visitors ) );
            reads.push(data.records[key].page_reads);
        })
        
    }

    //-------------------------------------BAR CHARTS FUNCTIONS-------------------------------------
    
    jQuery("canvas#" + range + "-day-chart").remove();
    jQuery(".bar-chart." + range + "-day-span").append('<canvas id="' + range + '-day-chart"></canvas>');

    var ctx = document.getElementById(id).getContext('2d');
    ctx.height = 500;
    var visitorChart = new Chart(ctx, {
        // The type of chart we want to create
        type: 'line',

        // The data for our dataset
        data: {
            labels: dateRange,
            datasets: [
                {
                    label: ['Page Reads'],
                    fill:false,
                    borderColor: '#2D8F00',
                    data: reads,
                    trendlineLinear: {
                                style: "#2D8F00",
                                lineStyle: "dotted",
                                width: 2
                            }
                },

                {
                    label: ['All Vistors'],
                    fill:false,
                    borderColor: '#546a79',
                    data: allVisits,
                    trendlineLinear: {
                                style: "#546a79",
                                lineStyle: "dotted",
                                width: 2
                            }
                }
            ]
        },
        // Configuration options go here
        options: {
            
            scales: {
                xAxes: [{
                    stacked: true
                }],
                yAxes: [{
                    stacked: true,
                    ticks: {
                        beginAtZero: true
                    }
                }]
            }
        }
    });

}
generate_bar_chart( '7-day-chart', 7 );
generate_bar_chart( '30-day-chart', 30 );
generate_bar_chart( '60-day-chart', 60 );
generate_bar_chart( '90-day-chart', 90 );

//-------------------------------------TOGGLE BAR CHARTS-------------------------------------
jQuery(document).on( 'click', '#analytics-page .toggle-visuals', function(){
            jQuery(this).toggleClass('active');
            jQuery('#top-bar').toggleClass('active');
            jQuery('.bar-chart-wrapper').slideToggle();
        });

        jQuery(document).on( 'click', '#analytics-page .bar-chart-wrapper ul li:not(.active)', function(){
            jQuery('.bar-chart-wrapper ul li').removeClass('active');
            jQuery(this).addClass('active');

            let chart = jQuery(this).data('range');
            jQuery('.bar-chart').hide();
            jQuery('.' + chart).fadeIn();
        } )

//-------------------------------------OUTPUT FUNCTIONS-------------------------------------
function output_analytics_entry_exit_tables(data) {

    jQuery("#" + data.selector + " .rows").html('');
    jQuery('#' + data.selector + ' .table-title').attr('title', data.description);
    if(data.total_records == 0) {
        jQuery("#" + data.selector + " .rows").append('<ul class="row"><li class="no-data">No data found.</li></ul>');
        hideTableUI( data.selector );
    } else {
        let iteration = 1;
        var classType = 'parent';
        var titleQuery;

        showTableUI( data.selector );
        initialize_table_structure( data.selector, data.description, data.offset, data.per_page, data.records, data.total_records, data.data_view );

        if( data.data_view == 'paths' ) {

            jQuery("#" + data.selector + " .header li:first-child").text('Paths / Queries');
            jQuery.each(data.records, function(key, value) {
                //Parent
                classType = 'parent';

                let bouncePercent = (data.records[key].bounces / data.records[key].total_visitors) * 100;
                bouncePercent = bouncePercent.toFixed(2);
                let viewPercent = (data.records[key].total_visitors / data.total_visitors) * 100;
                viewPercent = viewPercent.toFixed(2);

                jQuery("#" + data.selector + " .rows").append("<ul title='" + data.records[key].path + "' data-id='" + iteration + "' class='entry-" + classType + " row' data-type='" + classType + "'><li data-type='title' class='toggle-list'><span>+</span>" + concatLongString(data.records[key].path) + "</li><li><span class='visitor-percentage stat-percent'>" + viewPercent + "%</span>" + addCommas(data.records[key].total_visitors) + "</li><li class='bounces'><span class='bounce-percentage stat-percent'>" + bouncePercent + "%</span>" + addCommas(data.records[key].bounces) +"</li><div class='sublist'></div></ul>");
                classType = 'child';
                //Child
                jQuery.each(data.records[key].query, function(secondkey, value) {
                    
                    titleQuery = data.records[key].query[secondkey].query;

                    let childBouncePercent = (data.records[key].query[secondkey].bounces / data.records[key].query[secondkey].total_visitors) * 100;
                    childBouncePercent = childBouncePercent.toFixed(2);
                    let childViewPercent = (data.records[key].query[secondkey].total_visitors / data.total_visitors) * 100;
                    childViewPercent = childViewPercent.toFixed(2);
                    
                    jQuery("#" + data.selector + " [data-id="+iteration+"] .sublist ").append("<ul title='" + titleQuery + "' data-related='" + iteration + "' class='entry-" + classType + " row' data-type='" + classType + "'><li>" + concatLongString(titleQuery) + "</li><li><span class='visitor-percentage stat-percent'>" + childViewPercent + "%</span>" + addCommas(data.records[key].query[secondkey].total_visitors) + "</li><li class='bounces'><span class='bounce-percentage stat-percent'>" + childBouncePercent + "%</span>" + addCommas(data.records[key].query[secondkey].bounces) +"</li></ul>");
                    
                });
                iteration++;
            });
        } else {
            jQuery("#" + data.selector + " .header li:first-child").text('Titles');
            jQuery.each(data.records, function(key, value) {

                titleQuery = data.records[key].title;
                if( titleQuery === '' || titleQuery === undefined ) {
                    titleQuery = 'Home';
                }

                let bouncePercent = (data.records[key].bounces / data.records[key].total_visitors) * 100;

                bouncePercent = bouncePercent.toFixed(2);

                let viewPercent = (data.records[key].total_visitors / data.total_visitors) * 100;

                viewPercent = viewPercent.toFixed(2);

                //jQuery("#" + data.selector + " .rows").append("<ul title='" + titleQuery + "' class='entry-" + classType + " row' data-type='" + classType + "'> <li colspan='2'>" + concatLongString(titleQuery) + "</li><li><span class='visitor-percentage stat-percent'>" + viewPercent + "%</span>" + addCommas(data.records[key].total_visitors) + "</li><li class='bounces'><span class='bounce-percentage stat-percent'>" + bouncePercent + "%</span>" + addCommas(data.records[key].bounces) +"</li></ul>");
                jQuery("#" + data.selector + " .rows").append(
                    '       <div class="table-row">' +
                    '           <div class="table-cell p-2 border-b border-slate-500 text-left w-4/6">' + concatLongString(titleQuery) + '</div>' +
                    '           <div class="table-cell p-2 border-b border-slate-500 text-right w-2/6">' + '<span class="visitor-percentage stat-percent">' + viewPercent + '%</span>' + addCommas(data.records[key].total_visitors) + '</div>' +
                //    '           <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(data.records[key].bounces) + '</div>' +
                    '       </div>'
                );

            });
        }
    }
}



function output_analytics_page_visits_table(data) {
    jQuery("#" + data.selector + " .rows").html('');
    jQuery('#' + data.selector + ' .table-title').attr('title', data.description);

    if(data.total_records == 0) {
        //jQuery("#" + data.selector + " .rows").append('<ul class="row"><li class="no-data">No data found.</li></ul>');
        jQuery("#" + data.selector + " .rows").append('<div class="w-full text-center">No data found.</div>');
        hideTableUI( data.selector );
    } else {
        let iteration = 1;
        var classType = 'parent';
        var titleQuery;
        
        showTableUI( data.selector );
        initialize_table_structure( data.selector, data.description, data.offset, data.per_page, data.records, data.total_records, data.data_view);
        if( data.data_view == 'paths' ) {
            jQuery("#" + data.selector + " .header li:first-child").text('Paths / Queries');
            jQuery.each(data.records, function(key, value) {

                //Parent
                classType = 'parent';

                let bouncePercent = (data.records[key].bounces / data.records[key].total_page_views) * 100;
                bouncePercent = bouncePercent.toFixed(2);
                
                let viewPercent = (data.records[key].total_page_views / data.total_visitors) * 100;
                viewPercent = viewPercent.toFixed(2);
                
                let pageReadsPercent = (data.records[key].total_page_reads / data.records[key].total_page_views) * 100;
                pageReadsPercent = pageReadsPercent.toFixed(2)

                let uniqueVisitsPercent = (data.records[key].total_unique_visitors / data.records[key].total_page_views) * 100;
                uniqueVisitsPercent = uniqueVisitsPercent.toFixed(2)
                
                var parent_path = data.records[key].path;
                if (parent_path === "/"){
                    parent_path = "home";
                }
                
                jQuery("#" + data.selector + " .rows").append("<ul id ='" + data.records[key].path + "' title='" + data.records[key].path + "' data-id='" + iteration + "' class='entry-" + classType + " row' data-type='" + classType + "'><li data-type='title' class='toggle-list'><span>+</span>" + concatLongString(data.records[key].path) + "</li><li><span class='visitor-percentage stat-percent'>" + viewPercent + "%</span>" + addCommas(data.records[key].total_page_views) + "</li><li><span class='unique-visitors-percentage stat-percent'>" + uniqueVisitsPercent + "%</span>" + addCommas(data.records[key].total_unique_visitors) + "</li><li class='page-reads'><span class='page-reads-percentage stat-percent'>" + pageReadsPercent + "%</span>" + addCommas(data.records[key].total_page_reads) + "</li><li class='bounces'><span class='bounce-percentage stat-percent'>" + bouncePercent + "%</span>" + addCommas(data.records[key].bounces) +"</li><li class='bounces'>" + addCommas(data.records[key].claps) +"</li><div class='sublist'></div></ul>");

                classType = 'child';

                //Child
                jQuery.each(data.records[key].query, function(secondkey, value) {
                    titleQuery = data.records[key].query[secondkey].query;
                    
                   
                if (titleQuery === "" || titleQuery === null){
                    titleQuery = "/";
                }
                    let childBouncePercent = (data.records[key].query[secondkey].bounces / data.records[key].query[secondkey].
                    total_page_views) * 100;
                    childBouncePercent = childBouncePercent.toFixed(2);
                    
                    let childViewPercent = (data.records[key].query[secondkey].total_page_views / data.total_visitors) * 100;
                    childViewPercent = childViewPercent.toFixed(2);
                    
                    let pageReadsPercent = (data.records[key].total_page_reads /data.records[key].total_page_views) * 100;
                    pageReadsPercent = pageReadsPercent.toFixed(2)

                    let uniqueVisitsPercent = (data.records[key].query[secondkey].total_unique_visitors / data.records[key].query[secondkey].total_page_views) * 100;
                    uniqueVisitsPercent = uniqueVisitsPercent.toFixed(2)

                    var parent_path = data.records[key].path;

                    if (parent_path === "" || parent_path === undefined || parent_path === "http:"){
                        parent_path = "home";
                    }

                    if (parent_path.indexOf('/') > -1) {
                        var fields = parent_path.split('/');
                        var parent_path = fields[0];
                            if(parent_path == ""){
                                parent_path = "home"
                            }

                    }

                    jQuery("#" + parent_path + " .sublist" ).append("<ul title='" + titleQuery + "' data-related='" + iteration + "' class='entry-" + classType + " row' data-type='" + classType + "'> <li>" + concatLongString(titleQuery) + "</li><li><span class='visitor-percentage stat-percent'>" + childViewPercent + "%</span>" + addCommas(data.records[key].query[secondkey].total_page_views) + "</li><li><span class='unique-visitors-percentage stat-percent'>" + uniqueVisitsPercent + "%</span>" + addCommas(data.records[key].query[secondkey].total_unique_visitors) + "</li><li class='page-reads'><span class='page-reads-percentage stat-percent'>" + pageReadsPercent + "%</span>" + addCommas(data.records[key].query[secondkey].total_page_reads) + "</li><li class='bounces'><span class='bounce-percentage stat-percent'>" + childBouncePercent + "%</span>" + addCommas(data.records[key].query[secondkey].bounces) +"</li><li class='bounces'>"+ addCommas(data.records[key].query[secondkey].claps) +"</li></ul>");
                });
                iteration++;
            });
        } else {
            // var table_rows = '<div class="table table-auto w-full">' +
            // '   <div class="table-row-group">';
            var table_rows = '';
            // jQuery("#" + data.selector + " .rows").append(
            //     '<div class="table w-full">' +
            //     '   <div class="table-row-group">'
            // );
            jQuery.each(data.records, function(key, value) {

                jQuery("#" + data.selector + " .header li:first-child").text('Titles');
                titleQuery = data.records[key].title;
                if( titleQuery === '' || titleQuery === undefined ) {
                    titleQuery = 'Home';
                }

                let bouncePercent = (data.records[key].bounces / data.records[key].total_page_views) * 100;
                bouncePercent = bouncePercent.toFixed(2);

                let viewPercent = (data.records[key].total_page_views / data.total_visitors) * 100;
                viewPercent = viewPercent.toFixed(2)
                
                let pageReadsPercent = (data.records[key].total_page_reads / data.records[key].total_page_views) * 100;
                pageReadsPercent = pageReadsPercent.toFixed(2)
                
                let uniqueVisitsPercent = (data.records[key].total_unique_visitors / data.records[key].total_page_views) * 100;
                uniqueVisitsPercent = uniqueVisitsPercent.toFixed(2)

                //jQuery("#" + data.selector + " .rows").append("<ul title='" + titleQuery + "' class='entry-" + classType + " row' data-type='" + classType + "'> <li>" + concatLongString(titleQuery) + "</li><li><span class='visitor-percentage stat-percent'>" + viewPercent + "%</span>" + addCommas(data.records[key].total_page_views) + "</li><li><span class='unique-visitors-percentage stat-percent'>" + uniqueVisitsPercent + "%</span>" + addCommas(data.records[key].total_unique_visitors) + "</li><li class='page-reads'><span class='page-reads-percentage stat-percent'>" + pageReadsPercent + "%</span>" + addCommas(data.records[key].total_page_reads) + "</li><li class='bounces'><span class='bounce-percentage stat-percent'>" + bouncePercent + "%</span>" + addCommas(data.records[key].bounces) +"</li><li class='claps'>" + addCommas(data.records[key].total_claps) +"</li></ul>");
                jQuery("#" + data.selector + " .rows").append(
                '       <div class="table-row">' +
                '           <div class="table-cell p-2 border-b border-slate-500 text-left w-4/6">' + concatLongString(titleQuery) + '</div>' +
                '           <div class="table-cell p-2 border-b border-slate-500 text-right w-2/6">' + addCommas(data.records[key].total_page_views) + '</div>' +
                //'           <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(data.records[key].total_unique_visitors) + '</div>' +
                //'           <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(data.records[key].total_page_reads) + '</div>' +
                //'           <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(data.records[key].bounces) + '</div>' +
                '       </div>'
                );

            });
            jQuery("#" + data.selector + " .rows").append(table_rows);
        }
    }
}

function output_analytics_referrers_table(data) {

    jQuery("#" + data.selector + " .rows").html('');
    jQuery('#' + data.selector + ' .table-title').attr('title', data.description);
    if(data.total_records == 0) {
        jQuery("#" + data.selector + " .rows").append('<ul class="row"><li class="no-data">No data found.</li></ul>');
        hideTableUI( data.selector );
    } else {
        let iteration = 1;
        var classType = 'parent';

        showTableUI( data.selector );
        initialize_table_structure( data.selector, data.description, data.offset, data.per_page, data.records, data.total_records, data.data_view );

        jQuery.each(data.records, function(key, value) {

            //let viewPercent = Math.round( (data.records[key].visitors / data.total_visitors) * 100 );
            let viewCalc = (data.records[key].visitors / data.total_visitors) * 100;
            let viewPercent = viewCalc.toFixed(2);

            let icon = get_svg_icon(key);
            
            let uniqueVisitsPercent = (data.records[key].unique_visitors / data.records[key].visitors) * 100;
            uniqueVisitsPercent = uniqueVisitsPercent.toFixed(2)

            //jQuery("#" + data.selector + " .rows").append("<ul class='entry-" + classType + " row' data-type='" + classType + "'> <li colspan='2'>" + key + "</li><li><span class='visitor-percentage stat-percent'>" + viewPercent + "%</span>" + addCommas(data.records[key].visitors) + "</li><li><span class='unique-visitors-percentage stat-percent'>" + uniqueVisitsPercent + "%</span>" + addCommas(data.records[key].unique_visitors) + "</li><li>" + addCommas(data.records[key].actions) +"</li></ul>");
            jQuery("#" + data.selector + " .rows").append(
                '   <div class="table-row">' +
                '       <div class="table-cell p-2 border-b border-slate-500 text-left w-3/6">' + icon + key + '</div>' +
                '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(data.records[key].visitors) + '</div>' +
                '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(data.records[key].unique_visitors) + '</div>' +
                '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(data.records[key].actions) + '</div>' +
                '   </div>'
            );
        });
    }
}

function output_analytics_keywords_table(data) {
    jQuery("#" + data.selector + " .rows").html('');
    jQuery('#' + data.selector + ' .table-title').attr('title', data.description);
    if(data.total_records == 0) {
        jQuery("#" + data.selector + " .rows").append('<ul class="row"><li class="no-data">No data found.</li></ul>');
        hideTableUI( data.selector );
    } else {
        let iteration = 1;
        var classType = 'parent';

        showTableUI( data.selector );
        initialize_table_structure( data.selector, data.description, data.offset, data.per_page, data.records, data.total_records, data.data_view );

        jQuery.each(data.records, function(key, value) {
            //jQuery("#" + data.selector + " .rows").append("<ul class='entry-" + classType + " row' data-type='" + classType + "'> <li colspan='2'>" + data.records[key].keywords + "</li><li>" + addCommas(data.records[key].visitors) + "</li></ul>");
            jQuery("#" + data.selector + " .rows").append(
                '   <div class="table-row">' +
                '       <div class="table-cell p-2 border-b border-slate-500 text-left w-3/6">' + data.records[key].keywords + '</div>' +
                '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(data.records[key].visitors) + '</div>' +
                '   </div>'
            );
        });
    };
}

function output_analytics_engines(data) {

    jQuery("#" + data.selector + ' .chart-wrapper').html('');
    jQuery('#' + data.selector + ' .table-title').attr('title', data.description);
    if(data.total_records == 0) {
        jQuery("#" + data.selector + ' .chart-wrapper').append('No data found.');
        hideTableUI( data.selector );
    } else {
        let names = [];
        let legends = [];
        let visitors = [];
        let colors = ["#2D8F00","#4B89AA","#76c893", "#546a79" ];

        showTableUI( data.selector );

        let iconPath = jQuery('#' + data.selector + ' img.icon').attr('src');
        let infoIcon = '<img class="icon" src="' + iconPath +'" />';

        if( data.data_view == 'social_networks' ) {
            jQuery("#" + data.selector + " .table-title").html('Social Networks ' + infoIcon);

            let totalVisits = data.records.total_network_visitors;
            jQuery.each(data.records.social_networks, function(key, value) {
                //calculate percentages and store label as variable
                let percentage = Math.round( (data.records.social_networks[key].unique_visitors / totalVisits) * 100 );

                let label = addCommas(data.records.social_networks[key].social_network) + ': ' + percentage + '% (' + addCommas(data.records.social_networks[key].unique_visitors) + ' Unique Visits)';

                let legend = data.records.social_networks[key].social_network;

                if( data.records.social_networks[key].social_network != 'no_network' ){
                    visitors.push(data.records.social_networks[key].unique_visitors);
                    names.push(label);
                    legends.push(legend);
                    colors.push(data.records.social_networks[key].color);
                }
            });
            generate_pie_chart( 'pie', 'networks-chart', data.selector, visitors, names, colors, legends );
        } else {

            jQuery("#" + data.selector + " .table-title").html('Search Engines ' + infoIcon);

            let totalVisits = data.records.total_engine_visitors;
            jQuery.each(data.records.search_engines, function(key, value) {
                //calculate percentages and store label as variable
                let percentage = Math.round( (data.records.search_engines[key].unique_visitors / totalVisits) * 100 );
                let label = data.records.search_engines[key].search_engine + ': ' + percentage + '% (' + data.records.search_engines[key].unique_visitors + ' Unique Visits)';
                let legend = data.records.search_engines[key].search_engine;
                if( data.records.search_engines[key].search_engine != 'No Engine' ){
                    visitors.push(data.records.search_engines[key].unique_visitors);
                    names.push(label);
                    legends.push(legend);
                    colors.push(data.records.search_engines[key].color);
                }
            });
            generate_pie_chart( 'pie', 'engines-chart', data.selector, visitors, names, colors, legends );
        }
    }
}

function output_analytics_visit_overview(data) {
    jQuery("#" + data.selector + ' .chart-wrapper').html('');
    jQuery('#' + data.selector + ' .table-title').attr('title', data.description);

    let visitors = [];
    let legends = ['Unique Visits', 'Returning Visits'];
    let colors = ['#2D8F00','#4B89AA'];

    if(data.total_records == 0) {
        jQuery("#" + data.selector + ' .chart-wrapper').append('No data found.');
        jQuery("#" + data.selector + ' .overview-title').remove();
    } else {
        jQuery("#" + data.selector + ' .overview-title').remove();
        let total = data.records.total_visitors;
        let unique = data.records.total_unique_visitors;
        let difference = data.records.difference;

        let uniquePercentage = Math.round( (data.records.total_unique_visitors / data.records.total_visitors) * 100 ) + '%';
        let percentage = Math.round( (data.records.difference / data.records.total_visitors) * 100 ) + '%';
        let labels = ['Unique Visits: ' + addCommas(unique) + ' (' + uniquePercentage + ')', 'Returning Visits: ' + addCommas(difference) + ' (' + percentage + ')'];

        visitors.push(unique, difference);
        jQuery("#" + data.selector + ' .table-meta').append('<h6 class="overview-title">Total Visitors: ' + addCommas(total) +'</h6>');
        generate_pie_chart( 'doughnut', 'visit-overview-chart', data.selector, visitors, labels, colors, legends );
    }
}

function output_domain_metrics(data) {
    data.description = 'Domain metrics';
    jQuery("#" + data.selector + " .rows").html('');
    jQuery('#' + data.selector + ' .table-title').attr('title', data.description);

    showTableUI( data.selector );
    var classType = 'parent';

    jQuery('#' + data.selector + ' .metrics-domain').text(data.metrics.domain);

    jQuery("#" + data.selector + " .rows").append("<ul class='entry-" + classType + " row' data-type='" + classType + "'><li><strong>Domain Age:</strong> " + data.metrics.domain_age + "</li></ul>");
    jQuery("#" + data.selector + " .rows").append("<ul class='entry-" + classType + " row' data-type='" + classType + "'><li><strong>Google Indexed Pages:</strong> " + data.metrics.google_indexed_pages + "</li></ul>");
    jQuery("#" + data.selector + " .rows").append("<ul class='entry-" + classType + " row' data-type='" + classType + "'><li><strong>Bing Indexed Pages:</strong> " + data.metrics.bing_indexed_pages + "</li></ul>");
    jQuery("#" + data.selector + " .rows").append("<ul class='entry-" + classType + " row' data-type='" + classType + "'><li><strong>Alexa Page Rank:</strong> " + data.metrics.alexa_page_rank + "</li></ul>");
}


function output_visits_per_day_chart(data) {

    jQuery("canvas#days-of-week-chart").remove();
    jQuery("#vists-per-day .wrapper").append('<canvas id="days-of-week-chart" ></canvas>');
    
    var ctx = document.getElementById("days-of-week-chart").getContext('2d');
    var daysOfWeekChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Visits', // Name the series
                data: data.visits,
                fill: true,
                borderColor: '#2D8F00', // Add custom color border (Line)
                backgroundColor: '#2D8F00', // Add custom color background (Points and Fill)
                borderWidth: 1 // Specify bar border width
            },
            {
                label: 'Article Reads', // Name the series
                data: data.reads,
                fill: true,
                borderColor: '#4B89AA', // Add custom color border (Line)
                backgroundColor: '#4B89AA', // Add custom color background (Points and Fill)
                borderWidth: 1 // Specify bar border width
            }]
        },
        options: {
            tooltips: {
                displayColors: true,
                callbacks:{
                    mode: 'x',
                },
            },
            scales: {
                x: {
                stacked: false
                },
                y: {
                stacked: false,
                    suggestedMax: 200 * 1.2,
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },
            responsive: true,
            maintainAspectRatio: false,
        }
    });

}

function output_user_accounts_chart(data){

    jQuery("canvas#userAccountsChart").remove();
    jQuery("#user-accounts .wrapper").append('<canvas id="userAccountsChart"></canvas>');

    var ctx = document.getElementById("userAccountsChart").getContext('2d');
    var userAccountsChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: data.labels,
        datasets: data.data
        
    },
    options: {
        tooltips: {
            displayColors: true,
        },
        scales: {
            x: {
                stacked: true
            },
            y: {
                stacked: true,
                suggestedMax: data.maxUsers * 1.2,
            }
        },
        responsive: true,
        maintainAspectRatio: false,
        legend: { position: 'bottom' },
    }
});

}

function output_visits_per_hour(data){
    //destory old table on reload
    jQuery("#visits-per-hour-table").remove();
    jQuery("#visits-per-hour .wrapper").append("<div id='visits-per-hour-table'></div>");
    
    var hour_rows ='';
    jQuery.each(data.results, function( index, value ) {

        hour_rows=hour_rows+"<div class='hour-row'>"

        jQuery.each(value, function( subindex, subvalue ){
            let background_color= '';
            
            if (subvalue == 0){
                background_color ='#e0e0e0';
            }
            else if (subvalue <= data.legend_array[0].max){
                background_color= "#94d4eb";
            }
            else if (subvalue > data.legend_array[0].max && subvalue <= data.legend_array[1].max){
                background_color= "#48a4f6";
            }
            else if (subvalue > data.legend_array[1].max && subvalue <= data.legend_array[2].max){
                background_color= "#4087f3";
            }
            else if (subvalue > data.legend_array[2].max && subvalue <= data.legend_array[3].max){
                background_color= "#3368b8";
            }

            hour_rows=hour_rows+"<span class='hour-row-cell' style='background-color:"+background_color+";' >"+subvalue+"</span>";

        })

            hour_rows=hour_rows+"<span class='hour-row-cell hour'>"+index+"</span></div>";

    });
    
    var days_of_week_labels ="<div class='days-row'>";
    jQuery.each(data.days_of_week_labels, function( index, value ){
        var threeLetterDay = value.slice(0,3);
        days_of_week_labels = days_of_week_labels+"<span class='days-row-cell'>"+value+"</span><span class='days-row-cell-mobile'>"+threeLetterDay+"</span>";
    })
    days_of_week_labels =days_of_week_labels +"<span class=' days-row-cell'></span><span class=' days-row-cell-mobile'></span></div>";

    


    var legend ="<div class='legend'>";
    jQuery.each(data.legend_array, function( index, value ) {
        if (index ==0 ){
            var numbers ="<div><span>0</span><span class='legend-range-max'>"+value.max+"</span></div>";
        }else{ 
            var numbers ="<div class='legend-range-max'>"+value.max+"</div>";
        }
        legend = legend+"<div class='legend-range'><div class='legend-color' style='background-color:"+value.color+";'></div>"+numbers+"</div>";
        
        
    });
    legend = legend+"<div class='legend-range'></div></div>";
    
    jQuery('#visits-per-hour-table').append(hour_rows);
    jQuery('#visits-per-hour-table').append(days_of_week_labels);
    jQuery('#visits-per-hour-table').append(legend);

    jQuery(".hour:odd").empty();
    
    
}

function output_campaign_mediums_sources(data){
    jQuery("#campaign-mediums-sources .chart-wrapper").remove();
    jQuery("#campaign-mediums-sources").append("<div class='chart-wrapper'></div>");



    if(data.total_sessions === 0){
        jQuery("#" + data.selector + ' .chart-wrapper').append('<p>No data found.</p>');
    }else {	
        
        jQuery("#" + data.selector + ' .chart-wrapper p').remove();
        
        showTableUI(data.selector);

        let iconPath = jQuery('#' + data.selector + ' img.icon').attr('src');
        let infoIcon = '<img class="icon" src="' + iconPath +'" />';

        let labels = [];
        let legends = [];
        let sessions = [];
        let colors = ["#2D8F00","#4B89AA","#76c893", "#546a79","#43c705","#1a759f", "#79bf3a","#34a0a4" ];
    
        if (data.data_view== 'campaign_mediums'){

            jQuery("#" + data.selector + " .table-title").html('Campaign Traffic by Mediums' + infoIcon);

            let totalSessions = data.total_sessions;

            jQuery.each(data.campaign_medium_records, function (key, value){

                let percentage = Math.round( (data.campaign_medium_records[key].sessions / totalSessions) * 100 );

                let label = data.campaign_medium_records[key].medium + ': ' +percentage +'% ('+data.campaign_medium_records[key].sessions + ' Visits )' ;

                let legend = data.campaign_medium_records[key].medium;

                sessions.push(data.campaign_medium_records[key].sessions);
                labels.push(label);
                legends.push(legend);
                colors.push(data.campaign_medium_records[key].color);
            });
            generate_pie_chart( 'pie', 'medium-chart', data.selector, sessions, labels, colors, legends );
        } else if (data.data_view == 'campaign_sources'){

            jQuery("#" + data.selector + " .table-title").html('Campaign Traffic by Sources' + infoIcon);

            let totalSessions = data.total_sessions;

            jQuery.each(data.campaign_source_records, function (key, value){

                let percentage = Math.round( (data.campaign_source_records[key].sessions / totalSessions) * 100 );

                let label = data.campaign_source_records[key].source + ': ' +percentage +'% ('+data.campaign_source_records[key].sessions + ' Visits )' ;

                let legend = data.campaign_source_records[key].source;

                sessions.push(data.campaign_source_records[key].sessions);
                labels.push(label);
                legends.push(legend);
                colors.push(data.campaign_source_records[key].color);
            });
            generate_pie_chart( 'pie', 'sources-chart', data.selector, sessions, labels, colors, legends );
        }
    }
}

function output_campaign_data(data){

    jQuery("#" + data.selector + " .rows").html('');
    jQuery('#' + data.selector + ' .table-title').attr('title', data.description);
    if(data.total_records == 0) {
        jQuery("#" + data.selector + " .rows").append('<ul class="row"><li class="no-data">No data found.</li></ul>');
        hideTableUI( data.selector );
    } else {

        showTableUI( data.selector );

        initialize_table_structure( data.selector, data.description, data.offset, data.per_page, data.records, data.total_records, data.data_view);

        let campaignTitle = '';
        var classType = 'parent';


        jQuery.each(data.records, function(key, value){

            let campaignTitle = data.records[key].title;


            jQuery("#" + data.selector+" .rows").append("<ul title='" + campaignTitle + "' class='entry-" + classType + " row' data-type='" + classType + "'> <li colspan='2'>" + concatLongString(campaignTitle) + "</li><li>" + data.records[key].term + "</li><li>" + addCommas(data.records[key].sessions) +"</li></ul>")

        });
    }
}

function output_exit_survey_data(data){

    jQuery("#exit-survey .chart-wrapper").remove();
    jQuery("#exit-survey").append("<div class='chart-wrapper'></div>");

    if(data.total_results == 0){
        jQuery("#" + data.selector + ' .chart-wrapper').append('<p>No data found.</p>');
    }else {	
        jQuery("#" + data.selector + ' p').remove();
        jQuery("#" + data.selector + ' .chart-wrapper p').remove();
        let labels = [];
        let legends = [];
        let totals = [];
        let colors = ["#2D8F00","#4B89AA","#76c893", "#546a79","#43c705","#1a759f", "#79bf3a","#34a0a4" ];
        let totalRecords = data.total_results;
        jQuery("#" + data.selector).append('<p style="text-align:center;">Total Responses: '+totalRecords+'</p>');
        jQuery.each(data.records, function (key, value){

            let percentage = Math.round( (data.records[key].total / totalRecords) * 100 );

            let label = concatMediumString(data.records[key].exit_reason) + ': ' +percentage +'% ('+data.records[key].total + ' Responses )' ;

            let legend = concatMediumString(data.records[key].exit_reason) + ' ( '+data.records[key].total + ' Responses )';

            totals.push(data.records[key].total);
            labels.push(label);
            legends.push(legend);
            colors.push(data.records[key].color);
        });

        generate_pie_chart( 'doughnut', 'sources-chart', data.selector, totals, labels, colors, legends );
    }
}

//Autocomplete for Single Post Graphs
function add_post_search() {
    jQuery(".post-search").autocomplete({
        source: function(request, response) {
            var data = {
                search : request.term
            };
            axios.post(wpApiSettings.root + 'api/v1/analytics/autocompletesearchposts', data, {
                headers: { 'X-WP-Nonce' : wpApiSettings.nonce }
            }).then(function(data){
                data = JSON.parse(JSON.stringify(data));
                response(data);
            }).catch(function(error){
                console.log(error);
            });
        },
        minLength: 2,
        select: function(event, ui) {
            event.preventDefault();
            jQuery(this).val(ui.item.label);
            let post_name = ui.item.label;
            let parent_card = jQuery(this).closest('.analytics-card');
            let function_name = parent_card.data("function");

            import_card_analytics_data_from_api(function_name, 0, 0, null, post_name );

        }
    });
};

jQuery(document).ready(function() {
    add_post_search();
});
function output_single_blog_post_visits(data){

    jQuery("canvas#single-blog-post-chart").remove();

    if (data.records.length === 0){
        //remove old charts and p tags
        jQuery('#single-blog-post-visits .wrapper p').remove();
        jQuery("canvas#single-blog-post-chart").remove();
        //remove search on new date selection
        jQuery('.post-search').val('');
        //append message
        jQuery('#single-blog-post-visits .wrapper').append('<p style="text-align:center;">Please enter a post title above.</p>')
    } else{
        //remove message
        jQuery('#single-blog-post-visits .wrapper p').remove();
        //destory old chart
        jQuery("canvas#single-blog-post-chart").remove();
        //append new chart
        jQuery('#single-blog-post-visits .wrapper').css('height','320px')
        jQuery('#' + data.selector + ' .wrapper').append('<canvas id="single-blog-post-chart" style="height:350px;"></canvas>');

        var page_reads = [];
        var page_views= [];
        var labels = [];


        jQuery.each(data.records, function(key,value){
            let label = data.records[key].created_at;
            let page_read = data.records[key].page_reads;
            let page_view = data.records[key].page_views;

            labels.push(label);
            page_reads.push(page_read);
            page_views.push(page_view);
        });

        //add dummy points to center single point data
        if (page_reads.length === 1){
            page_reads.unshift('0');
            page_reads.push('0');
        }
        
        if (page_views.length === 1){
            page_views.unshift('0');
            page_views.push('0');
        }

        if (labels.length === 1){
            labels.unshift(' ');
            labels.push(' ');	
        }

        var ctx = document.getElementById('single-blog-post-chart').getContext('2d');
            var myChart = new Chart(ctx, {
                type: 'line',
                data:{
                    labels : labels,
                    datasets: datasets =[{
                        label: 'Article Reads',
                        data: page_reads,
                        fill: false,
                        borderColor:"#4B89AA"
                        },
                    { 
                        label: 'Visits',
                        data: page_views,
                        fill: false,
                        borderColor:"#2D8F00"
                    }]
                },
                options: {
                tooltips: {
                    displayColors: true,
                    callbacks:{
                        mode: 'x',
                    },
                },
                scales: {
                    x: {
                    stacked: false,
                    offset: true
                    },
                    y: {
                    stacked: false,
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                responsive: true,
                maintainAspectRatio: false,
            }
        });
    }
}

function output_sessions_locations_data(data){
    jQuery("#" + data.selector + " .rows").html('');
    jQuery('#' + data.selector + ' .table-title').attr('title', data.description);
    if(data.total_records == 0) {
        jQuery("#" + data.selector + " .rows").append('<ul class="row"><li class="no-data">No data found.</li></ul>');
        hideTableUI( data.selector );
    } else {

        showTableUI( data.selector );

        initialize_table_structure( data.selector, data.description, data.offset, data.per_page, data.records, data.total_records, data.data_view);

        let sessionLocation = '';


        jQuery.each(data.records, function(key, value){

            let sesssionLocation = data.records[key].location;
            let sessionZip = data.records[key].zip_code;
            //jQuery("#" + data.selector+" .rows").append("<ul title='" +  sesssionLocation  + "' class='row' > <li colspan='2'>" + concatLongString( sesssionLocation ) + "</li><li>" + addCommas(data.records[key].sessions) + "</li><li>" + data.records[key].timezone +"</li></ul>")
            jQuery("#" + data.selector+" .rows").append(
                '       <div class="table-row">' +
                '           <div class="table-cell p-2 border-b border-slate-500 text-left w-4/6">' + concatLongString( sesssionLocation ) + '</div>' +
                '           <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(data.records[key].sessions) + '</div>' +
                '           <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + data.records[key].timezone + '</div>' +
                '       </div>'
            );
        });
    }
}
function output_user_locations_data(data){

    jQuery("#" + data.selector + " .rows").html('');
    jQuery('#' + data.selector + ' .table-title').attr('title', data.description);
    if(data.total_records == 0) {
        jQuery("#" + data.selector + " .rows").append('<ul class="row"><li class="no-data">No data found.</li></ul>');
        hideTableUI( data.selector );
    } else {

        showTableUI( data.selector );

        initialize_table_structure( data.selector, data.description, data.offset, data.per_page, data.records, data.total_records, data.data_view);

        let userLocation = '';


        jQuery.each(data.records, function(key, value){

            let userLocation = data.records[key].location;
            let userZip = data.records[key].zip_code;
            jQuery("#" + data.selector+" .rows").append("<ul title='" +  userLocation  + "' class='row' > <li colspan='2'>" + concatLongString( userLocation ) + "</li><li>" + addCommas(data.records[key].users) + "</li><li>" + concatLongString(data.records[key].timezone) +"</li></ul>")

        });
    }
}

function output_blog_category_data(data){
    jQuery("#" + data.selector + " .rows").html('');
    jQuery('#' + data.selector + ' .table-title').attr('title', data.description);
    if(data.total_records == 0) {
        jQuery("#" + data.selector + " .rows").append('<ul class="row"><li class="no-data">No data found.</li></ul>');
        hideTableUI( data.selector );
    } else {

        let iteration = 1;
        var titleQuery;
        
        showTableUI( data.selector );
        initialize_table_structure( data.selector, data.description, data.offset, data.per_page, data.records, data.total_records, data.data_view);
        
        jQuery("#" + data.selector + " .header li:first-child").text('Categories / Posts');

        //Parent
        jQuery.each(data.records, function(key, value) {
            classType = 'parent';
            
            let viewPercent = (value.totals.total_page_views / data.total_page_views) * 100;
            viewPercent = viewPercent.toFixed(2);
            
            let pageReadsPercent = (value.totals.total_page_reads / value.totals.total_page_views) * 100;
            pageReadsPercent = pageReadsPercent.toFixed(2)

            let uniqueVisitsPercent = (value.totals.total_unique_visitors / value.totals.total_page_views) * 100;
            uniqueVisitsPercent = uniqueVisitsPercent.toFixed(2)
            
            //jQuery("#" + data.selector + " .rows").append("<ul id ='" + value.totals.slug + "' title='" + key + "' data-id='" + iteration + "' class='entry-" + classType + " row' data-type='" + classType + "'><li data-type='title' class='toggle-list'><span>+</span>" + concatLongString(key) + "</li><li><span class='visitor-percentage stat-percent'>" + viewPercent + "%</span>" + addCommas(value.totals.total_page_views) + "</li><li><span class='unique-visitors-percentage stat-percent'>" + uniqueVisitsPercent + "%</span>" + addCommas(value.totals.total_unique_visitors) + "</li><li class='page-reads'><span class='page-reads-percentage stat-percent'>" + pageReadsPercent + "%</span>" + addCommas(value.totals.total_page_reads) + "</li><li class='claps'>" + addCommas(value.totals.total_claps) +"</li><div class='sublist'></div></ul>");
            jQuery("#" + data.selector + " .rows").append(
                '       <div class="table-row">' +
                '           <div class="table-cell p-2 border-b border-slate-500 text-left w-2/6">' + concatLongString(key) + '</div>' +
                '           <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(value.totals.total_page_views) + '</div>' +
                '           <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(value.totals.total_unique_visitors) + '</div>' +
                '           <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(value.totals.total_page_reads) + '</div>' +
                '           <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(value.totals.total_claps) + '</div>' +
                '       </div>'
            );

            
            //Child
            jQuery.each(value.posts, function(secondkey, secondvalue) {

                classType = 'child';
                if(secondkey !== 'totals'){
                    let childViewPercent = (secondvalue.page_views / data.total_visitors) * 100;
                    childViewPercent = childViewPercent.toFixed(2);
                    
                    let pageReadsPercent = (secondvalue.page_reads /data.total_page_views) * 100;
                    pageReadsPercent = pageReadsPercent.toFixed(2)
                    
                    let uniqueVisitsPercent = (secondvalue.unique_visitors / secondkey.total_page_views) * 100;
                    uniqueVisitsPercent = uniqueVisitsPercent.toFixed(2)
                
                    //jQuery("#" + value.totals.slug + " .sublist" ).append("<ul title='" + secondkey + "' data-related='" + iteration + "' class='entry-" + classType + " row' data-type='" + classType + "'> <li>" + concatLongString(secondkey) + "</li><li><span class='visitor-percentage stat-percent'>" + childViewPercent + "%</span>" + addCommas(secondvalue.page_views) + "</li><li><span class='unique-visitors-percentage stat-percent'>" + uniqueVisitsPercent + "%</span>" + addCommas(secondvalue.unique_visitors) + "</li><li class='page-reads'><span class='page-reads-percentage stat-percent'>" + pageReadsPercent + "%</span>" + addCommas(secondvalue.page_reads) + "</li><li class='claps'>"+ addCommas(secondvalue.claps) +"</li></ul>");
                    jQuery("#" + value.totals.slug + " .sublist" ).append(
                        '       <div class="table-row">' +
                        '           <div class="table-cell p-2 border-b border-slate-500 text-left w-2/6">' + concatLongString(secondkey) + '</div>' +
                        '           <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(secondvalue.page_views) + '</div>' +
                        '           <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(secondvalue.unique_visitors) + '</div>' +
                        '           <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(secondvalue.page_reads) + '</div>' +
                        '           <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(secondvalue.claps) + '</div>' +
                        '       </div>'
                    );
                }
            });
            iteration++;
        });
    }
}

function output_track_clicks_data(data){
    jQuery("#" + data.selector + " .rows").html('');
    jQuery('#' + data.selector + ' .table-title').attr('title', data.description);

    if(data.total_records == 0) {
        jQuery("#" + data.selector + " .rows").append('<ul class="row"><li class="no-data">No data found.</li></ul>');
        hideTableUI( data.selector );
    } else {

        showTableUI( data.selector );
        initialize_table_structure( data.selector, data.description, data.offset, data.per_page, data.records, data.total_records, data.data_view );

        jQuery.each(data.records, function(key, value) {
            var page_title = data.records[key].page_title
            if ( page_title == '' )
                page_title = 'Home';
            var page_url = data.records[key].page_url;
            if ( page_url == '' )
                page_url = aa_admin.url;

            let icon = get_svg_icon(data.records[key].click_type);

            //jQuery("#" + data.selector + " .rows").append("<ul class='entry-" + classType + " row' data-type='" + classType + "'> <li colspan='2'>" + key + "</li><li><span class='visitor-percentage stat-percent'>" + viewPercent + "%</span>" + addCommas(data.records[key].visitors) + "</li><li><span class='unique-visitors-percentage stat-percent'>" + uniqueVisitsPercent + "%</span>" + addCommas(data.records[key].unique_visitors) + "</li><li>" + addCommas(data.records[key].actions) +"</li></ul>");
            jQuery("#" + data.selector + " .rows").append(
                '   <div class="table-row">' +
                '       <div class="table-cell p-2 border-b border-slate-500 text-left w-2/6">' + page_title + '</div>' +
                '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(data.records[key].page_url) + '</div>' +
                '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + data.records[key].link_content + '</div>' +
                '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + icon + '</div>' +
                '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(data.records[key].clicks) + '</div>' +
                '   </div>'
            );
        });
    }
        
}

//-------------------------------------HELPER FUNCTIONS-------------------------------------
function generate_pie_chart( chartType, id, selector, data, labels, colors, legend ) {
    jQuery("canvas#" + id).remove();
    jQuery('#' + selector + ' .chart-wrapper').append('<canvas style="width: 300px; height: 300px; " id="' + id +'"></canvas>');
    chartData = {
        labels: labels,
        datasets: [{
            data: data,
            backgroundColor: colors,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero:true
                        }
                    }]
                }
            }
        }],
    };

    var ctx = document.getElementById(id).getContext('2d');
    var engineChart = new Chart(ctx, {
            type: chartType,
            data: chartData,
            options: {
                responsive: true,
                legend: {
                    labels: {
                        display: true,
                        generateLabels: function(chart) {
                            var data = chart.data;
                            if (data.labels.length && data.datasets.length) {
                                return data.labels.map(function(label, i) {
                                    return {
                                        text: legend[i],
                                        fillStyle: data.datasets[0].backgroundColor[i],
                                        strokeStyle: data.datasets[0].backgroundColor[i],
                                        index: i,
                                    };
                                });
                                }
                                return [];
                            }
                        }
                },
                tooltips: {
                    enabled: true,
                    mode: 'label',
                    callbacks: {
                        label: function(tooltipItem, data) {
                            var indice = tooltipItem.index;
                            return data.labels[indice];
                        }
                    }
                },
            }
        });
}
function initialize_table_structure( selector, description, offset, per_page, records, total_records, view ){
    jQuery('#' + selector + ' .prev-btn').data('offset', offset - 1);
    jQuery('#' + selector + ' .next-btn').data('offset', offset + 1);
    jQuery('#' + selector + ' .prev-btn').attr('data-offset', offset - 1);
    jQuery('#' + selector + ' .next-btn').attr('data-offset', offset + 1);
    jQuery('#' + selector + ' .current-result-view').data('offset', offset);
    jQuery('#' + selector + ' .current-result-view').text((offset * per_page + 1) + " - " + ((offset * per_page) + Object.keys(records).length));
    jQuery('#' + selector + ' .total-result-view').text(total_records);
    jQuery('#' + selector + ' .info').attr('title', description);

    let tableTotal = jQuery('#' + selector + ' .current-result-view').text().split("-").pop();

    if( total_records > 10 && offset == 0 ){
        jQuery('#' + selector + ' .table-per-page').removeClass('disabled');
        jQuery('#' + selector + ' .last-btn').removeClass('disabled');
        jQuery('#' + selector + ' .next-btn').removeClass('disabled');
        jQuery('#' + selector + ' .prev-btn').addClass('disabled');
        jQuery('#' + selector + ' .first-btn').addClass('disabled');
    } else if( total_records <= 10 && offset == 0 ){
        jQuery('#' + selector + ' .table-per-page').addClass('disabled');
        jQuery('#' + selector + ' .last-btn').addClass('disabled');
        jQuery('#' + selector + ' .next-btn').addClass('disabled');
        jQuery('#' + selector + ' .prev-btn').addClass('disabled');
        jQuery('#' + selector + ' .first-btn').addClass('disabled');
    } else if( total_records > 10 && offset > 0 ){
        jQuery('#' + selector + ' .table-per-page').removeClass('disabled');
        jQuery('#' + selector + ' .last-btn').removeClass('disabled');
        jQuery('#' + selector + ' .next-btn').removeClass('disabled');
        jQuery('#' + selector + ' .prev-btn').removeClass('disabled');
        jQuery('#' + selector + ' .first-btn').removeClass('disabled');
    }

    if( offset >= 1 && parseInt(tableTotal) == total_records ){
        jQuery('#' + selector + ' .last-btn').addClass('disabled');
        jQuery('#' + selector + ' .next-btn').addClass('disabled');
    }


    //Check per_page
    if( per_page > 10 ){
        jQuery('#' + selector).addClass('overflow-active');
    } else {
        jQuery('#' + selector).removeClass('overflow-active');
    }
    //Check data_view && per_page
    if( view == 'paths' ){
        jQuery('#' + selector).addClass('overflow-active');
    } else if( view == 'title' && per_page == 10 ) {
        jQuery('#' + selector).removeClass('overflow-active');
    }
    detectTableWidth( total_records );
    jQuery(window).on('resize', function(){
        detectTableWidth( total_records );
    })
}

function showTableUI(selector){
    jQuery('#' + selector + ' .view-list').css('display', 'flex');
    jQuery('#' + selector + ' .table-per-page').css('display', 'block');
    jQuery('#' + selector + ' .table-footer').css('display', 'flex');
}
function hideTableUI(selector){
    jQuery('#' + selector + ' .view-list').css('display', 'none');
    jQuery('#' + selector + ' .table-per-page').css('display', 'none');
    jQuery('#' + selector + ' .table-footer').css('display', 'none');
}
function addCommas(nStr){
    nStr += '';
    var x = nStr.split('.');
    var x1 = x[0];
    var x2 = x.length > 1 ? '.' + x[1] : '';
    var rgx = /(\d+)(\d{3})/;
    while (rgx.test(x1)) {
    x1 = x1.replace(rgx, '$1' + ',' + '$2');
    }
    return x1 + x2;
}

//-------------------------------------CLICK/CHANGE FUNCTIONS-------------------------------------
jQuery(document).on( 'change', '.table-per-page:not(.disabled)', function() {
    let parent_card = jQuery(this).closest('.analytics-card');
    parent_card.find('tbody tr').html('');
    let function_name = parent_card.data("function");
    parent_card.removeClass('loaded');
    let per_page = jQuery(this).val();
    let dataView = parent_card.find('.view-list li.active').data('view');
    import_card_analytics_data_from_api( function_name, per_page, 0, dataView);

});

jQuery(document).on( 'click', '.paginate-btn:not(.disabled)', function() {
    let parent_card = jQuery(this).closest('.analytics-card');
    parent_card.find('.rows').html('');
    let function_name = parent_card.data("function");
    parent_card.removeClass('loaded');
    let per_page = parent_card.find('.table-per-page').val();
    let offset = jQuery(this).data('offset');
    let dataView = parent_card.find('.view-list li.active').data('view');

    if( jQuery(this).hasClass('first-btn') ){
        offset = 0;
    }

    if( jQuery(this).hasClass('last-btn') ) {
        let results = jQuery(this).siblings('.table-footer-results');
        results = results.children('.total-result-view').text();

        offset = Math.floor( (parseInt(results) / per_page) );
    }

    import_card_analytics_data_from_api( function_name, per_page, offset, dataView );
});

//Table Column hover function(s)
jQuery(document).on( 'mouseenter', '#analytics-page .table-wrapper .rows .row li', function(){
    let parent = jQuery(this).closest('.table-wrapper').attr('id');
    let children = jQuery(this).children('span');

    if( children.hasClass('bounce-percentage') ) {
        jQuery('#' + parent + ' .bounce-percentage').addClass('active');
    } else if( children.hasClass('visitor-percentage') ){
        jQuery('#' + parent + ' .visitor-percentage').addClass('active');
    }else if( children.hasClass('unique-visitors-percentage') ){
        jQuery('#' + parent + ' .unique-visitors-percentage').addClass('active');
    } else if( children.hasClass('page-reads-percentage') ){
        jQuery('#' + parent + ' .page-reads-percentage').addClass('active');
    }
});
jQuery(document).on( 'mouseleave', '#analytics-page .table-wrapper .rows .row li', function(){
    jQuery('.table-wrapper .rows .row li span').removeClass('active');
});

function detectTableWidth( results ){
    let tableWidth = jQuery('.table-wrapper').width();
    if( tableWidth < 665 && results >= 1 ) {
        jQuery('.table-wrapper').addClass('overflow2');
    } else {
        jQuery('.table-wrapper').removeClass('overflow2');
    }
}

function import_card_analytics_data_from_api( function_name, per_page, offset, dataView, post_name ) {

    var dateData = jQuery('#reportrange').data('daterangepicker');
    var data = {
        function_name : function_name,
        start_date: dateData.startDate.format('MMMM D, YYYY'),
        end_date: dateData.endDate.format('MMMM D, YYYY'),
        per_page: per_page,
        offset: offset,
        data_view: dataView,
        post_name : post_name,
    }

    axios.post(wpApiSettings.root + 'api/v1/analytics/loaddata', data, {
        headers: { 'X-WP-Nonce' : wpApiSettings.nonce }
    }).then(function(data){
        var jsonParse;
        try {
            jsonParse = JSON.parse( JSON.stringify( data ) );
        }
        catch (e) {
            console.log("Load all data error for " + function_name + " : "+e);
        };
        let jsonResult = jsonParse.result;
        pass_api_data_to_function( function_name, jsonResult );
        jQuery("#" + function_name).addClass("loaded");
    }).catch(function(error){
        console.log(error);
    });


}
function concatMediumString( string ){
        let shortString = string;
        if( string && string.length > 25 ) {
            shortString = string.substr(0,25) + '...';
        } else {
            shortString = string;
        }
    return shortString;
}
function concatLongString( string ){
        let shortString = string;
        if( string && string.length > 50 ) {
            shortString = string.substr(0,50) + '...';
        } else {
            shortString = string;
        }
    return shortString;
}

function output_analytics_blogs_visits_table(data) {
    
    jQuery("#" + data.selector + " .rows").html('');
    jQuery('#' + data.selector + ' .table-title').attr('title', data.description);

    if(data.total_records == 0) {
        jQuery("#" + data.selector + " .rows").append('<ul class="row"><li class="no-data">No data found.</li></ul>');
        hideTableUI( data.selector );
    } else {
        let iteration = 1;
        var classType = 'parent';
        var titleQuery;
        
        showTableUI( data.selector );
        initialize_table_structure( data.selector, data.description, data.offset, data.per_page, data.records, data.total_records, data.data_view);
        if( data.data_view == 'paths' ) {
            jQuery("#" + data.selector + " .header li:first-child").text('Paths / Queries');
            jQuery.each(data.records, function(key, value) {
                
                //Parent
                classType = 'parent';

                let bouncePercent = (data.records[key].bounces / data.records[key].total_page_views) * 100;
                bouncePercent = bouncePercent.toFixed(2);
                
                let viewPercent = (data.records[key].total_page_views / data.total_visitors) * 100;
                viewPercent = viewPercent.toFixed(2);
                
                let pageReadsPercent = (data.records[key].total_page_reads / data.records[key].total_page_views) * 100;
                pageReadsPercent = pageReadsPercent.toFixed(2)

                let uniqueVisitsPercent = (data.records[key].total_unique_visitors / data.records[key].total_page_views) * 100;
                uniqueVisitsPercent = uniqueVisitsPercent.toFixed(2)
                
                var parent_path = data.records[key].path;
                if (parent_path === "/"){
                    parent_path = "home";
                }
                
                //jQuery("#" + data.selector + " .rows").append("<ul id ='" + data.records[key].path + "' title='" + data.records[key].path + "' data-id='" + iteration + "' class='entry-" + classType + " row' data-type='" + classType + "'><li data-type='title' class='toggle-list'><span>+</span>" + concatLongString(data.records[key].path) + "</li><li><span class='visitor-percentage stat-percent'>" + viewPercent + "%</span>" + addCommas(data.records[key].total_page_views) + "</li><li><span class='unique-visitors-percentage stat-percent'>" + uniqueVisitsPercent + "%</span>" + addCommas(data.records[key].total_unique_visitors) + "</li><li class='page-reads'><span class='page-reads-percentage stat-percent'>" + pageReadsPercent + "%</span>" + addCommas(data.records[key].total_page_reads) + "</li><li class='bounces'><span class='bounce-percentage stat-percent'>" + bouncePercent + "%</span>" + addCommas(data.records[key].bounces) +"</li><li class='claps'>" + addCommas(data.records[key].claps) +"</li><div class='sublist'></div></ul>");
                jQuery("#" + data.selector + " .rows").append(
                '   <div class="table-row">' +
                '       <div class="table-cell p-2 border-b border-slate-500 text-left w-1/6">' + concatLongString(data.records[key].path) + '</div>' +
                '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(data.records[key].total_page_views) + '</div>' +
                '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(data.records[key].total_unique_visitors) + '</div>' +
                '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(data.records[key].total_page_reads) + '</div>' +
                '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(data.records[key].bounces) + '</div>' +
                '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(data.records[key].claps) + '</div>' +
                '   </div>'
                );

                classType = 'child';

                //Child
                jQuery.each(data.records[key].query, function(secondkey, value) {
                    titleQuery = data.records[key].query[secondkey].query;
                    
                    if (titleQuery === undefined || titleQuery === null){
                        titleQuery = "/";
                    }
                    let childBouncePercent = (data.records[key].query[secondkey].bounces / data.records[key].query[secondkey].
                    total_page_views) * 100;
                    childBouncePercent = childBouncePercent.toFixed(2);
                    
                    let childViewPercent = (data.records[key].query[secondkey].total_page_views / data.total_visitors) * 100;
                    childViewPercent = childViewPercent.toFixed(2);
                    
                    let pageReadsPercent = (data.records[key].total_page_reads /data.records[key].total_page_views) * 100;
                    pageReadsPercent = pageReadsPercent.toFixed(2)
                    
                    let uniqueVisitsPercent = (data.records[key].query[secondkey].total_unique_visitors / data.records[key].query[secondkey].total_page_views) * 100;
                    uniqueVisitsPercent = uniqueVisitsPercent.toFixed(2)
                    
                    var parent_path = data.records[key].path;
                    
                    if (parent_path === undefined || parent_path === null || parent_path === "http:"){
                        parent_path = "home";
                    }
                    
                    if (parent_path.indexOf('/') > -1) {
                        var fields = parent_path.split('/');
                        var parent_path = fields[0];
                        if(parent_path == ""){
                            parent_path = "home"
                        }
                        
                    }
                    
                    //jQuery("#" + parent_path + " .sublist" ).append("<ul title='" + titleQuery + "' data-related='" + iteration + "' class='entry-" + classType + " row' data-type='" + classType + "'> <li>" + concatLongString(titleQuery) + "</li><li><span class='visitor-percentage stat-percent'>" + childViewPercent + "%</span>" + addCommas(data.records[key].query[secondkey].total_page_views) + "</li><li><span class='unique-visitors-percentage stat-percent'>" + uniqueVisitsPercent + "%</span>" + addCommas(data.records[key].query[secondkey].total_unique_visitors) + "</li><li class='page-reads'><span class='page-reads-percentage stat-percent'>" + pageReadsPercent + "%</span>" + addCommas(data.records[key].query[secondkey].total_page_reads) + "</li><li class='bounces'><span class='bounce-percentage stat-percent'>" + childBouncePercent + "%</span>" + addCommas(data.records[key].query[secondkey].bounces) +"</li><li class='bounces'>"+ addCommas(data.records[key].query[secondkey].claps) +"</li></ul>");
                    jQuery("#" + parent_path + " .sublist" ).append(
                        '   <div class="table-row">' +
                        '       <div class="table-cell p-2 border-b border-slate-500 text-left w-1/6">' + concatLongString(titleQuery) + '</div>' +
                        '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(data.records[key].query[secondkey].total_page_views) + '</div>' +
                        '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(data.records[key].query[secondkey].total_unique_visitors) + '</div>' +
                        '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(data.records[key].query[secondkey].total_page_reads) + '</div>' +
                        '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(data.records[key].query[secondkey].bounces) + '</div>' +
                        '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(data.records[key].query[secondkey].claps) + '</div>' +
                        '   </div>'
                    );
                });
                iteration++;
            });
        } else {
            jQuery.each(data.records, function(key, value) {

                jQuery("#" + data.selector + " .header li:first-child").text('Titles');
                titleQuery = data.records[key].title;

                let bouncePercent = (data.records[key].bounces / data.records[key].total_page_views) * 100;
                bouncePercent = bouncePercent.toFixed(2);

                let viewPercent = (data.records[key].total_page_views / data.total_visitors) * 100;
                viewPercent = viewPercent.toFixed(2)
                
                let pageReadsPercent = (data.records[key].total_page_reads / data.records[key].total_page_views) * 100;
                pageReadsPercent = pageReadsPercent.toFixed(2)
                
                let uniqueVisitsPercent = (data.records[key].total_unique_visitors / data.records[key].total_page_views) * 100;
                uniqueVisitsPercent = uniqueVisitsPercent.toFixed(2)

                //jQuery("#" + data.selector + " .rows").append("<ul title='" + titleQuery + "' class='entry-" + classType + " row' data-type='" + classType + "'> <li colspan='2'>" + concatLongString(titleQuery) + "</li><li><span class='visitor-percentage stat-percent'>" + viewPercent + "%</span>" + addCommas(data.records[key].total_page_views) + "</li><li><span class='unique-visitors-percentage stat-percent'>" + uniqueVisitsPercent + "%</span>" + addCommas(data.records[key].total_unique_visitors) + "</li><li class='page-reads'><span class='page-reads-percentage stat-percent'>" + pageReadsPercent + "%</span>" + addCommas(data.records[key].total_page_reads) + "</li><li class='bounces'><span class='bounce-percentage stat-percent'>" + bouncePercent + "%</span>" + addCommas(data.records[key].bounces) +"</li><li class='claps'>" + addCommas(data.records[key].claps) +"</li></ul>");
                jQuery("#" + data.selector + " .rows").append(
                    '   <div class="table-row">' +
                    '       <div class="table-cell p-2 border-b border-slate-500 text-left w-1/6">' + concatLongString(titleQuery) + '</div>' +
                    '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(data.records[key].total_page_views) + '</div>' +
                    '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(data.records[key].total_unique_visitors) + '</div>' +
                    '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(data.records[key].total_page_reads) + '</div>' +
                    '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(data.records[key].bounces) + '</div>' +
                    '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(data.records[key].claps) + '</div>' +
                    '   </div>'
                );

            });

        }
    }
}

//Switch View(s) of Table
jQuery(document).on( 'click', '.view-list li', function(e){
        let parent_card = jQuery(this).closest('.analytics-card');
        parent_card.find('.rows').html('');
        parent_card.find('.view-list li').removeClass('active');
        jQuery(this).addClass('active');

        let function_name = parent_card.data("function");
        parent_card.removeClass('loaded');
        let per_page = parent_card.find('.table-per-page').val();
        let dataView = jQuery(this).data('view');
        import_card_analytics_data_from_api( function_name, per_page, 0, dataView );
    } );

    //Expand nested Parent/Child rows
    jQuery(document).on( 'click', '#analytics-page .toggle-list', function(){
        let parent_card = jQuery(this).closest('.analytics-card').attr('id');
        let buttonText = '+';
        let parentID = jQuery(this).closest('[data-id]').data('id');

        let tableWidth = jQuery('#' + parent_card).width();

        jQuery(this).toggleClass('expanded');
        if( jQuery(this).hasClass('expanded') ) {
            buttonText = '-';
            jQuery('#' + parent_card).css('max-width', tableWidth + 'px');
        } else {
            jQuery('#' + parent_card).css('max-width', '100%');
        }
        jQuery(this).find('span').html(buttonText);
        jQuery(this).siblings('.sublist').toggle();
    });

        jQuery('.bar-chart-wrapper').css('display', 'none');
        jQuery(document).ready( function(){
            jQuery('.bar-chart-wrapper').css('display', 'none');
        });	

function import_analytics_data_from_api_detail( start_date, end_date, function_name ) {
    
    var offset = 0;
    var iteration = jQuery('#' + function_name + '-detail').data('iteration');
    if ( iteration != 0 && iteration != undefined) {
        offset = 200 * iteration;
        console.log('There\'s been an iteration offset is ' + offset);
    }
    
    var data = {
        function_name : function_name,
        start_date: start_date,
        end_date: end_date,
        offset: offset,
        per_page: 200,
    };

    axios.post(wpApiSettings.root + 'api/v1/analytics/loaddata', data, {
        headers: { 'X-WP-Nonce' : wpApiSettings.nonce }
    }).then(function(data) {
        var jsonParse;
        try {
            if('undefined' === typeof data || data.length == 0) {
                return;
            }
            jsonParse = JSON.parse( JSON.stringify(data.data));
        }
        catch (e) {
            console.log("Load all data error for " + function_name + " : "+e);
        };
        if('undefined' === typeof jsonParse || jsonParse.length == 0) {
            return;
        }
        let jsonResult = jsonParse.result;
        //console.log(jsonResult);

        //pass_api_data_to_function( function_name, jsonResult );
        switch( function_name ) {
            case 'page-visits':
                var count = Object.keys(jsonResult.records).length;
                jQuery.each(jsonResult.records, function(key, value) {

                    jQuery("#" + jsonResult.selector + " .header li:first-child").text('Titles');
                    titleQuery = jsonResult.records[key].title;
                    if( titleQuery === '' || titleQuery === undefined ) {
                        titleQuery = 'Home';
                    }

                    let bouncePercent = (jsonResult.records[key].bounces / jsonResult.records[key].total_page_views) * 100;
                    bouncePercent = bouncePercent.toFixed(2);

                    let viewPercent = (jsonResult.records[key].total_page_views / jsonResult.total_visitors) * 100;
                    viewPercent = viewPercent.toFixed(2)
                    
                    let pageReadsPercent = (jsonResult.records[key].total_page_reads / jsonResult.records[key].total_page_views) * 100;
                    pageReadsPercent = pageReadsPercent.toFixed(2)
                    
                    let uniqueVisitsPercent = (jsonResult.records[key].total_unique_visitors / jsonResult.records[key].total_page_views) * 100;
                    uniqueVisitsPercent = uniqueVisitsPercent.toFixed(2)

                    //jQuery("#" + data.selector + " .rows").append("<ul title='" + titleQuery + "' class='entry-" + classType + " row' data-type='" + classType + "'> <li>" + concatLongString(titleQuery) + "</li><li><span class='visitor-percentage stat-percent'>" + viewPercent + "%</span>" + addCommas(data.records[key].total_page_views) + "</li><li><span class='unique-visitors-percentage stat-percent'>" + uniqueVisitsPercent + "%</span>" + addCommas(data.records[key].total_unique_visitors) + "</li><li class='page-reads'><span class='page-reads-percentage stat-percent'>" + pageReadsPercent + "%</span>" + addCommas(data.records[key].total_page_reads) + "</li><li class='bounces'><span class='bounce-percentage stat-percent'>" + bouncePercent + "%</span>" + addCommas(data.records[key].bounces) +"</li><li class='claps'>" + addCommas(data.records[key].total_claps) +"</li></ul>");
                    jQuery("#" + jsonResult.selector + " .detailrows").append(
                        //"<ul title='" + titleQuery + "' class='entry-" + classType + " row' data-type='" + classType + "'> <li>" + concatLongString(titleQuery) + "</li><li><span class='visitor-percentage stat-percent'>" + viewPercent + "%</span>" + addCommas(data.records[key].total_page_views) + "</li><li><span class='unique-visitors-percentage stat-percent'>" + uniqueVisitsPercent + "%</span>" + addCommas(data.records[key].total_unique_visitors) + "</li><li class='page-reads'><span class='page-reads-percentage stat-percent'>" + pageReadsPercent + "%</span>" + addCommas(data.records[key].total_page_reads) + "</li><li class='bounces'><span class='bounce-percentage stat-percent'>" + bouncePercent + "%</span>" + addCommas(data.records[key].bounces) +"</li><li class='claps'>" + addCommas(data.records[key].total_claps) +"</li></ul>"
                        '       <div class="table-row">' +
                        '           <div class="table-cell p-2 border-b border-slate-500 text-left w-2/6">' + concatLongString(titleQuery) + '</div>' +
                        '           <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(jsonResult.records[key].total_page_views) + '</div>' +
                        '           <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(jsonResult.records[key].total_unique_visitors) + '</div>' +
                        '           <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(jsonResult.records[key].total_page_reads) + '</div>' +
                        '           <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(jsonResult.records[key].bounces) + '</div>' +
                        '       </div>'
                        );

                });
                if ( count < 200 ) {
                    jQuery('#page-visits-loadmore').html('');
                    jQuery('#page-visits-loadmore').append('No more records')
                    jQuery('#page-visits-loadmore').attr('disabled', 'disabled');
                    jQuery('#page-visits-loadmore').removeClass('hover:bg-blue-800');
                }
                break;
            case 'entry-pages':
            case 'exit-pages':
                var count = Object.keys(jsonResult.records).length;
                jQuery.each(jsonResult.records, function(key, value) {

                    titleQuery = jsonResult.records[key].title;
                    if( titleQuery === '' || titleQuery === undefined ) {
                        titleQuery = 'Home';
                    }
    
                    let bouncePercent = (jsonResult.records[key].bounces / jsonResult.records[key].total_visitors) * 100;
    
                    bouncePercent = bouncePercent.toFixed(2);
    
                    let viewPercent = (jsonResult.records[key].total_visitors / jsonResult.total_visitors) * 100;
    
                    viewPercent = viewPercent.toFixed(2);
    
                    //jQuery("#" + data.selector + " .rows").append("<ul title='" + titleQuery + "' class='entry-" + classType + " row' data-type='" + classType + "'> <li colspan='2'>" + concatLongString(titleQuery) + "</li><li><span class='visitor-percentage stat-percent'>" + viewPercent + "%</span>" + addCommas(data.records[key].total_visitors) + "</li><li class='bounces'><span class='bounce-percentage stat-percent'>" + bouncePercent + "%</span>" + addCommas(data.records[key].bounces) +"</li></ul>");
                    jQuery("#" + jsonResult.selector + " .detailrows").append(
                        '       <div class="table-row">' +
                        '           <div class="table-cell p-2 border-b border-slate-500 text-left w-4/6">' + concatLongString(titleQuery) + '</div>' +
                        '           <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(jsonResult.records[key].total_visitors) + '</div>' +
                        '           <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(jsonResult.records[key].bounces) + '</div>' +
                        '       </div>'
                    );
    
                });
                if ( count < 200 ) {
                    jQuery('#' + function_name + '-loadmore').html('');
                    jQuery('#' + function_name + '-loadmore').append('No more records')
                    jQuery('#' + function_name + '-loadmore').attr('disabled', 'disabled');
                    jQuery('#' + function_name + '-loadmore').removeClass('hover:bg-blue-800');
                }
                break;
            case 'referrers':
                var count = Object.keys(jsonResult.records).length;
                jQuery.each(jsonResult.records, function(key, value) {

                    //let viewPercent = Math.round( (data.records[key].visitors / data.total_visitors) * 100 );
                    let viewCalc = (jsonResult.records[key].visitors / jsonResult.total_visitors) * 100;
                    let viewPercent = viewCalc.toFixed(2);
                    
                    let uniqueVisitsPercent = (jsonResult.records[key].unique_visitors / jsonResult.records[key].visitors) * 100;
                    uniqueVisitsPercent = uniqueVisitsPercent.toFixed(2)
        
                    //Icons
                    var icon = get_svg_icon(key);
                    //jQuery("#" + data.selector + " .rows").append("<ul class='entry-" + classType + " row' data-type='" + classType + "'> <li colspan='2'>" + key + "</li><li><span class='visitor-percentage stat-percent'>" + viewPercent + "%</span>" + addCommas(data.records[key].visitors) + "</li><li><span class='unique-visitors-percentage stat-percent'>" + uniqueVisitsPercent + "%</span>" + addCommas(data.records[key].unique_visitors) + "</li><li>" + addCommas(data.records[key].actions) +"</li></ul>");
                    jQuery("#" + jsonResult.selector + " .detailrows").append(
                        '   <div class="table-row">' +
                        '       <div class="table-cell p-2 border-b border-slate-500 text-left w-3/6">' + icon + key + '</div>' +
                        '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(jsonResult.records[key].visitors) + '</div>' +
                        '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(jsonResult.records[key].unique_visitors) + '</div>' +
                        '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(jsonResult.records[key].actions) + '</div>' +
                        '   </div>'
                    );
                });
                if ( count < 200 ) {
                    jQuery('#referrers-loadmore').html('');
                    jQuery('#referrers-loadmore').append('No more records')
                    jQuery('#referrers-loadmore').attr('disabled', 'disabled');
                    jQuery('#referrers-loadmore').removeClass('hover:bg-blue-800');
                }
                break;
            case 'sessions-locations':
                var count = Object.keys(jsonResult.records).length;
                jQuery.each(jsonResult.records, function(key, value){
                    let sesssionLocation = jsonResult.records[key].location;
                    let sessionZip = jsonResult.records[key].zip_code;
                    //jQuery("#" + data.selector+" .rows").append("<ul title='" +  sesssionLocation  + "' class='row' > <li colspan='2'>" + concatLongString( sesssionLocation ) + "</li><li>" + addCommas(data.records[key].sessions) + "</li><li>" + data.records[key].timezone +"</li></ul>")
                    jQuery("#" + jsonResult.selector+" .detailrows").append(
                        '       <div class="table-row">' +
                        '           <div class="table-cell p-2 border-b border-slate-500 text-left w-4/6">' + concatLongString( sesssionLocation ) + '</div>' +
                        '           <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(jsonResult.records[key].sessions) + '</div>' +
                        '           <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + jsonResult.records[key].timezone + '</div>' +
                        '       </div>'
                    );
                });
                if ( count < 200 ) {
                    jQuery('#sessions-locations-loadmore').html('');
                    jQuery('#sessions-locations-loadmore').append('No more records')
                    jQuery('#sessions-locations-loadmore').attr('disabled', 'disabled');
                    jQuery('#sessions-locations-loadmore').removeClass('hover:bg-blue-800');
                }
                break;
            case 'track-clicks':
                //console.log(jsonResult);
                var count = Object.keys(jsonResult.records).length;
                jQuery.each(jsonResult.records, function(key, value) {
                    var page_title = jsonResult.records[key].page_title
                    if ( page_title == '' )
                        page_title = 'Home';
                    var page_url = jsonResult.records[key].page_url;
                    if ( page_url == '' )
                        page_url = aa_admin.url;
        
                    let icon = get_svg_icon(jsonResult.records[key].click_type);
        
                    //jQuery("#" + data.selector + " .rows").append("<ul class='entry-" + classType + " row' data-type='" + classType + "'> <li colspan='2'>" + key + "</li><li><span class='visitor-percentage stat-percent'>" + viewPercent + "%</span>" + addCommas(data.records[key].visitors) + "</li><li><span class='unique-visitors-percentage stat-percent'>" + uniqueVisitsPercent + "%</span>" + addCommas(data.records[key].unique_visitors) + "</li><li>" + addCommas(data.records[key].actions) +"</li></ul>");
                    jQuery("#" + jsonResult.selector + " .detailrows").append(
                        '   <div class="table-row">' +
                        '       <div class="table-cell p-2 border-b border-slate-500 text-left w-2/6">' + page_title + '</div>' +
                        '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(jsonResult.records[key].page_url) + '</div>' +
                        '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + jsonResult.records[key].link_content + '</div>' +
                        '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + icon + '</div>' +
                        '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(jsonResult.records[key].clicks) + '</div>' +
                        '   </div>'
                    );
                });
                if ( count < 200 ) {
                    jQuery('#track-clicks-loadmore').html('');
                    jQuery('#track-clicks-loadmore').append('No more records')
                    jQuery('#track-clicks-loadmore').attr('disabled', 'disabled');
                    jQuery('#track-clicks-loadmore').removeClass('hover:bg-blue-800');
                }
                break;
            case 'blog-visits':
                var count = Object.keys(jsonResult.records).length;
                jQuery.each(jsonResult.records, function(key, value) {

                    jQuery("#" + jsonResult.selector + " .header li:first-child").text('Titles');
                    titleQuery = jsonResult.records[key].title;
    
                    let bouncePercent = (jsonResult.records[key].bounces / jsonResult.records[key].total_page_views) * 100;
                    bouncePercent = bouncePercent.toFixed(2);
    
                    let viewPercent = (jsonResult.records[key].total_page_views / jsonResult.total_visitors) * 100;
                    viewPercent = viewPercent.toFixed(2)
                    
                    let pageReadsPercent = (jsonResult.records[key].total_page_reads / jsonResult.records[key].total_page_views) * 100;
                    pageReadsPercent = pageReadsPercent.toFixed(2)
                    
                    let uniqueVisitsPercent = (jsonResult.records[key].total_unique_visitors / jsonResult.records[key].total_page_views) * 100;
                    uniqueVisitsPercent = uniqueVisitsPercent.toFixed(2)

                    var title = concatLongString(titleQuery);
                    if(title == '')
                        title = 'Home';
    
                    //jQuery("#" + data.selector + " .rows").append("<ul title='" + titleQuery + "' class='entry-" + classType + " row' data-type='" + classType + "'> <li colspan='2'>" + concatLongString(titleQuery) + "</li><li><span class='visitor-percentage stat-percent'>" + viewPercent + "%</span>" + addCommas(data.records[key].total_page_views) + "</li><li><span class='unique-visitors-percentage stat-percent'>" + uniqueVisitsPercent + "%</span>" + addCommas(data.records[key].total_unique_visitors) + "</li><li class='page-reads'><span class='page-reads-percentage stat-percent'>" + pageReadsPercent + "%</span>" + addCommas(data.records[key].total_page_reads) + "</li><li class='bounces'><span class='bounce-percentage stat-percent'>" + bouncePercent + "%</span>" + addCommas(data.records[key].bounces) +"</li><li class='claps'>" + addCommas(data.records[key].claps) +"</li></ul>");
                    jQuery("#" + jsonResult.selector + " .detailrows").append(
                        '   <div class="table-row">' +
                        '       <div class="table-cell p-2 border-b border-slate-500 text-left w-1/6">' + title + '</div>' +
                        '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(jsonResult.records[key].total_page_views) + '</div>' +
                        '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(jsonResult.records[key].total_unique_visitors) + '</div>' +
                        '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(jsonResult.records[key].total_page_reads) + '</div>' +
                        '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(jsonResult.records[key].bounces) + '</div>' +
                        '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(jsonResult.records[key].claps) + '</div>' +
                        '   </div>'
                    );
    
                });
                if ( count < 200 ) {
                    jQuery('#blog-visits-loadmore').html('');
                    jQuery('#blog-visits-loadmore').append('No more records')
                    jQuery('#blog-visits-loadmore').attr('disabled', 'disabled');
                    jQuery('#blog-visits-loadmore').removeClass('hover:bg-blue-800');
                }
                break;
            case 'keywords':
                var count = Object.keys(jsonResult.records).length;
                jQuery.each(jsonResult.records, function(key, value) {
                    //jQuery("#" + data.selector + " .rows").append("<ul class='entry-" + classType + " row' data-type='" + classType + "'> <li colspan='2'>" + data.records[key].keywords + "</li><li>" + addCommas(data.records[key].visitors) + "</li></ul>");
                    jQuery("#" + jsonResult.selector + " .detailrows").append(
                        '   <div class="table-row">' +
                        '       <div class="table-cell p-2 border-b border-slate-500 text-left w-3/6">' + jsonResult.records[key].keywords + '</div>' +
                        '       <div class="table-cell p-2 border-b border-slate-500 text-right w-1/6">' + addCommas(jsonResult.records[key].visitors) + '</div>' +
                        '   </div>'
                    );
                });
                if ( count < 200 ) {
                    jQuery('#keywords-loadmore').html('');
                    jQuery('#keywords-loadmore').append('No more records')
                    jQuery('#keywords-loadmore').attr('disabled', 'disabled');
                    jQuery('#keywords-loadmore').removeClass('hover:bg-blue-800');
                }

        }
    }).catch(function(error){
        console.log(error);
    });
}

jQuery('#sessions-locations-loadmore').on('click', function(){
    var iteration = jQuery('#sessions-locations-detail').data('iteration');
    jQuery('#sessions-locations-detail').data('iteration', iteration + 1);
    var dateData = jQuery('#reportrange').data('daterangepicker');
    var start_date = dateData.startDate.format('MMMM D, YYYY');
    var end_date = dateData.endDate.format('MMMM D, YYYY');

    import_analytics_data_from_api_detail( start_date, end_date, 'sessions-locations' );
    
});

jQuery('#referrers-loadmore').on('click', function(){
    var iteration = jQuery('#referrers-detail').data('iteration');
    jQuery('#referrers-detail').data('iteration', iteration + 1);
    var dateData = jQuery('#reportrange').data('daterangepicker');
    var start_date = dateData.startDate.format('MMMM D, YYYY');
    var end_date = dateData.endDate.format('MMMM D, YYYY');

    import_analytics_data_from_api_detail( start_date, end_date, 'referrers' );
    
});

jQuery('#page-visits-loadmore').on('click', function(){
    var iteration = jQuery('#page-visits-detail').data('iteration');
    jQuery('#page-visits-detail').data('iteration', iteration + 1);
    var dateData = jQuery('#reportrange').data('daterangepicker');
    var start_date = dateData.startDate.format('MMMM D, YYYY');
    var end_date = dateData.endDate.format('MMMM D, YYYY');

    import_analytics_data_from_api_detail( start_date, end_date, 'page-visits' );
    
});

jQuery('#entry-pages-loadmore').on('click', function(){
    var iteration = jQuery('#entry-pages-detail').data('iteration');
    jQuery('#entry-pages-detail').data('iteration', iteration + 1);
    var dateData = jQuery('#reportrange').data('daterangepicker');
    var start_date = dateData.startDate.format('MMMM D, YYYY');
    var end_date = dateData.endDate.format('MMMM D, YYYY');

    import_analytics_data_from_api_detail( start_date, end_date, 'entry-pages' );
    
});

jQuery('#exit-pages-loadmore').on('click', function(){
    var iteration = jQuery('#exit-pages-detail').data('iteration');
    jQuery('#exit-pages-detail').data('iteration', iteration + 1);
    var dateData = jQuery('#reportrange').data('daterangepicker');
    var start_date = dateData.startDate.format('MMMM D, YYYY');
    var end_date = dateData.endDate.format('MMMM D, YYYY');

    import_analytics_data_from_api_detail( start_date, end_date, 'exit-pages' );
    
});

jQuery('#track-clicks-loadmore').on('click', function(){
    var iteration = jQuery('#track-clicks-detail').data('iteration');
    jQuery('#track-clicks-detail').data('iteration', iteration + 1);
    var dateData = jQuery('#reportrange').data('daterangepicker');
    var start_date = dateData.startDate.format('MMMM D, YYYY');
    var end_date = dateData.endDate.format('MMMM D, YYYY');

    import_analytics_data_from_api_detail( start_date, end_date, 'track-clicks' );
    
});

jQuery('#blog-visits-loadmore').on('click', function(){
    var iteration = jQuery('#blog-visits-detail').data('iteration');
    jQuery('#blog-visits-detail').data('iteration', iteration + 1);
    var dateData = jQuery('#reportrange').data('daterangepicker');
    var start_date = dateData.startDate.format('MMMM D, YYYY');
    var end_date = dateData.endDate.format('MMMM D, YYYY');

    import_analytics_data_from_api_detail( start_date, end_date, 'blog-visits' );
   
});

jQuery('#blog-category-visits-loadmore').on('click', function(){
    var iteration = jQuery('#blog-category-visits-detail').data('iteration');
    jQuery('#blog-category-visits-detail').data('iteration', iteration + 1);
    var dateData = jQuery('#reportrange').data('daterangepicker');
    var start_date = dateData.startDate.format('MMMM D, YYYY');
    var end_date = dateData.endDate.format('MMMM D, YYYY');

    import_analytics_data_from_api_detail( start_date, end_date, 'blog-category-visits' );
   
});

jQuery('#keywords-loadmore').on('click', function(){
    var iteration = jQuery('#keywords-detail').data('iteration');
    jQuery('#keywords-detail').data('iteration', iteration + 1);
    var dateData = jQuery('#reportrange').data('daterangepicker');
    var start_date = dateData.startDate.format('MMMM D, YYYY');
    var end_date = dateData.endDate.format('MMMM D, YYYY');

    import_analytics_data_from_api_detail( start_date, end_date, 'keywords' );
   
});


function get_svg_icon( referrer ) {
    var icon = '';
    ref2lc = referrer.toLowerCase();

    if ( ref2lc.includes( 'google' ) ) {
        icon = '<img class="icon default" src="' + aa_admin.icons_url + 'google.svg" />';
    } else if ( ref2lc.includes( 'yahoo' ) ) {
        icon = '<img class="icon default" src="' + aa_admin.icons_url + 'yahoo.svg" />';
    } else if ( ref2lc.includes( 'duckduckgo' ) ) {
        icon = '<img class="icon default" src="' + aa_admin.icons_url + 'duckduckgo.svg" />';
    } else if ( ref2lc.includes( 'facebook' ) ) {
        icon = '<img class="icon default" src="' + aa_admin.icons_url + 'facebook.svg" />';
    } else if ( ref2lc.includes( 'youtube' ) ) {
        icon = '<img class="icon default" src="' + aa_admin.icons_url + 'youtube.svg" />';
    } else if ( ref2lc.includes( 'android' ) ) {
        icon = '<img class="icon default" src="' + aa_admin.icons_url + 'android.svg" />';
    } else if ( ref2lc.includes( 'phone' ) ) {
        icon = '<img class="icon default" src="' + aa_admin.icons_url + 'phone.svg" />';
    } else if ( ref2lc.includes( 'email' ) ) {
        icon = '<img class="icon default" src="' + aa_admin.icons_url + 'email.svg" />';
    }
    else {
        icon = '<img class="icon default" src="' + aa_admin.icons_url + 'ae.svg" />';
    }
    return icon;
    
}