<?php 
/*
  +----------------------------------------------------------------------+
  | PHP QA GCOV Website                                                  |
  +----------------------------------------------------------------------+
  | Copyright (c) 2005-2006 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Author: Daniel Pronych <pronych@php.net>                             |
  |         Nuno Lopes <nlopess@php.net>                                 |
  +----------------------------------------------------------------------+
*/

/* $Id$ */

// Name: GCOV Viewer page
// Desc: page for view PHP version information such as code coverage


// Include the site API
include 'site.api.php';

// Initialize the core components
api_init($appvars);

$content  = ''; // Stores content collected during execution
$error    = ''; // Start by assuming no error has occurred
$fileroot = ''; // base directory for including external files (used for external builds)

$file     = isset($_REQUEST['file']) ? basename($_REQUEST['file']) : '';
$version  = isset($_REQUEST['version']) && isset($appvars['site']['tags'][$_REQUEST['version']]) ? $_REQUEST['version'] : '';
$mode     = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';


if(isset($_REQUEST['username']) && ctype_alnum($_REQUEST['username']) &&
   file_exists('other_platforms/'.$_REQUEST['username'].'/'.$version))
{
	$fileroot = 'other_platforms/'.$_REQUEST['username'].'/';
	$username = $_REQUEST['username'];
	$appvars['site']['builderusername'] = $username;
}


// Define the function array
// each array element starts with the name of the command
// title: page title
// option: options include phpinc (parse file as a PHP include) text (use pre tags)
$func_array = array(
	'compile_results' =>
		array(
			'option' => 'phpinc', 
			'pagetitle' => 'PHP: Compile Results for '.$version,
			'pagehead' => 'Compile Results'
		),
	// todo: remove php_test_log
	'php_test_log' =>
		array(
			'option' => 'text',
			'pagetitle' => 'PHP: Test Log for '. $version,
			'pagehead' => 'Test Log'
		),
	'params' =>
		array(
			'option' => 'phpinc',
			'pagetitle' => 'PHP: Parameter Parsing Report for '.$version,
			'pagehead' => 'Parameter Parsing Report'
		),
	'valgrind' =>
		array(
			'option' => 'phpinc',
			'pagetitle' => 'PHP: Valgrind Report for '.$version,
			'pagehead' => 'Valgrind Report'
		),
	'tests' =>
		array(
			'option' => 'phpinc', 
			'pagetitle' => 'PHP: Test Failures for '.$version,
			'pagehead' => 'Test Failures'
		),
	'system' =>
		array(
			'option' => 'phpinc',
			'pagetitle' => 'PHP: System Info',
			'pagehead' => 'System Info'
		)
	);

// Define the acceptable modes for the graphs
$graph_mode_array = array(
		'Week'  => 'Weekly',
		'Month' => 'Monthly',
		'Year'  => 'Yearly'
);

$graph_types_array = array('codecoverage','failures','memleaks','warnings');


if(isset($_REQUEST['func']))
{
	$func = $_REQUEST['func'];
}
else
{
	$func = isset($username) ? 'system' : 'menu';
}
$appvars['site']['func'] = $func;

