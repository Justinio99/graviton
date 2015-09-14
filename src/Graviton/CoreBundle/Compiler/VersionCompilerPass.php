<?php
/** A custom compiler pass class */

namespace Graviton\CoreBundle\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Container;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://swisscom.ch
 */
class VersionCompilerPass implements CompilerPassInterface
{

    /**
     * add version numbers of packages to the container
     *
     * @param ContainerBuilder $container Container
     *
     * @return void
     */
    public function process(ContainerBuilder $container)
    {
        $container->setParameter(
            'graviton.core.version.data',
            $this->getPackageVersions($container->getParameter('kernel.root_dir'))
        );
    }

    /**
     * @param string $rootDir path to root dir
     *
     * @return array version numbers of packages
     */
    private function getPackageVersions($rootDir)
    {
        $versions = array();
        array_push($versions, $this->getContextVersion());
        $versions = $this->getInstalledPackagesVersion($rootDir, $versions);

        return $versions;
    }

    /**
     * returns the version of graviton or wrapper
     *
     * @return array
     */
    private function getContextVersion()
    {
        $result = shell_exec('composer show -s --no-ansi');
        $lines = explode(PHP_EOL, $result);
        $wrapper = array();
        foreach ($lines as $line) {
            if (strpos($line, 'versions') !== false) {
                $wrapperVersionArr = explode(':', $line);
                $wrapper['version'] = trim(str_replace('*', '', $wrapperVersionArr[1]));
            } elseif (strpos($line, 'name') !== false) {
                $wrapperNameArr = explode(':', $line);
                $wrapper['id'] = trim($wrapperNameArr[1]);
                $wrapper['isWrapper'] = true;
            }
        }

        return $wrapper;
    }

    /**
     * returns version for every installed package
     *
     * @param string $rootDir  path to root directory
     * @param array  $versions versions array
     * @return array
     */
    private function getInstalledPackagesVersion($rootDir, $versions)
    {
        if (strpos($rootDir, 'vendor')) {
            $packageNames = shell_exec('cd '.$rootDir.'/../../../../ && composer show -i');
        } else {
            $packageNames = shell_exec('composer show -i');
        }
        $packages = explode(PHP_EOL, $packageNames);
        //last index is always empty
        array_pop($packages);
        foreach ($packages as $package) {
            preg_match_all('/([^\s]+)/', $package, $match);
            if (strpos($match[0][0], 'grv') === 0 | $match[0][0] === 'graviton') {
                array_push($versions, array('id' => $match[0][0], 'version' => $match[0][1], 'isWrapper' => false ));
            }
        }

        return $versions;
    }
}
