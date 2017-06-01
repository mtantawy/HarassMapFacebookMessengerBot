<?php
namespace HarassMapFbMessengerBot\Service;

use Interop\Container\ContainerInterface;

class TranslationService
{
    const LANG_FILES_PATH = '../lang/';

    protected $container;
       
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
}
