<?php
// $Id$

// Try to remove antispam bits from the address
function clean_antispam($email)
{
    $remove_spam = "![-_]?(NO|I[-_]?HATE|DELETE|REMOVE)[-_]?(THIS)?(ME|SPAM)?[-_]?!i";
    return preg_replace($remove_spam, "", trim($email));
}

// Try to check that this email address is valid
function is_emailable_address($email)
{
    // Exclude our mailing list hosting servers
    $hosts_regex = "!(lists\.php\.net)!i";
    $excluded_hosts = preg_match($hosts_regex, $email);

    if (!$excluded_hosts && !empty($email)) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) == $email;
    } else {
        return FALSE;
    }
}
