<?php
/**
 * WebMD Cache 2 Library
 * 
 * Functions/classes used by multiple parts of the cache component
 * 
 * @package WebMD Blogs Plugin\Cache2
 * @since r32
 * @version r@RELEASE_NUMBER@
 */

/**
 * Find the Wordpress root directory
 * 
 * This runs when Wordpress isn't bootstrapped yet. It tries to figure out where the root directory is.
 * This is a little tricky because we do a lot of symlinking during development, and there are
 * different results when running from the command line vs. starting through a browser.
 *
 * @return String|Void exits on fail, killing the daemon or job
 */
function webmd_cache2_wp_rootpath() {
	exec( 'pwd', $output );
	$current_dir = dirname( $output[0] );
	$root_dir = str_replace( array('/wp-content/plugins/webmd/php', '/wp-content/plugins/webmd'), '', $current_dir );
	if ( is_file( $root_dir . '/wp-config.php' ) ) {
		return $root_dir;
	} else {
		$current_dir = dirname( $_SERVER['PHP_SELF'] );
		$root_dir = str_replace( array('/wp-content/plugins/webmd/php', '/wp-content/plugins/webmd'), '', $current_dir );
		if ( is_file( $root_dir . '/wp-config.php' ) ) {
			return $root_dir;
		}
	}
	exit(1);
}

/**
 * Get the domain name from the server
 * 
 * The uname command returns the real URL instead of the vanity URL
 * on blogpub in production and qa01, so we need to translate.
 */
function webmd_cache2_wp_domain() {
	exec( 'uname -n', $output );
	switch( $output[0] ) {
		case 'blgpb01l-con-07.portal.webmd.com':
			return 'blogpub.webmd.com';
		case 'blgpb01q-con-08.portal.webmd.com':
			return 'blogpub.qa01.webmd.com';
		default:
			return $output[0];
	}
}


/**
 * Cache 2 Job
 *
 * Interface to interact with a job in the queue
 * 
 * @package WebMD Blogs Plugin\Cache2
 */
class WebMD_Cache2Job {

	private $data = array();
	private $dbTable = 'webmdcache';
	private $jobID;

	public function __construct( $jobID, $dbTable = NULL ) {
		global $wpdb;
		if ( is_numeric( $jobID ) ) {
			$this->jobID = $jobID;
			if ( $dbTable ) {
				$this->dbTable = $dbTable;
			}
			$this->dbTable = $wpdb->base_prefix . $this->dbTable;
			$this->load();
		} else {
			throw new Exception('jobID must be numeric');
		}
	}

