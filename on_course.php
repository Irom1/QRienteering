<?php
require 'common_routines.php';

// Get the submitted info
// echo "<p>\n";
$event = $_GET["event"];

$results_string = "";
$competitor_directory = "./${event}/Competitors";
$competitor_list = scandir("${competitor_directory}");
$competitor_list = array_diff($competitor_list, array(".", ".."));

$courses_array = scandir('./' . $_GET["event"] . '/Courses');
$courses_array = array_diff($courses_array, array(".", "..")); // Remove the annoying . and .. entries

$not_started = array();
$on_course = array();
foreach ($courses_array as $course) {
  $on_course[$course] = array();
}

foreach ($competitor_list as $competitor) {
  if (!file_exists("${competitor_directory}/${competitor}/finish")) {
    if (!file_exists("${competitor_directory}/${competitor}/start")) {
      $not_started[] = $competitor;
    }
    else {
      $course = file_get_contents("${competitor_directory}/${competitor}/course");
      $on_course[$course][] = $competitor;
    }
  }
}

$outstanding_entrants = false;
$results_string = "";
if (count($not_started) > 0) {
  $outstanding_entrants = true;
  $results_string .= "<p>Registered but not started\n";
  $results_string .= "<table><tr><th>Name</th></tr>\n";
  foreach ($not_started as $competitor) {
    $competitor_name = file_get_contents("${competitor_directory}/${competitor}/name");
    $results_string .= "<tr><td>${competitor_name}</td></tr>";
  }
  $results_string .= "</table>\n<p><p><p>\n";
}

foreach (array_keys($on_course) as $course) {
//  echo "Looking at $course.\n";
//  print_r($on_course[$course]);
  if (count($on_course[$course]) > 0) {
    $outstanding_entrants = true;
    $results_string .= "<p>Currently on " . ltrim($course, "0..9-") . "\n";
    $results_string .= "<table><tr><th>Name</th><th>Start time</th><th>Last control</th><th>Last control time</th></tr>\n";
    foreach ($on_course[$course] as $competitor) {
      $competitor_path = "${competitor_directory}/${competitor}";
      $competitor_name = file_get_contents("${competitor_path}/name");
      $start_time = file_get_contents("${competitor_path}/start");
      $controls_done = scandir("${competitor_path}");
      $controls_done = array_diff($controls_done, array(".", "..", "course", "name", "next", "start", "finish", "extra", "dnf"));
      $num_controls_done = count($controls_done);
      if ($num_controls_done > 0) {
        $last_control_file = $num_controls_done - 1;
        $last_control_time = file_get_contents("${competitor_path}/${last_control_file}");

        // For the split times, controls are 0 based, but for printing, make them 1 based
        $last_control = $num_controls_done;
      }
      else {
        $last_control = "start";
        $last_control_time = $start_time;
      }
    
      // See if they have mispunched anything more recently than the last correct punch - if so,
      // report this
      if (file_exists("{$competitor_path}/extra")) {
        $extra_controls = explode("\n", file_get_contents("{$competitor_path}/extra"));
        $num_extra_controls = count($extra_controls);
        $last_extra_control_info = $extra_controls[$num_extra_controls - 1];
        if (trim($last_extra_control_info) == "") {
          // The last line is often blank - what a hack on my part
          // Should have a better way of finding the last control actually found, but this will do
          // for the moment
          $last_extra_control_info = $extra_controls[$num_extra_controls - 2];
        }
        $last_extra_control_pieces = explode(",", $last_extra_control_info);   // Format is control-id,time

        if (intval($last_extra_control_pieces[1]) > intval($last_control_time)) {
           $last_control_time = $last_extra_control_pieces[1];
           $last_control = "{$last_extra_control_pieces[0]} (not on course)";
        }
      }

      $results_string .= "<tr><td>${competitor_name}</td>\n<td>" . strftime("%T", $start_time) . "</td>\n";
      $results_string .= "<td>${last_control}</td>\n<td>" . strftime("%T", $last_control_time) . "</td></tr>\n";
    }
    $results_string .= "</table><p><p><p>\n";
  }
}

?>


<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <meta content="text/html; charset=ISO-8859-1"
 http-equiv="content-type">
  <title>Orienteering Event Management</title>
  <meta content="Mark O'Connell" name="author">
<?php
echo get_table_style_header();
?>
<?php
echo get_paragraph_style_header();
?>
</head>
<body>
<br>


<?php
if (outstanding_entrants) {
  echo $results_string;
}
else {
  echo "<p>No outstanding entrants at this point.\n";
}
?>

</body>
</html>
