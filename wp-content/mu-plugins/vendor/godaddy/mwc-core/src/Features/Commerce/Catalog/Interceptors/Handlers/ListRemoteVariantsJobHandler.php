<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Schedule\Exceptions\InvalidScheduleException;
use GoDaddy\WordPress\MWC\Common\Schedule\Schedule;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\ListRemoteVariantsJobInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\ListProductsOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\ListProductsResponseContract;
use GoDaddy\WordPress\MWC\Core\Interceptors\Handlers\AbstractInterceptorHandler;

/**
 * List variant products, which inserts products that don't exist locally.
 * Companion to {@see RemoteProductsPollingProcessor}.
 */
class ListRemoteVariantsJobHandler extends AbstractInterceptorHandler
{
    /** @var positive-int default maximum number of variant products to get per request */
    protected const DEFAULT_MAX_PER_REQUEST = 50;

    protected ProductsServiceContract $productsService;

    public function __construct(ProductsServiceContract $productsService)
    {
        $this->productsService = $productsService;
    }

    /**
     * Schedules a new background job to list variants, if the products in the provided `$listProductsResponse` have any variants included in them.
     *
     * @param ListProductsResponseContract $listProductsResponse
     * @return void
     */
    public static function scheduleIfHasVariants(ListProductsResponseContract $listProductsResponse) : void
    {
        $variantProductIds = ArrayHelper::flatten(array_map(
            static function (ProductAssociation $productAssociation) : array {
                return TypeHelper::arrayOfStrings($productAssociation->remoteResource->variants);
            },
            $listProductsResponse->getProducts()
        ));

        if (! $variantProductIds) {
            return;
        }

        $job = Schedule::singleAction()->setName(ListRemoteVariantsJobInterceptor::JOB_NAME);

        try {
            $job
                ->setArguments($variantProductIds)
                ->setScheduleAt(new DateTime())
                ->schedule();
        } catch (InvalidScheduleException $e) {
            SentryException::getNewInstance('Could not schedule job to list variants.', $e);
        }
    }

    /**
     * List variant products with the given IDs, which inserts products that don't exist locally.
     * {@see RemoteProductsPollingProcessor}.
     *
     * @note This method is public so that external code can call it to list variants inline with a product read.
     *
     * @param string[] $variantProductIds
     * @param positive-int $maxPerRequest maximum number of variant products to get per request
     */
    public function processVariants(array $variantProductIds, int $maxPerRequest) : void
    {
        $chunkedIds = $this->getChunkedIds($variantProductIds, $maxPerRequest);
        $listProductsOperation = ListProductsOperation::getNewInstance();

        foreach ($chunkedIds as $chunkVariantProductIds) {
            try {
                $this->productsService->listProducts(
                    $listProductsOperation->setIds($chunkVariantProductIds)
                );
            } catch(Exception $e) {
                // @TODO in the future perhaps we want to re-schedule the list job, but with back-off {agibson 2023-06-15}
                SentryException::getNewInstance('Failed to list variant products.', $e);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function run(...$args)
    {
        list($variantProductIds, $maxPerRequest) = array_pad($args, 2, null);

        $this->processVariants(
            TypeHelper::arrayOfStrings($variantProductIds),
            $this->maxPerRequestOrDefault($maxPerRequest),
        );
    }

    /**
     * @param mixed $maxPerRequest
     *
     * @return positive-int
     */
    protected function maxPerRequestOrDefault($maxPerRequest) : int
    {
        $intMax = TypeHelper::int($maxPerRequest, 0);

        if ($intMax > 0) {
            return $intMax;
        }

        return static::DEFAULT_MAX_PER_REQUEST;
    }

    /**
     * @param string[] $variantProductIds
     * @param positive-int $maxPerRequest
     *
     * @return string[][]
     */
    protected function getChunkedIds(array $variantProductIds, int $maxPerRequest) : array
    {
        return array_chunk(array_unique($variantProductIds), $maxPerRequest);
    }
}
