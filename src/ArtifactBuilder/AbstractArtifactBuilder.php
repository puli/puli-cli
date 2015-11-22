<?php
namespace Puli\Cli\ArtifactBuilder;

use Webmozart\Console\Api\Args\Args;

abstract class AbstractArtifactBuilder implements ArtifactBuilder
{
    protected function parseParams(Args $args, array &$bindingParams)
    {
        foreach ($args->getOption('param') as $parameter) {
            $pos = strpos($parameter, '=');

            if (false === $pos) {
                throw new RuntimeException(sprintf(
                    'The "--param" option expects a parameter in the form '.
                    '"key=value". Got: "%s"',
                    $parameter
                ));
            }

            $key = substr($parameter, 0, $pos);
            $value = StringUtil::parseValue(substr($parameter, $pos + 1));

            $bindingParams[$key] = $value;
        }
    }
}