	public function start() {
		if ( is_numeric( $this->jobID ) ) {
			global $wpdb;
			$this->load();
			if ( $this->data->status != 'running' ) {
				$cmd = '/usr/bin/php ' . WP_PLUGIN_DIR . '/webmd/php/cache2job.php';
				$args = array( $this->jobID );
				$process = new WebMD_Cache2Process( $cmd, $args, true );
				return $process->run();
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function cancel() {
		if ( is_numeric( $this->jobID ) ) {
			global $wpdb;
			$this->load();
			if ( $this->data->status == 'running' ) {
				return $this->kill();
			} else {
				$this->status('cancelled');
				return true;
			}
		} else {
			return false;
		}
	}

	public function kill() {
		global $wpdb;
		$this->load();
		if ( is_numeric( $this->data->pid ) ) {
			exec("kill -9 $job->pid > /dev/null 2> /dev/null" );
			$this->status('killed');
			return true;
		} else {
			return false;
		}
	}

	public function delete() {
		global $wpdb;
		if ( is_numeric( $this->jobID ) ) {
			$query = $wpdb->prepare( "DELETE FROM $this->dbTable WHERE id = %d", $this->jobID );
			return $wpdb->query( $query );
		} else {
			return false;
		}
	}

	public function save( $attributes = array() ) {
		if ( is_numeric( $this->jobID ) && is_array( $attributes ) ) {
			global $wpdb;
			unset( $attributes['id'] );			
			return $wpdb->update(
				$this->dbTable,
				$attributes,
				array( 'id' => $this->jobID )
			);
		} else {
			return false;
		}
	}

	public function load() {
		if ( is_numeric( $this->jobID ) ) {
			global $wpdb;
			$query = $wpdb->prepare( "SELECT * FROM $this->dbTable WHERE id = %d", $this->jobID );
			$this->data = $wpdb->get_row( $query );
			if ( is_object( $this->data ) ) {
				return true;
			} else {
				$this->data = new stdClass();
				$this->data->status = 'fail';
				return false;
			}
		} else {
			$this->data = new stdClass();
			$this->data->status = 'fail';
		}
	}

	public function error( $code, $details ) {
		if ( is_numeric( $code ) ) {
			$args = array(
				'status' => "error $code",
				'stopped' => date( "Y-m-d H:i:s" )
			);
			if ( is_string( $details ) ) {
				$args['details'] = $details;
			}
			$this->save( $args );
		}
	}

	public function status( $status = NULL ) {
		if ( $status ) {
			$date = date("Y-m-d H:i:s");
			switch( $status ) {
				case 'waiting':
					$this->save(array(
						'created' => $date,
						'status' => $status
					));
					break;
				case 'running':
					$this->save(array(
						'started' => $date,
						'status' => $status
					));
					break;
				case 'killed':
				case 'cancelled':
				case 'done':
					$this->save(array(
						'stopped' => $date,
						'status' => $status
					));
					break;
				default:
					if ( strpos( $status, "error" ) !== false ) {
						if ( defined( "CACHE2_ECHO_JOB_ERRORS" ) ) {
							echo $status;
						}
						$this->save(array(
							'stopped' => $date,
							'status' => $status
						));
					}
					break;
			}
		}
		$this->load();
		return $this->data->status;
	}

	public function blogID() {
		return $this->data->blogid;
	}

	public function created() {
		return $this->data->created;
	}

	public function id() {
		return $this->jobID;
	}

	public function pid() {
		return $this->data->pid;
	}

	public function started() {
		return $this->data->started;
	}

	public function stopped() {
		return $this->data->stopped;
	}

	public function details() {
		return $this->data->details;
	}

}

/**
 * Cache 2 Job Queue
 * 
 * Object to control the jobs queue
 * 
 * @package WebMD Blogs Plugin\Cache2
 */
class WebMD_Cache2JobsQueue {
	
	private $table;

	/**
	 * If for some reason we want to have multiple queues (ex: one for each blog)
	 * at a later date, will be easy to do by creating separate tables and pointing
	 * to them. Also will help with unit tests.
	 */
	function __construct( $table = NULL ) {
		global $wpdb;
		if ( $table ) {
			$this->table = $table;
		} else {
			$this->table = $wpdb->base_prefix . 'webmdcache';
		}
	}

	/**
	 * Create needed database table for a queue
	 */
	public function bootstrap() {
		global $wpdb;
		$sql = "CREATE TABLE $this->table (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			pid smallint(10) DEFAULT 0 NOT NULL,
			blogid smallint(10) DEFAULT 0 NOT NULL,
			created timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
			started timestamp DEFAULT 0 NOT NULL,
			stopped timestamp DEFAULT 0 NOT NULL,
			status varchar(10) DEFAULT 'waiting' NOT NULL,
			mem bigint(20) DEFAULT 0 NOT NULL,
			items bigint(20) DEFAULT 0 NOT NULL,
			details text NOT NULL,
			PRIMARY KEY (id)
		);";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		return true;
	}


	/**
	 * Return list of job IDs currently in the queue
	 *
	 * @param String $status narrow query by status, accepts 'waiting', 'running', or 'killed'
	 * @return Array 
	 */
	public function getAllJobs( $status = NULL ) {
		global $wpdb;
		$result = array();
		if ( $status ) {
			$where = 'WHERE status = %s';
		}
		$query = $wpdb->prepare( "SELECT id FROM $this->table $where", $status );
		$rows = $wpdb->get_results( $query );
		foreach( $rows as $row ) {
			$result[] = $row->id;
		}
		return $result;
	}

	/**
	 * Add a job to the queue if there isn't already an identical job scheduled
	 *
	 * @param Array $data
	 * @return Number|Boolean pid if successful, false if fail
	 */
	public function addJob( $blogID ) {
		if ( $this->isValidBlog( $blogID ) && !$this->isAlreadyScheduled( $blogID ) ) {
			global $wpdb;
			$wpdb->insert(
				$this->table,
				array(
					'blogid' => $blogID,
					'status' => 'waiting'
				)
			);
			return $wpdb->insert_id;
		} else {
			return false;
		}
	}

	/**
	 * Add jobs for all public blogs (excludes 1, which isn't ever a real blog)
	 */
	public function addJobsForAllBlogs() {
		global $wpdb;
		$blogs = $wpdb->get_results( "SELECT blog_id FROM $wpdb->blogs WHERE blog_id != 1 AND public = 1" );
		foreach ( $blogs as $blog ) {
			$this->addJob( $blog->blog_id );
		}
		return true;
	}

	/**
	 * Delete all jobs from the queue
	 * 
	 * @return Boolean
	 */
	public function deleteAllJobs() {
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE $this->table;" );
		return ( count( $this->getAllJobs() ) === 0 );
	}

	/**
	 * Delete any job records which are older than 30 days, don't keep more than 500 records (keeps the table from getting too big)
	 * 
	 * @return Void
	 */
	public function deleteOldJobs() {
		$jobs = $this->getAllJobs();

		// delete old jobs
		foreach( $jobs as $id ) {
			$job = new WebMD_Cache2Job( $id );
			$elapsed = time() - strtotime( $job->created() );
			if ( $elapsed > 60*60*24*30 ) {
				$job->delete();
			}
		}
	}

	/**
	 * Trim the table to 500 jobs or the specified limit
	 */
	public function trimQueue( $limit = 500 ) {
		if ( is_numeric( $limit ) ) {
			global $wpdb;
			$jobs = $this->getAllJobs();
			arsort( $jobs );
			$jobsChunks = array_chunk( $jobs, $limit );
			$jobsDiff = array_diff( $jobs, $jobsChunks[0] );
			array_walk( $jobsDiff, create_function( '&$val', '$val = "id = $val";' ) );
			$query = "DELETE FROM $this->table WHERE " . implode( " OR ", $jobsDiff );
			return $wpdb->query( $query );
		} else {
			return false;
		}
	}

	/**
	 * Kill any jobs which have been running longer than 1 hour
	 * 
	 * @return Void
	 */
	public function killOldRunningJobs() {
		$jobs = $this->getAllJobs( 'running' );
		foreach( $jobs as $jobID ) {
			$job = new WebMD_Cache2Job( $jobID );
			$elapsed = time() - strtotime( $job->started() );
			if ( $elapsed > 60*60 ) {
				$job->kill();
			}
		}
	}

	/**
	 * Remove queue database table
	 * 
	 * @return Boolean
	 */
	public function uninstall() {
		global $wpdb;
		$wpdb->query( "DROP TABLE $this->table" );
		return true;
	}


	/**
	 * Is this a valid blog? i.e. does a record of it exist in the DB and is it set to public?
	 * 
	 * @return Boolean
	 */
	protected function isValidBlog( $blogID ) {
		if ( is_numeric( $blogID ) && intval( $blogID ) !== 1 ) {
			global $wpdb;
			$query = $wpdb->prepare( "SELECT * FROM $wpdb->blogs WHERE blog_id = %d", $blogID );
			$row = $wpdb->get_row( $query );
			if ( is_object( $row ) ) {
				if ( intval( $row->public ) === 1 ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Is a job already scheduled for this blog?
	 *
	 * @return Boolean
	 */
	protected function isAlreadyScheduled( $blogID ) {
		$already_scheduled = false;
		$jobs = $this->getAllJobs( 'waiting' );
		foreach( $jobs as $id ) {
			$job = new WebMD_Cache2Job( $id );
			if ( $job->blogID() == $blogID ) {
				$already_scheduled = true;
			}
		}
		return $already_scheduled;
	}

}

/**
 * URL list
 * 
 * Get a list of all URLs on a blog, including:
 * - Index pages
 * - Blog entries
 * - Category pages
 * - Tag pages
 * - RSS feeds
 * 
 * @package WebMD Blogs Plugin\Cache2
 */
class WebMD_Cache2URLList {

	private $urls = array();

	public function __construct( $blog_id = 1 ) {
		switch_to_blog( $blog_id );
		$this->home();
		$this->feeds();
		$this->categories();
		$this->posts();
		$this->dateIndexes();
		restore_current_blog();
	}

	public function getURLs() {
		return $this->urls;
	}

	protected function home() {
		$this->urls[ 'home' ] = get_bloginfo( 'wpurl' );
	}

	protected function categories() {
		$categories = get_categories();
		foreach( $categories as $category ) {
			$this->urls[ 'cat_' . $category->cat_ID ] = get_category_link( $category->cat_ID );
		}
	}

	protected function feeds() {
		$this->urls[ 'atom' ] = get_bloginfo( 'atom_url' );
		$this->urls[ 'atom_comments' ] = get_bloginfo( 'comments_atom_url' );
		$this->urls[ 'rss' ] = get_bloginfo( 'rss_url' );
		$this->urls[ 'rss2' ] = get_bloginfo( 'rss2_url' );
		$this->urls[ 'rss2_comments' ] = get_bloginfo( 'comments_rss2_url' );
	}	

	protected function posts() {
		$posts = new WP_Query('post_type=post&posts_per_page=-1&post_status=publish');
		$posts = $posts->posts;
		foreach( $posts as $post ) {
			$this->urls[ 'post_' . $post->ID ] = get_permalink( $post->ID );
		}
	}

	protected function dateIndexes() {
		$archives = wp_get_archives( 'echo=0' );
		preg_match_all( "/<a href=['|\"](.*?)['|\"]/", $archives, $archive_urls );
		foreach( $archive_urls[1] as $key => $url ) {
			preg_match_all( "/(\d+\/\d+\/?)$/", $url, $match );
			$yd = str_replace( "/", '', $match[1][0] );
			if ( !array_key_exists( $yd, $this->urls ) ) {
				$this->urls[ 'archive_' . $yd ] = $url;
			}
		}
	}

}

/**
 * Process manager
 * 
 * This is 100% *nix-centric, will bomb on Windows
 * 
 * @package WebMD Blogs Plugin\Cache2
 */
class WebMD_Cache2Process {

	private $background;
	private $cmd;
	private $cmdBuilt;
	private $cmdOutput;
	private $error = FALSE;
	private $pid;
	private $result;

	function __construct( $cmd = NULL, $args = array(), $background = FALSE ) {
		$this->cmd = $cmd;
		$this->args = $args;
		$this->background = $background;
		$this->buildCommand();
	}

	public function run() {
		
		// declare vars
		$pid = NULL;
		$output = NULL;
		$return = NULL;
		
		// reset any saved data
		$this->reset();
		
		// run the command
		exec( $this->cmdBuilt, $output, $return );

		if ( $this->background ) {
			
			// find the pid
			$pid = (int)$output[0];

		} else {
			
			// save the output
			$this->cmdOutput = $output;

		}
		if ( $return !== 0 ) {
			$this->error = TRUE;
		}
		if ( is_numeric( $pid ) ) {
			$this->pid = $pid;
		}
		return $this->success();
	}

	public function restart() {
		if ( $this->kill() ) {
			return $this->run();
		} else {
			return false;
		}
	}

	public function success() {
		return !$this->error;
	}

	public function kill() {
		if ( $this->isRunning() ) {
			if ( $this->killPID( $this->pid ) ) {
				$this->reset();
				return true;
			}
		}
		return false;
	}

	static function killPID( $pid ) {
		if ( is_numeric( $pid ) ) {
			$killcmd = new WebMD_Cache2Process( 'kill', array( '-9', $pid ) );
			return $killcmd->run();
		}
	}

	public function getPID() {
		return $this->pid;
	}

	public function isRunning() {
		return $this->isPIDRunning( $this->pid );
	}

	static function isPIDRunning( $pid ) {
		if ( is_numeric( $pid ) ) {
			return file_exists( '/proc/' . $pid );
		} else {
			return false;
		}
	}

	public function output( $asString = TRUE ) {
		if ( $asString ) {
			return implode( "\n", $this->cmdOutput );
		} else {
			return $this->cmdOutput;
		}
	}

	protected function buildCommand() {
		$args = '';
		$cmd = $this->cmd;
		$outputDirect = '';
		
		// add arguments if specified
		if ( is_array( $this->args ) && count( $this->args ) ) {
			foreach ( $this->args as $arg ) {
				$cmd .= ' ' . escapeshellarg( $arg );
			}
		}

		// handle output differently for background jobs vs. immediate execution
		if ( $this->background ) {
			$cmd .= ' > /dev/null 2> /dev/null & echo $!';
		} else {
			$cmd .= ' 2> /dev/null';
		}
		$this->cmdBuilt = $cmd;
	}

	protected function reset() {
		$this->cmdOutput = NULL;
		$this->error = FALSE;
		$this->pid = NULL;
		$this->result = NULL;
	}

}


/**
 * To write a cache
 *
 * @package WebMD Blogs Plugin\Cache2
 */
class WebMD_Cache2CacheWriter {

	private $asset_map = array();
	private $job;
	private $cache_dir;
	private $cache_temp_dir_root;
	private $cache_temp_dir;
	private $request_map = array();

	public function __construct( $jobID, $cache_dir = NULL, $cache_temp_dir = NULL ) {
		$this->loadJob( $jobID );
		switch_to_blog( $this->job->blogID() );
		$this->setCacheDirs( $cache_dir, $cache_temp_dir );
		$this->buildMaps();
		restore_current_blog();
	}

	public function write() {
		switch_to_blog( $this->job->blogID() );
		$this->writeNewCacheRequests();
		$this->writeNewCacheAssets();
		$this->deployNewCache();
		$this->fixPermissions();
		$this->cleanUp();
		restore_current_blog();
	}

	public function totalItems() {
		return count( $this->asset_map ) + count( $this->request_map );
	}

	protected function writeNewCacheRequests() {
		foreach( $this->request_map as $map ) {

			$file = $map['file'];
			$url = $map['url'];

			// run the command
			$cmd = "/usr/bin/php " . WP_CONTENT_DIR . "/plugins/webmd/php/cache2request.php";
			$args = array( $this->job->blogID(), $url );
			$process = new WebMD_Cache2Process( $cmd, $args );
			if ( $process->run() ) {
				$html = $process->output();
			} else {
				$this->job->error( 1, 'cache2request process failed with args ' . implode( ' ', $args ) );
				exit(1);
			}

			if ( strlen( $html ) === 0 ) {
				$this->job->error( 2, 'cache2request process returned empty html with args ' . implode( ' ', $args ) );
				exit(1);
			}

			// filter output
			$processor = new WebMD_Cache2PostProcessor();
			$html = $processor->doSubstitutions( $html );

			// write the cache file
			$dir = dirname( $file );
			if ( !is_dir( $dir ) ) {
				if ( !mkdir( $dir, 0777, true ) ) {
					$this->job->error( 3, "unable to create $dir" );
				}
			}

			file_put_contents( $file, $html );

			if ( !is_file( $file ) || filesize( $file ) === 0 ) {
				$this->job->error( 4, "$file failed to be written" );
				exit(1);
			}

		}
	}

	protected function writeNewCacheAssets() {
		foreach( $this->asset_map as $map ) {
			if ( is_file( $map['src'] ) ) {
				$dir = dirname( $map['dest'] );
				if ( !is_dir( $dir ) ) {
					if ( !mkdir( $dir, 0777, true ) ) {
						$this->job->error( 5, "unable to create $dir" );
					}
				}
				if ( !copy( $map['src'], $map['dest'] ) ) {
					$this->job->error( 6, "unable to copy " . $map['src'] . " to " . $map['dest'] );
				}
			} else {
				$this->job->error( 7, $map['src'] . " is not a file" );
				exit(1);
			}
		}
	}

	protected function deleteCache() {
		if ( is_dir( $this->cache_dir ) ) {
			$this::delTree( $this->cache_dir );
			if ( is_dir( $this->cache_dir ) ) {
				$this->job->error( 8, $this->cache_dir . " was not deleted" );
				exit(1);
			}
		}
	}

	protected function deployNewCache() {
		$this->deleteCache();
		$cmd = 'cp -r ';
		$args = array(
			$this->cache_temp_dir,
			$this->cache_dir
		);
		$process = new WebMD_Cache2Process( $cmd, $args );
		if ( !$process->run() ) {
			$this->job->error( 9, $this->cache_temp_dir . " was not deployed to " . $this->cache_dir );
			exit(1);
		}
		if ( is_dir( $cache_temp_dir ) ) {
			$this->job->error( 10 );
			exit(1);
		}
	}

	protected function fixPermissions() {
		$cmd = "chmod";
		$args = array( '-R', '0777', $this->cache_dir );
		$process = new WebMD_Cache2Process( $cmd, $args );
		if ( !$process->run() ) {
			$this->job->error( 11, 'failed to set permissions on ' . $this->cache_dir );
			exit(1);
		}
	}

	protected function cleanUp() {
		$this->delTree( $this->cache_temp_dir_root );
		if ( is_dir( $this->cache_temp_dir_root ) ) {
			$this->job->error( 12, 'failed to clean up temp directory at ' . $this->cache_temp_dir_root );
			exit(1);
		}
	}

	static function delTree( $dir ) {
		$files = glob( $dir . '*', GLOB_MARK );
	    foreach( $files as $file ){
	        if( substr( $file, -1 ) == '/' )
	            WebMD_Cache2CacheWriter::delTree( $file );
	        else
	            unlink( $file );
	    }
	    if ( is_dir( $dir ) ) {
	    	rmdir( $dir );
		}
	}

	protected function loadJob( $jobID ) {
		if ( is_numeric( $jobID ) ) {
			$this->job = new WebMD_Cache2Job( $jobID );
			if ( $this->job->status == 'fail' ) {
				throw new Exception( "failed to load job with ID $jobID" );
			}
		} else {
			throw new Exception( "requires numeric JobID" );
		}
	}

	protected function setCacheDirs( $cache_dir, $cache_temp_dir ) {
		if ( $cache_dir ) {
			$this->cache_dir = $cache_dir;
		} else {
			$this->cache_dir = WP_CONTENT_DIR . '/cache/' . webmd_slug() . '/';
		}
		if ( $cache_temp_dir ) {
			$this->cache_temp_dir = $cache_temp_dir;
		} else {
			$this->cache_temp_dir_root = WP_CONTENT_DIR . '/cache_temp/' . $this->job->id();
			$this->cache_temp_dir =  $this->cache_temp_dir_root . '/' . webmd_slug();
		}
	}

	protected function buildMaps() {
		$assetMap = new WebMD_Cache2DeployMapAssets( $this->job->blogID(), $this->cache_temp_dir );
		$this->asset_map = $assetMap->getMap();
		$requestMap = new WebMD_Cache2DeployMapRequests( $this->job->blogID(), $this->cache_temp_dir );
		$this->request_map = $requestMap->getMap();
	}

}


/**
 * Template for resource maps
 * 
 * @package WebMD Blogs Plugin\Cache2
 */
abstract class WebMD_Cache2DeployMap {

	protected $blogID;
	protected $cacheTempDir;
	protected $map = array();

	public function __construct( $blogID = null, $cacheTempDir = null ) {
		if ( is_numeric( $blogID ) && is_string( $cacheTempDir ) ) {
			switch_to_blog( $blogID );
			$this->blogID = $blogID;
			$this->cacheTempDir = $cacheTempDir;
			$this->buildMap();
			restore_current_blog();
		} else {
			throw new Exception('expects two parameters, blogID must be a number and cacheTempDir must be a string');
		}
	}

	public function getMap() {
		return $this->map;
	}

	protected function buildMap() {
		// this should be overridden by child objects
	}

}


/**
 * Map of URL requests
 * 
 * @package WebMD Blogs Plugin\Cache2
 * @uses WebMD_Cache2DeployMap
 */
class WebMD_Cache2DeployMapRequests extends WebMD_Cache2DeployMap {

	protected function buildMap() {
		$urls = new WebMD_Cache2URLList( $this->blogID );
		foreach( $urls->getURLs() as $key => $url ) {
			$url = str_replace( get_bloginfo('url'), '', $url );
			$url = rtrim( $url, '/' );
			$blogpath = PATH_CURRENT_SITE . webmd_slug();
			switch( $key ) {
				case 'atom':
				case 'atom_comments':
				case 'rss':
				case 'rss2':
				case 'rss2_comments':
					$this->map[] = array(
						'url' => $blogpath . $url,
						'file' => $this->cacheTempDir . $url . ".xml"
					);
					break;
				case 'home':
					$this->map[] = array(
						'url' => $blogpath . $url . '/',
						'file' => $this->cacheTempDir . '/index.html'
					);
					break;
				case ( strpos( $key, 'cat' ) !== false ):
				case ( strpos( $key, 'archive' ) !== false ):
					$url = ltrim( $url, '/blog' );
					$this->map[] = array(
						'url' => $blogpath . '/' . $url,
						'file' => $this->cacheTempDir . '/' . $url . "/index.html"
					);
					break;
				case ( strpos( $key, 'post' ) !== false ):
					$this->map[] = array(
						'url' => $blogpath . $url,
						'file' => $this->cacheTempDir . $url
					);
					break;
			}
		}
	}

}

/**
 * Build maps of assets and requests to deploy
 *
 * @package WebMD Blogs Plugin\Cache2
 * @uses WebMD_Cache2DeployMap
 */
class WebMD_Cache2DeployMapAssets extends WebMD_Cache2DeployMap {

	protected function buildMap() {
		$this->buildAssetMapThemeFiles();
		$this->buildAssetMapPluginJS();
		$this->buildAssetMapUploads();
	}

	protected function buildAssetMapThemeFiles() {
		$themeDir = get_stylesheet_directory();
		$themeRelDir = str_replace( get_theme_root(), '', $themeDir );		
		$themeFileRegexp = '/^.+\.(js|css|jpg|jpeg|gif|png)/i';
		$themeFiles = $this->getAssetListFromDir( $themeDir, $themeFileRegexp );
		foreach( $themeFiles as $name => $object ) {
			$assetDest = $this->cacheTempDir . '/wp-content/themes' . $themeRelDir . str_replace( $themeDir, '', $name );
			$this->map[] = array(
				'src' => $name,
				'dest' => $assetDest
			);
		}
	}

	protected function buildAssetMapPluginJS() {
		$pluginsJSRelDir = '/plugins/webmd/js/user';
		$pluginJSDir = WP_CONTENT_DIR . $pluginsJSRelDir;
		$pluginFileRegexp = '/^.+\.js/i';
		$pluginJSFiles = $this->getAssetListFromDir( $pluginJSDir, $pluginFileRegexp );
		foreach( $pluginJSFiles as $name => $object ) {
			$assetDest = $this->cacheTempDir . '/wp-content' . $pluginsJSRelDir . '/' . basename( $name );
			$this->map[] = array(
				'src' => $name,
				'dest' => $assetDest
			);
		}
	}

	protected function buildAssetMapUploads() {
		$uploadDir = WP_CONTENT_DIR . '/blogs.dir/' . $this->blogID;
		$uploads = $this->getAssetListFromDir( $uploadDir );
		foreach( $uploads as $name => $object ) {
			if ( !is_dir( $name ) ) {
				$assetDest = $this->cacheTempDir . str_replace( $uploadDir, '', $name );
				$this->map[] = array(
					'src' => $name,
					'dest' => $assetDest
				);
			}
		}
	}

	protected function getAssetListFromDir( $dir = null, $regexp = null ) {
		if ( is_dir( $dir ) ) {
			$dirIterator = new RecursiveDirectoryIterator( $dir );
			$iteratorIterator = new RecursiveIteratorIterator( $dirIterator );
			if ( is_string( $regexp ) ) {
				return new RegexIterator( $iteratorIterator, $regexp );
			} else {
				return $iteratorIterator;
			}			
		} else {
			return array();
		}
	}

}

/**
 * Add a post-process function
 *
 * @package WebMD Blogs Plugin\Cache2
 */
class WebMD_Cache2PostProcessor {

	private $searches = array();
	private $transient;

	public function __construct( $transient = NULL ) {
		if ( !$transient ) {
			$transient = 'webmd_cache2_pp_searches';
		}
		$this->transient = $transient;
		$this->load();
	}

	public function add( $search = NULL, $replace = NULL, $weight = 0 ) {
		if ( $search && $replace ) {
			$this->searches[] = array(
				'search' => $search,
				'replace' => $replace,
				'weight' => $weight
			);

			// make sure values are unique
			$this->searches = array_map( "unserialize", array_unique (array_map( "serialize" , $this->searches ) ) );
			
			// sort by weight
			usort( $this->searches, array( "WebMD_Cache2PostProcessor", "orderByWeight" ) );
		}
	}

	public function load() {
		$searches = get_site_transient( $this->transient );
		if ( !is_array( $searches ) ) {
			$searches = array();
		}
		$this->searches = $searches;
	}

	public function save() {
		set_site_transient( $this->transient, $this->searches );
	}

	public function doSubstitutions( $html ) {
		if ( $html ) {
			if ( is_array( $this->searches ) ) {
				foreach( $this->searches as $search ) {
					$html = preg_replace( $search['search'], $search['replace'], $html );
				}
			}
		}
		return $html;
	}

	public function clear() {
		$this->searches = array();
	}

	static function orderByWeight( $a, $b ) {
		return $a['weight'] - $b['weight'];
	}
}


if ( defined('ABSPATH') && !class_exists('WP_List_Table') ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
if ( defined('ABSPATH') && class_exists('WP_List_Table') ) {

	/**
	 * Display jobs in a table
	 *
	 * @package WebMD Blogs Plugin\Cache2
	 * @uses WP_List_Table
	 */
	class WebMD_Cache2JobsTable extends WP_List_Table {

		function __construct(){
	        global $status, $page;
	                
	        //Set parent defaults
	        parent::__construct( array(
	            'singular'  => 'job',     //singular name of the listed records
	            'plural'    => 'jobs',    //plural name of the listed records
	            'ajax'      => false        //does this table support ajax?
	        ) );
	        
	    }

	    function column_default( $item, $column_name ) {
	    	return $item[$column_name];
	    }

	    function column_blogid( $item ) {
	    	if ( is_numeric( $item['blogid'] ) ) {
	    		switch_to_blog( $item['blogid'] );
	    		$title = get_bloginfo('name');
	    		restore_current_blog();
	    		return $title;
	    	}
	    }

	    function column_created( $item ) {
	    	if ( intval( $item['created'] ) ) {
	    		return date( "m/d/y g:i a", strtotime( $item['created'] ) );
	    	}
	    }

	    function column_started( $item ) {
	    	if ( intval( $item['started'] ) ) {
	    		return date( "m/d/y g:i a", strtotime( $item['started'] ) );
	    	}
	    }

	    function column_stopped( $item ) {
	    	if ( intval( $item['stopped'] ) ) {
	    		return date( "m/d/y g:i a", strtotime( $item['stopped'] ) );
	    	}
	    }

	    function column_actions( $item ) {
	    	if ( $item['status'] == 'waiting' || $item['status'] == 'running' ) {
	    		return '<a href="?page='. $_REQUEST['page'] .'&canceljob='. $item['id'] .'">Cancel</a>';
	    	}
	    }

	    function column_duration( $item ) {
	    	date_default_timezone_set( 'UTC' );
	    	if ( intval( $item['started'] ) && ( $item['status'] == 'running' || $item['status'] == 'done' ) ) {
				if ( intval( $item['stopped'] ) ) {
					$duration = strtotime( $item['stopped'] ) - strtotime( $item['started'] );
				} else {
					$duration = time() - strtotime( $item['started'] );
				}
				$mins = floor( $duration/60 );
				if ( $mins > 0 && !$_GET['dev'] ) {
					$output = '';
					$secs = $duration - ($mins*60);
					if ( $min == 1 ) {
						$output .= "1 min";
					} else {
						$output .= "$mins mins";
					}
					if ( $secs > 0 ) {
						$output .= " $secs secs";
					}
					return $output;
				} else {
					return $duration . ' seconds';
				}
			}
	    }

	    function column_items( $item ) {
	    	if ( $item['status'] == 'done' ) {
	    		return number_format( $item['items'] );
	    	}
	    }

	    function column_itemssec( $item ) {
	    	if ( intval( $item['started'] ) ) {
				if ( intval( $item['stopped'] ) ) {
					$duration = strtotime( $item['stopped'] ) - strtotime( $item['started'] );
				} else {
					$duration = time() - strtotime( $item['started'] );
				}
				if ( $item['status'] == 'done' ) {
	    			return number_format( $item['items'] / $duration, 2 );
	    		}
	    	}
	    }

	    function column_mem( $item ) {
	    	if ( $item['status'] == 'done' ) {
	    		return number_format( $item['mem'] / 1000000 ) . ' MB';
	    	}
	    }

		function get_columns(){        
	        $columns = array(
	            'id'        => 'Job#',
	            'blogid'    => 'Blog',
	            'status'  => 'Status',
	            'created' => 'Created',
	            'started' => 'Started',
	            'stopped' => 'Stopped',
	            'duration' => 'Duration',
	            'actions' => 'Actions'
	        );
	        if ( $_GET['dev'] ) {
	        	$columns['pid'] = 'PID';
	        	$columns['items'] = 'Items';
	        	$columns['itemssec'] = 'Items/sec';
	        	$columns['mem'] = 'Peak Mem Usage';
	        	$columns['details'] = 'Details';
	        }
	        return $columns;
	    }

	    function get_sortable_columns() {
	        $sortable_columns = array(
	            'id'     => array( 'id' , true ),     //true means its already sorted
	            'status'    => array( 'status', false ),
	            'created'  => array( 'created', false ),
	            'started'  => array( 'started', false ),
	            'stopped'  => array( 'stopped', false ),
	            'duration'  => array( 'duration', false )
	        );
	        if ( $_GET['dev'] ) {
	        	$sortable_columns['items'] = array( 'items', false );
	        	$sortable_columns['itemssec'] = array( 'itemssec', false );
	        	$sortable_columns['mem'] = array( 'mem', false );
	        }
	        return $sortable_columns;
	    }

		function prepare_items() {
			global $wpdb;

			// for pagination
			$total_items = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM wp_webmdcache;" ) );
			if ( $_GET['dev'] && $total_items > 0 ) {
				$per_page = $total_items;
			} else {
				$per_page = 20;
			}

			// fetch data based on query params
			$sort = '';
			$limit = '';
			if ( $_GET['orderby'] ) {
				$sort = ' ORDER BY ' . $_GET['orderby'];
				if ( $_GET['order'] ) {
					$sort .= ' ' . $_GET['order'];
				}
			}
			if ( $_GET['paged'] > 1 ) {
				$start = ($_GET['paged']-1) * $per_page;
				$limit .= ' LIMIT ' . $start . ',' . $per_page;
			} else {
				$limit .= ' LIMIT 0,' . $per_page;
			}
			$query = "SELECT * FROM wp_webmdcache" . $sort . $limit;
			$this->items = $wpdb->get_results( $query, ARRAY_A );

			// column headers
			$columns = $this->get_columns();
			$sortable = $this->get_sortable_columns();
	        $this->_column_headers = array( $columns, array(), $sortable );

	        // pagination
	        $this->set_pagination_args( array(
	            'total_items' => $total_items,                  //WE have to calculate the total number of items
	            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
	            'total_pages' => ceil( $total_items / $per_page )   //WE have to calculate the total number of pages
	        ) );

		}	
	}
}

?>