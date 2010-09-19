<?php
/**
 * This file contains WP Events Calendar plugin.
 *
 * This is the main WPEC file.
 * @internal			Complete the description.
 *
 * @package			WP-Events-Calendar
 * @since			1.0
 * 
 * @autbor			Luke Howell <luke@wp-eventscalendar.com>
 *
 * @copyright			Copyright (c) 2007-2009 Luke Howell
 *
 * @license			GPLv3 {@link http://www.gnu.org/licenses/gpl}
 * @filesource
 */
/*
--------------------------------------------------------------------------
$Id$
--------------------------------------------------------------------------
This file is part of the WordPress Events Calendar plugin project.

For questions, help, comments, discussion, etc., please join our
forum at {@link http://www.wp-eventscalendar.com/forum}. You can
also go to Luke's ({@link http://www.lukehowelll.com}) blog.

WP Events Calendar is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.   See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
--------------------------------------------------------------------------
*/
if(!class_exists('EC_DB')):

/**
 * This class is used by WPEC to access and modify the database.
 *
 * All the DB work needed by WPEC is done through this class. 
 * Internally, it uses the $wpdb global object provided by WordPress.
 *
 * If installing the plugin, the class will be called upon to create
 * or update the WPEC database table.
 *
 * tHIS IS WHY IT IS IMPORTANT TO DEACTIVATE AND THEN REACTIVATE 
 * THE PLUGIN WHEN UPGRADING TO A NEW VERSION.
 * 
 * Later on, EC_DB is used to read, create, modify and delete events.
 *
 * @package WP-Events-Calendar
 * @since   6.0  
 */
class EC_DB {

	/**
	 * Holds an instance of the $wpdb object.
	 * @var object
	 * @access private
	 */
	var $db;

	/**
	 * Name of the WPEC table where events are stored.
	 * @var string
	 * @access private
	 */
	var $mainTable;

	/**
	 * Name of the posts table with its prefix.
	 * @var string
	 * @access private
	 */
	var $postsTable;

	var $categoryTable;

	var $locationTable;

	/**
	 * Holds the main WPEC table version.
	 * @var int
	 * @access private
	 */
	var $dbVersion;

	/**
	 * Constructor. 
	 * Loads the $wpdb global object and makes sure we have the good table name
	 */
	function EC_DB() {
		global $wpdb;
		$this->dbVersion = "109";
		$this->db = $wpdb;
		$this->mainTable = $this->db->prefix . 'eventscalendar_main';
		
		// FIXME why is this needed? Is it for backward compatibility?
		$this->mainTableCaps = $this->db->prefix . 'EventsCalendar_main';
		if ($this->db->get_var("show tables like '$this->mainTableCaps'") == $this->mainTableCaps)
			$this->mainTable = $this->mainTableCaps;

		$this->postsTable = $this->db->prefix . 'posts';
		$this->categoryTable = $this->db->prefix . 'eventscalendar_category';
		$this->locationTable = $this->db->prefix . 'eventscalendar_location';
	}

	/**
	 * Called on plugin activation to create or upgrade the WPEC table.
	 * FIXME I don't think we need that much code here. get_option will
	 *       return false if an option does not exist. This means that
	 *       if the eventscalendar_db_version is false or different
	 *       from the new version, we just execute the SQL.
	 */
	function createTable() {

		/**
		 * Table definition and creation routines for the category table
		 */

		$sqlCategoryTable = "CREATE TABLE " . $this->categoryTable . " (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			name varchar(255) CHARACTER SET utf8 NOT NULL,
			UNIQUE (name),
			PRIMARY KEY  id (id)
			);";

		if ($this->db->get_var("show tables like '$this->categoryTable'") != $this->categoryTable) {

			require_once(ABSPATH . "wp-admin/upgrade-functions.php");
			dbDelta($sqlCategoryTable);

			// Request whithout CHARACTER SET utf8 if the CREATE TABLE failed
			if ($this->db->get_var("show tables like '$this->categoryTable'") != $this->categoryTable ) {
				$sql = str_replace("CHARACTER SET utf8 ","",$sqlCategoryTable);
				dbDelta($sql);
			}
		}

		/**
		 * Table definition and creation routines for the location table
		 */

