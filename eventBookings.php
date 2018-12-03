<?php

# Class to create a simple event bookings system


require_once ('frontControllerApplication.php');
class eventBookings extends frontControllerApplication
{
	# Function to assign defaults additional to the general application defaults
	public function defaults ()
	{
		# Specify available arguments as defaults or as NULL (to represent a required argument)
		$defaults = array (
			'applicationName' => 'Event booking',
			'div' => strtolower (__CLASS__),
			'database' => 'eventbookings',
			'table' => 'events',
			'databaseStrictWhere' => true,
			'administrators' => true,
			'useEditing' => true,
			'useSettings' => true,
			'settingsTableExplodeTextarea' => array ('sessions', 'projects'),
			'useCamUniLookup' => false,
		);
		
		# Return the defaults
		return $defaults;
	}
	
	
	# Function assign additional actions
	public function actions ()
	{
		# Specify additional actions
		$actions = array (
			'home' => array (
				'description' => false,
				'url' => '',
				'tab' => 'Home',
				'icon' => 'house',
			),
			'add' => array (
				'description' => 'Add new event form',
				'url' => 'add.html',
				'tab' => 'Create event',
				'icon' => 'add',
				'administrator' => true,
			),
			'events' => array (
				'description' => 'Event settings',
				'url' => 'data/events/',
				'tab' => 'Event settings',
				'icon' => 'page_edit',
				'administrator' => true,
			),
			'forms' => array (
				'description' => 'View event forms',
				'url' => 'forms/',
				'tab' => 'View forms',
				'icon' => 'application_form',
				'administrator' => true,
			),
			'form' => array (
				'description' => false,
				'url' => 'forms/',
				'usetab' => 'forms',
			),
			'editing' => array (	// Inteded for download
				'description' => 'Bookings',
				'url' => 'data/',
				'tab' => 'Bookings',
				'icon' => 'application_view_list',
				'administrator' => true,
			),
		);
		
		# Return the actions
		return $actions;
	}
	
	
	# Database structure definition
	public function databaseStructure ()
	{
		return "
			CREATE TABLE `administrators` (
			  `username` varchar(255) COLLATE utf8_unicode_ci PRIMARY KEY NOT NULL COMMENT 'Username',
			  `active` enum('','Yes','No') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Yes' COMMENT 'Currently active?'
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='System administrators';
			
			CREATE TABLE `events` (
			  `id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT COMMENT 'Event #',
			  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Event name',
			  `form` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Form template',
			  `introductionHtml` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Introductory text',
			  `formCompleteText` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Form complete text',
			  `confirmationIntroductoryText` text COLLATE utf8mb4_unicode_ci COMMENT 'Confirmation e-mail introductory text',
			  `recipientEmail` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Submissions e-mail address',
			  `dataProtectionHtml` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Data protection note',
			  `ticketLimit` int(11) DEFAULT NULL COMMENT 'Ticket limit',
			  `guestsLimit` int(11) DEFAULT NULL COMMENT 'Guests limit',
			  `guestsLimitTotalsField` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Field for determining guest ticket totals for limit',
			  `soldOutMessage` VARCHAR(255) NOT NULL DEFAULT 'We regret that all tickets have now been booked. We hope you will be able to join us at another event in due course.' COMMENT 'Sold out message'
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table of events';
			
			CREATE TABLE `settings` (
			  `id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT COMMENT 'Automatic key (ignored)',
			  `feedbackRecipient` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Recipient e-mail'
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Settings';
		";
	}
	
	
	# Additional processing
	public function mainPreActions ()
	{
		# Get the list of events
		$this->events = $this->databaseConnection->select ($this->settings['database'], 'events');
		
		# Disable tabs if loading from a URL outside the system tree
		if (substr ($_SERVER['REQUEST_URI'], 0, strlen ($this->baseUrl)) != $this->baseUrl) {
			$this->settings['disableTabs'] = true;
		}
		
		# When editing events, highlight the event settings tab, rather than the (natural) editing tab
		if (preg_match ("|^{$this->baseUrl}/data/events/|", $_SERVER['REQUEST_URI'])) {
			$this->tabForced = 'events';
		}
		
	}
	
	
	# Welcome screen
	public function home ()
	{
		# Start the HTML
		$html  = "\n<p><strong>Welcome to the event booking system.</strong></p>";
		
		# Create an actions table
		$table = array ();
		foreach ($this->events as $eventId => $event) {
			$table[$eventId] = array (
				'Name'					=> $event['name'],
				'Event settings'		=> "<a href=\"{$this->baseUrl}/data/events/{$eventId}/edit.html\">Event settings</a>",
				'View embeddable form'	=> "<a href=\"{$this->baseUrl}/forms/{$eventId}/\">View embeddable form</a>",
				'Bookings'				=> "<a href=\"{$this->baseUrl}/data/{$event['form']}/\">Bookings</a>",
			);
		}
		$html .= application::htmlTable ($table, array (), 'lines', $keyAsFirstColumn = false, false, $allowHtml = true, $showColons = true);
		
		# Show the HTML
		echo $html;
	}
	
	
	# Function to create a list of forms
	private function formsList ()
	{
		# Create a list of forms
		$events = array ();
		foreach ($this->events as $eventId => $event) {
			$events[$eventId] = "<a href=\"{$this->baseUrl}/forms/{$eventId}/\">" . htmlspecialchars ($event['name']) . '</a>';
		}
		$html .= "\n<p>Please select the relevant form:</p>";
		$html .= application::htmlUl ($events, 0, 'boxylist');
		
		# Return the HTML
		return $html;
	}
	
	
	# Event forms list page
	public function forms ()
	{
		# Create the list
		$html = $this->formsList ();
		echo $html;
	}
	
	
	# Event form
	public function form ($formId)
	{
		# Start the HTML
		$html = '';
		
		# Ensure the form exists
		if (!ctype_digit ($formId) || !isSet ($this->events[$formId])) {
			$html = $this->page404 ();
			echo $html;
			return false;
		}
		
		# Get the event attributes
		$event = $this->events[$formId];
		
		# Add the title
		$html = "\n<h2>" . htmlspecialchars ($event['name']) . '</h2>';
		
		# If there is a ticket limit, ensure it has not been reached
		if ($this->isSoldOut ($event)) {
			$html .= "\n<p>" . htmlspecialchars ($event['soldOutMessage']) . '</p>';
			echo $html;
			return;
		}
		
		# If there is a guests ticket limit, ensure it has not been reached
		$attributes = array ();
		if ($this->guestsSoldOut ($event)) {
			$attributes['guests'] = array (
				'default' => '',
				'editable' => false,
				'append' => '<br />Sorry, we regret that all guest tickets have now been booked.',
			);
		}
		
		# Determine the table to use
		$table = $event['form'];
		
		# Create a new form
		$form = new form (array (
			'div' => 'ultimateform horizontalonly',
			'displayRestrictions' => false,
			'formCompleteText' => $this->tick . ' ' . $event['formCompleteText'],
			'autofocus' => true,
			'nullText' => '',
			'ip' => false,
			'databaseConnection' => $this->databaseConnection,
			'unsavedDataProtection' => true,
			'confirmationEmailIntroductoryText' => $event['confirmationIntroductoryText'],
		));
		if ($event['introductionHtml']) {
			$introductionHtml = "\n<div class=\"graybox\">" . $event['introductionHtml'] . "\n</div>";
			$form->heading ('', $introductionHtml);
		}
		$form->dataBinding (array (
			'database' => $this->settings['database'],
			'table' => $table,
			'intelligence' => true,
			'attributes' => $attributes,
		));
		if ($event['dataProtectionHtml']) {
			$form->heading ('', '<br />' . $event['dataProtectionHtml']);
		}
		
		# Set output to e-mail, confirmation e-mail, and screen
		$form->setOutputEmail ($event['recipientEmail'], $this->settings['administratorEmail'], $event['name'] . ': booking');
		$form->setOutputConfirmationEmail ('email', $event['recipientEmail'], $event['name'] . ': your booking');
		$form->setOutputScreen ();
		
		# Process the form
		if ($result = $form->process ($html)) {
			
			# Insert into the database
			if (!$this->databaseConnection->insert ($this->settings['database'], $table, $result)) {
				echo "\n<p class=\"warning\">There was a problem saving your submission.</p>";
				application::dumpData ($this->databaseConnection->error ());
			}
		}
		
		# Show the HTML
		echo $html;
	}
	
	
	# Function to determine if all tickets have been booked
	private function isSoldOut ($event)
	{
		# If no ticket limit, the event is not sold out
		if (!$event['ticketLimit']) {
			return false;
		}
		
		# Get the result
		$totalBooked = $this->databaseConnection->getTotal ($this->settings['database'], $event['form']);
		
		# Determine whether the event is all sold out
		$isSoldOut = ($totalBooked >= $event['ticketLimit']);
		
		# Return the result
		return $isSoldOut;
	}
	
	
	# Function to determine if all guest tickets have been booked
	private function guestsSoldOut ($event)
	{
		# If no guest ticket limit, the event guest tickets are not sold out
		if (!$event['guestsLimit']) {
			return false;
		}
		
		# Construct the query
		$table = $event['form'];
		$field = $event['guestsLimitTotalsField'];
		$query = "SELECT SUM(CAST(REPLACE (`{$field}`, '', '') AS SIGNED INTEGER)) AS total FROM `{$table}`;";	// The REPLACE is done to ensure an ENUM field uses the values not the index positions for a count
		
		# Get the result
		$totalBooked = $this->databaseConnection->getOneField ($query, 'total');
		
		# Determine whether the event is all sold out
		$isSoldOut = ($totalBooked >= $event['guestsLimit']);
		
		# Return the result
		return $isSoldOut;
	}
	
	
	# Conference registration
	public function add ()
	{
		# Delegate to sinenomine, with the specified actions
		$_GET['table'] = 'events';
		$_GET['do'] = 'add';
		return $this->editing ();
	}
	
	
	# Admin editing section, substantially delegated to the sinenomine editing component
	public function editing ($attributes = array (), $deny = false, $sinenomineExtraSettings = array ())
	{
		# Define sinenomine settings
		$sinenomineExtraSettings = array (
			'submitButtonPosition' => 'end',
			'int1ToCheckbox' => true,
			'simpleJoin' => true,
			'datePicker' => true,
			'richtextEditorToolbarSet' => 'BasicLonger',
			'richtextWidth' => 600,
			'richtextHeight' => 200,
		);
		
		# Define table attributes
		$attributesByTable = $this->formDataBindingAttributes ($table);
		$attributes = array ();
		foreach ($attributesByTable as $table => $attributesForTable) {
			foreach ($attributesForTable as $field => $fieldAttributes) {
				$attributes[] = array ($this->settings['database'], $table, $field, $fieldAttributes);
			}
		}
		
		# Define tables to deny editing for
		$deny[$this->settings['database']] = array (
			'administrators',
			'settings',
			'users',
		);
		
		# On the main editing tab page, labelled as downloads, hide the main events entry from the listing
		if ($_SERVER['REQUEST_URI'] == $this->baseUrl . '/' . $this->actions[__FUNCTION__]['url']) {
			$deny[$this->settings['database']][] = 'events';
		}
		
		# Hand off to the default editor, which will echo the HTML
		parent::editing ($attributes, $deny, $sinenomineExtraSettings);
	}
	
	
	# Helper function to define the dataBinding attributes
	private function formDataBindingAttributes ()
	{
		# Get the available form templates
		$internalTables = array ('administrators', 'events', 'settings');
		$forms = $this->databaseConnection->getTables ($this->settings['database'], false, $internalTables, true);
		
		# Define the properties, by table
		$dataBindingAttributes = array (
			'events' => array (
				'email' => array ('default' => $this->userVisibleIdentifier, 'editable' => false, ),
				'form' => array ('type' => 'select', 'values' => $forms, ),
			),
		);
		
		# Return the properties
		return $dataBindingAttributes;
	}
}

?>