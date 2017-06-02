<?php
namespace HarassMapFbMessengerBot\Handler;

use Interop\Container\ContainerInterface;
use Tgallice\FBMessenger\Callback\CallbackEvent;
use HarassMapFbMessengerBot\User;

interface Handler
{
    public function __construct(ContainerInterface $container, CallbackEvent $event, User $user);

    public function handle();
}
