<?php

namespace HarassMapFbMessengerBot;

use DateTime;

class Report
{
    const STEP_INIT = 'init';
    const STEP_RELATION = 'relation';
    const STEP_DETAILS = 'details';
    const STEP_DATE = 'date';
    const STEP_TIME = 'time';
    const STEP_HARASSMENT_TYPE = 'harassment_type';
    const STEP_HARASSMENT_TYPE_DETAILS = 'harassment_type_details';
    const STEP_ASSISTANCE_OFFERED = 'assistance_offered';
    const STEP_LOCATION = 'location';
    const STEP_DONE = 'done';

    private $id;
    private $userId;
    private $step;
    private $relation;
    private $details;
    private $date;
    private $time;
    private $harassmentType;
    private $harassmentTypeDetails;
    private $assistenceOffered;
    private $latitude;
    private $longitude;
    private $createdAt;
    private $updatedAt;

    public $orderedSteps = [
        self::step_INIT,
        self::step_RELATION,
        self::step_DETAILS,
        self::step_DATE,
        self::step_TIME,
        self::step_HARASSMENT_TYPE,
        self::step_HARASSMENT_TYPE_DETAILS,
        self::step_ASSISTANCE_OFFERED,
        self::step_LOCATION,
        self::step_DONE
    ];

    public function __construct(
        int $userId,
        string $step,
        string $relation,
        string $details,
        string $date,
        string $time,
        string $harassmentType,
        string $harassmentTypeDetails,
        bool $assistenceOffered,
        string $latitude,
        string $longitude,
        int $id = null,
        DateTime $createdAt = null,
        DateTime $updatedAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->step = $step;
        $this->relation = $relation;
        $this->details = $details;
        $this->date = $date;
        $this->time = $time;
        $this->harassmentType = $harassmentType;
        $this->harassmentTypeDetails = $harassmentTypeDetails;
        $this->assistenceOffered = $assistenceOffered;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getStep(): string
    {
        return $this->step;
    }

    public function getRelation(): string
    {
        return $this->relation;
    }

    public function getDetails(): string
    {
        return $this->details;
    }

    public function getDateTime(): DateTime
    {
        return new DateTime($this->date . $this->time);
    }

    public function getHarassmentType(): string
    {
        return $this->harassmentType;
    }

    public function getHarassmentTypeDetails(): string
    {
        return $this->harassmentTypeDetails;
    }

    public function isAssistanceOffered(): bool
    {
        return (bool) $this->assistenceOffered;
    }

    public function getLocation(): array
    {
        return [
            'longitude' => $this->longitude,
            'latitude' => $this->latitude,
        ];
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
