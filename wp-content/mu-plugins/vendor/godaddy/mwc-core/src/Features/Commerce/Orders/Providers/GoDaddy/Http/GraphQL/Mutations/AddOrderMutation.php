<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Http\GraphQL\Mutations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

class AddOrderMutation extends AbstractGraphQLOperation
{
    protected $operation = 'mutation($input: OrderInput!) {
        addOrder(input: $input) {
            id
            cartId
            context {
                channelId
                owner
                storeId
            }
            lineItems {
                details {
                    productAssetUrl
                    sku
                    unitOfMeasure
                }
                fulfillmentMode
                id
                name
                quantity
                status
                totals {
                    discountTotal {
                        currencyCode
                        value
                    }
                    feeTotal {
                        currencyCode
                        value
                    }
                    subTotal {
                        currencyCode
                        value
                    }
                    taxTotal {
                        currencyCode
                        value
                    }
                }
                type
                unitAmount {
                    currencyCode
                    value
                }
            }
            notes {
                author
                authorType
                content
                createdAt
                deletedAt
                id
                shouldNotifyCustomer
            }
            processedAt
            statuses {
                fulfillmentStatus
                paymentStatus
                status
            }
            totals {
                discountTotal {
                    currencyCode
                    value
                }
                feeTotal {
                    currencyCode
                    value
                }
                shippingTotal {
                    currencyCode
                    value
                }
                subTotal {
                    currencyCode
                    value
                }
                taxTotal {
                    currencyCode
                    value
                }
                total {
                    currencyCode
                    value
                }
            }
        }
    }';

    protected $operationType = 'mutation';
}
