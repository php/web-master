<?php
$config = array(
	'repos' => array(
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

switch  ($_SERVER['HTTP_X_GITHUB_EVENT']) {
	case 'ping':
		break;
	case 'pull_request':
		$payload = json_decode($body);
		$action = $payload->action;
		$PRNumber = $payload->number;
		$PR = $payload->pull_request;
		$htmlUrl = $PR->html_url;
		$title = $PR->title;
		$description = $PR->body;
		$repoName = $PR->base->repo->name;

		$targetBranch = $PR->base->ref;
		$mergeable = $PR->mergeable;

		// if we somehow end up receiving a PR for a repo not matching anything send it to systems so that we can fix it
		$to = 'systems@php.net';
		foreach ($config['repos'] as $repoPrefix => $email) {
			if (strpos($repoName, $repoPrefix) === 0) {
				$to = $email;
			}
		}

		$subject = sprintf('[PR][%s][#%s][%s][%s] - %s', $repoName, $PRNumber, $targetBranch, $action, $title);
		$message = sprintf("You can view the Pull Request on github:\r\n%s", $htmlUrl);
		if ($mergeable === false) {
			$message .= "\r\n\r\nWarning: according to github, the Pull Request cannot be merged without manual conflict resolution!";
		}
		$message .= sprintf("\r\n\r\nPull Request Description:\r\n%s", $description);
		$headers = "From: noreply@php.net\r\nContent-Type: text/plain; charset=utf-8\r\n";
		mail($to, '=?utf-8?B?'.base64_encode($subject).'?=', $message, $headers, "-fnoreply@php.net");
		break;
	default:
		header('HTTP/1.1 501 Not Implemented');
}

function verify_signature($requestBody) {
	if(isset($_SERVER['HTTP_X_HUB_SIGNATURE'])){
		$parts = explode("=", $_SERVER['HTTP_X_HUB_SIGNATURE'], 2);
		if (count($parts) == 2) {
			return hash_hmac($parts[0], $requestBody, getenv('GITHUB_SECRET')) === $parts[1];
		}
	}
	return false;
}
