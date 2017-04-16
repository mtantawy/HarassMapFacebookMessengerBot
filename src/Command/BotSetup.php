<?php

namespace HarassMapFbMessengerBot\Command;

use Tgallice\FBMessenger\Messenger;
use Tgallice\FBMessenger\Model\Button\Postback;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BotSetup extends Command
{
    protected function configure()
    {
        $this
        ->setName('bot:setup')
        ->setDescription('Performs Facebook Messenger Bot Setup.')
        ->setHelp('This command sets Greeting message, Get Started button, and Persistent Menu.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pageToken = getenv('PAGE_TOKEN');
        $verifyToken = getenv('VERIFY_TOKEN');

        $messenger = Messenger::create($pageToken);

        $settings = require('BotSettings.php');

        $messenger->setGreetingText(
            $settings['greeting_text']['default'],
            $settings['greeting_text']['localized']
        );
        $messenger->setStartedButton($settings['get_started']);
        $messenger->setPersistentMenu($settings['persistent_menu']);
    }
}
