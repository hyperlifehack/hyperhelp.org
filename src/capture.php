<?php
session_start(); // some values will have to be remembered
/*=== header start ===*/
require_once('./lib/php/lib_mysqli_commands.php');	// library needed to access database

// init database
config::set('lib_mysqli_interface_instance',new lib_mysqli_interface()); // create instance from class and store reference to this instance in config for reuse
config::set('lib_mysqli_commands_instance',new lib_mysqli_commands()); // create instance from class and store reference to this instance in config for reuse

require_once('./lib/php/lib_security.php');			// will mysql-real-escape all input
require_once('./lib/php/lib_session.php');			// check if user is allowed to access this page
/*=== header ends ===*/

$logged_in_user = session_valid(); // currently logged in user
$status_capture = ""; // string if and why login would not work
$refresh_page = false; // if page should be refreshed or not

if($logged_in_user)
{
	if(isset($_REQUEST['action']))
	{
		/* neuen eintrag erfassen */
		if($_REQUEST['action'] == "capture")
		{
			$NewRecord = config::get('lib_mysqli_commands_instance')->newRecord("records");
			
			// RecordAdd - add a arbitrary record to a arbitrary table
			$NewRecord->username = $_REQUEST["username"];
	
			$date_and_time = $_REQUEST["when_date"]." ".$_REQUEST["when_time"];
			
			$when = parse_date2timestamp($date_and_time);
			$NewRecord->when = $when;
	
			$NewRecord->howmany_minutes = $_REQUEST["howmany_minutes"];
			$NewRecord->to_whom = $_REQUEST["to_whom"];
			$NewRecord->what = $_REQUEST["what"];
			$NewRecord = config::get('lib_mysqli_commands_instance')->RecordAdd("records",$NewRecord); // returns the record-object from database, containing a new, database generated id, that is important for editing/deleting the record later
			$test = config::get('lib_mysqli_interface_instance')->get('last_id'); // get id of last inserted record // DOES THIS REALLY WORK?
			$NewRecord->id = config::get('lib_mysqli_interface_instance')->get('last_id'); // get id of last inserted record
			
			// store what as template
			if(isset($_REQUEST['store_what_as_template']))
			{
				// test if template exists
				$NewAction = config::get('lib_mysqli_commands_instance')->newRecord("actions");
				$NewAction->keyword = $_REQUEST['what'];
				
				$ActionExists = config::get('lib_mysqli_commands_instance')->records("actions",$NewAction,"keyword");
				if($ActionExists)
				{
					// check if user is not allready associated with this action
					if (strpos($ActionExists->users, $logged_in_user->username) !== false)
					{
						// do nothing, user is allready associated with this action
					}
					else
					{
						// add user to the list of users that use this action
						$ActionExists->users = $ActionExists->users.$logged_in_user->username.",";
						$ActionExists_id = config::get('lib_mysqli_commands_instance')->RecordEdit("actions",$ActionExists);
					}
				}
				else
				{
					// add action to database
					$NewAction->description = $_REQUEST['what'];
					$NewAction->users = $logged_in_user->username.",";
					$NewAction = config::get('lib_mysqli_commands_instance')->RecordAdd("actions",$NewAction); // returns the record-object from database, containing a new, database generated id, that is important for editing/deleting the record later
				}
			}

			if(lib_mysqli_commands::get('worked',true))
			{
				$status_capture = "thank you for your hard work and dedication! :) your working-time have been recorded with the recordID: ".$NewRecord->id."<br> Page will refresh in 3 sec...";
				$refresh_page = true;
			}
		}
	}
}

/* wandelt ein datum wie 30.12.2016 in eine TimeStamp um (ms since 1970) */
function parse_date2timestamp($datum)
{
	$parsed = date_parse_from_format("Y.m.d H:i", $datum);
	
	// $date = new DateTime($parsed['year']."-".$parsed['month']."-".$parsed['day']);
			
	/* not accurate ? */
	$TimeStamp = mktime(
			$parsed['minute'],
			$parsed['hour'],
			0, // $parsed['second'],
			$parsed['month'],
			$parsed['day'],
			$parsed['year']
	);
	
	$test = date('d.m.Y H:i', $TimeStamp); // just for checking
	
	return $TimeStamp; //returns: 1483052400
}

