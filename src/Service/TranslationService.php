<?php
namespace HarassMapFbMessengerBot\Service;

use Interop\Container\ContainerInterface;

class TranslationService
{
    const LANG_FILES_PATH = '../lang/';

    protected $container;

    private $loadedStrings = [];

    private $availableLocales = [
        'en_US',
        'ar_AR',
    ];
       
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getLocalizedString(string $string, string $locale, string $gender = null): string
    {
        if (! in_array($locale, $this->availableLocales)) {
            $locale = 'en_US';
        }

        if (!isset($this->loadedStrings[$locale])) {
            $this->loadedStrings[$locale] = include __DIR__ . '/' . self::LANG_FILES_PATH . $locale . '.php';
        }

        if (is_array($this->loadedStrings[$locale][$string])) {
            return $this->loadedStrings[$locale][$string][$gender] ?? $string;
        }

        return $this->loadedStrings[$locale][$string] ?? $string;
    }
}
