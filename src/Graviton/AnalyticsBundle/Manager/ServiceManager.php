<?php
/**
 * ParamConverter class for entry point to Analytics Bundle
 */

namespace Graviton\AnalyticsBundle\Manager;

use Graviton\AnalyticsBundle\Helper\JsonMapper;
use Graviton\AnalyticsBundle\Model\AnalyticModel;
use Graviton\DocumentBundle\Service\DateConverter;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;
use Doctrine\Common\Cache\CacheProvider;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Router;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Graviton\AnalyticsBundle\Exception\AnalyticUsageException;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * Service Request Converter and startup for Analytics
 *
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class ServiceManager
{
    /** Cache name for services */
    const CACHE_KEY_SERVICES = 'analytics_services';
    const CACHE_KEY_SERVICES_URLS = 'analytics_services_urls';
    const CACHE_KEY_SERVICES_PREFIX = 'analytics_';

    /** @var RequestStack */
    protected $requestStack;

    /** @var AnalyticsManager */
    protected $analyticsManager;

    /** @var CacheProvider */
    protected $cacheProvider;

    /** @var DateConverter */
    protected $dateConverter;

    /** @var Router */
    protected $router;

    /** @var string */
    protected $directory;

    /** @var int */
    protected $cacheTimeMetadata;

    /** @var Filesystem */
    protected $fs;

    /**
     * @var string
     */
    private $skipCacheHeaderName = 'x-analytics-no-cache';

    /**
     * ServiceConverter constructor.
     * @param RequestStack     $requestStack        Sf Request information service
     * @param AnalyticsManager $analyticsManager    Db Manager and query control
     * @param CacheProvider    $cacheProvider       Cache service
     * @param DateConverter    $dateConverter       date converter
     * @param Router           $router              To manage routing generation
     * @param string           $definitionDirectory Where definitions are stored
     * @param int              $cacheTimeMetadata   How long to cache metadata
     */
    public function __construct(
        RequestStack $requestStack,
        AnalyticsManager $analyticsManager,
        CacheProvider $cacheProvider,
        DateConverter $dateConverter,
        Router $router,
        $definitionDirectory,
        $cacheTimeMetadata
    ) {
        $this->requestStack = $requestStack;
        $this->analyticsManager = $analyticsManager;
        $this->cacheProvider = $cacheProvider;
        $this->dateConverter = $dateConverter;
        $this->router = $router;
        $this->directory = $definitionDirectory;
        $this->cacheTimeMetadata = $cacheTimeMetadata;
        $this->fs = new Filesystem();
    }

    /**
     * Scan base root directory for analytic definitions
     * @return array
     */
    private function getDirectoryServices()
    {
        $services = $this->cacheProvider->fetch(self::CACHE_KEY_SERVICES);

        if (is_array($services)) {
            return $services;
        }

        $services = [];
        if (strpos($this->directory, 'vendor/graviton/graviton')) {
            $this->directory = str_replace('vendor/graviton/graviton/', '', $this->directory);
        }
        if (!is_dir($this->directory)) {
            return $services;
        }

        $finder = Finder::create()
            ->files()
            ->in($this->directory)
            ->path('/\/analytics\//i')
            ->name('*.json')
            ->notName('_*')
            ->notName('*.pipeline.json')
            ->notName('*.pipeline.*.json')
            ->sortByName();

        foreach ($finder as $file) {
            $key = $file->getFilename();
            $data = json_decode($file->getContents());
            if (json_last_error()) {
                throw new InvalidConfigurationException(
                    sprintf('Analytics file: %s could not be loaded due to error: %s', $key, json_last_error_msg())
                );
            }

            $data->multiPipeline = false;

            // filename without extension
            $searchBaseName = substr($file->getFilename(), 0, -5);

            // is/are there a pipeline files?
            $pipelineFinder = Finder::create()
                ->files()
                ->in(dirname($file->getPathname()))
                ->depth(0)
                ->name($searchBaseName.'.pipeline.json')
                ->name($searchBaseName.'.pipeline.*.json');

            $pipelineFiles = iterator_to_array($pipelineFinder);

            if (count($pipelineFiles) == 1) {
                // only 1 -> standard
                $data->aggregate = json_decode(reset($pipelineFiles)->getContents());
            }

            if (count($pipelineFiles) > 1) {
                // multipipeline!
                foreach ($pipelineFiles as $singlePipeline) {
                    // get key name
                    preg_match(
                        '/\.pipeline\.([a-z]*)\.json/',
                        $singlePipeline->getFilename(),
                        $matches
                    );

                    if (!isset($matches[1])) {
                        throw new \LogicException(
                            'Could not infer pipeline name from filename for '.
                            $singlePipeline->getPathname()
                        );
                    }

                    $pipeName = $matches[1];

                    $data->aggregate[$pipeName] = json_decode($singlePipeline->getContents());
                    $data->multiPipeline = true;
                }
            }

            $services[$data->route] = $data;
        }

        $this->cacheProvider->save(self::CACHE_KEY_SERVICES, $services, $this->cacheTimeMetadata);

        return $services;
    }

    /**
     * Return array of available services
     *
     * @return array
     */
    public function getServices()
    {
        $services = $this->cacheProvider->fetch(self::CACHE_KEY_SERVICES_URLS);
        if (is_array($services)) {
            return $services;
        }

        $services = [];
        foreach ($this->getDirectoryServices() as $name => $service) {
            $services[] = [
                '$ref' => $this->router->generate(
                    'graviton_analytics_service',
                    [
                        'service' => $service->route
                    ],
                    false
                ),
                'profile' => $this->router->generate(
                    'graviton_analytics_service_schema',
                    [
                        'service' => $service->route
                    ],
                    true
                )
            ];
        }
        $this->cacheProvider->save(
            self::CACHE_KEY_SERVICES_URLS,
            $services,
            $this->cacheTimeMetadata
        );
        return $services;
    }

    /**
     * Get service definition
     *
     * @param string $name Route name for service
     * @throws NotFoundHttpException
     * @return AnalyticModel
     */
    private function getAnalyticModel($name)
    {
        $services = $this->getDirectoryServices();
        // Locate the schema definition
        if (!array_key_exists($name, $services)) {
            throw new NotFoundHttpException(
                sprintf('Analytic definition "%s" was not found', $name)
            );
        }

        $mapper = new JsonMapper();

        /** @var AnalyticModel $model */
        $model = $mapper->map($services[$name], new AnalyticModel());
        $model->setDateConverter($this->dateConverter);

        return $model;
    }

    /**
     * Will map and find data for defined route
     *
     * @return array
     */
    public function getData()
    {
        $serviceRoute = $this->requestStack->getCurrentRequest()->get('service');

        // Locate the model definition
        $model = $this->getAnalyticModel($serviceRoute);

        $cacheTime = $model->getCacheTime();
        $cacheKey = $this->getCacheKey($model);

        //Cached data if configured
        if ($cacheTime &&
            !$this->requestStack->getCurrentRequest()->headers->has($this->skipCacheHeaderName) &&
            $cache = $this->cacheProvider->fetch($cacheKey)
        ) {
            return $cache;
        }

        $data = $this->analyticsManager->getData($model, $this->getServiceParameters($model));

        if ($cacheTime) {
            $this->cacheProvider->save($cacheKey, $data, $cacheTime);
        }

        return $data;
    }

    /**
     * generate a cache key also based on query
     *
     * @param AnalyticModel $schema schema
     *
     * @return string cache key
     */
    private function getCacheKey($schema)
    {
        return self::CACHE_KEY_SERVICES_PREFIX
            .$schema->getRoute()
            .sha1(serialize($this->requestStack->getCurrentRequest()->query->all()));
    }

    /**
     * Locate and display service definition schema
     *
     * @return mixed
     */
    public function getSchema()
    {
        $serviceRoute = $this->requestStack->getCurrentRequest()->get('service');

        // Locate the schema definition
        $model = $this->getAnalyticModel($serviceRoute);

        return $model->getSchema();
    }

    /**
     * returns the params as passed from the user
     *
     * @param AnalyticModel $model model
     *
     * @return array the params, converted as specified
     * @throws AnalyticUsageException
     */
    private function getServiceParameters(AnalyticModel $model)
    {
        $params = [];
        if (!is_array($model->getParams())) {
            return $params;
        }

        foreach ($model->getParams() as $param) {
            if (!isset($param->name)) {
                throw new \LogicException("Incorrect spec (no name) of param in analytics route " . $model->getRoute());
            }

            $paramValue = $this->requestStack->getCurrentRequest()->query->get($param->name, null);

            // default set?
            if (is_null($paramValue) && isset($param->default)) {
                $paramValue = $param->default;
            }

            // required missing?
            if (is_null($paramValue) && (isset($param->required) && $param->required === true)) {
                throw new AnalyticUsageException(
                    sprintf(
                        "Missing parameter '%s' in analytics route '%s'",
                        $param->name,
                        $model->getRoute()
                    )
                );
            }

            if (!is_null($param->type) && !is_null($paramValue)) {
                switch ($param->type) {
                    case "integer":
                        $paramValue = intval($paramValue);
                        break;
                    case "boolean":
                        $paramValue = boolval($paramValue);
                        break;
                    case "array":
                        $paramValue = explode(',', $paramValue);
                        break;
                    case "array<integer>":
                        $paramValue = array_map('intval', explode(',', $paramValue));
                        break;
                    case "array<boolean>":
                        $paramValue = array_map('boolval', explode(',', $paramValue));
                        break;
                }
            }

            $params[$param->name] = $paramValue;
        }

        return $params;
    }
}
