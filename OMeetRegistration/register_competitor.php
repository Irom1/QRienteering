<?php
require '../OMeetCommon/common_routines.php';

ck_testing();

// Get the submitted info
// echo "<p>\n";

// Make sure any funky HTML sequeneces in the name are escaped
$competitor_name = htmlentities($_GET["competitor_name"]);
$course = $_GET["course"];

if (isset($_GET["registration_info"])) {
  $registration_info_supplied = true;
  $raw_registration_info = $_GET["registration_info"];
  $registration_info = parse_registration_info($raw_registration_info);
}
else {
  $registration_info_supplied = false;
}

$key = $_GET["key"];
$event = $_GET["event"];
if (!key_is_valid($key)) {
  error_and_exit("Unknown key \"{$key}\", are you using an authorized link?\n");
}

if (!is_dir(get_event_path($event, $key, "..")) || !file_exists(get_event_path($event, $key, "..") . "/description")) {
  error_and_exit("Unknown event \"{$event}\" (" . get_base_path($event, $key, "..") . "), are you using an authorized link?\n");
}

$check_for_cookie_support = 0;
if (!$registration_info_supplied || $registration_info["si_stick"] == "") {
  $check_for_cookie_support = 1;
}

if ($check_for_cookie_support) {
  if ($_COOKIE["testing_cookie_support"] != "can this be read?") {
    error_and_exit("Registration failing, this phone / device must support cookies for QRienteering to work properly.\n" .
                   "<p>Proper cookie support not being detected.");
  }
}

$courses_array = scandir(get_courses_path($event, $key, ".."));
$courses_array = array_diff($courses_array, array(".", "..")); // Remove the annoying . and .. entries
// print_r($courses_array);
// echo "<p>\n";

$body_string = "";
$using_si_stick = false;

// Validate the info
$error = false;
if (!in_array($course, $courses_array)) {
  $body_string .= "<p>ERROR: Course must be specified.\n";
  $error = true;
}

if ($competitor_name == "") {
  $body_string .= "<p>ERROR: Competitor name must be specified.\n";
  $error = true;
}

// Input information is all valid, save the competitor information
if (!$error) {
  // Generate the competitor_id and make sure it is truly unique
  $tries = 0;
  while ($tries < 5) {
    $competitor_id = uniqid();
    $competitor_path = get_competitor_path($competitor_id, $event, $key, "..");
    mkdir ($competitor_path, 0777);
    $competitor_file = fopen($competitor_path . "/name", "x");
    if ($competitor_file !== false) {
      break;
    }
    $tries++;
  }

  if ($tries === 5) {
    $body_string .= "ERROR Cannot register " . $competitor_name . " with id: " . $competitor_id . "\n";
    $error = true;
  }
  else {
    $body_string .= "<p>Registration complete: " . $competitor_name . " on " . ltrim($course, "0..9-");

    $cookie_path = isset($_SERVER["REQUEST_URI"]) ? dirname(dirname($_SERVER["REQUEST_URI"])) : "";

    $saved_competitor_name = $competitor_name;
    $i = 0;
    while (isset($_GET["extra-{$i}"])) {
      if ($_GET["extra-{$i}"] != "") {
        $saved_competitor_name .= " - " . htmlentities($_GET["extra-{$i}"]);
      }
      $i++;
    }

    // Save the information about the competitor
    fwrite($competitor_file, $saved_competitor_name);
    fclose($competitor_file);
    file_put_contents($competitor_path . "/course", $course);
    mkdir("./{$competitor_path}/controls_found");

    $current_time = time();

    if ($registration_info_supplied) {
      // Save the safety information for a few hours so it can be auto-filled if someone wants to go out on a second course
      file_put_contents("{$competitor_path}/registration_info", $raw_registration_info);
      setcookie("{$key}-safety_info", $raw_registration_info, $current_time + 3600 * 4, $cookie_path);
      if ($registration_info["si_stick"] != "") {
        file_put_contents("{$competitor_path}/si_stick", $registration_info["si_stick"]);
        $using_si_stick = true;
      }

      if (($registration_info["is_member"] == "yes") && isset($registration_info["member_id"]) && ($registration_info["member_id"] != "")) {
        // Format will be member_id:timestamp_of_last_registration,member_id:timestamp_of_last_registration,...
        // 3 month timeout
        $time_cutoff = $current_time - (86400 * 90);
        $member_ids = array_map(function ($elt) { return (explode(":", $elt)); }, explode(",", $_COOKIE["{$key}-member_ids"]));
        $member_ids_hash = array();
        array_map(function ($elt) use (&$member_ids_hash, $time_cutoff)
                     { if ($elt[1] > $time_cutoff) { $member_ids_hash[$elt[0]] = $elt[1]; } }, $member_ids);
        $member_ids_hash[$registration_info["member_id"]] = $current_time;
        $member_cookie = implode(",", array_map (function ($elt) use ($member_ids_hash) { return($elt . ":" . $member_ids_hash[$elt]); }, array_keys($member_ids_hash)));
        setcookie("{$key}-member_ids", $member_cookie, $current_time + 86400 * 120, $cookie_path);
      }
    }
    else {
      # This is a BYOM registration
      # Save some cookies to optimize the next BYOM registration on this device
      # Save the information for 30 days
      $email_address_supplied = isset($_GET["email_address"]) ? $_GET["email_address"] : "";
      if ($email_address_supplied != "") {
        $just_email_registration_info = implode(",", array("email_address", base64_encode($_GET["email_address"]),
                                                           "BYOM", base64_encode("yes")));
        file_put_contents("{$competitor_path}/registration_info", $just_email_registration_info);
      }

      $byom_registration_cookie = base64_encode($competitor_name) . "," . base64_encode($email_address_supplied);
      setcookie("byom_registration_info", $byom_registration_cookie, $current_time + (86400 * 30), $cookie_path);
    }
    
    if (!$using_si_stick) {
      // Set the cookies with the name, course, next control
      $timeout_value = $current_time + 3600 * 6;  // 6 hour timeout, should be fine for most any course
      setcookie("competitor_id", $competitor_id, $timeout_value, $cookie_path);
      setcookie("course", $course, $timeout_value, $cookie_path);
      setcookie("event", $_GET["event"], $timeout_value, $cookie_path);
      setcookie("key", $key, $timeout_value, $cookie_path);
    }
  }
}

if (!$error) {
  set_success_background();
}
else {
  set_error_background();
}

echo get_web_page_header(true, false, true);

echo $body_string;

if (!$error) {
  if ($using_si_stick) {
    echo "<p>To start the course, clear and check your SI unit, then proceed to the start control with your SI unit.\n";
  }
  else {
    echo "<p>To start the course, please proceed to start and scan the start QR code there or click the \"Start course\" button below to start now.\n";
    echo "<p><form action=\"../OMeet/start_course.php\"> <input type=\"submit\" value=\"Start course\"> </form>\n";
  }
}

echo get_web_page_footer();
?>
