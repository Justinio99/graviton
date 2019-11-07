<?php
/**
 * build a collection_name to routerId mapping for ExtReference Types
 *
 * This is all done the cheap way by just inferring collection names from
 * the available serviecs that are tagged as rest service. This also means
 * we need to stick to the naming conventions already there even more.
 */

namespace Graviton\DocumentBundle\DependencyInjection\Compiler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Graviton\DocumentBundle\DependencyInjection\Compiler\Utils\DocumentMap;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class DocumentMapCompilerPass implements CompilerPassInterface
{
    /**
     * create mapping from services
     *
     * @param ContainerBuilder $container container builder
     * @return void
     */
    public function process(ContainerBuilder $container)
    {
        // If it's inside vendor library or running as graviton base.
        $rootDir = $container->getParameter('kernel.root_dir');

        if (strpos($rootDir, 'vendor/graviton')) {
            $dirs = [
                $rootDir.'/../..'
            ];
        } else {
            $dirs = [
                __DIR__ . '/../../../..',
                $rootDir.'/../vendor/graviton'
            ];
        }

        $dynamicBundleDir = $container->getParameter('graviton.generator.dynamicbundle.dir');
        if (!empty($dynamicBundleDir)) {
            // if this is not an absolute dir, make it relative to the base dir
            if (substr($dynamicBundleDir, 0, 1) !== '/') {
                $dynamicBundleDir = $container->getParameter('kernel.root_dir').'/../'.$dynamicBundleDir;
            }

            $dirs[] = $dynamicBundleDir;
        } else {
            // default dynamic bundle dir is withing our ./src
            $dynamicBundleDir = __DIR__.'/../../../../GravitonDyn';
            if (!is_dir($dynamicBundleDir)) {
                $dynamicBundleDir = null;
            }
        }

        $registry = $container->get('doctrine_mongodb');;
        foreach ($registry->getManagers() as $dm) {
            /** @var DocumentManager $dm */
            $classes = $dm->getMetadataFactory()->getAllMetadata();
            var_dump($classes); die;
            $dm->getHydratorFactory()->generateHydratorClasses($classes);
        }

        var_dump($registry); die;

        $documentMap = new DocumentMap(
            (new Finder())
                ->in($dirs)
                ->path('Resources/config/doctrine')
                ->name('*.mongodb.yml'),
            (new Finder())
                ->in($dirs)
                ->path('Resources/config/serializer')
                ->name('*.xml'),
            (new Finder())
                ->in($dirs)
                ->path('Resources/config/schema')
                ->name('*.json')
        );

        $container->set('graviton.document.map', $documentMap);
        $container->setParameter('graviton.generator.dynamicbundle.dir', $dynamicBundleDir);
    }
}
