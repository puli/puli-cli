<?php


namespace Puli\Cli\ArtifactBuilder;


use Puli\Discovery\Binding\AbstractBinding;
use Puli\Discovery\Binding\ResourceBinding;
use Webmozart\Console\Api\Args\Args;
use Webmozart\Console\Api\Args\Format\Option;
use Webmozart\Console\Api\Config\OptionCommandConfig;
use Webmozart\PathUtil\Path;

class ResourceArtifactBuilder extends AbstractArtifactBuilder
{
    /**
     * Alters the "bind add" options to add options specific to the kind of artifact managed by this builder.
     *
     * @param OptionCommandConfig $optionCommandConfig
     */
    public function alterAddOptionCommandConfig(OptionCommandConfig $optionCommandConfig)
    {
        $optionCommandConfig->addOption('language', null, Option::REQUIRED_VALUE, 'The language of the resource query', 'glob', 'language');
    }


    /**
     * Returns true if this builder can build the binding from Args passed in parameter.
     *
     * @param Args $args
     * @return boolean
     */
    public function canBuildFromArgs(Args $args)
    {
        return true;
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

        return new ResourceBinding(
            Path::makeAbsolute($artifact, '/'),
            $args->getArgument('type'),
            $bindingParams,
            $args->getOption('language')
        );
    }
}