/* remember what was entered in the form, as session */
function remember_value($value)
{
	if (isset ( $_REQUEST [$value] ))
	{
		$_SESSION[$value] = $_REQUEST[$value];
		echo $_REQUEST[$value];
	}
	else
	{
		// if value was entered previously
		if(isset($_SESSION[$value])) echo $_SESSION[$value];
	}
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php
include('text/head.php');
// if a record was created reload the page
if($refresh_page)
{
	echo '<meta http-equiv="refresh" content="3;URL=capture.php"/>';
}
?>
</head>

<body id="body">
	<div id="parent">
		<div class="centered">
			<div id="content">
				<div class="border">
					<div class="element">
						<div id="headline" class="boxShadows" style="margin-top: 0px;" title="for security: works without Javascript :-D">
							<h1>
								<div id="headline_text">capture action</div>
							</h1>
							<h2><div id="status"><?php echo $status_capture; ?></div></h2>
							<a style="color: white;" href="logout.php">(logout)</a>
						</div>
						<div class="element_content">
							<form class="form-signin" action="capture.php#capture">
								<input name="action" value="capture" hidden>
								<h2>Who?</h2>
								<h1>
								<?php
									if($logged_in_user)
									{
										echo $logged_in_user->username;
									}
								?>
								</h1>
								<div class="table">
									<div class="column100">
										<div class="line">
											<img class="profilepicture" src="<?php if($logged_in_user && isset($logged_in_user->profilepicture)){ echo $logged_in_user->profilepicture; } ?>" alt="ProfilePicture - logged in user">
										</div>
										<div class="line">
											<div class="prop">
												user:
											</div>
											<div class="value">
												<input id="who_username" name="username" type="username" placeholder="username" value="<?php if($logged_in_user) echo $logged_in_user->username; ?>" readonly>
											</div>
										</div>
									</div>
								</div>

								<h2>howmuch?</h2>

								<div class="table">
									<div class="column100">
										<div class="line">
											<div class="prop">
												<div>
												Minutes:
												</div>
												<a href="capture.php?howmany_minutes=10#capture"><div class="element_select">10</div></a>
												<a href="capture.php?howmany_minutes=20#capture"><div class="element_select">20</div></a>
												<a href="capture.php?howmany_minutes=30#capture"><div class="element_select">30</div></a>
												<a href="capture.php?howmany_minutes=45#capture"><div class="element_select">45</div></a>
												<a href="capture.php?howmany_minutes=60#capture"><div class="element_select">60</div></a>
												<a href="capture.php?howmany_minutes=120#capture"><div class="element_select">120</div></a>
											</div>
											<div class="value">
												<div id="minuten">
													<input name="howmany_minutes" id="howmany_minutes" value="<?php remember_value("howmany_minutes") ?>" type="text" placeholder="10">
												</div>
											</div>
										</div>
									</div>
								</div>

								<h2 title="previously often helped users">to whom?</h2>

								<div class="table">
									<div class="column100">
										<div class="line">
											<div id="list_of_usernames" class="column100" style="text-align: center;">
												<?php

												$users = config::get('lib_mysqli_commands_instance')->users(); // get all users
												
												$to_whom_select_profilepicture = "";
												foreach($users as $key => $user) {
													echo '<a href="capture.php?to_whom_select='.$user->username.'#capture"><div class="element_select">'.$user->username.'</div></a>';

													if(isset($_SESSION["to_whom_select"])) // find the profile picture of the user to_whom the service benifits
													{
														if($_SESSION["to_whom_select"] == $user->username)
														{
															$to_whom_select_profilepicture = $user->profilepicture;
														}
													}
												}
												?>
											</div>
										<div class="line">
											<input name="to_whom" id="to_whom" placeholder="username" value="<?php remember_value("to_whom_select") ?>" type="text" style="width: 100%;"/>
											<img class="profilepicture" src="<?php echo $to_whom_select_profilepicture ?>" alt="ProfilePicture to_whom_select">
										</div>
									</div>
								</div>
								
								<h2 title="What task did you help your neighbour / the otheruser with?">what?</h2>

								<?php
									$output_status = false;
									if(isset($ActionExists_id))
									{
										$status_template = "Action (id:".$ActionExists_id.") has been added to your Templates.";
										$output_status = true;
									}
									else if(isset($NewAction->id))
									{
										$status_template = "The Action-Template has been saved under the id:".$NewAction->id; 
										$output_status = true;
									}
									if($output_status)
									{
										echo '
										<div class="table">
											<div class="column100">
												<div class="line">
													<div id="status">'.$status_capture.'</div>
												</div>
											</div>
											<div class="column100">
												<div class="line">
													<div id="status">'.$status_template.'</div>
												</div>
											</div>
										</div>';
									}
								?>
								<div class="table">
									<div class="column100">
										<div class="line">
											<input name="what" id="what" placeholder="what?" value="<?php remember_value("what_select") ?>" type="text" style="width: 100%;"/>
											<input type="checkbox" name="store_what_as_template" value="true">store as template?<br>
										</div>
										<div class="line">
											<div id="list_of_actions" class="column100" style="text-align: center;">
												<?php
												if($logged_in_user)
												{
													// show all templates from all users
													$Actions_of_that_User = config::get('lib_mysqli_interface_instance')->query("SELECT * FROM `".config::get("db_name")."`.`actions`;");
													// uncomment this to only see the templates associated with the current logged in user
													// $Actions_of_that_User = config::get('lib_mysqli_interface_instance')->query("SELECT * FROM `".config::get("db_name")."`.`actions` WHERE `users` LIKE '%".$logged_in_user->username."%';");
													foreach($Actions_of_that_User as $key => $action) {
														echo '<a href="capture.php?what_select='.$action->keyword.'#capture"><div class="element_select">'.$action->keyword.'</div></a>'; 
													}
												}
												?>
											</div>
										</div>
									</div>
								</div>

								<h2 title="When did help your neighbour / the otheruser?">When?</h2>

								<div class="table">
										<div class="line">
											<div class="prop">
												Am:
											</div>
											<div class="value">
											<?php 
											// today
											$current_date = date("Y.m.d");	// 20010310
											$current_time = date("H:i");	// 17:16
											?>
												<input id="when_date" name="when_date" type="text" placeholder="dd.mm.yyyy" value="<?php echo $current_date; ?>"/>
												
												<input id="when_time" name="when_time" type="text" placeholder="hh:mm" value="<?php echo $current_time; ?>"/>
											</div>
										</div>
								</div>
								<div class="column100">
									<input type="submit" name="erfassen" value="Erfassen" class="button" style="width: 100%; height: 40px;"/>
								</div>
							</form>
						</div>
					</div>
					<!-- end of element -->
				</div>
			</div>
		</div>
	</div>
</body>
</html>