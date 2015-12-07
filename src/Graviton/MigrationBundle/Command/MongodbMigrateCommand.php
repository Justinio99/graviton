<?php
/**
 * graviton:mongodb:migrations:execute command
 */

namespace Graviton\MigrationBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Finder\Finder;
use Graviton\MigrationBundle\Command\Helper\DocumentManager as DocumentManagerHelper;
use AntiMattr\MongoDB\Migrations\OutputWriter;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://swisscom.ch
 */
class MongodbMigrateCommand extends Command
{
    /**
     * @var Finder
     */
    private $finder;

    /**
     * @var DocumentManagerHelper
     */
    private $documentManager;

    /**
     * @var string
     */
    private $databaseName;

    /**
     * @param Finder                $finder          finder that finds configs
     * @param DocumentManagerHelper $documentManager dm helper to get access to db in command
     * @param string                $databaseName    name of database where data is found in
     */
    public function __construct(Finder $finder, DocumentManagerHelper $documentManager, $databaseName)
    {
        $this->finder = $finder;
        $this->documentManager = $documentManager;
        $this->databaseName = $databaseName;

        parent::__construct();
    }

    /**
     * setup command
     *
     * @return void
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('graviton:mongodb:migrate');
    }

    /**
     * call execute on found commands
     *
     * @param InputInterface  $input  user input
     * @param OutputInterface $output command output
     *
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $base = strpos(getcwd(), 'vendor/') === false ? getcwd() :  getcwd() . '/../../../../';
        $this->finder->in($base)->path('Resources/config')->name('/migrations.(xml|yml)/')->files();

        foreach ($this->finder as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $output->writeln('Found '.$file->getRelativePathname());

            $command = $this->getApplication()->find('mongodb:migrations:migrate');

            $helperSet = $command->getHelperSet();
            $helperSet->set($this->documentManager, 'dm');
            $command->setHelperSet($helperSet);

            $command->setMigrationConfiguration($this->getConfiguration($file->getPathname(), $output));

            $arguments = $input->getArguments();
            $arguments['command'] = 'mongodb:migrations:migrate';
            $arguments['--configuration'] = $file->getPathname();

            $migrateInput = new ArrayInput($arguments);
            $returnCode = $command->run($migrateInput, $output);

            if ($returnCode !== 0) {
                $output->writeln(
                    '<error>Calling mongodb:migrations:migrate failed for '.$file->getRelativePathname().'</error>'
                );
                return $returnCode;
            }
        }
    }

    /**
     * get configration object for migration script
     *
     * This is based on antromattr/mongodb-migartion code but extends it so we can inject
     * non local stuff centrally.
     *
     * @param string $filepath path to configuration file
     * @param Output $output   ouput interface need by config parser to do stuff
     *
     * @return AntiMattr\MongoDB\Migrations\Configuration\Configuration
     */
    private function getConfiguration($filepath, $output)
    {
        $outputWriter = new OutputWriter(
            function ($message) use ($output) {
                return $output->writeln($message);
            }
        );

        $info = pathinfo($filepath);
        $namespace = 'AntiMattr\MongoDB\Migrations\Configuration';
        $class = $info['extension'] === 'xml' ? 'XmlConfiguration' : 'YamlConfiguration';
        $class = sprintf('%s\%s', $namespace, $class);
        $configuration = new $class($this->documentManager->getDocumentManager()->getConnection(), $outputWriter);

        // register databsae name before loading to ensure that loading does not fail
        $configuration->setMigrationsDatabaseName($this->databaseName);

        // load additional config from migrations.(yml|xml)
        $configuration->load($filepath);

        return $configuration;
    }
}
