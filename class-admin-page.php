<?php
/**
 * Create the admin view for the data.
 */
namespace AwesomeAnalytics;

class AdminPage {
    /**
	 * Start performing actions.
	 *
	 * @return void
	 */
    function __construct() {

		add_action('admin_menu', array( $this, 'register_admin_page' ), 20 );
		//add_action('admin_head', array( $this, 'admin_page_css' ) );
		//add_action('admin_footer', array( $this, 'admin_page_js' ) );

	}

	/**
	 * Register the admin analytics page.
	 *
	 * @return void
	 */
	

	function register_admin_page() {

		$admin_page = add_menu_page( 'Analytics', 'Analytics', 'manage_options', 'analytics', array( $this, 'admin_page' ), 'dashicons-chart-bar', 40 );
		
		// load js files specific to the Awesome Analytics admin page
		add_action( 'load-toplevel_page_analytics', array( $this,'load_analytics_admin_scripts') );
	}
	
	function load_analytics_admin_scripts() {
        add_action( 'admin_enqueue_scripts', array( $this,'enqueue_analytics_admin_js') );
    }

	function enqueue_analytics_admin_js(){
		wp_enqueue_style( 'twcss', plugins_url('/dist/output.css', __FILE__) );
        wp_enqueue_script( 'chart-js', plugins_url('/js/chart.js',__FILE__ ) );
		wp_enqueue_script( 'chart-js-trendline', plugins_url('/js/chartjs-plugin-trendline.js',__FILE__ ) );
		wp_enqueue_script( 'moment-js',plugins_url('/js/moment.js',__FILE__ ) );
		wp_enqueue_script( 'date-range-picker-js', plugins_url('/js/daterangepicker.js',__FILE__ ) );
		wp_enqueue_style( 'date-range-picker-css', plugins_url('/css/daterangepicker.css',__FILE__ ) );
		wp_enqueue_script( 'jquery-ui-autocomplete' );
		wp_enqueue_script( 'sweetalert-js', plugins_url('/js/sweetalerts2.js',__FILE__ ) );
		wp_enqueue_script( 'axios', 'https://unpkg.com/axios/dist/axios.min.js' );
		wp_enqueue_script( 'flowbite', plugins_url('/node_modules/flowbite/dist/flowbite.js', __FILE__) );
		wp_enqueue_style( 'bootstrap-css', plugins_url('/css/bootstrap.min.css',__FILE__ ) );
		wp_enqueue_script( 'bootstrap-js', plugins_url('/js/bootstrap.bundle.min.js',__FILE__ ) );
		// Localize new scripts
		wp_register_script( 'aa-admin-js', plugins_url('/js/aa-admin-scripts.js', __FILE__), array( 'jquery','wp-api-request'), filemtime( plugin_dir_path(__FILE__) . 'js/aa-admin-scripts.js' ), true );
		$aa_object = array(
			'icons_url'   => plugins_url('/icons/', __FILE__),
			'info_svg'    => AdminPage::get_svg('info'),
			'bar_data_7'  => AdminPage::get_bar_data(7),
			'bar_data_30' => AdminPage::get_bar_data(30),
			'bar_data_60' => AdminPage::get_bar_data(60),
			'bar_data_90' => AdminPage::get_bar_data(90),
			'url'         => get_option('site_url'),
		);
		wp_localize_script( 'aa-admin-js', 'aa_admin', $aa_object);
		wp_enqueue_script('aa-admin-js');
		
    }
	
	/**
	 *
	 *
	 * @return void
	 */

	 public static function render_per_page() { ?>
		<select name="per_page" class="table-per-page">
			<option value="10">10</option>
			<option value="25">25</option>
			<option value="50">50</option>
		</select>
	<?php }

	public static function render_table_footer() { ?>
		<div class="table-footer" data-page-results="0">
			<span class="first-btn paginate-btn" data-offset="0"><< First Page</span> |
			<span class="prev-btn paginate-btn" data-offset="0">< Prev</span>

			<span class="table-footer-results">Showing: <span class="current-result-view" data-offset="0"></span> Out of <span class="total-result-view"></span></span>

			<span class="next-btn paginate-btn" data-offset="1">Next ></span> |
			<span class="last-btn paginate-btn" data-offset="-">Last Page >></span>
		</div>
	<?php }
	public static function render_bar_charts( $range, string $display='none' ) {
		?>
			<div class="bar-chart <?php echo $range ?>-day-span" style="display:<?php echo $display; ?>;">
				<canvas id="<?php echo $range ?>-day-chart"></canvas>
			</div>
		<?php
	}

	public static function get_bar_data( $range ){
		$today = date('Y-m-d', strtotime("today"));
		$data = json_encode(DataQuery::calculate_bar_charts( $today, $range ));
		return $data;
	}
	public static function get_svg( $name, string $class='default' ) {
		if( $class === 'down' ){
			$class = 'down';
		}
		return "<img class='icon " . $class . "' src='" . plugins_url( '',  __FILE__ ) . '/icons/' . $name . '.svg' . "' />";
	}

