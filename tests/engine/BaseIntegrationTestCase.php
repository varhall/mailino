<?php

namespace Tests\Engine;

use Nette\DI\Container;

class BaseIntegrationTestCase extends BaseTestCase
{
    /** @var Container */
    protected $container;

    public function setUp()
    {
        parent::setUp();

        $config = [
            'mailino'    => [
                'template_dir'  => __DIR__ . '/../fixtures/templates/',
                'sender'  => [
                    'email' => 'sender@test.com',
                    'name' => 'Sender',
                ],
                'subject_prefix' => 'TST',
                'mjml' => [
                    'api_id' => '190dacaa-f961-4537-b474-61350ea4ac6f',
                    'secret' => '1aa4e4ba-1df6-4b70-a3ac-c5df39ee24c6'
                ]
            ],
            'services'  => [
                'nette.mailer' => TestMailer::class
            ]
        ];


        $this->container = $this->createContainer($config);
    }
}