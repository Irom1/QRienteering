<?php
require 'common_routines.php';
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <meta content="text/html; charset=ISO-8859-1"
 http-equiv="content-type">
  <title>Orienteering Event Management</title>
  <meta content="Mark O'Connell" name="author">
</head>
<body>
<br>

<?php
// Get some phpinformation, just in case
// Verify that php is running properly
// echo 'Current PHP version: ' . phpversion();
// phpinfo();

function is_event($filename) {
  return (is_dir($filename) && (substr($filename, -5) == "Event"));
}

function name_to_link($pathname) {
  $final_element = basename($pathname);
  return ("<li><a href=./finish_event.php?event=${final_element}>${final_element}</a>\n");
}

echo "<p>\n";

$event_name = $_GET["event"];
if (strcmp($event_name, "") == 0) {
  $event_list = scandir("./");
  $event_list = array_filter($event_list, is_event);
  $event_output_array = array_map(name_to_link, $event_list);
  echo "<p>Choose your event:<p>\n<ul>\n" . implode("\n", $event_output_array) . "</ul>";
}
else {
  if (substr($event_name, -5) == ".done") {
    echo "<p>Event {$event_name} already completed.";
  }
  else {
    rename("./{$event_name}", "./{$event_name}.done");
    echo "<p>Event {$event_name} completed.";
  }
}

?>


</body>
</html>
