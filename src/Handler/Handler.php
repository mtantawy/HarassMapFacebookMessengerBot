<?php
namespace HarassMapFbMessengerBot\Handler;

use Interop\Container\ContainerInterface;
use Tgallice\FBMessenger\Callback\CallbackEvent;

interface Handler
{
    public function __construct(ContainerInterface $container, CallbackEvent $event);

    public function handle();
}
