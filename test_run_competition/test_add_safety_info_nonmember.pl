#!/usr/bin/perl

use strict;
use MIME::Base64;

require "../testing/testHelpers.pl";

my(%GET, %TEST_INFO, %COOKIE, %POST);
my($cmd, $output);

create_key_file();
mkdir(get_base_path("UnitTestPlayground"));

set_test_info(\%GET, \%COOKIE, \%POST, \%TEST_INFO, $0);




###########
# Test 1 - Success nonmember registration
# 
%TEST_INFO = qw(Testname TestNonMemberWithStick);
%GET = qw(key UnitTestPlayground competitor_first_name Karen competitor_last_name Yeowell si_stick 3959473);
%COOKIE = ();  # empty hash

hashes_to_artificial_file();
$cmd = "php ../OMeetWithMemberList/add_safety_info.php";
$output = qx($cmd);

if ($output =~ /type=hidden name="waiver_signed" value="signed"/) {
  error_and_exit("Waiver signed hidden input found.\n$output");
}

if ($output !~ /type=checkbox name="waiver_signed" value="signed"/) {
  error_and_exit("Waiver checkbox input not found.\n$output");
}

if ($output !~ /I am participating of my own accord and hold the organizers harmless/) {
  error_and_exit("Waiver language not found.\n$output");
}

if ($output !~ /type=hidden name="competitor_first_name" value="Karen"/) {
  error_and_exit("Hidden input first_name not found.\n$output");
}

if ($output !~ /type=hidden name="si_stick" value="3959473"/) {
  error_and_exit("Hidden input si_stick not found.\n$output");
}

if ($output !~ /type=hidden name="club_name" value=""/) {
  error_and_exit("Hidden empty input club not found.\n$output");
}

if ($output !~ /input type="text" size=50 name="email"  >/) {
  error_and_exit("Presupplied email address not found.\n$output");
}

success();

###########
# Test 2 - Success nonmember registration
# 
%TEST_INFO = qw(Testname TestNonMemberNoStickWithClub);
%GET = qw(key UnitTestPlayground competitor_first_name Karen competitor_last_name Yeowell club_name CSU);
$GET{"si_stick"} = "";
%COOKIE = ();  # empty hash

hashes_to_artificial_file();
$cmd = "php ../OMeetWithMemberList/add_safety_info.php";
$output = qx($cmd);

if ($output =~ /type=hidden name="waiver_signed" value="signed"/) {
  error_and_exit("Waiver signed hidden input found.\n$output");
}

if ($output !~ /type=checkbox name="waiver_signed" value="signed"/) {
  error_and_exit("Waiver checkbox input not found.\n$output");
}

if ($output !~ /I am participating of my own accord and hold the organizers harmless/) {
  error_and_exit("Waiver language not found.\n$output");
}

if ($output !~ /type=hidden name="competitor_first_name" value="Karen"/) {
  error_and_exit("Hidden input first_name not found.\n$output");
}

if ($output !~ /type=hidden name="si_stick" value=""/) {
  error_and_exit("Hidden empty input si_stick not found.\n$output");
}

if ($output !~ /type=hidden name="club_name" value="CSU"/) {
  error_and_exit("Hidden input club not found.\n$output");
}

if ($output !~ /input type="text" size=50 name="email"  >/) {
  error_and_exit("Presupplied email address not found.\n$output");
}

success();





###########
# Test 3 - Failed nonmember registration - no first name
# 
%TEST_INFO = qw(Testname TestNonMemberUsingNoFirstName);
%GET = qw(key UnitTestPlayground competitor_last_name Dokey si_stick 14xx21);
%COOKIE = ();  # empty hash

hashes_to_artificial_file();
$cmd = "php ../OMeetWithMemberList/add_safety_info.php";
$output = qx($cmd);

if ($output !~ /please go back and enter a valid first name/) {
  error_and_exit("Invalid missing first name error not found.\n$output");
}

success();


###########
# Test 4 - Failed nonmember registration - no last name
# 
%TEST_INFO = qw(Testname TestNonMemberUsingNoFirstName);
%GET = qw(key UnitTestPlayground competitor_first_name Mokey si_stick 14xx21);
%COOKIE = ();  # empty hash

hashes_to_artificial_file();
$cmd = "php ../OMeetWithMemberList/add_safety_info.php";
$output = qx($cmd);

if ($output !~ /please go back and enter a valid last name/) {
  error_and_exit("Invalid missing last name error not found.\n$output");
}

success();



###########
# Test 5 - Failed nonmember registration - bad stick id specified
# 
%TEST_INFO = qw(Testname TestNonMemberUsingBadStickNumber);
%GET = qw(key UnitTestPlayground competitor_first_name Mokey competitor_last_name Dokey si_stick 14xx21);
%COOKIE = ();  # empty hash

hashes_to_artificial_file();
$cmd = "php ../OMeetWithMemberList/add_safety_info.php";
$output = qx($cmd);

if ($output !~ /Invalid si unit id "14xx21", only numbers allowed.  Please go back and re-enter./) {
  error_and_exit("Bad si unit id error message not found.\n$output");
}

success();






#################
# End the test successfully
my($rm_cmd) = "rm -rf " . get_base_path("UnitTestPlayground");
print "Executing $rm_cmd\n";
qx($rm_cmd);
remove_key_file();
qx(rm artificial_input);
