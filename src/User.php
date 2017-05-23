<?php

namespace HarassMapFbMessengerBot;

use DateTime;

class User
{
    const LOCALE_DEFAULT = 'ar_AR';
    const GENDER_UNKNOWN = 'unknown';
    const TIMEZONE_DEFAULT = 0;

    private $id;
    private $psid;
    private $firstName;
    private $lastName;
    private $locale;
    private $timezone;
    private $gender;
    private $preferredLanguage;
    private $createdAt;
    private $updatedAt;

    public function __construct(
        string $psid,
        string $firstName,
        string $lastName,
        string $locale = self::LOCALE_DEFAULT,
        int $timezone = self::TIMEZONE_DEFAULT,
        string $gender = self::GENDER_UNKNOWN,
        string $preferredLanguage = self::LOCALE_DEFAULT,
        int $id = null,
        DateTime $createdAt = null,
        DateTime $updatedAt = null
    ) {
        $this->id = $id;
        $this->psid = $psid;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->locale = $locale;
        $this->timezone = $timezone;
        $this->gender = $gender;
        $this->preferredLanguage = $preferredLanguage;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPsid(): string
    {
        return $this->psid;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getTimezone(): int
    {
        return $this->timezone;
    }

    public function getGender(): string
    {
        return $this->gender;
    }

    public function getPreferredLanguage(): string
    {
        return $this->preferredLanguage;
    }

    public function getCreatedAt(): DateTime
    {
        return new DateTime($this->createdAt);
    }

    public function getUpdatedAt(): DateTime
    {
        return new DateTime($this->UpdatedAt);
    }
}