if($version || $func === 'search')
{
	$appvars['site']['mytag'] = $version;

	if($func == 'search')
	{
		if(isset($_REQUEST['os']))
		{
			$count = 0;
			$os = $_REQUEST['os'];
			$stmtarray = array();

			$appvars['page']['title'] = 'PHP: Other Platform Search Results';
			$appvars['page']['head'] = 'Other Platform Search Results';
			$appvars['page']['headtitle'] = 'Search Results';

			if($os == 'all')
			{
				$sql = 'SELECT user_name, last_user_os FROM remote_builds';
			}
			else
			{
				$sql = 'SELECT user_name, last_user_os FROM remote_builds WHERE last_user_os=?';
				$stmtarray = array($os);
			}
			if ($stmt = $mysqlconn->prepare($sql))
				$stmt->execute($stmtarray);

			// todo: allow check to be narrowed down to a specific PHP version
			while($stmt && $row = $stmt->fetch())
			{
				list($user_name, $last_user_os) = $row;

				$dirroot = 'other_platforms/'.$user_name;

				foreach($appvars['site']['tags'] as $phpvertag)
				{

					if(file_exists($dirroot.'/'.$phpvertag.'/system.inc'))
					{
						$content .= <<< HTML
<a href="viewer.php?username=$user_name&version=$phpvertag">$user_name</a> (PHP Version: $phpvertag, OS: $last_user_os)<br />
HTML;
						++$count;
					} // End check if tag is active for this user

				} // End loop through each accepted PHP version

			} // End loop through results

			if($count == 0)
			{
				$content = 'Your search seems too narrow to have any results.';
			} // End check for no results

		} // End check if OS is set
		else
		{
			$stmt = null;
			$sql = 'SELECT DISTINCT last_user_os FROM remote_builds';
			if ($stmt = $mysqlconn->prepare($sql))
				$stmt->execute();

			$appvars['page']['title'] = 'PHP: Search Other Platforms';
			$appvars['page']['head'] = 'Other Platform Search';
			$appvars['page']['headtitle'] = 'Search';

			$content .= <<< HTML
<p>Select the platforms you wish to search for existing builds.</p>
<form method="post" action="viewer.php">
<input type="hidden" name="func" value="search" />
<table border="0">
<tr>
<td>Operating System(s):</td>
<td><select name="os">
<option value="all">All Platforms</option>
HTML;

			while($stmt && $row = $stmt->fetch())
			{
		  	list($os) = $row;

		  	$content .= <<< HTML
<option value="$os">$os</option>
HTML;

			}

			$content .= <<< HTML
</select></td>
</table>
<input type="submit" value="Search" />
</form>
HTML;
		} // End check for Operating System set

	} // End check for function search	

	elseif (isset($func_array[$func]))
	{
		$incfile = $file ? $file : $func;

		// Determine the file path
		$filepath = $fileroot.$version.'/'.$incfile.'.inc';

		// Obtain file contents by the required method
		if($func_array[$func]['option'] == 'phpinc') // Parse file as a PHP script
		{
	                ob_start();
        	        if(file_exists($filepath))
                	{
				include $filepath;
			}
			$content = ob_get_clean();
		}
		else // Treat the file contents as regular text file
		{
			$content = @file_get_contents($filepath);

			if($func_array[$func]['option'] == 'text')
				$content = '<pre>'.$content.'</pre>';
		}

		$appvars['page']['title']     = $func_array[$func]['pagetitle'];
		$appvars['page']['head']      = $func_array[$func]['pagehead'];
		$appvars['page']['headtitle'] = $version;

		// Determine title based on success or failure
		if($content)
		{
			if(isset($username))
			{
				$appvars['page']['head'] .= " (builder: $username)";
			}
		}
		else
		{
			$appvars['page']['head'] .= ' Data File Not Available';
			$content = 'File could not be opened.  Please try again in a few minutes, or return to the <a href="/">listing</a> page.';
		}

	} // End check for func defined in func_array

	elseif($func == 'graph')
	{
		// If date is not set display all available dates
		if(isset($graph_mode_array[$mode]))
		{
			$appvars['page']['title'] = 'PHP: '.$version.' '.$graph_mode_array[$mode] . ' Graphs';
			$appvars['page']['head'] = $graph_mode_array[$mode]. ' Graphs';
			$appvars['page']['headtitle'] = $version;

			$content .= '<p>The following images show the changes in code coverage, compile warnings, memory leaks and test failures:</p>';

			$graph_count = 0;

			foreach($graph_types_array as $graph_type)
			{
				$graph = "$version/graphs/{$graph_type}_$mode.png";

				if(file_exists($graph))
				{
					$content .= '<img src="'.$graph.'" />&nbsp;'."\n";

					if(++$graph_count == 2)
						$content .= "<br />\n";
				}
			}

		}
		else // Display the graphs for the specified PHP version and date
		{
			$appvars['page']['title'] = 'PHP: '.$version.' Graphs';
			$appvars['page']['head'] = 'Graphs';
			$appvars['page']['headtitle'] = $version;

			$content .= <<< HTML
<p>Select the period of time you wish to view as a graphical progression:</p>
HTML;

			foreach($graph_mode_array as $idx => $graph_mode)
			{
				$content .= <<< HTML
<a href="viewer.php?version=$version&func=graph&mode=$idx">$graph_mode</a><br />
HTML;
			}
		} // End check for valid graph mode

	} // End check for func=graph

	else if($func == 'lcov') 
	// Displays the lcov content for this version
	{
		// Define page variables
		$appvars['page']['title'] = 'PHP: '. $version.' Code Coverage Report';
		$appvars['page']['head'] = $version.': Code Coverage Report';
		$appvars['page']['headtitle'] = $version;		

		if (@is_dir("$version/lcov_html")) {
			header("Location: /$version/lcov_html/");
			exit;
		} else {
			$content = "Sorry, but the lcov data isn't available at this time.";
		}
	}
	else if($func == 'menu')
	{
		$content = 'Please choose one function from the menu on the left.';
	}
	else
	{
		// Define page variables
		$appvars['page']['title'] = 'PHP: Test and Code Coverage Analysis';
		$appvars['page']['head'] = 'PHP function not active';
		
		$error .= 'The PHP version specified exists but the function specified does not appear to serve any purpose at this time.';
	}
}
else
{
	// Define page variables
	$appvars['page']['title'] = 'PHP: Test and Code Coverage Analysis';
	$appvars['page']['head'] = 'PHP version not active';
	
	$error .= 'The PHP version specified does not appear to exist on the website.';
}

// Outputs the site header to the screen
api_showheader($appvars);

// If an error occurred the command did not exist
if($error)
{
	echo 'Oops!  Seems we were unable to execute your command.  The following are the errors the system found: <br />'.$error;
}
else
{
	echo $content;
}
?>

<?php
// Outputs the site footer to the screen
api_showfooter($appvars);
