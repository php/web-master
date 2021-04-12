<?php

namespace App;

class Access
{
    const PHP_ADMINS = [
        'jimw',
        'rasmus',
        'andrei',
        'zeev',
        'andi',
        'sas',
        'thies',
        'rubys',
        'ssb',
        'wez',
        'shane',
        'sterling',
        'goba',
        'imajes',
        'jon',
        'alan_k',
        'stas',
        'iliaa',
        'jmcastagnetto',
        'mj',
        'gwynne',
        'lsmith',
        'dsp',
        'philip',
        'davidc',
        'helly',
        'derick',
        'bjori',
        'pajoye',
        'danbrown',
        'felipe',
        'johannes',
        'tyrael',
        'salathe',
        'cmb',
        'kalle',
        'krakjoe',
        'nikic'
    ];

    const PHP_MIRROR_SITE_ADMINS = [
        'jimw',
        'rasmus',
        'andrei',
        'zeev',
        'andi',
        'sas',
        'thies',
        'rubys',
        'ssb',
        'imajes',
        'goba',
        'derick',
        'cortesi',
        'wez',
        'bjori',
        'philip',
        'danbrown',
        'tyrael',
        'dm',
        'kalle',
        'googleguy',
        'nikic'
    ];

    /**
     * Returns true, if user is admin, otherwise false
     *
     * @param $username
     *
     * @return bool
     */
    public static function isAdmin($username)
    {
        return in_array($username, self::PHP_ADMINS, true);
    }

    /**
     * Returns true, if user is mirror site admin, otherwise false
     *
     * @param $username
     *
     * @return bool
     */
    public static function isMirrorSiteAdmin($username)
    {
        return in_array($username, self::PHP_MIRROR_SITE_ADMINS, true);
    }

    /**
     * Returns true, if username can modify userId row
     *
     * @param $username
     * @param $userId
     *
     * @return bool
     */
    public static function canUserEdit($username, $userId)
    {
        if (self::isAdmin($username)) {
            return true;
        }

        $db = DB::connect();
        $sql = $db->prepare('SELECT userid FROM users WHERE userid = ? AND (email = ? OR username = ?)');
        $sql->execute([$userId, $username, $username]);

        return $sql->rowCount() > 0;
    }
}