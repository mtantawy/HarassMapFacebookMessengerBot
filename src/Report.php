<?php

namespace HarassMapFbMessengerBot;

use DateTime;

class Report
{
    const STEP_INIT = 'init';
    const STEP_RELATION = 'relation';
    const STEP_DETAILS = 'details';
    const STEP_DATETIME = 'datetime';
    const STEP_HARASSMENT_TYPE = 'harassment_type';
    const STEP_HARASSMENT_TYPE_DETAILS = 'harassment_type_details';
    const STEP_ASSISTANCE_OFFERED = 'assistance_offered';
    const STEP_LOCATION = 'location';
    const STEP_DONE = 'done';

    const HARASSMENT_TYPES = [
        'verbal' => [
            1 => 'النظر المتفحّص',
            2 => 'التلميحات بالوجه',
            3 => 'النداءات (البسبسة)',
            4 => 'التعليقات',
            5 => 'الملاحقة أو التتبع',
            6 => 'الدعوة الجنسية',
        ],
        'physical' => [
            1 => 'اللمس',
            2 => 'التعري',
            3 => 'التهديد والترهيب',
            4 => 'الاعتداء الجنسي',
            5 => 'الاغتصاب',
            6 => 'التحرش الجماعي',
        ]
    ];

    const ORDERED_STEPS = [
        self::STEP_INIT,
        self::STEP_RELATION,
        self::STEP_DETAILS,
        self::STEP_DATETIME,
        self::STEP_HARASSMENT_TYPE,
        self::STEP_HARASSMENT_TYPE_DETAILS,
        self::STEP_ASSISTANCE_OFFERED,
        self::STEP_LOCATION,
        self::STEP_DONE
    ];

    private $id;
    private $userId;
    private $step;
    private $relation;
    private $details;
    private $datetime;
    private $harassmentType;
    private $harassmentTypeDetails;
    private $assistenceOffered;
    private $latitude;
    private $longitude;
    private $createdAt;
    private $updatedAt;

    public function __construct(
        int $userId,
        string $step,
        string $relation = null,
        string $details = null,
        DateTime $datetime = null,
        string $harassmentType = null,
        string $harassmentTypeDetails = null,
        bool $assistenceOffered = null,
        string $latitude = null,
        string $longitude = null,
        int $id = null,
        DateTime $createdAt = null,
        DateTime $updatedAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->step = $step;
        $this->relation = $relation;
        $this->details = $details;
        $this->datetime = $datetime;
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

    public function getRelation(): ?string
    {
        return $this->relation;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function getDateTime(): ?DateTime
    {
        return new DateTime($this->datetime);
    }

    public function getHarassmentType(): ?string
    {
        return $this->harassmentType;
    }

    public function getHarassmentTypeDetails(): ?string
    {
        return $this->harassmentTypeDetails;
    }

    public function isAssistanceOffered(): ?bool
    {
        return (bool) $this->assistenceOffered;
    }

    public function getLocation(): ?array
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
