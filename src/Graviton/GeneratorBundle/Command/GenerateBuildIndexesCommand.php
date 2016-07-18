<?php
/**
 * generate MongoDB Fulltext-Search Indexes
 */

namespace Graviton\GeneratorBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Graviton\GeneratorBundle\Definition\Loader\LoaderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * Here, we generate all MongoDB Fulltext-Search Indexes
 *
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://swisscom.ch
 */
class GenerateBuildIndexesCommand extends Command
{

    /**
     * @var documentManager
     */
    private $documentManager;

    /**
     * GenerateBuildIndexesCommand constructor.
     *
     * @param LoaderInterface $definitionLoader The definition Loader - loads Definitions from JSON-Files
     * @param DocumentManager $documentManager  The Doctrine Document Manager
     * @param String          $name             The Name of this Command
     */
    public function __construct(
        DocumentManager $documentManager,
        $name = null
    ) {
        parent::__construct($name);

        $this->documentManager = $documentManager;
    }

    /**
     * {@inheritDoc}
     *
     * @return void
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('graviton:generate:build-indexes')
            ->setDescription(
                'Generates Mongo-Text Indexes (MongoDB >= 2.6) for collections as defined'
            );
    }

    /**
     * {@inheritDoc}
     *
     * @param InputInterface  $input  input
     * @param OutputInterface $output output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Check mongo db version
        $mongoVersion = $this->getMongoDBVersion('Graviton\\CoreBundle\\Document\\App');
        if ((float) $mongoVersion < 2.6) {
            $output->writeln("MongoDB Version < 2.6 installed: " . $mongoVersion);
            exit();
        }

        $metadatas = $this->documentManager->getMetadataFactory()->getAllMetadata();
        /** @var ClassMetadata $metadata */
        foreach ($metadatas as $metadata) {
            $indexes = $metadata->getIndexes();
            $searchName = 'search'.$metadata->getCollection().'Index';
            foreach ($indexes as $index) {
                if (array_key_exists('keys', $index) && array_key_exists($searchName, $index['keys'])) {
                    if (array_key_exists('options', $index) && !empty($index['options'])) {
                        $collection = $this->documentManager->getDocumentCollection($metadata->getName());
                        if (!$collection) {
                            continue;
                        }
                        $newIndex = [];
                        $weights = [];
                        foreach ($index['options'] as $optionName => $optionsValue) {
                            if (strpos($optionName, 'search_') !== false) {
                                $optionName = str_replace('search_', '', $optionName);
                                $newIndex[$optionName] = 'text';
                                $weights[$optionName] = floatval($optionsValue);
                            }
                        }
                        if (empty($weights)) {
                            continue;
                        }
                        foreach ($collection->getIndexInfo() as $indexInfo) {
                            // When using doctrine name may have a _1
                            if (strpos($indexInfo['name'], $searchName) !== false) {
                                $output->writeln("Deleting Custom Text index {$searchName}");
                                $this->documentManager->getDocumentDatabase($metadata->getName())->command(
                                    [
                                        "deleteIndexes" => $collection->getName(),
                                        "index" => $indexInfo['name']
                                    ]
                                );
                                break;
                            }
                        }
                        $output->writeln($metadata->getName().": created custom Text index {$searchName}");
                        $collection->ensureIndex(
                            $newIndex,
                            [
                                'weights' => $weights,
                                'name'    => $searchName,
                                'default_language'  => 'de',
                                'language_override' => 'dummy'
                            ]
                        );
                    }
                }
            }
        }

    }

    /**
     * Gets the installed MongoDB Version
     * @param String $className The Classname of the collection, needed to fetch the right DB connection
     * @return String getMongoDBVersion The version of the MongoDB as a string
     */
    private function getMongoDBVersion($className)
    {
        $buildInfo = $this->documentManager->getDocumentDatabase($className)->command(['buildinfo' => 1]);
        if (isset($buildInfo['version'])) {
            return $buildInfo['version'];
        } else {
            return 'unkown';
        }
    }
}
