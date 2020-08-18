<?php

namespace Varhall\Mailino\DI;

use Nette\DI\Config\Helpers;
use Varhall\Mailino\Services\AbstractEmailsService;

/**
 * Nette extension class
 *
 * @author Ondrej Sibrava <sibrava@varhall.cz>
 */
class MailinoExtension extends \Nette\DI\CompilerExtension
{
    protected function configuration()
    {
        $builder = $this->getContainerBuilder();
        return Helpers::merge($this->getConfig(), [
            'template_dir'      => $builder->parameters['appDir'] . DIRECTORY_SEPARATOR . '/presenters/templates/emails',
            'sender_email'      => '',
            'sender_name'       => '',
            'subject_prefix'    => '',
            'verify_ssl'        => FALSE,
        ]);
    }

    public function beforeCompile()
    {
        $config = $this->configuration();
        $builder = $this->getContainerBuilder();

        foreach ($builder->findByType(AbstractEmailsService::class) as $definition) {
            $this->addServiceSetup($definition, 'setTemplateDir', $config['template_dir']);
            $this->addServiceSetup($definition, 'setSenderEmail', $config['sender_email']);
            $this->addServiceSetup($definition, 'setSenderName', $config['sender_name']);
            $this->addServiceSetup($definition, 'setSubjectPrefix', $config['subject_prefix']);
            $this->addServiceSetup($definition, 'setVerifySsl', $config['verify_ssl']);
        }
    }

    protected function addServiceSetup(&$definitition, $entity, $value)
    {
        foreach ($definitition->getSetup() as $setup) {
            if ($setup->entity === $entity)
                return;
        }

        $definitition->addSetup($entity, [ $value ]);
    }
}
