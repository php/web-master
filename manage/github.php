<?php // vim: et ts=2 sw=2

// This script evolved from a quick'n'dirty shell script. If you are reading
// this feel free to clean it!

require '../include/login.inc';

@include './github-config.php';
if (!defined('GITHUB_CLIENT_ID') || !defined('GITHUB_CLIENT_SECRET')) {
  die('GITHUB_CLIENT_ID or GITHUB_CLIENT_SECRET not defined. Please verify ./github-config.php');
}

define('GITHUB_PHP_OWNER_TEAM_ID', 65141);
define('GITHUB_REPO_TEAM_ID', 138591);
if (!defined('GITHUB_USER_AGENT')) {
  define('GITHUB_USER_AGENT', 'php.net repository management (master.php.net, systems@php.net, johannes@php.net)');
}

function github_api($endpoint, $method = 'GET', $options = [])
{
  $options['method'] = $method;
  $options['user_agent'] = GITHUB_USER_AGENT;

  $ctxt = stream_context_create(['http' => $options]);

  $url = 'https://api.github.com'.$endpoint;
  $s = @file_get_contents($url, false, $ctxt);
  if ($s === false) {
    die('Request to GitHub failed. Endpoint: '.$endpoint);
  }
  
  $retval = json_decode($s);
  return $retval;
}
  
function github_current_user($access_token = false)
{
  if (!$access_token) {
    $access_token = $_SESSION['github']['access_token'];
  }

  if (empty($_SESSION['github']['current_user'])) {
    $user = github_api('/user?access_token='.urlencode($access_token));
    if (!$user->login) {
      die('Failed to get current user');
    }

    $_SESSION['github']['current_user'] = $user;
  }

  return $_SESSION['github']['current_user'];
}

function github_require_valid_user()
{
  if (isset($_SESSION['github']['access_token'])) {
    return true;
  }

  if (isset($_GET['code'])) {
    $data = [
      'client_id' => GITHUB_CLIENT_ID,
      'client_secret' => GITHUB_CLIENT_SECRET,
      'code' => $_GET['code']
    ];
    $data_encoded = http_build_query($data);
    $opts = [
      'method' => 'POST',
      'user_agent' => GITHUB_USER_AGENT,
      'header'  => 'Content-type: application/x-www-form-urlencoded',
      'content' => $data_encoded,
    ];
    $ctxt = stream_context_create(['http' => $opts]);
    $s = @file_get_contents('https://github.com/login/oauth/access_token', false, $ctxt);
    if (!$s) {
      die('Failed while checking with GitHub,either you are trying to hack us or our configuration is wrong (GITHUB_CLIENT_SECRET outdated?)');
    }
    $gh = [];
    parse_str($s, $gh);
    if (empty($gh['access_token'])) {
      die("GitHub responded but didn't send an access_token");
    }

    $user = github_current_user($gh['access_token']);

    $endpoint = '/teams/'.urlencode(GITHUB_PHP_OWNER_TEAM_ID).'/members/'.urlencode($user->login);
    $opts = ['user_agent' => GITHUB_USER_AGENT];
    $ctxt = stream_context_create(['http' => $opts]);
    $is_member = file_get_contents('https://api.github.com'.$endpoint.'?access_token='.urlencode($gh['access_token']), false, $ctxt);

    if ($is_member === false) {
      head("github administration");
      echo '<h1>You (Authenticated GitHub user: '.htmlentities($user->login). ') are no member of the php organization on github.</h1>'.
        '<p>Please contact an existing member if you see need.</p>';
      foot();
      exit;
    }
    // SUCCESS
    $_SESSION['github']['access_token'] = $gh['access_token'];
    header('Location: github.php');
    exit;
  }

  // Start oauth
  header('Location: https://github.com/login/oauth/authorize?scope=repo&client_id='.urlencode(GITHUB_CLIENT_ID));
  exit;
}

if (isset($_POST['description']) && isset($_SESSION['github']['access_token'])) {
  action_create_repo();
} elseif (isset($_GET['login']) || isset($_GET['code']) || isset($_SESSION['github']['access_token'])) {
  action_form();
} else {
  action_default();
}

function action_default()
{
  head("github administration");
  echo '<p>This tool is for administrating PHP repos on GitHub. Currently it is used for adding repos only.</p>';
  echo '<p><b>NOTE:</b> Only members of the PHP organisation on GitHub can use this tool. We try to keep the number of members limited.</p>';
  echo '<p>In case you are a member you can <a href="github.php?login=1">login using GitHub</a>.</p>';
  foot();
}

function action_form()
{
  github_require_valid_user();
  $user = $_SESSION['github']['current_user'];
  head("github administration");
?>
<p><b>GitHub user: </b> <?php echo htmlentities($user->login); ?></p>
<p>Creating a GitHub repo using this form ensures the proper configuration. This
includes disabling the GitHub wiki and issue tracker as well as enabling the
php-pulls user to push changes made on git.php.net.</p>
<p>The name, description and homepage should follow other existing repositories.</p>
<form method="post" action="github.php">
Github repo name: http://github.com/php/<input name="name"> (i.e. pecl-category-foobar)<br>
Description: <input name="description"> (i.e. PECL foobar extension)<br>
Homepage: <input name="homepage"> (i.e. http://pecl.php.net/package/foobar)<br>
<input type="submit" value="Create Repository on GitHub">
<input type="hidden" name="action" value="create">
<?php
  foot();
}

function action_create_repo()
{
  github_require_valid_user();

  $data = [
    'name' => $_POST['name'],
    'description' => $_POST['description'],

    'homepage' => $_POST['homepage'],
    'private' => false,
    'has_issues' => false,
    'has_wiki' => false,
    'has_downloads' => false,
    'team_id' => GITHUB_REPO_TEAM_ID,
  ];
  $data_j = json_encode($data);
  $opts = [
    'content' => $data_j,
  ];
  $res = github_api('/orgs/php/repos?access_token='.urlencode($_SESSION['github']['access_token']), 'POST', $opts);

  head("github administration");
  if (isset($res->html_url)) {
    echo '<p>Repo created!</p><p><a href="'.htmlentities($res->html_url, ENT_QUOTES).'">Check on GitHub</a>.</p>';
  } else {
    echo "Error while creating repo.";
  }
  foot();
}
?>
