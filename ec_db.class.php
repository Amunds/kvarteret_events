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
 * Helper function to test if a string is an integer
 *
 * @param string $str
 * @return bool
 */
function string_is_int($str) {
	if ((string)$str == (string)(int)$str) {
		return true;
	} else {
		return false;
	}
}

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

	var $arrangerTable;

	var $arrangerUserTable;

	var $userTable;

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
		$this->dbVersion = "112";
		$this->db = $wpdb;
		$this->mainTable = $this->db->prefix . 'eventscalendar_main';
		
		// FIXME why is this needed? Is it for backward compatibility?
		$this->mainTableCaps = $this->db->prefix . 'EventsCalendar_main';
		if ($this->db->get_var("show tables like '$this->mainTableCaps'") == $this->mainTableCaps)
			$this->mainTable = $this->mainTableCaps;

		$this->postsTable = $this->db->prefix . 'posts';
		$this->categoryTable = $this->db->prefix . 'eventscalendar_category';
		$this->locationTable = $this->db->prefix . 'eventscalendar_location';
		$this->arrangerTable = $this->db->prefix . 'eventscalendar_arranger';
		$this->arrangerUserTable = $this->db->prefix . 'eventscalendar_arrangeruser';
		$this->userTable = $this->db->prefix . 'users';
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
		 * Table definition and creation routines for the arranger table
		 */

		$sqlArrangerTable = "CREATE TABLE " . $this->arrangerTable . " (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			name varchar(255) CHARACTER SET utf8 NOT NULL,
			description text CHARACTER SET utf8,
			url varchar(512) CHARACTER SET utf8,
			UNIQUE (name),
			PRIMARY KEY  id (id)
			);";

		if ($this->db->get_var("show tables like '$this->arrangerTable'") != $this->arrangerTable) {

			require_once(ABSPATH . "wp-admin/upgrade-functions.php");
			dbDelta($sqlArrangerTable);

			// Request whithout CHARACTER SET utf8 if the CREATE TABLE failed
			if ($this->db->get_var("show tables like '$this->arrangerTable'") != $this->arrangerTable ) {
				$sql = str_replace("CHARACTER SET utf8 ","",$sqlArrangerTable);
				dbDelta($sql);
			}
		}

		/**
		 * Table definition and creation routines for the arrangeruser table
		 * This will restrict users to only use the arranger they've been assigned
		 */

		$sqlArrangerUserTable = "CREATE TABLE " . $this->arrangerUserTable . " (
			userId int NOT NULL,
			arrangerId int NOT NULL,
			FOREIGN KEY (userId) REFERENCES " . $this->userTable . " (ID),
			FOREIGN KEY (arrangerId) REFERENCES " . $this->arrangerTable . " (id),
			PRIMARY KEY  id (userId, arrangerId)
			);";

		if ($this->db->get_var("show tables like '$this->arrangerUserTable'") != $this->arrangerUserTable) {

			require_once(ABSPATH . "wp-admin/upgrade-functions.php");
			dbDelta($sqlArrangerUserTable);

			// Request whithout CHARACTER SET utf8 if the CREATE TABLE failed
			if ($this->db->get_var("show tables like '$this->arrangerUserTable'") != $this->arrangerUserTable ) {
				$sql = str_replace("CHARACTER SET utf8 ","",$sqlArrangerUserTable);
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
			arrangerId integer NOT NULL,
			PRIMARY KEY  id (id),
			INDEX (locationId),
			INDEX (categoryId),
			INDEX (arrangerId),
			FOREIGN KEY (locationId) REFERENCES " . $this->locationTable . "(id),
			FOREIGN KEY (categoryId) REFERENCES " . $this->categoryTable . "(id),
			FOREIGN KEY (arrangerId) REFERENCES " . $this->arrangerTable . "(id)
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
			dbDelta($sqlArrangerTable);
			dbDelta($sqlArrangerUserTable);
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
	function addEvent($title, $location, $linkout, $description, $startDate, $startTime, $endDate, $endTime, $accessLevel, $postID, $categoryId, $arrangerId) {
		$postID = is_null($postID) ? "NULL" : "'$postID'";

		// The integer check has to be made _before_ we treat $location as a column value, makes life easier
		$locationColumn = string_is_int(trim($location)) ? "locationId" : "eventLocation";
		$location = is_null($location) ? "NULL" : "'$location'";

		$description = is_null($description) ? "NULL" : "'$description'";
		$startDate = is_null($startDate) ? "NULL" : "'$startDate'";
		$endDate = is_null($endDate) ? "NULL" : "'$endDate'";
		$linkout = is_null($linkout) ? "NULL" : "'$linkout'";
		$startTime = is_null($startTime) ? "NULL" : "'$startTime'";
		$accessLevel = is_null($accessLevel) ? "NULL" : "'$accessLevel'";
		$endTime = is_null($endTime) ? "NULL" : "'$endTime'";
		$categoryId = is_null($categoryId) ? "NULL" : "'$categoryId'";
		$arrangerId = is_null($arrangerId) ? "NULL" : "'$arrangerId'";

		$sql = "INSERT INTO `$this->mainTable` ("
			 ."`id`, `eventTitle`, `eventDescription`, `" . $locationColumn . "`, `eventLinkout`,`eventStartDate`, `eventStartTime`, `eventEndDate`, `eventEndTime`, `accessLevel`, `postID`, `categoryId`, `arrangerId`) "
			 ."VALUES ("
			 ."NULL , '$title', $description, $location, $linkout, $startDate, $startTime, $endDate, $endTime , $accessLevel, $postID, $categoryId, $arrangerId);";

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
	function editEvent($id, $title, $location, $linkout, $description, $startDate, $startTime, $endDate, $endTime, $accessLevel, $postID, $categoryId, $arrangerId) {

		// just to make sure
		if (empty($id))
			return;

		// todo get rid of the quotes here. don't need them anymore
		// since we are using wpdb->prepare()
		$postID = is_null($postID) ? "NULL" : "'$postID'";
		//$title = is_null($postID) ? "NULL" : "'$title'";

		// The integer check has to be made _before_ we treat $location as a column value, makes life easier
		$locationColumn = string_is_int(trim($location)) ? "locationId" : "eventLocation";
		$location = is_null($location) ? "NULL" : "'$location'";
		$resetOtherLocationColumn = ($locationColumn == 'locationId') ? "eventLocation" : "locationId";

		$description = is_null($description) ? "NULL" : "'$description'";
		$startDate = is_null($startDate) ? "NULL" : "'$startDate'";
		$endDate = is_null($endDate) ? "NULL" : "'$endDate'";
		$linkout = is_null($linkout) ? "NULL" : "'$linkout'";
		$startTime = is_null($startTime) ? "NULL" : "'$startTime'";
		$accessLevel = is_null($accessLevel) ? "NULL" : "'$accessLevel'";
		$endTime = is_null($endTime) ? "NULL" : "'$endTime'";
		$categoryId = is_null($categoryId) ? "NULL" : "'$categoryId'";
		$arrangerId = is_null($arrangerId) ? "NULL" : "'$arrangerId'";

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
			."`accessLevel` = $accessLevel, "
			."`categoryId` = $categoryId, "
			."`arrangerId` = $arrangerId"
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
	 * @param string $name
	 * @return int The id of the inserted row
	 */
	function addCategory($name) {
		$sql = "INSERT INTO `$this->categoryTable` "
		     . "(name) "
		     . "VALUES (%s);";
		$this->db->query($this->db->prepare($sql, $name));
		return $this->db->insert_row;
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
	 * @param string $name
	 * @param string $description
	 * @return int number of rows acted upon
	 */
	function addLocation($name, $description = null) {
		$sql = "INSERT INTO `$this->locationTable` "
		     . "(name, description) "
		     . "VALUES (%s, %s);";
		$this->db->query($this->db->prepare($sql, $name, $description));
		return $this->db->insert_id;
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

		$sql = "SELECT COUNT(*) FROM `$this->locationTable` "
		     . "WHERE name = %s";
		$count = $this->db->get_var($this->db->prepare($sql, $newName, $id));

		if ($count == 0) {
			$sql = "UPDATE `$this->locationTable` "
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
			$sql = "DELETE FROM `$this->locationTable` WHERE id = " . intval($id);
			return $this->db->query($sql);
		} else {
			return False;
		}
	}

	/**
	 * ARRANGER FUNCTIONS
	 */

	/**
	 * Creates a specific arranger, must not exist before
	 *
	 * @param string $name
	 * @param string $description
	 * @param string $url
	 * @return int The id of the inserted row
	 */
	function addArranger($name, $description = null, $url = null) {
		$sql = "INSERT INTO `$this->arrangerTable` "
		     . "(name, description, url) "
		     . "VALUES (%s, %s, %s);";
		$this->db->query($this->db->prepare($sql, $name, $description, $url));
		return $this->db->insert_id;
	}

	/**
	 * Edit a specific location.
	 * $newName must not exist on beforehand.
	 *
	 * @param int $id
	 * @param string $newName
	 * @param string $newDescription
	 * @param string $newUrl
	 */
	function editArranger($id, $newName, $newDescription, $newUrl) {
		$newName = trim($newName);
		if (strlen($newName) == 0) {
			return False;
		}

		$sql = "SELECT COUNT(*) FROM `$this->arrangerTable` "
		     . "WHERE name = %s";
		$count = $this->db->get_var($this->db->prepare($sql, $newName, $id));

		if ($count == 0) {
			$sql = "UPDATE `$this->arrangerTable` "
			     . "SET name = %s, "
			     . "SET description = %s, "
			     . "SET url = %s "
			     . "WHERE id = %d;";

			return $this->db->query($this->db->prepare($sql, $newName, $newDescription, $newUrl, $id));
		} else {
			return False;
		}
	}

	/**
	 * Returns a specific arranger
	 *
	 * @param int $id
	 * @return string
	 */
	function getArranger($id) {
		$sql = "SELECT * FROM `$this->arrangerTable` WHERE id = " . intval($id);
		return $this->db->get_results($sql);
	}

	/**
	 * Returns a list of all arrangers
	 *
	 * @return array
	 */
	function getArrangerList () {
		$sql = "SELECT * FROM `$this->arrangerTable`;";
		return $this->db->get_results($sql);
	}

	/**
	 * Deletes a specific arranger.
	 * Will check if any posts or user relationships refer to it, before deletion.
	 *
	 * @param int $id
	 * @return bool or number of rows acted upon
	 */
	function deleteArranger($id) {
		$sql = "SELECT COUNT(*) FROM `$this->mainTable` WHERE arrangerId = " . intval($id);
		$count = $this->db->get_var($sql);

		$countUserRelation = count($this->getArrangerUserRelation($id, 'arranger'));

		if (($count == 0) && ($countUserRelation == 0)){
			$sql = "DELETE FROM `$this->arrangerTable` WHERE id = " . intval($id);
			return $this->db->query($sql);
		} else {
			return False;
		}
	}

	/**
	 * Makes a relationship between a wordpress user and an arranger
	 * Will check if arranger and user exist before the query is executed.
	 *
	 * @param	int	$arrangerId	A valid arrangerId
	 * @param	int	$userId	A valid userId
	 */
	function addArrangerUser ($arrangerId, $userId) {
		$sql = "INSERT INTO `$this->arrangerUserTable` (arrangerId, userId) VALUES (%d, %d)";

		if (!get_userdata($userId) || (count($this->getArranger($arrangerId)) == 0)) {
			return False;
		} else {
			return $this->db->query($this->db->prepare($sql, $arrangerId, $userId));
		}
	}

	/**
	 * Delets a relation between a user and an arranger
	 *
	 * @param	int	$arrangerId	A valid arrangerId
	 * @param	int	$userId	A valid userId
	 * @return	int	Number of rows that where deleted
	 */
	function deleteArrangerUser ($arrangerId, $userId) {
		// This one could get a little troublesome when a user is removed from wordpress.
		$sql = "DELETE FROM `$this->arrangerUserTable` WHERE arrangerId = %d AND userId = %d";
		return $this->db->query($this->db->prepare($sql, $arrangerId, $userId));
	}

	/**
	 * Will fetch a list of either the arrangers a user is related to
	 * or a list users related to an arranger.
	 *
	 * @param	int	$id	A valid id of either user or arranger
	 * @param	string	$idType	The kind of id you're supplying, can either be user or arranger.
	 * @return	array or bool	you'll get a bool if $idType is not recognized.
	 */
	function getArrangerUserRelation ($id, $idType) {
		if ($idType == 'user') {
			$sql = "SELECT * FROM `$this->arrangerUserTable` WHERE userId = %d";
		} else if ($idType == 'arranger') {
			$sql = "SELECT * FROM `$this->arrangerUserTable` WHERE arrangerId = %d";
		}

		if (isset($sql)) {
			return $this->db->get_results($this->db->prepare($sql, $id));
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
	 * Returns a list of events based on a set of filters, eg. date, location, category etc.
	 * Will order by ascending dates.
	 *
	 * @param array $filter
	 * @param int $limit
	 * @param int offset
	 * @return array
	 */
	function getFilteredEventList ($filter, $limit, $offset) {
		/*
		 * filter can contain the following keys:
		 * locationId => array(id1, id2, ...) or locationId => id1
		 * categoryId => array(id1, id2, ...) or categoryId => id1
		 * arrangerId => array(id1, id2, ...) or arrangerId => id1
		 * eventDateStart => array('date' => 'Y-m-d' format, 'req' => ('<', '<=', '=', '>=' or '>')
		 * eventDateEnd => array('date' => 'Y-m-d' format, 'req' => ('<', '<=', '=', '>=' or '>')
		 */
		
		/*
		 * example
		 * $filter = array(
		 *  // We pick all events which end on this specific date (2010-10-19) and further
		 *  'eventDateEnd' => array('date' => '2010-10-19', 'req' => '>='), 
		 *  'locationId' => 1,
		 *  'categoryId' => array(3,2)
		 * );
		 */
		
		$legalRequirements = array('<', '<=', '=', '>=', '>');
		
		if (!is_array($filter)) {
			$filter = array();
		}
		
		$input = array();
		
		$sql = "SELECT * FROM `$this->mainTable`";
		
		$hasFilter = false;
		if (count($filter) > 0) {
			$sql .= " WHERE ";
			$hasFilter = true;
		}
		
		if (isset($filter['locationId'])) {
			$e = $filter['locationId'];

			if (is_array($e) && !empty($e)) {
				$tmpSql = "";
				foreach ($e as $v) {
					$tempSql .= "%d,";
					$input[] = intval($v);
				}
				
				$sql .= "(locationId IN (" . substr($tempSql, 0, -1) . ")) AND ";
				unset($tempSql);
			} else {
				$sql .= "(locationId = %d) AND ";
				$input[] = intval($e);
			}
			unset($e);
		}
		
		if (isset($filter['categoryId'])) {
			$e = $filter['categoryId'];

			if (is_array($e) && !empty($e)) {
				$tmpSql = "";
				foreach ($e as $v) {
					$tempSql .= "%d,";
					$input[] = intval($v);
				}

				$sql .= "(categoryId IN (" . substr($tempSql, 0, -1) . ")) AND ";
				unset($tempSql);
			} else {
				$sql .= "(categoryId = %d) AND ";
				$input[] = intval($e);
			}
			unset($e);
		}

		if (isset($filter['arrangerId'])) {
			$e = $filter['arrangerId'];

			if (is_array($e) && !empty($e)) {
				$tmpSql = "";
				foreach ($e as $v) {
					$tempSql .= "%d,";
					$input[] = intval($v);
				}

				$sql .= "(arrangerId IN (" . substr($tempSql, 0, -1) . ")) AND ";
				unset($tempSql);
			} else {
				$sql .= "(arrangerId = %d) AND ";
				$input[] = intval($e);
			}
			unset($e);
		}

		if (isset($filter['eventDateStart']) && is_array($filter['eventDateStart'])) {
			$e = $filter['eventDateStart'];

			if (isset($e['date']) && isset($e['req']) && in_array($e['req'], $legalRequirements)) {
				$sql .= "(eventDateStart " . $e['req'] . " %s)";
				$input[] = strval($e['date']);
			}
			unset($e);
		}
		
		if (isset($filter['eventDateEnd']) && is_array($filter['eventDateEnd'])) {
			$e = $filter['eventDateEnd'];

			if (isset($e['date']) && isset($e['req']) && in_array($e['req'], $legalRequirements)) {
				$sql .= "(eventDateEnd " . $e['req'] . " %s)";
				$input[] = strval($e['date']);
			}
			unset($e);
		}
		
		$sql .= " ORDER BY eventStartDate ASC, eventStartTime ASC";
		
		if ($limit > 0) {
			$sql .= " LIMIT " . intval($limit);
			if ($offset > 0) {
				$sql .= " OFFSET " . intval($offset);
			}
		}
		
		if ($hasFilter) {
			$sql = $this->db->prepare($sql, $input);
		}
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
