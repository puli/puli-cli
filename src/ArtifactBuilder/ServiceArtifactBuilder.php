<?php


namespace Puli\Cli\ArtifactBuilder;


use Puli\Discovery\Binding\AbstractBinding;
use Puli\Discovery\Binding\ClassBinding;
use Puli\Discovery\Binding\ServiceBinding;
use Webmozart\Console\Api\Args\Args;
use Webmozart\Console\Api\Args\Format\Option;
use Webmozart\Console\Api\Config\OptionCommandConfig;

class ServiceArtifactBuilder extends AbstractArtifactBuilder
{
    /**
     * Alters the "bind add" options to add options specific to the kind of artifact managed by this builder.
     *
     * @param OptionCommandConfig $optionCommandConfig
     */
    public function alterAddOptionCommandConfig(OptionCommandConfig $optionCommandConfig)
    {
        $optionCommandConfig->addOption('service', null, Option::NO_VALUE, 'Adds a service binding');
    }

    /**
     * Returns true if this builder can build the binding from Args passed in parameter.
     *
     * @param Args $args
     * @return boolean
     */
    public function canBuildFromArgs(Args $args)
    {
        if ($args->isOptionSet('service')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the binding from Args passed in parameter.
     *
     * @param Args $args
     * @return AbstractBinding
     */
    public function buildFromArgs(Args $args)
    {
        $bindingParams = array();
        $artifact = $args->getArgument('artifact');

        $this->parseParams($args, $bindingParams);

        return new ServiceBinding(
            $artifact,
            $args->getArgument('type'),
            $bindingParams
        );

    }


}
