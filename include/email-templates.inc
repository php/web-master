<?php // vim: et ts=4 sw=4
/* Mail Templates */

function mt_approve_user($userinfo, $mailtext) {
    $message =
"Your VCS account ($userinfo[username]) was created.

If you ever forget your password, you can reset it at:
    https://master.php.net/forgot.php

To change your password, or other information about you please login on:
    https://master.php.net/manage/users.php?username=$userinfo[username]

Your profile page will be created shortly and can be found at:
    https://people.php.net/$userinfo[username]

The $userinfo[username]@php.net forward to $userinfo[email] should
be active within the next 24 hours.


Please note: Before you can push to our repositories, please add your
SSH Key on master (using the URL above).

All our git repositories are listed at:
    http://git.php.net/

SVN is still used by certain projects (most notably docs):
    http://svn.php.net/


Welcome to the PHP development team! If you encounter any problems
with your VCS account, feel free to send us a note at $mailtext.
";
    return $message;
}

function mt_remove_user($userinfo) {
    $message =
"Your VCS account ($userinfo[username]) was deleted.

Feel free to send us a note at group@php.net to find out why this
was done.";

    return $message;
}

function mt_deny_user($userinfo) {
    $message = 
"Your VCS account request ($userinfo[username]) was denied.

The most likely reason is that you did not read the reasons for
which VCS accounts are granted, and your request failed to meet
the list of acceptable criteria.

We urge you to make another appeal for a VCS account, but first
it helps to write the appropriate list and:

 * Introduce yourself
 * Explain what you want to work on
 * And show what work you've already done (patches)

Choose a list that relates to your request:

 * Internals:     internals@lists.php.net 
 * Documentation: phpdoc@lists.php.net 
 * PECL:          pecl-dev@lists.php.net 
 * PEAR:          pear-group@lists.php.net 
 * Other:         group@php.net 

PHP accounts are granted to developers who have earned the trust
of existing PHP developers through patches, and have demonstrated
the ability to work with others.
";

    return $message;
}

