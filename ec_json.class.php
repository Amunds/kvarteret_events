<?php

if(!class_exists('EC_JSON')):

require_once(EVENTSCALENDARCLASSPATH . '/ec_db.class.php');

class EC_JSON {

	/**
	 * Holds the EC_DB object.
	 * @var Object
	 * @access private
	 */
	private $db;

	private $nonPrivilegeFunctions = array();
	private $arrangerFunctions = array();
	private $administratorFunctions = array();

	private $userData;

	/**
	 * Constructor
	 * 
	 * Will call a function if it's referenced in $_GET['EC_json']
	 * and is a valid function at the current userlevel.
	 * 
	 * get-functions will echo a json-string of the final result.
	 */
	public function __construct () {
		global $user_ID;
		
		get_currentuserinfo();
		
		if ($user_ID != '') {
			$this->userData = get_userdata($user_ID);
		}
		
		$this->db = new EC_DB();

		header('Content-Type: application/json');

		$this->nonPrivilegeFunctions = array(
			'getEvent',
			'getUpcomingEvents',
			'getFilteredEventList',
			'getLocation',
			'getLocationList',
			'getCategory',
			'getCategoryList',
			'getArranger',
			'getArrangerList',
		);

		$this->arrangerFunctions = array(
			'addEvent',
			'editEvent',
		);

		$this->administratorFunctions = array(
			'addLocation',
			'editLocation',
			'addCategory',
			'editCategory',
			'addArranger',
			'editArranger',
		);

		$action = strval($_GET['EC_json']);

		if (in_array($action, $this->nonPrivilegeFunctions)) {
			$this->$action();
		} else if (is_user_logged_in() && in_array($action, $this->arrangerFunctions)) {
			$this->$action();
		} else if (is_user_logged_in() && in_array($action, $this->administratorFunctions)) {
			$this->$action();
		}
	}

	/**
	 * Will return a associative array of a specified
	 * error message and error code
	 *
	 * @param string $msg Message
	 * @param int $code Error code
	 * @return array
	 */
	private function error ($msg, $code) {
		$arr = array(
			'error' => array(
				'code' => intval($code),
				'msg' => strval($msg),
			),
		);

		return $arr;
	}

	private function prepareEvent ($event) {
		if ($event->arrangerId > 0) {
			$tmp = $this->db->getArranger($event->arrangerId);
			if (isset($tmp[0])) {
				$event->arranger = $tmp[0]->name;
			}
			unset($tmp);
		}

		if ($event->categoryId > 0) {
			$tmp = $this->db->getCategory($event->categoryId);
			if (isset($tmp[0])) {
				$event->category = $tmp[0]->name;
			}
			unset($tmp);
		}

		if (empty($event->eventLocation) && ($event->locationId > 0)) {
			$tmp = $this->db->getLocation($event->locationId);
			if (isset($tmp[0])) {
				$event->eventLocation = $tmp[0]->name;
			}
		}

		return $event;
	}

	private function getEvent($echo = true) {
		$id = intval($_GET['EC_id']);

		$event = $this->db->getEvent($id);

		if (count($event) > 0) {
			$event[0] = $this->prepareEvent($event[0]);
		}

		if ($echo) {
			echo json_encode($event);
		} else {
			return $event;
		}
	}

	private function getLocationList ($echo = true) {
		$list = $this->db->getLocationList();

		if ($echo) {
			echo json_encode($list);
		} else {
			return $list;
		}
	}

	private function getCategoryList ($echo = true) {
		$list = $this->db->getCategoryList();

		if ($echo) {
			echo json_encode($list);
		} else {
			return $list;
		}
	}

	private function getArrangerList ($echo = true) {
		$list = $this->db->getArrangerList();

		if ($echo) {
			echo json_encode($list);
		} else {
			return $list;
		}
	}

	private function getUpcomingEvents () {
		$limit = 10;
		if (isset($_GET['EC_limit'])) {
			$limit = intval($limit);
		}

		$events = $this->db->getUpcomingEvents($limit);

		foreach ($events as &$event) {
			$event = $this->prepareEvent($event);
		}

		echo json_encode($events);
	}

	private function getFilteredEventList () {
		$krav = array();

		if (isset($_GET['EC_startDate'])) {
			// Must be a valid MySQL date
			$krav['eventStartDate'] = array(
				'date' => strval($_GET['EC_startDate']),
				'req' => '>=',
			);
		} else {
			$krav['eventStartDate'] = array(
				'date' => date('Y-m-d'),
				'req' => '>=',
			);
		}

		if (isset($_GET['EC_endDate'])) {
			// Must be a valid MySQL date
			$krav['eventEndDate'] = array(
				'date' => strval($_GET['EC_endDate']),
				'req' => '<=',
			);
		}

		if (isset($_GET['EC_locationId'])) {
			// locationId kan være adskilt med ',' hvis det ikke er en
			// array fra før ala EC_locationId[] = 1, EC_locationId[] = 2, osv.
			if (is_array($_GET['EC_locationId'])) {
				$krav['locationId'] = $_GET['EC_locationId'];
			} else {
				$krav['locationId'] = explode(',', $_GET['EC_locationId']);
			}
		}

		if (isset($_GET['EC_categoryId'])) {
			// locationId kan være adskilt med ',' hvis det ikke er en array fra før
			if (is_array($_GET['EC_categoryId'])) {
				$krav['categoryId'] = $_GET['EC_categoryId'];
			} else {
				$krav['categoryId'] = explode(',', $_GET['EC_categoryId']);
			}
		}

		if (isset($_GET['EC_arrangerId'])) {
			// locationId kan være adskilt med ',' hvis det ikke er en array fra før
			if (is_array($_GET['EC_arrangerId'])) {
				$krav['arrangerId'] = $_GET['EC_arrangerId'];
			} else {
				$krav['arrangerId'] = explode(',', $_GET['EC_arrangerId']);
			}
		}

		$limit = 20;
		if (isset($_GET['EC_limit'])) {
			$limit = intval($_GET['EC_limit']);
		}

		$offset = 0;
		if (isset($_GET['EC_offset'])) {
			$offset = intval($_GET['EC_offset']);
		}

		$events = $this->db->getFilteredEventList($krav, $limit, $offset);

		foreach ($events as &$event) {
			$event = $this->prepareEvent($event);
		}

		echo json_encode($events);
	}

	private function addLocation () {
		/**
		 * Demands that $_POST['EC_name'] and $_POST['EC_description'] be set
		 */

		if (!isset($_POST['EC_name']) || !isset($_POST['EC_description'])) {
			return False;
		}

		$res = $this->db->addLocation (strval($_POST['EC_name']), strval($_POST['EC_description']));
	}

	private function addCategory () {
		/**
		 * Demands that $_POST['EC_name'] be set
		 */

		if (!isset($_POST['EC_name'])) {
			return False;
		}

		$res = $this->db->addCategory (strval($_POST['EC_name']));
	}

	private function addArranger () {
		/**
		 * Demands that $_POST['EC_name'] and $_POST['EC_description'] be set
		 */

		if (!isset($_POST['EC_name']) || !isset($_POST['EC_description'])) {
			return False;
		}

		$res = $this->db->addArranger (strval($_POST['EC_name']), strval($_POST['EC_description']));
	}
}

endif;
