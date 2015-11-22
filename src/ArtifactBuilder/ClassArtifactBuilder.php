<?php


namespace Puli\Cli\ArtifactBuilder;


use Puli\Discovery\Binding\AbstractBinding;
use Puli\Discovery\Binding\ClassBinding;
use Webmozart\Console\Api\Args\Args;
use Webmozart\Console\Api\Args\Format\Option;
use Webmozart\Console\Api\Config\OptionCommandConfig;

class ClassArtifactBuilder extends AbstractArtifactBuilder
{
    /**
     * Alters the "bind add" options to add options specific to the kind of artifact managed by this builder.
     *
     * @param OptionCommandConfig $optionCommandConfig
     */
    public function alterAddOptionCommandConfig(OptionCommandConfig $optionCommandConfig)
    {
        $optionCommandConfig->addOption('class', null, Option::NO_VALUE, 'Force adding of a class binding');

    }

    /**
     * Returns true if this builder can build the binding from Args passed in parameter.
     *
     * @param Args $args
     * @return boolean
     */
    public function canBuildFromArgs(Args $args)
    {
        $artifact = $args->getArgument('artifact');

        if (false !== strpos($artifact, '\\') || $args->isOptionSet('class')) {
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

        return new ClassBinding(
            $artifact,
            $args->getArgument('type'),
            $bindingParams
        );

    }
}