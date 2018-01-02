<?php

namespace Varhall\Mailino\DI;

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
        return $this->getConfig([
            'template_dir'      => $builder->parameters['appDir'] . DIRECTORY_SEPARATOR . '/presenters/templates/emails',
            'sender_email'      => '',
            'sender_name'       => '',
            'subject_prefix'    => '',
        ]);
    }

    public function beforeCompile()
    {
        $config = $this->configuration();
        $builder = $this->getContainerBuilder();

        $classes = $builder->getClassList();

        if (empty($classes[AbstractEmailsService::class][true]))
            return;

        foreach ($classes[AbstractEmailsService::class][true] as $name) {
            $definition = $builder->getDefinition($name);

            $this->addServiceSetup($definition, 'setTemplateDir', $config['template_dir']);
            $this->addServiceSetup($definition, 'setSenderEmail', $config['sender_email']);
            $this->addServiceSetup($definition, 'setSenderName', $config['sender_name']);
            $this->addServiceSetup($definition, 'setSubjectPrefix', $config['subject_prefix']);
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
