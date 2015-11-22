<?php
namespace Puli\Cli\ArtifactBuilder;


use Puli\Discovery\Binding\AbstractBinding;
use Webmozart\Console\Api\Args\Args;
use Webmozart\Console\Api\Config\OptionCommandConfig;

interface ArtifactBuilder
{
    /**
     * Alters the "bind add" options to add options specific to the kind of artifact managed by this builder.
     *
     * @param OptionCommandConfig $optionCommandConfig
     */
    public function alterAddOptionCommandConfig(OptionCommandConfig $optionCommandConfig);

    /**
     * Returns true if this builder can build the binding from Args passed in parameter.
     *
     * @param Args $args
     * @return boolean
     */
    public function canBuildFromArgs(Args $args);

    /**
     * Returns the binding from Args passed in parameter.
     *
     * @param Args $args
     * @return AbstractBinding
     */
    public function buildFromArgs(Args $args);
}