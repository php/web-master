<?php
function verify_signature($requestBody) {
	if(isset($_SERVER['HTTP_X_HUB_SIGNATURE'])){
		$parts = explode("=", $_SERVER['HTTP_X_HUB_SIGNATURE'], 2);
		if (count($parts) == 2) {
			return hash_hmac($parts[0], $requestBody, getenv('GITHUB_SECRET')) === $parts[1];
		}
	}
	return false;
}

function get_repo_email($repos, $repoName) {
    // if we somehow end up receiving a PR for a repo not matching anything send it to systems so that we can fix it
    $to = 'systems@php.net';
    foreach ($repos as $repoPrefix => $email) {
        if (strpos($repoName, $repoPrefix) === 0) {
            $to = $email;
        }
    }

    return $to;
}

function prep_title($action, $PR, $base) {
    $PRNumber = $PR->number;
    $title = $PR->title;

    $repoName = $base->repo->name;
    $targetBranch = $base->ref;

    $subject = sprintf('[PR][%s][#%s][%s][%s] - %s', $repoName, $PRNumber, $targetBranch, $action, $title);

    return $subject;
}


$CONFIG = array(
	'repos' => array(
		'php-langspec' => 'standards@lists.php.net',
		'php-src' => 'git-pulls@lists.php.net',
		'web-' => 'php-webmaster@lists.php.net',
		'pecl-' => 'pecl-dev@lists.php.net',
	),
);

$body = file_get_contents("php://input");

if (!verify_signature($body)) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

$PR = $payload->pull_request;
$action = $payload->action;
$payload = json_decode($body);
$htmlUrl = $PR->html_url;
$repoName = $PR->base->repo->name;
$description = $PR->body;

switch  ($_SERVER['HTTP_X_GITHUB_EVENT']) {
	case 'ping':
		break;
	case 'pull_request':
		$mergeable = $PR->mergeable;

        $to = get_repo_email($CONFIG["repos"], $repoName);
        $subject = prep_title($action, $PR, $PR->base);

		$message = sprintf("You can view the Pull Request on github:\r\n%s", $htmlUrl);
		if ($mergeable === false) {
			$message .= "\r\n\r\nWarning: according to github, the Pull Request cannot be merged without manual conflict resolution!";
		}
		$message .= sprintf("\r\n\r\nPull Request Description:\r\n%s", $description);
		$headers = "From: noreply@php.net\r\nContent-Type: text/plain; charset=utf-8\r\n";
		mail($to, '=?utf-8?B?'.base64_encode($subject).'?=', $message, $headers, "-fnoreply@php.net");
		break;

    case 'pull_request_review_comment':
        $username = $payload->user->login;
		$comment = $payload->comment->body;

        $to = get_repo_email($CONFIG["repos"], $repoName);
        $subject = prep_title($action, $PR, $PR->base);
		$message = sprintf("You can view the Pull Request on github:\r\n%s", $htmlUrl);
		$message .= sprintf("\r\n\r\nPull Request Comment:\r\n%s", $description);
		$message .= sprintf("\r\nMade by: %s", $username);

		$headers = "From: noreply@php.net\r\nContent-Type: text/plain; charset=utf-8\r\n";
		mail($to, '=?utf-8?B?'.base64_encode($subject).'?=', $message, $headers, "-fnoreply@php.net");
        break;

	default:
		header('HTTP/1.1 501 Not Implemented');
}

