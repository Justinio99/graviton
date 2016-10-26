<?php
/**
 * ApiDefinitionLoaderPass
 */

namespace Graviton\ProxyExtensionBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class ApiDefinitionLoaderPass
 *
 * @package Graviton\ProxyExtensionBundle\Definition\Loader
 *
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://swisscom.ch
 */
class ApiDefinitionLoaderPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container Symfony Service container
     */
    public function process(ContainerBuilder $container)
    {
        // always first check if the primary service is defined
        if (!$container->has('graviton.proxy.service.loaderfactory')) {
            return;
        }

        $definition = $container->findDefinition('graviton.proxy.service.loaderfactory');

        // find all service IDs with the app.mail_transport tag
        $taggedServices = $container->findTaggedServiceIds('graviton.proxy.definition.loader');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $definition->addMethodCall(
                    'addLoaderDefinition',
                    array(
                        new Reference($id),
                        $attributes["alias"]
                    )
                );
            }
        }
    }
}