		$sqlLocationTable = "CREATE TABLE " . $this->locationTable . " (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			name varchar(255) CHARACTER SET utf8 NOT NULL,
			description text CHARACTER SET utf8,
			UNIQUE (name),
			PRIMARY KEY  id (id)
			);";

		if ($this->db->get_var("show tables like '$this->locationTable'") != $this->locationTable) {

			require_once(ABSPATH . "wp-admin/upgrade-functions.php");
			dbDelta($sqlLocationTable);

			// Request whithout CHARACTER SET utf8 if the CREATE TABLE failed
			if ($this->db->get_var("show tables like '$this->locationTable'") != $this->locationTable ) {
				$sql = str_replace("CHARACTER SET utf8 ","",$sqlLocationTable);
				dbDelta($sql);
			}
		}

		/**
		 * Table definition and creation routines for the main table
		 */

		// The locationId can be used when using recurring locations.
		// The eventLocation columns is to be used when using non-recurring locations.
		$sqlMainTable = "CREATE TABLE " . $this->mainTable . " (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			eventTitle varchar(255) CHARACTER SET utf8 NOT NULL,
			eventDescription text CHARACTER SET utf8 NOT NULL,
			eventLocation varchar(255) CHARACTER SET utf8 default NULL,
			eventLinkout varchar(255) CHARACTER SET utf8 default NULL,
			eventStartDate date NOT NULL,
			eventStartTime time default NULL,
			eventEndDate date NOT NULL,
			eventEndTime time default NULL,
			accessLevel varchar(255) CHARACTER SET utf8 NOT NULL default 'public',
			postID mediumint(9) NULL DEFAULT NULL,
			locationId integer,
			categoryId integer NOT NULL,
			PRIMARY KEY  id (id),
			INDEX (locationId),
			INDEX (categoryId),
			FOREIGN KEY (locationId) REFERENCES " . $this->locationTable . "(id),
			FOREIGN KEY (categoryId) REFERENCES " . $this->categoryTable . "(id)
			);";

		if ($this->db->get_var("show tables like '$this->mainTable'") != $this->mainTable ) {
			require_once(ABSPATH . "wp-admin/upgrade-functions.php");
			dbDelta($sqlMainTable);

			// Request whithout CHARACTER SET utf8 if the CREATE TABLE failed
			if ($this->db->get_var("show tables like '$this->mainTable'") != $this->mainTable ) {
				$sql = str_replace("CHARACTER SET utf8 ","",$sqlMainTable);
				dbDelta($sql);
			}
			add_option("events_calendar_db_version", $this->dbVersion);
		}

		$installed_ver = get_option( "events_calendar_db_version" );

		if ($installed_ver != $this->dbVersion) {

			require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
			dbDelta($sqlCategoryTable);
			dbDelta($sqlLocationTable);
			dbDelta($sqlMainTable);

			$this->db->query("UPDATE " . $this->mainTable . " SET `eventLocation` = REPLACE(`eventLocation`,' ','');");
			$this->db->query("UPDATE " . $this->mainTable . " SET `eventLocation` = REPLACE(`eventLocation`,'',NULL);");
			$this->db->query("UPDATE " . $this->mainTable . " SET `eventStartTime` = REPLACE(`eventStartTime`,'00:00:00',NULL);");
			$this->db->query("UPDATE " . $this->mainTable . " SET `eventEndTime` = REPLACE(`eventEndTime`,'00:00:00',NULL);");

			update_option( "events_calendar_db_version", $this->dbVersion);
		}
	}

	/**
	 * Initializes the WPEC options.
	 *
	 * This makes sure our options are in database with sensible values.
	 *
	 * There are two sets of options, the Events Calendar general options
	 * and the widget options.
	 */
	function initOptions() {

		$options = get_option('optionsEventsCalendar');
		if(!is_array($options)) $options = array();
		if (!isset($options['dateFormatWidget'])) $options['dateFormatWidget'] = 'm-d';
		if (!isset($options['timeFormatWidget'])) $options['timeFormatWidget'] = 'g:i a';
		if (!isset($options['dateFormatLarge'])) $options['dateFormatLarge'] = 'n/j/Y';
		if (!isset($options['timeFormatLarge'])) $options['timeFormatLarge'] = 'g:i a';
		if (!isset($options['timeStep'])) $options['timeStep'] = '30';
		if (!isset($options['adaptedCSS'])) $options['adaptedCSS'] = '';
		if (!isset($options['jqueryextremstatus'])) $options['jqueryextremstatus'] = 'false';
		if (!isset($options['todayCSS'])) $options['todayCSS'] = 'border:thin solid blue;font-weight: bold;';
		if (!isset($options['dayHasEventCSS'])) $options['dayHasEventCSS'] = 'color:red;';
		if (!isset($options['daynamelength'])) $options['daynamelength'] = '3';
		if (!isset($options['daynamelengthLarge'])) $options['daynamelengthLarge'] = '3';
		if (!isset($options['accessLevel'])) $options['accessLevel'] = 'level_10';
		update_option('optionsEventsCalendar', $options);

		$widget_options = get_option('widgetEventsCalendar');
		if (!is_array($widget_options) || empty($widget_options))
			$widget_options = array();
		if (!isset($widget_options['title']))
			$widget_options['title'] = __('Events Calendar', 'events-calendar');
		if (!isset($widget_options['type']))
			$widget_options['type'] = 'calendar';
		if (!isset($widget_options['listCount']))
			$widget_options['listCount'] = 5;
		update_option('widgetEventsCalendar', $widget_options);
	}

	/**
	 * Adds a new event into database.
	 *
	 * @param int 		$id 			the event id
	 * @param string 	$title 			the event title
	 * @param string or int	$location 		the event location, either locationId or text
	 * @param string 	$linkout 		URL to an external web site
	 * @param string 	$description 		description of the event
	 * @param date		$startDate 		date of the event. If empty, will be today.
	 * @param time 		$startTime 		start time of the event.
	 * @param date 		$endDate 		end date. if empty, will be same as start date.
	 * @param time 		$endTime		end time
	 * @param int 		$accessLevel 		who has access to this event
	 * @param int 		$postId 		post id if use activated it
	 * @param int 		$categoryId 	event category, must be valid
	 */
	function addEvent($title, $location, $linkout, $description, $startDate, $startTime, $endDate, $endTime, $accessLevel, $postID, $categoryId) {
		$postID = is_null($postID) ? "NULL" : "'$postID'";

		$location = is_null($location) ? "NULL" : "'$location'";
		$locationColumn = is_int(trim($location, "'")) ? "locationId" : "eventLocation";

		$description = is_null($description) ? "NULL" : "'$description'";
		$startDate = is_null($startDate) ? "NULL" : "'$startDate'";
		$endDate = is_null($endDate) ? "NULL" : "'$endDate'";
		$linkout = is_null($linkout) ? "NULL" : "'$linkout'";
		$startTime = is_null($startTime) ? "NULL" : "'$startTime'";
		$accessLevel = is_null($accessLevel) ? "NULL" : "'$accessLevel'";
		$endTime = is_null($endTime) ? "NULL" : "'$endTime'";
		$categoryId = is_null($categoryId) ? "NULL" : "'$categoryId'";

		$sql = "INSERT INTO `$this->mainTable` ("
			 ."`id`, `eventTitle`, `eventDescription`, `" . $locationColumn . "`, `eventLinkout`,`eventStartDate`, `eventStartTime`, `eventEndDate`, `eventEndTime`, `accessLevel`, `postID`, `categoryId`) "
			 ."VALUES ("
			 ."NULL , '$title', $description, $location, $linkout, $startDate, $startTime, $endDate, $endTime , $accessLevel, $postID, $categoryId);";

		$this->db->query($sql);
	}

	/**
	 * Updates an already existing event.
	 *
	 * @param int 		$id 			the event id
	 * @param string 	$title 			the event title
	 * @param string or int	$location 		the event location, either locationId or text
	 * @param string 	$linkout 		URL to an external web site
	 * @param string 	$description 		description of the event
	 * @param date 		$startDate 		date of the event. If empty, will be today.
	 * @param time 		$startTime 		start time of the event.
	 * @param date 		$endDate 		end date. if empty, will be same as start date.
	 * @param time 		$endTime 		end time
	 * @param int 		$accessLevel 		who can access this event
	 * @param int 		$postId 		post id if use activated it
	 * @param int 		$categoryId 	event category, must be valid
	 */
	function editEvent($id, $title, $location, $linkout, $description, $startDate, $startTime, $endDate, $endTime, $accessLevel, $postID, $categoryId) {

		// just to make sure
		if (empty($id))
			return;

		// todo get rid of the quotes here. don't need them anymore
		// since we are using wpdb->prepare()
		$postID = is_null($postID) ? "NULL" : "'$postID'";
		//$title = is_null($postID) ? "NULL" : "'$title'";
		$location = is_null($location) ? "NULL" : "'$location'";

		$location = is_null($location) ? "NULL" : "'$location'";
		$locationColumn = is_int(trim($location, "'")) ? "locationId" : "eventLocation";
		$resetOtherLocationColumn = is_int(trim($location, "'")) ? "eventLocation" : "locationId";

		$description = is_null($description) ? "NULL" : "'$description'";
		$startDate = is_null($startDate) ? "NULL" : "'$startDate'";
		$endDate = is_null($endDate) ? "NULL" : "'$endDate'";
		$linkout = is_null($linkout) ? "NULL" : "'$linkout'";
		$startTime = is_null($startTime) ? "NULL" : "'$startTime'";
		$accessLevel = is_null($accessLevel) ? "NULL" : "'$accessLevel'";
		$endTime = is_null($endTime) ? "NULL" : "'$endTime'";
		$categoryId = is_null($categoryId) ? "NULL" : "'$categoryId'";

		$sql = "UPDATE `$this->mainTable` SET "
			."`eventTitle` = '$title', "
			."`eventDescription` = $description, "
			."`" .  $locationColumn . "` = $location, "
			."`" .  $resetOtherLocationColumn . "` = NULL, "
			."`eventLinkout` = $linkout, "
			."`eventStartDate` = $startDate, "
			."`eventStartTime` = $startTime, "
			."`eventEndDate` = $endDate, "
			."`eventEndTime` = $endTime, "
			."`postID` = $postID, "
			."`accessLevel` = $accessLevel"
			."`categoryId` = $categoryId"
			." WHERE `id` = $id LIMIT 1;";

		$this->db->query($sql);
	}

	/**
	 * Deletes an event.
	 * @param int $id 		ID of the event to delete.
	 */
	function deleteEvent($id) {
		if (empty($id))
			return;

		$sql = "DELETE FROM `$this->mainTable` WHERE `id` = %d";
		$this->db->query($this->db->prepare($sql,(int)$id));
	}

	/**
	 * Returns the events for a specified date.
	 *
	 * @param date $d
	 * @return array 
	 */
	function getDaysEvents($d) {
		$sql = "SELECT *"
		 	. "  FROM `$this->mainTable`"
		  	. " WHERE `eventStartDate` <= '$d'"
			. "   AND `eventEndDate` >= '$d'"
			. " ORDER BY `eventStartTime`, `eventEndTime`;";
		return $this->db->get_results($sql);
	}

	/**
	 * CATEGORY FUNCTIONS
	 */

	/**
	 * Creates a specific category, must not exist before
	 *
	 * @param int $id
	 */
	function addCategory($name) {
		$sql = "INSERT INTO `$this->categoryTable` "
		     . "(name) "
		     . "VALUES (%s);";
		return $this->db->query($this->db->prepare($sql, $name));
	}

	/**
	 * Edit a specific category.
	 * $newName must not exist on beforehand.
	 *
	 * @param int $id
	 * @param string $newName
	 * @return bool or number of rows acted upon
	 */
	function editCategory($id, $newName) {
		$newName = trim($newName);
		if (strlen($newName) == 0) {
			return False;
		}

		$sql = "SELECT COUNT(*) FROM `$this->categoryTable` "
		     . "WHERE name = %s";
		$count = $this->db->get_var($this->db->prepare($sql, $newName, $id));

		if ($count == 0) {
			$sql = "UPDATE`$this->categoryTable` "
			     . "SET name = %s "
			     . "WHERE id = %d;";

			return $this->db->query($this->db->prepare($sql, $newName, $id));
		} else {
			return False;
		}
	}

	/**
	 * Returns a specific category
	 *
	 * @param int $id
	 * @return string
	 */
	function getCategory($id) {
		$sql = "SELECT name FROM `$this->categoryTable` WHERE id = " . intval($id);
		return $this->db->get_results($sql);
	}

	/**
	 * Returns a list of all categories
	 *
	 * @return array
	 */
	function getCategoryList () {
		$sql = "SELECT * FROM `$this->categoryTable`;";
		return $this->db->get_results($sql);
	}

	/**
	 * Deletes a specific category. 
	 * Will check if any posts refer to before deletion.
	 *
	 * @param int $id
	 * @return bool or number of rows acted upon
	 */
	function deleteCategory($id) {
		$sql = "SELECT COUNT(*) FROM `$this->mainTable` WHERE locationId = " . intval($id);
		$count = $this->db->get_var($sql);

		if ($count == 0) {
			$sql = "DELETE FROM `$this->categoryTable` WHERE id = " . intval($id);
			return $this->db->query($sql);
		} else {
			return False;
		}
	}

	/**
	 * LOCATION FUNCTIONS
	 */

	/**
	 * Creates a specific location, must not exist before
	 *
	 * @param int $id
	 * @return int number of rows acted upon
	 */
	function addLocation($name, $description = null) {
		$sql = "INSERT INTO `$this->locationTable` "
		     . "(name, description) "
		     . "VALUES (%s, %s);";
		return $this->db->query($this->db->prepare($sql, $name, $description));
	}

	/**
	 * Edit a specific location.
	 * $newName must not exist on beforehand.
	 *
	 * @param int $id
	 * @param string $newName
	 */
	function editLocation($id, $newName, $newDescription) {
		$newName = trim($newName);
		if (strlen($newName) == 0) {
			return False;
		}

		$sql = "SELECT COUNT(*) FROM `$this->categoryTable` "
		     . "WHERE name = %s";
		$count = $this->db->get_var($this->db->prepare($sql, $newName, $id));

		if ($count == 0) {
			$sql = "UPDATE `$this->categoryTable` "
			     . "SET name = %s, "
			     . "SET description = %s"
			     . "WHERE id = %d;";

			return $this->db->query($this->db->prepare($sql, $newName, $newDescription, $id));
		} else {
			return False;
		}
	}

	/**
	 * Returns a specific location
	 *
	 * @param int $id
	 * @return string
	 */
	function getLocation($id) {
		$sql = "SELECT * FROM `$this->locationTable` WHERE id = " . intval($id);
		return $this->db->get_results($sql);
	}

	/**
	 * Returns a list of all locations
	 *
	 * @return array
	 */
	function getLocationList () {
		$sql = "SELECT * FROM `$this->locationTable`;";
		return $this->db->get_results($sql);
	}

	/**
	 * Deletes a specific location.
	 * Will check if any posts refer to it, before deletion.
	 *
	 * @param int $id
	 * @return bool or number of rows acted upon
	 */
	function deleteLocation($id) {
		$sql = "SELECT COUNT(*) FROM `$this->mainTable` WHERE locationId = " . intval($id);
		$count = $this->db->get_var($sql);

		if ($count == 0) {
			$sql = "DELETE FROM `$this->categoryTable` WHERE id = " . intval($id);
			return $this->db->query($sql);
		} else {
			return False;
		}
	}

	/**
	 * Returns a specific event.
	 *
	 * @param int $id
	 * @return array
	 */
	function getEvent($id) {
		$sql = "SELECT * FROM `$this->mainTable` WHERE `id` = $id LIMIT 1;";
		return $this->db->get_results($sql);
	}

	/**
	 * Returns upcoming events.
	 * @param int $num 		number of events to retrieve
	 * @return array
	 */
	function getUpcomingEvents($num = 5) {
		$dt = date('Y-m-d');
		$sql = "SELECT *"
			. "  FROM `$this->mainTable`"
			. " WHERE `eventStartDate` >= '$dt'"
			. "    OR `eventEndDate` >= '$dt'"
			. " ORDER BY eventStartDate, eventStartTime LIMIT $num";
		return $this->db->get_results($sql);
	}

	/**
	 * Returns the latest post id.
	 *
	 * @todo Why should we call this. Latest post could be anything!!!
	 *
	 * @return array
	 */
	function getLatestPost() {
		$sql = "SELECT `id` FROM `$this->postsTable` ORDER BY `id` DESC LIMIT 1;";
		return $this->db->get_results($sql);
	}
}
endif;
?>