	function admin_page_css() {
        $clap_toggle_check = apply_filters('clap_toggle', true);
        if ($clap_toggle_check === false) { 
            ?>
            <style>
                .table-wrapper.four-col .row li.claps{
                    display: none;
                }
            </style>
            <?php
        }
	}
	/**
	 *
	 *
	 * @return void
	 */
	function admin_page() {

        echo $this->admin_page_css();
        
        
        ?>
	<div class="wrap">
		<h1 class="wp-heading-inline">Analytics</h1>
			<div id="analytics-page" class="is-loading">

				<div class="options-bar">
					<div id="reportrange" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; max-width: fit-content">
						<?php echo AdminPage::get_svg('calendar'); ?>&nbsp;
						<span></span><?php echo AdminPage::get_svg('chevron', 'down'); ?>
					</div>
					<ul class="options">
						<li class="active"><a href="#">Stats</a></li> |
						<li><a href="#">Settings</a></li>
					</ul>
				</div>
				<section id="top-bar" class="analytics-card" data-function="quick-stats">
					<span class="loader"></span>
				</section>

				<!-- This month statistics -->
				<div class="grid justify-items-end bg-qs-gray rounded-br-lg pb-3 pr-3"><button class="block text-black bg-gray-50 hover:bg-gray-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button" data-modal-toggle="tmStatistics">This month statistics</button></div>

				<div id="tmStatistics" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-9990 w-full md:inset-0 h-modal md:h-full z-9999">
					<div class="relative p-4  w-full max-w-6xl h-full md:h-auto rounded-lg">
						<!-- Modal content -->
						<div class="relative bg-qs-gray rounded-lg shadow dark:bg-gray-700 border-2 border-slate-50">
							<!-- Modal header -->
							<div class="flex justify-between items-start p-4 rounded-t">
								<h3 class="text-lg font-semibold text-slate-50 dark:text-white">
									This month Statistics
								</h3>
								<button type="button" class="text-gray-50 bg-transparent hover:bg-gray-200 hover:text-[#ff7c73] rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-toggle="tmStatistics">
									<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>  
								</button>
							</div>
							<!-- Modal body -->
							<div class="p-6 space-t-6">
								<div id="tm-statistics" class="bg-qs-gray text-white" data-function="tm-quick-stats">
								</div>
								
							</div>
							<!-- Modal footer -->
							<div class="flex justify-end p-6 space-x-2 rounded-b">
								<!--<button data-modal-toggle="tmStatistics" type="button" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600">Close</button>-->
							</div>
						</div>
					</div>
				</div>

				<div id="bar-chart">

					<div class="bar-chart-wrapper">
						<ul>
							<li class="7-day active" data-range="7-day-span">7 Day Span</li> |
							<li class="30-day" data-range="30-day-span">30 Day Span</li> |
							<li class="60-day" data-range="60-day-span">60 Day Span</li> |
							<li class="90-day" data-range="90-day-span">90 Day Span</li>
						</ul>

						<?php
							AdminPage::render_bar_charts( 7, 'block' );
							AdminPage::render_bar_charts( 30 );
							AdminPage::render_bar_charts( 60 );
							AdminPage::render_bar_charts( 90 );
						?>

					</div>

					<button class="toggle-visuals"><?php echo AdminPage::get_svg('bar-graph'); ?> Visualize</button>
					<!--<button class="block text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button" data-modal-toggle="defaultModal">Toggle modal</button>-->
				</div>

				<div class="analytics-cards">
				<div class="analytics-column">

					<!-- Unique VS Regular Visitors Table -->
					<section id="visit-overview" class="table-wrapper chart analytics-card loaded" data-function="visit-overview">
						<!-- Table Meta -->
						<div class="table-meta single-table">
							<h6 class="table-title">Visitors Overview <?php echo AdminPage::get_svg('info'); ?></h6>
						</div>
						<div class="loader-div">
							<span class="loader"></span>
						</div>
						<div class="chart-wrapper"></div>
					</section>

					<!--TailwindCSS example card -->
					<div class="p-2 m-2 max-w-full bg-white rounded-lg border border-white shadow-aa dark:bg-gray-800 dark:border-gray-700">
						<div class="mb-1">
							<ul class="flex flex-wrap -mb-px text-sm font-medium text-center justify-end" id="myTab" data-tabs-toggle="#myTabContent" role="tablist">
								<li class="mx-2" role="presentation">
									<button class="inline-block pt-4 rounded-t-lg text-blue-600" id="page-visits-tab" data-tabs-target="#page-visits" type="button" role="tab" aria-controls="page-visits" aria-selected="false">Page Visits</button>
								</li>
								<li class="mx-2" role="presentation">
									<button class="inline-block pt-4 rounded-t-lg hover:text-blue-600 dark:hover:text-blue-600" id="entry-pages-tab" data-tabs-target="#entry-pages" type="button" role="tab" aria-controls="dashboard" aria-selected="false">Entry Pages</button>
								</li>
								<li class="mx-2" role="presentation">
									<button class="inline-block pt-4 rounded-t-lg hover:text-blue-600 dark:hover:text-blue-600" id="exit-pages-tab" data-tabs-target="#exit-pages" type="button" role="tab" aria-controls="settings" aria-selected="false">Exit Pages</button>
								</li>
							</ul>
						</div>
						<div id="myTabContent">
							<div class="hidden p-2 bg-white rounded-lg dark:bg-gray-800 analytics-card loaded" id="page-visits" role="tabpanel" aria-labelledby="page-visits-tab" data-function="page-visits">
							<div class="table-meta single-table">
									<h6 class="table-title text-black">Page Visits <?php echo AdminPage::get_svg('info'); ?></h6>
									<ul class="view-list">
										<li data-view="titles" class="titles active">Titles</li>
										<li data-view="paths">Paths</li>
									</ul>
								<!--<select name="per-page" id="table-per-page" class="table-per-page">
									<option value="10">10</option>
									<option value="25">25</option>
									<option value="50">50</option>
								</select>-->
							</div>
								<div class="loader-div">
									<span class="loader"></span>
								</div>
							<!-- Table Header -->
							<div class="table w-full">
								<div class="table-header-group font-bold">
									<div class="table-row">
										<div class="table-cell text-left w-4/6">Titles</div>
										<div class="table-cell text-right w-2/6">Visits</div>
										<!--<div class="table-cell text-right w-1/6">Unique Visits</div>
										<div class="table-cell text-right w-1/6">Page Reads</div>
										<div class="table-cell text-right w-1/6">Bounces</div>-->
									</div>
								</div>
								<div class="table-row-group rows">
								</div>
							</div>
								<!-- Table Data -->
								<!--<div class="rows"></div>-->
								<div class="table-footer flex justify-center">
									<button class="block text-white bg-gray-600 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button" data-modal-toggle="page-visits-detail">
									Details
									</button>
								</div>
								<div id="page-visits-detail" data-iteration="0" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 w-full md:inset-0 h-modal md:h-full">
									<div class="relative p-4 w-full max-w-4xl h-full max-h-screen md:h-auto">
										<!-- Modal content -->
										<div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
											<!-- Modal header -->
											<div class="flex justify-between items-start p-4 rounded-t border-b dark:border-gray-600">
												<h3 class="text-xl font-semibold text-gray-900 dark:text-white">
													Page Visits
												</h3>
												<button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-toggle="page-visits-detail">
													<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>  
												</button>
											</div>
											<!-- Modal body -->
											<div class="p-6 space-y-6">
												<div class="table w-full">
													<div class="table-header-group font-bold">
														<div class="table-row">
															<div class="table-cell text-left w-2/6">Titles</div>
															<div class="table-cell text-right w-1/6">Visits</div>
															<div class="table-cell text-right w-1/6">Unique Visits</div>
															<div class="table-cell text-right w-1/6">Page Reads</div>
															<div class="table-cell text-right w-1/6">Bounces</div>
														</div>
													</div>
													<div class="table-row-group detailrows">
													</div>
												</div>
													<div class="table-footer flex justify-center">
														<button class="block text-white bg-gray-600 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button" id="page-visits-loadmore">
															Load More
														</button>
													</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							
							<div class="hidden p-2 bg-white rounded-lg dark:bg-gray-800 analytics-card loaded" id="entry-pages" role="tabpanel" aria-labelledby="entry-pages-tab" data-function="entry-pages">
							<div class="table-meta single-table">
									<h6 class="table-title text-black">Entry Pages <?php echo AdminPage::get_svg('info'); ?></h6>
									<ul class="view-list">
										<li data-view="titles" class="titles active">Titles</li>
										<li data-view="paths">Paths</li>
									</ul>
								<!--<select name="per-page" id="table-per-page" class="table-per-page">
									<option value="10">10</option>
									<option value="25">25</option>
									<option value="50">50</option>
								</select>-->
							</div>
								<div class="loader-div">
									<span class="loader"></span>
								</div>
							<!-- Table Header -->
							<div class="table w-full">
								<div class="table-header-group font-bold">
									<div class="table-row">
										<div class="table-cell text-left w-4/6">Titles</div>
										<div class="table-cell text-right w-2/6">Views</div>
										<!--<div class="table-cell text-right w-1/6">Bounces</div>-->
									</div>
								</div>
								<div class="table-row-group rows">
								</div>
							</div>
								<!-- Table Data -->
								<!--<div class="rows"></div>-->
								<div class="table-footer flex justify-center">
									<button class="block text-white bg-gray-600 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button" data-modal-toggle="entry-pages-detail">
									Details
									</button>
								</div>
								<div id="entry-pages-detail" data-iteration="0" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 w-full md:inset-0 h-modal md:h-full">
									<div class="relative p-4 w-full max-w-4xl h-full max-h-screen md:h-auto">
										<!-- Modal content -->
										<div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
											<!-- Modal header -->
											<div class="flex justify-between items-start p-4 rounded-t border-b dark:border-gray-600">
												<h3 class="text-xl font-semibold text-gray-900 dark:text-white">
													Entry Pages
												</h3>
												<button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-toggle="entry-pages-detail">
													<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>  
												</button>
											</div>
											<!-- Modal body -->
											<div class="p-6 space-y-6">
												<div class="table w-full">
													<div class="table-header-group font-bold">
														<div class="table-row">
															<div class="table-cell text-left w-4/6">Titles</div>
															<div class="table-cell text-right w-1/6">Views</div>
															<div class="table-cell text-right w-1/6">Bounces</div>
														</div>
													</div>
													<div class="table-row-group detailrows">
													</div>
												</div>
													<div class="table-footer flex justify-center">
														<button class="block text-white bg-gray-600 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button" id="entry-pages-loadmore">
															Load More
														</button>
													</div>
											</div>
										</div>
									</div>
								</div>
							</div>

							<div class="hidden p-2 bg-white rounded-lg dark:bg-gray-800 analytics-card loaded" id="exit-pages" role="tabpanel" aria-labelledby="exit-pages-tab" data-function="exit-pages">
							<div class="table-meta single-table">
									<h6 class="table-title text-black">Exit Pages <?php echo AdminPage::get_svg('info'); ?></h6>
									<ul class="view-list">
										<li data-view="titles" class="titles active">Titles</li>
										<li data-view="paths">Paths</li>
									</ul>
								<!--<select name="per-page" id="table-per-page" class="table-per-page">
									<option value="10">10</option>
									<option value="25">25</option>
									<option value="50">50</option>
								</select>-->
							</div>
								<div class="loader-div">
									<span class="loader"></span>
								</div>
							<!-- Table Header -->
							<div class="table w-full">
								<div class="table-header-group font-bold">
									<div class="table-row">
										<div class="table-cell text-left w-4/6">Titles</div>
										<div class="table-cell text-right w-2/6">Views</div>
										<!--<div class="table-cell text-right w-1/6">Bounces</div>-->
									</div>
								</div>
								<div class="table-row-group rows">
								</div>
							</div>
								<!-- Table Data -->
								<!--<div class="rows"></div>-->
								<div class="table-footer flex justify-center">
									<button class="block text-white bg-gray-600 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button" data-modal-toggle="exit-pages-detail">
									Details
									</button>
								</div>
								<div id="exit-pages-detail" data-iteration="0" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 w-full md:inset-0 h-modal md:h-full">
									<div class="relative p-4 w-full max-w-4xl h-full max-h-screen md:h-auto">
										<!-- Modal content -->
										<div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
											<!-- Modal header -->
											<div class="flex justify-between items-start p-4 rounded-t border-b dark:border-gray-600">
												<h3 class="text-xl font-semibold text-gray-900 dark:text-white">
													Exit Pages
												</h3>
												<button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-toggle="exit-pages-detail">
													<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>  
												</button>
											</div>
											<!-- Modal body -->
											<div class="p-6 space-y-6">
												<div class="table w-full">
													<div class="table-header-group font-bold">
														<div class="table-row">
															<div class="table-cell text-left w-4/6">Titles</div>
															<div class="table-cell text-right w-1/6">Views</div>
															<div class="table-cell text-right w-1/6">Bounces</div>
														</div>
													</div>
													<div class="table-row-group detailrows">
													</div>
												</div>
													<div class="table-footer flex justify-center">
														<button class="block text-white bg-gray-600 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button" id="exit-pages-loadmore">
															Load More
														</button>
													</div>
											</div>
										</div>
									</div>
								</div>
							</div>

						</div>
					</div>

					<!--TailwindCSS example card -->
					<div class="p-2 m-2 max-w-full bg-white rounded-lg border border-white shadow-aa dark:bg-gray-800 dark:border-gray-700">
						<div class="mb-1">
							<ul class="flex flex-wrap -mb-px text-sm font-medium text-center justify-end" id="myTab" data-tabs-toggle="#myTabContent" role="tablist">
								<li class="mx-2" role="presentation">
									<button class="inline-block pt-4 rounded-t-lg text-blue-600" id="blog-visits-tab" data-tabs-target="#blog-visits" type="button" role="tab" aria-controls="blog-visits" aria-selected="false">Blog Visits</button>
								</li>
								<li class="mx-2" role="presentation">
									<button class="inline-block pt-4 rounded-t-lg hover:text-blue-600 dark:hover:text-blue-600" id="blog-category-visits-tab" data-tabs-target="#blog-category-visits" type="button" role="tab" aria-controls="blog-category-visits" aria-selected="false">Blog Category Visits</button>
								</li>
								<li class="mx-2" role="presentation">
									<button class="inline-block pt-4 rounded-t-lg hover:text-blue-600 dark:hover:text-blue-600" id="keywords-tab" data-tabs-target="#keywords" type="button" role="tab" aria-controls="keywords" aria-selected="false">Keywords</button>
								</li>
							</ul>
						</div>
						<div id="myTabContent">
							<div class="hidden p-2 bg-white rounded-lg dark:bg-gray-800 analytics-card loaded" id="blog-visits" role="tabpanel" aria-labelledby="blog-visits-tab" data-function="blog-visits">
							<div class="table-meta single-table">
									<h6 class="table-title text-black">Blog Visits <?php echo AdminPage::get_svg('info'); ?></h6>
									<!--<ul class="view-list">
										<li data-view="titles" class="titles active">Titles</li>
										<li data-view="paths">Paths</li>-->
									</ul>
								<!--<select name="per-page" id="table-per-page" class="table-per-page">
									<option value="10">10</option>
									<option value="25">25</option>
									<option value="50">50</option>
								</select>-->
							</div>
								<div class="loader-div">
									<span class="loader"></span>
								</div>
							<!-- Table Header -->
							<div class="table w-full">
								<div class="table-header-group font-bold">
									<div class="table-row">
										<div class="table-cell text-left w-1/6">Titles</div>
										<div class="table-cell text-right w-1/6">Visits</div>
										<div class="table-cell text-right w-1/6">Unique Visits</div>
										<div class="table-cell text-right w-1/6">Page Reads</div>
										<div class="table-cell text-right w-1/6">Bounces</div>
										<div class="table-cell text-right w-1/6">Claps</div>
									</div>
								</div>
								<div class="table-row-group rows">
								</div>
							</div>
								<!-- Table Data -->
								<!--<div class="rows"></div>-->
								<div class="table-footer flex justify-center">
									<button class="block text-white bg-gray-600 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button" data-modal-toggle="blog-visits-detail">
									Details
									</button>
								</div>
								<div id="blog-visits-detail" data-iteration="0" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 w-full md:inset-0 h-modal md:h-full">
									<div class="relative p-4 w-full max-w-4xl h-full max-h-screen md:h-auto">
										<!-- Modal content -->
										<div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
											<!-- Modal header -->
											<div class="flex justify-between items-start p-4 rounded-t border-b dark:border-gray-600">
												<h3 class="text-xl font-semibold text-gray-900 dark:text-white">
													Blog Visits
												</h3>
												<button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-toggle="blog-visits-detail">
													<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>  
												</button>
											</div>
											<!-- Modal body -->
											<div class="p-6 space-y-6">
												<div class="table w-full">
													<div class="table-header-group font-bold">
														<div class="table-row">
															<div class="table-cell text-left w-2/6">Titles</div>
															<div class="table-cell text-right w-1/6">Visits</div>
															<div class="table-cell text-right w-1/6">Unique Visits</div>
															<div class="table-cell text-right w-1/6">Page Reads</div>
															<div class="table-cell text-right w-1/6">Bounces</div>
														</div>
													</div>
													<div class="table-row-group detailrows">
													</div>
												</div>
													<div class="table-footer flex justify-center">
														<button class="block text-white bg-gray-600 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button" id="blog-visits-loadmore">
															Load More
														</button>
													</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							
							<div class="hidden p-2 bg-white rounded-lg dark:bg-gray-800 analytics-card loaded" id="blog-category-visits" role="tabpanel" aria-labelledby="blog-category-visits-tab" data-function="blog-category-visits">
							<div class="table-meta single-table">
									<h6 class="table-title text-black">Blog Category Visits<?php echo AdminPage::get_svg('info'); ?></h6>
									<ul class="view-list">
										<li data-view="titles" class="titles active">Titles</li>
										<li data-view="paths">Paths</li>
									</ul>
								<!--<select name="per-page" id="table-per-page" class="table-per-page">
									<option value="10">10</option>
									<option value="25">25</option>
									<option value="50">50</option>
								</select>-->
							</div>
								<div class="loader-div">
									<span class="loader"></span>
								</div>
							<!-- Table Header -->
							<div class="table w-full">
								<div class="table-header-group font-bold">
									<div class="table-row">
										<div class="table-cell text-left w-2/6">Titles</div>
										<div class="table-cell text-right w-1/6">Vsits</div>
										<div class="table-cell text-right w-1/6">Unique Visits</div>
										<div class="table-cell text-right w-1/6">Page Reads</div>
										<div class="table-cell text-right w-1/6">Claps</div>
									</div>
								</div>
								<div class="table-row-group rows">
								</div>
							</div>
								<!-- Table Data -->
								<!--<div class="rows"></div>-->
								<div class="table-footer flex justify-center">
									<button class="block text-white bg-gray-600 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button" data-modal-toggle="blog-category-visits-detail">
									Details
									</button>
								</div>
								<div id="blog-category-visits-detail" data-iteration="0" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 w-full md:inset-0 h-modal md:h-full">
									<div class="relative p-4 w-full max-w-4xl h-full max-h-screen md:h-auto">
										<!-- Modal content -->
										<div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
											<!-- Modal header -->
											<div class="flex justify-between items-start p-4 rounded-t border-b dark:border-gray-600">
												<h3 class="text-xl font-semibold text-gray-900 dark:text-white">
													Blog Category Visits
												</h3>
												<button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-toggle="blog-category-visits-detail">
													<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>  
												</button>
											</div>
											<!-- Modal body -->
											<div class="p-6 space-y-6">
												<div class="table w-full">
													<div class="table-header-group font-bold">
														<div class="table-row">
															<div class="table-cell text-left w-2/6">Titles</div>
															<div class="table-cell text-right w-1/6">Visits</div>
															<div class="table-cell text-right w-1/6">Unique Visits</div>
															<div class="table-cell text-right w-1/6">Page Reads</div>
															<div class="table-cell text-right w-1/6">Claps</div>
														</div>
													</div>
													<div class="table-row-group detailrows">
													</div>
												</div>
													<div class="table-footer flex justify-center">
														<button class="block text-white bg-gray-600 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button" id="blog-category-visits-loadmore">
															Load More
														</button>
													</div>
											</div>
										</div>
									</div>
								</div>
							</div>

							<div class="hidden p-2 bg-white rounded-lg dark:bg-gray-800 analytics-card loaded" id="keywords" role="tabpanel" aria-labelledby="keywords-tab" data-function="keywords">
							<div class="table-meta single-table">
									<h6 class="table-title text-black">Keywords <?php echo AdminPage::get_svg('info'); ?></h6>
									<!--<ul class="view-list">
										<li data-view="titles" class="titles active">Titles</li>
										<li data-view="paths">Paths</li>
									</ul>-->
								<!--<select name="per-page" id="table-per-page" class="table-per-page">
									<option value="10">10</option>
									<option value="25">25</option>
									<option value="50">50</option>
								</select>-->
							</div>
								<div class="loader-div">
									<span class="loader"></span>
								</div>
							<!-- Table Header -->
							<div class="table w-full">
								<div class="table-header-group font-bold">
									<div class="table-row">
										<div class="table-cell text-left w-4/6">Keyword</div>
										<div class="table-cell text-right w-2/6">Visitors</div>
										<!--<div class="table-cell text-right w-1/6">Bounces</div>-->
									</div>
								</div>
								<div class="table-row-group rows">
								</div>
							</div>
								<!-- Table Data -->
								<!--<div class="rows"></div>-->
								<div class="table-footer flex justify-center">
									<button class="block text-white bg-gray-600 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button" data-modal-toggle="keywords-detail">
									Details
									</button>
								</div>
								<div id="keywords-detail" data-iteration="0" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 w-full md:inset-0 h-modal md:h-full">
									<div class="relative p-4 w-full max-w-4xl h-full max-h-screen md:h-auto">
										<!-- Modal content -->
										<div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
											<!-- Modal header -->
											<div class="flex justify-between items-start p-4 rounded-t border-b dark:border-gray-600">
												<h3 class="text-xl font-semibold text-gray-900 dark:text-white">
													Keywords
												</h3>
												<button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-toggle="keywords-detail">
													<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>  
												</button>
											</div>
											<!-- Modal body -->
											<div class="p-6 space-y-6">
												<div class="table w-full">
													<div class="table-header-group font-bold">
														<div class="table-row">
															<div class="table-cell text-left w-4/6">Keyword</div>
															<div class="table-cell text-right w-2/6">Visitors</div>
														</div>
													</div>
													<div class="table-row-group detailrows">
													</div>
												</div>
													<div class="table-footer flex justify-center">
														<button class="block text-white bg-gray-600 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button" id="keywords-loadmore">
															Load More
														</button>
													</div>
											</div>
										</div>
									</div>
								</div>
							</div>

						</div>
					</div>

					<!-- Single Blog Visits Table -->
					<section id="single-blog-post-visits" class="table-wrapper four-col analytics-card loaded" data-function="single-blog-post-visits">
					<!-- Table Meta -->
					<div class="table-meta single-table">
						<h6 class="table-title">Single Blog Post Visits <?php echo AdminPage::get_svg('info'); ?></h6>
						
					</div>
					<div class="loader-div">
						<span class="loader"></span>
					</div>
					
					<div class="wrapper" style="max-width: 95%; margin: 0 auto 60px; ">
						<div class="select-container">
						<input type="text" name="post-name" class="post-search" placeholder="Post Title" style="display: inline-block;height: 40px;" value="<?php ?>">
						</div>
						<canvas id="single-blog-post-chart"></canvas>
					</div>
					<?php AdminPage::render_table_footer(); ?>

					</section>

					<!-- Vists Per Day of Week Table -->
					<section id="vists-per-day" class="table-wrapper three-col analytics-card loaded" data-function="vists-per-day">
						<!-- Table Meta -->
						<div class="table-meta single-table">
							<h6 class="table-title">Views per Day of Week <?php echo AdminPage::get_svg('info'); ?></h6>
						</div>
						<div class="wrapper" style="max-width: 95%; margin: 0 auto 30px; height:350px;">
        					<canvas id="days-of-week-chart" ></canvas>
    					</div>
						<?php AdminPage::render_table_footer(); ?>
                    </section>

					<!-- Campaign Data Table -->
					<section id="campaign-data" class="table-wrapper three-col analytics-card loaded" data-function="campaign-data">
						<!-- Table Meta -->
						<div class="table-meta single-table">
							<h6 class="table-title">Campaign Data <?php echo AdminPage::get_svg('info'); ?></h6>
							<select name="per-page" id="table-per-page" class="table-per-page">
								<option value="10">10</option>
								<option value="25">25</option>
								<option value="50">50</option>
							</select>
						</div>
						<div class="loader-div">
							<span class="loader"></span>
						</div>
						<!-- Table Header -->
						<ul class="header row">
							<li>Campaign</li>
							<li>Term</li>
							<li>Sessions</li>
						</ul>
						<!-- Table Data -->
						<div class="rows"></div>

						<?php AdminPage::render_table_footer(); ?>

					</section>

					<!-- Campaign Mediums and Sources Chart -->
					<section id="campaign-mediums-sources" class="table-wrapper three-col analytics-card loaded" data-function="campaign-mediums-sources">
						<!-- Table Meta -->
						<div class="table-meta single-table">
							<h6 class="table-title">Campaign Traffic by Mediums <?php echo AdminPage::get_svg('info'); ?></h6>
							<ul class="view-list">
								<li data-view="campaign_mediums">Mediums</li>
								<li data-view="campaign_sources">Sources</li>
							</ul>
						</div>
						<div class="loader-div">
							<span class="loader"></span>
						</div>
						<div class="chart-wrapper"></div>
				</section>
			
			<?php 
				// check if site has user registration capabilities 
				$user_registration_check = get_option('users_can_register');
					
				if ($user_registration_check){	
			?>
				<!-- User Locations -->
				<section id="user-locations" class="table-wrapper three-col analytics-card loaded" data-function="user-locations">
					<div class="table-meta single-table">
						<h6 class="table-title">User Locations<?php echo AdminPage::get_svg('info'); ?></h6>
						<select name="per-page" id="table-per-page" class="table-per-page">
							<option value="10">10</option>
							<option value="25">25</option>
							<option value="50">50</option>
						</select>
					</div>
					<div class="loader-div">
						<span class="loader"></span>
					</div>
					<!-- Table Header -->
					<ul class="header row">
						<li>Location</li>
						<li>Users</li>
						<li>Timezone</li>
					</ul>
					<!-- Table Data -->
					<div class="rows"></div>
					<?php AdminPage::render_table_footer(); ?>
                </section> 
			<?php } ?>
			</div>
				<div class="analytics-column">

                    <!-- Domain Metrics -->
                    <section id="domain-metrics" class="table-wrapper one-col analytics-card loaded" data-function="domain-metrics">
                        <!-- Table Meta -->
                        <div class="table-meta single-table">
                            <h6 class="table-title">Domain Metrics <?php echo AdminPage::get_svg('info'); ?></h6>
                        </div>
                        <div class="loader-div">
                            <span class="loader"></span>
                        </div>
                        <!-- Table Header -->
                        <ul class="header row">
                            <li>Domain metrics for <span class="metrics-domain"></span></li>
                        </ul>
                        <!-- Table Data -->
                        <div class="rows"></div>
                    </section>

				    <!-- Social Media / Search Engine Table -->
					<section id="engines" class="table-wrapper chart analytics-card loaded" data-function="engines">
						<!-- Table Meta -->
						<div class="table-meta single-table">
							<h6 class="table-title">Social Networks <?php echo AdminPage::get_svg('info'); ?></h6>
							<ul class="view-list">
								<li data-view="social_networks">Social Networks</li>
								<li data-view="search_engines">Search Engines</li>
							</ul>
						</div>
						<div class="loader-div">
							<span class="loader"></span>
						</div>
						<div class="chart-wrapper"></div>
					</section>

					<!-- TailwindCSS Card -->
					<div class="py-1 m-2 max-w-full bg-white rounded-lg border border-white shadow-aa dark:bg-gray-800 dark:border-gray-700">
						<div class="mb-1">
							<div class="bg-white rounded-lg dark:bg-gray-800 analytics-card loaded" id="track-clicks" data-function="track-clicks">
								<div class="table-meta single-table">
									<h6 class="table-title text-black">Phone and Email Clicks <?php echo AdminPage::get_svg('info'); ?></h6>
								<!--<select name="per-page" id="table-per-page" class="table-per-page">
									<option value="10">10</option>
									<option value="25">25</option>
									<option value="50">50</option>
								</select>-->
								</div>
								<div class="loader-div">
									<span class="loader"></span>
								</div>
								<!-- Table Header -->
								<div class="table w-full">
									<div class="table-header-group font-bold">
										<div class="table-row">
											<div class="table-cell text-left w-2/6">Page Url</div>
											<div class="table-cell text-right w-1/6">Page Title</div>
											<div class="table-cell text-right w-1/6">Content</div>
											<div class="table-cell text-right w-1/6">Click Type</div>
											<div class="table-cell text-right w-1/6">Total clicks</div>
										</div>
									</div>
									<div class="table-row-group rows">
									</div>
								</div>
								<!-- Table Data -->
								<!--<div class="rows"></div>-->
								<div class="table-footer flex justify-center">
									<button class="block text-white bg-gray-600 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button" data-modal-toggle="track-clicks-detail">
									Details
									</button>
								</div>
								<div id="track-clicks-detail" data-iteration="0" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 w-full md:inset-0 h-modal md:h-full">
									<div class="relative p-4 w-full max-w-4xl h-full max-h-screen md:h-auto">
										<!-- Modal content -->
										<div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
											<!-- Modal header -->
											<div class="flex justify-between items-start p-4 rounded-t border-b dark:border-gray-600">
												<h3 class="text-xl font-semibold text-gray-900 dark:text-white">
													Phone and Email Clicks
												</h3>
												<button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-toggle="track-clicks-detail">
													<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>  
												</button>
											</div>
											<!-- Modal body -->
											<div class="p-6 space-y-6">
												<div class="table w-full">
													<div class="table-header-group font-bold">
														<div class="table-row">
														<div class="table-cell text-left w-2/6">Page Url</div>
														<div class="table-cell text-right w-1/6">Page Title</div>
														<div class="table-cell text-right w-1/6">Content</div>
														<div class="table-cell text-right w-1/6">Click Type</div>
														<div class="table-cell text-right w-1/6">Total clicks</div>
														</div>
													</div>
													<div class="table-row-group detailrows">
													</div>
												</div>
													<div class="table-footer flex justify-center">
														<button class="block text-white bg-gray-600 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button" id="track-clicks-loadmore">
															Load More
														</button>
													</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- TailwindCSS Card -->
					<div class="p-2 m-2 max-w-full bg-white rounded-lg border border-white shadow-aa dark:bg-gray-800 dark:border-gray-700">
						<div class="mb-1">
							<ul class="flex flex-wrap -mb-px text-sm font-medium text-center justify-end" id="myTab2" data-tabs-toggle="#myTabContent2" role="tablist">
								<li class="mr-2" role="presentation">
									<button class="inline-block pt-4 rounded-t-lg text-blue-600" id="referrers-tab" data-tabs-target="#referrers" type="button" role="tab" aria-controls="referrers" aria-selected="false">Referrers</button>
								</li>
								<li class="mr-2" role="presentation">
									<button class="inline-block pt-4 rounded-t-lg text-blue-600" id="sessions-locations-tab" data-tabs-target="#sessions-locations" type="button" role="tab" aria-controls="sessions-location" aria-selected="false">Visitor Locations</button>
								</li>
							</ul>
						</div>
						<div id="myTabContent2">
							<div class="hidden p-2 bg-white rounded-lg dark:bg-gray-800 analytics-card loaded" id="referrers" role="tabpanel" aria-labelledby="referrers-tab" data-function="referrers">
								<div class="table-meta single-table">
									<h6 class="table-title text-black">Referrers <?php echo AdminPage::get_svg('info'); ?></h6>
								<!--<select name="per-page" id="table-per-page" class="table-per-page">
									<option value="10">10</option>
									<option value="25">25</option>
									<option value="50">50</option>
								</select>-->
								</div>
								<div class="loader-div">
									<span class="loader"></span>
								</div>
								<!-- Table Header -->
								<div class="table w-full">
									<div class="table-header-group font-bold">
										<div class="table-row">
											<div class="table-cell text-left w-3/6">Referrers</div>
											<div class="table-cell text-right w-1/6">Visits</div>
											<div class="table-cell text-right w-1/6">Unique Visits</div>
											<div class="table-cell text-right w-1/6">Actions</div>
										</div>
									</div>
									<div class="table-row-group rows">
									</div>
								</div>
								<!-- Table Data -->
								<!--<div class="rows"></div>-->
								<div class="table-footer flex justify-center">
									<button class="block text-white bg-gray-600 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button" data-modal-toggle="referrers-detail">
									Details
									</button>
								</div>
								<div id="referrers-detail" data-iteration="0" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 w-full md:inset-0 h-modal md:h-full">
									<div class="relative p-4 w-full max-w-4xl h-full max-h-screen md:h-auto">
										<!-- Modal content -->
										<div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
											<!-- Modal header -->
											<div class="flex justify-between items-start p-4 rounded-t border-b dark:border-gray-600">
												<h3 class="text-xl font-semibold text-gray-900 dark:text-white">
													Referrers
												</h3>
												<button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-toggle="referrers-detail">
													<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>  
												</button>
											</div>
											<!-- Modal body -->
											<div class="p-6 space-y-6">
												<div class="table w-full">
													<div class="table-header-group font-bold">
														<div class="table-row">
															<div class="table-cell text-left w-3/6">Referrers</div>
															<div class="table-cell text-right w-1/6">Visits</div>
															<div class="table-cell text-right w-1/6">Unique Visits</div>
															<div class="table-cell text-right w-1/6">Actions</div>
														</div>
													</div>
													<div class="table-row-group detailrows">
													</div>
												</div>
													<div class="table-footer flex justify-center">
														<button class="block text-white bg-gray-600 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button" id="referrers-loadmore">
															Load More
														</button>
													</div>
											</div>
										</div>
									</div>
								</div>
							</div>

							<div class="hidden p-2 bg-white rounded-lg dark:bg-gray-800 analytics-card loaded" id="sessions-locations" role="tabpanel" aria-labelledby="sessions-locations-tab" data-function="sessions-locations">
								<div class="table-meta single-table">
									<h6 class="table-title text-black">Visitor Locations <?php echo AdminPage::get_svg('info'); ?></h6>
								<!--<select name="per-page" id="table-per-page" class="table-per-page">
									<option value="10">10</option>
									<option value="25">25</option>
									<option value="50">50</option>
								</select>-->
								</div>
								<div class="loader-div">
									<span class="loader"></span>
								</div>
								<!-- Table Header -->
								<div class="table w-full">
									<div class="table-header-group font-bold">
										<div class="table-row">
											<div class="table-cell text-left w-4/6">Location</div>
											<div class="table-cell text-right w-1/6">Visitors</div>
											<div class="table-cell text-right w-1/6">Timezone</div>
										</div>
									</div>
									<div class="table-row-group rows">
									</div>
								</div>
								<!-- Table Data -->
								<!--<div class="rows"></div>-->
								<div class="table-footer flex justify-center">
									<button class="block text-white bg-gray-600 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button" data-modal-toggle="sessions-locations-detail">
									Details
									</button>
								</div>
								<div id="sessions-locations-detail" data-iteration="0" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 w-full md:inset-0 h-modal md:h-full">
									<div class="relative p-4 w-full max-w-4xl h-full max-h-screen md:h-auto">
										<!-- Modal content -->
										<div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
											<!-- Modal header -->
											<div class="flex justify-between items-start p-4 rounded-t border-b dark:border-gray-600">
												<h3 class="text-xl font-semibold text-gray-900 dark:text-white">
													Visitor Locations
												</h3>
												<button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-toggle="sessions-locations-detail">
													<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>  
												</button>
											</div>
											<!-- Modal body -->
											<div class="p-6 space-y-6">
												<div class="table w-full">
													<div class="table-header-group font-bold">
														<div class="table-row">
															<div class="table-cell text-left w-4/6">Location</div>
															<div class="table-cell text-right w-1/6">Visitors</div>
															<div class="table-cell text-right w-1/6">Time Zone</div>
														</div>
													</div>
													<div class="table-row-group detailrows">
													</div>
												</div>
												<div class="table-footer flex justify-center">
													<button class="block text-white bg-gray-600 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button" id="sessions-locations-loadmore">
														Load More
													</button>
												</div>
												<!--<div class="detailrows">
											
												</div>-->
											</div>
										</div>
									</div>
								</div>
							</div>

						</div>
					</div>

					<!-- User Accounts Table -->
					<section id="user-accounts" class="table-wrapper three-col analytics-card loaded" data-function="user-accounts">
						<!-- Table Meta -->
						<div class="table-meta single-table">
							<h6 class="table-title">User Accounts <?php echo AdminPage::get_svg('info'); ?></h6>
						</div>
						<div class="wrapper" style="max-width: 95%; margin: 0 auto 30px; height:350px;">
        					<canvas id="userAccountsChart" ></canvas>
    					</div>
						<?php AdminPage::render_table_footer(); ?>
                    </section>
					<!-- Visits Per Hour Table -->
					<section id="visits-per-hour" class="table-wrapper three-col analytics-card loaded" data-function="visits-per-hour">
						<!-- Table Meta -->
						<div class="table-meta single-table">
							<h6 class="table-title">Visits by Hour <?php echo AdminPage::get_svg('info'); ?></h6>
						</div>
						<div class="wrapper" style="max-width: 95%; margin: 0 auto 30px; " >
							<div id="visits-per-hour-table"></div>
    					</div>
						<?php AdminPage::render_table_footer(); ?>
                    </section>
				</div>
				</div>

			</div><!-- CLOSE .ANALYTICS-CARDS -->
		</div> <!-- CLOSE #ANALYTICS-PAGE -->
	</div> <!-- CLOSE .WRAP -->



	<?php
	//$this->admin_page_js();
	}
}

$AdminPage = new AdminPage;

