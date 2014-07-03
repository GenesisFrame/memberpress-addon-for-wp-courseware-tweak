<?php
/*
 * Plugin Name: WP Courseware - MemberPress Add On
 * Version: 1.0
 * Plugin URI: http://flyplugins.com
 * Description: The official extension for WP Courseware to add support for the MemberPress membership plugin for WordPress.
 * Author: Fly Plugins
 * Author URI: http://flyplugins.com
 */
/*
 Copyright 2013 Fly Plugins

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

 http://www.apache.org/licenses/LICENSE-2.0

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
 */


// Main parent class
include_once 'class_members.inc.php';

// Hook to load the class
add_action('init', 'WPCW_MemberPress_init',1);

/**
 * Initialise the membership plugin, only loaded if WP Courseware 
 * exists and is loading correctly.
 */
function WPCW_MemberPress_init()
{
	$item = new WPCW_Members_MemberPress();
	
	// Check for WP Courseware
	if (!$item->found_wpcourseware()) {
		$item->attach_showWPCWNotDetectedMessage();
		return;
	}
	
	// Not found the membership tool
	if (!$item->found_membershipTool()) {
		$item->attach_showToolNotDetectedMessage();
		return;
	}
	
	// Found the tool and WP Courseware, attach.
	$item->attachToTools();
}


/**
 * Membership class that handles the specifics of the MembersPress WordPress plugin and
 * handling the data for levels for that plugin.
 */
class WPCW_Members_MemberPress extends WPCW_Members
{
	const GLUE_VERSION  = 1.00; 
	const EXTENSION_NAME = 'MemberPress';
	const EXTENSION_ID = 'WPCW_memberpress';
	
	/**
	 * Main constructor for this class.
	 */
	function __construct()
	{
		// Initialise using the parent constructor 
		parent::__construct(WPCW_Members_MemberPress::EXTENSION_NAME, WPCW_Members_MemberPress::EXTENSION_ID, WPCW_Members_MemberPress::GLUE_VERSION);
	}
	
	
	/**
	 * Get the membership levels for this specific membership plugin. (id => array (of details))
	 */
	protected function getMembershipLevels()
	{
	
	$args=array(
  		'post_type' => 'memberpressproduct',
  		'post_status' => 'publish',
  		'numberposts' => -1
	);
	$levelData = get_posts($args);
	
		if ($levelData && count($levelData) > 0)
		{
			$levelDataStructured = array();
			
			// Format the data in a way that we expect and can process
			foreach ($levelData as $levelDatum)
			{
				
				$levelItem = array();
				$levelItem['name'] 	= $levelDatum->post_title;
				$levelItem['id'] 	= $levelDatum->ID;
				$levelItem['raw'] 	= $levelDatum;
				
					
				$levelDataStructured[$levelItem['id']] = $levelItem;
				
			}
			
			return $levelDataStructured;
		}
		
		return false;
	}
	
	
	/**
	 * Function called to attach hooks for handling when a user is updated or created.
	 */	
	
	protected function attach_updateUserCourseAccess()
	{
		// Events called whenever the user levels are changed, which updates the user access.
		add_action('mepr-txn-store', 		array($this, 'handle_updateUserCourseAccess'),10);
	}

	/**
	 * Function just for handling the membership callback, to interpret the parameters
	 * for the class to take over.
	 * 
	 * @param Integer $id The ID if the user being changed.
	 * @param Array $levels The list of levels for the user.
	 */
	public function handle_updateUserCourseAccess($txn)
	{
		// Get all user levels, with IDs.
		$user = $txn->user(); //Load the user this transaction belongs to
		$productList = $user->active_product_subscriptions('ids'); //Returns an array of Product ID's the user has purchased and is paid up on.
		// Get user ID from transaction
		$userid = $txn->user_id;

		// Over to the parent class to handle the sync of data.
		parent::handle_courseSync($userid, $productList);
	}
		
	
	/**
	 * Detect presence of the membership plugin.
	 */
	public function found_membershipTool()
	{
		return function_exists('mepr_plugin_info');
	}
}
?>