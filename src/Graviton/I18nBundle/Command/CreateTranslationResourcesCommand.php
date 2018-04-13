<?php
/**
 * create translation resources based on the available resources in the mongodb catalog
 */

namespace Graviton\I18nBundle\Command;

use Doctrine\ODM\MongoDB\DocumentRepository;
use MongoDB\Driver\Exception\ConnectionTimeoutException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class CreateTranslationResourcesCommand extends Command
{
    /**
     * @var DocumentRepository
     */
    private $languageRepo;

    /**
     * @var DocumentRepository
     */
    private $translatableRepo;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $resourceDir;

    /**
     * @param DocumentRepository     $languageRepo     Language Repository
     * @param DocumentRepository     $translatableRepo Translatable Repository
     * @param Filesystem             $filesystem       symfony/filesystem tooling
     */
    public function __construct(
        DocumentRepository $languageRepo,
        DocumentRepository $translatableRepo,
        Filesystem $filesystem
    ) {
        $this->languageRepo = $languageRepo;
        $this->translatableRepo = $translatableRepo;
        $this->filesystem = $filesystem;
        $this->resourceDir = __DIR__.'/../Resources/translations/';

        parent::__construct();
    }

    /**
     * set up command
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('graviton:i18n:create:resources')
            ->setDescription(
                'Create translation resource stub files for all the available languages/domains in the database'
            );
    }

    /**
     * run command
     *
     * @param InputInterface  $input  input interface
     * @param OutputInterface $output output interface
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Creating translation resource stubs");

        // Pause a bit before generating the languages.
        $this->openDbConnection($output);

        $languages = $this->languageRepo->findAll();
        $domains = $this->translatableRepo->createQueryBuilder()
            ->distinct('domain')
            ->select('domain')
            ->getQuery()
            ->execute()
            ->toArray();

        array_walk(
            $languages,
            function ($language) use ($output, $domains) {
                array_walk(
                    $domains,
                    function ($domain) use ($output, $language) {
                        $file = implode('.', [$domain, $language->getId(), 'odm']);
                        $this->filesystem->touch(implode(DIRECTORY_SEPARATOR, [$this->resourceDir, $file]));
                        $output->writeln("<info>Generated file $file</info>");
                    }
                );
            }
        );
    }

    /**
     * Loop until DB connection is insured.
     *
     * @param OutputInterface $output Used to inform about db connection status
     *
     * @return void
     */
    private function openDbConnection(OutputInterface $output)
    {
        $output->writeln('Checking DB connection');

        $loopCount = 0;
        $connection = $this->languageRepo->getDocumentManager()->getConnection();

        while (!($connected = $connection->isConnected())) {
            try {
                $connection->connect();
                break;
            } catch (\MongoConnectionException $e) {
                $output->writeln('DB is not yet connected, sleep 1 second.');
                sleep(1);
            }
            $loopCount++;
            if ($loopCount > 20) {
                throw new ConnectionTimeoutException('DB connection failed.');
            }
        }

        $output->writeln('DB connected.');
    }
}
