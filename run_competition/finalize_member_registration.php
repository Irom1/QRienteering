<?php
require '../common_routines.php';
require 'name_matcher.php';

ck_testing();

$matching_info = read_names_info("./members.csv", "./nicknames.csv");

if (!isset($_GET["member_id"])) {
  error_and_exit("No member id specified, please restart registration.\n");
}
else {
  $member_id = $_GET["member_id"];
  if (get_full_name($member_id, $matching_info) == "") {
    error_and_exit("No such member id {$_GET["member_id"]} found, please retry or ask for assistance.\n");
  }
}

if (!isset($_GET["using_stick"])) {
  error_and_exit("No value found for SI stick usage - error in scripting?  Please restart registration.\n");
}

$using_stick_value = $_GET["using_stick"];
if (($using_stick_value != "yes") && ($using_stick_value != "no")) {
  error_and_exit("Invalid value \"{$using_stick_value}\" for SI stick usage.  Please restart registration.\n");
}

$si_stick = "";
if ($using_stick_value == "yes") {
  if (!isset($_GET["si_stick_number"])) {
    error_and_exit("Yes specified for SI stick usage but no SI stick number found.  Please restart registration.\n");
  }
  $si_stick = $_GET["si_stick_number"];
  if (!preg_match("/^[0-9]+$/", $si_stick)) {
    error_and_exit("Yes specified for SI stick usage but invalid SI stick number found \"{$si_stick}\"," .
                   "only numbers allowed.  Please restart registration.\n");
  }
}

$name_info = get_member_name_info($member_id, $matching_info);


$registration_info_string = implode(",", array("first_name", base64_encode($name_info[0]),
                                               "last_name", base64_encode($name_info[1]),
                                               "club_name", base64_encode("NEOC"),  // Should NOT be hardcoded
                                               "si_stick", base64_encode($si_stick),
                                               "email_address", base64_encode(""),
                                               "cell_phone", base64_encode(""),
                                               "car_info", base64_encode(""),
                                               "is_member", base64_encode("yes")));

// Redirect to the main registration screens
echo "<html><head><meta http-equiv=\"refresh\" content=\"0; URL=../register.php?registration_info=${registration_info_string}\" /></head></html>";
?>
