<?php
namespace HarassMapFbMessengerBot\Service;

use HarassMapFbMessengerBot\User;
use Interop\Container\ContainerInterface;
use Tgallice\FBMessenger\Model\UserProfile;
use DateTime;

class UserService
{
    const TABLE_USERS = 'users';

    protected $container;
       
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getOrCreateUserByFacebookPSID(string $psid): User
    {
        if (is_null($user = $this->getUserByFacebookPSID($psid))) {
            $this->createUser($psid, $this->getFacebookUserProfile($psid));
            $user = $this->getUserByFacebookPSID($psid);
        }

        return $user;
    }

    public function getUserByFacebookPSID(string $psid): ?User
    {
        $user = $this->container->dbConnection->fetchAssoc(
            'SELECT * FROM `' . self::TABLE_USERS . '` WHERE `psid` = ?',
            [$psid]
        );

        return is_array($user) ? new User(
            $user['psid'],
            $user['first_name'],
            $user['last_name'],
            $user['locale'],
            $user['timezone'],
            $user['gender'],
            $user['preferred_language'],
            $user['id'],
            new DateTime($user['created_at']),
            new DateTime($user['updated_at'])
        ) : null;
    }

    public function createUser(string $psid, UserProfile $facebookUserProfile)
    {
        $this->container->dbConnection->insert(self::TABLE_USERS, [
            'psid' => $psid,
            'first_name' => $facebookUserProfile->getFirstName(),
            'last_name' => $facebookUserProfile->getLastName(),
            'locale' => $facebookUserProfile->getLocale() ?? User::LOCALE_DEFAULT,
            'timezone' => $facebookUserProfile->getTimezone() ?? User::TIMEZONE_DEFAULT,
            'gender' => $facebookUserProfile->getGender() ?? User::GENDER_UNKNOWN,
            'preferred_language' => $facebookUserProfile->getLocale() ?? User::LOCALE_DEFAULT,
        ]);
    }

    public function getFacebookUserProfile(string $psid): UserProfile
    {
        return $this->container->messenger->getUserProfile($psid);
    }
}
