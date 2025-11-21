<?php

declare (strict_types=1);
/**
 * |------
 * | ! Generated code !
 * | Altering this code will result in changes being overwritten |
 * |-------------------------------------------------------------|.
 */
namespace Voxel\Vendor\Paddle\SDK\Resources\ClientTokens;

use Voxel\Vendor\Paddle\SDK\Client;
use Voxel\Vendor\Paddle\SDK\Entities\ClientToken;
use Voxel\Vendor\Paddle\SDK\Entities\ClientToken\ClientTokenStatus;
use Voxel\Vendor\Paddle\SDK\Entities\Collections\ClientTokenCollection;
use Voxel\Vendor\Paddle\SDK\Entities\Collections\Paginator;
use Voxel\Vendor\Paddle\SDK\Exceptions\ApiError;
use Voxel\Vendor\Paddle\SDK\Exceptions\SdkExceptions\MalformedResponse;
use Voxel\Vendor\Paddle\SDK\Resources\ClientTokens\Operations\CreateClientToken;
use Voxel\Vendor\Paddle\SDK\Resources\ClientTokens\Operations\ListClientTokens;
use Voxel\Vendor\Paddle\SDK\Resources\ClientTokens\Operations\UpdateClientToken;
use Voxel\Vendor\Paddle\SDK\ResponseParser;
class ClientTokensClient
{
    public function __construct(private readonly Client $client)
    {
    }
    /**
     * @throws ApiError          On a generic API error
     * @throws MalformedResponse If the API response was not parsable
     */
    public function list(ListClientTokens $listOperation = new ListClientTokens()): ClientTokenCollection
    {
        $parser = new ResponseParser($this->client->getRaw('/client-tokens', $listOperation));
        return ClientTokenCollection::from($parser->getData(), new Paginator($this->client, $parser->getPagination(), ClientTokenCollection::class));
    }
    /**
     * @throws ApiError          On a generic API error
     * @throws MalformedResponse If the API response was not parsable
     */
    public function get(string $id): ClientToken
    {
        $parser = new ResponseParser($this->client->getRaw("/client-tokens/{$id}"));
        return ClientToken::from($parser->getData());
    }
    /**
     * @throws ApiError          On a generic API error
     * @throws MalformedResponse If the API response was not parsable
     */
    public function create(CreateClientToken $createOperation): ClientToken
    {
        $parser = new ResponseParser($this->client->postRaw('/client-tokens', $createOperation));
        return ClientToken::from($parser->getData());
    }
    /**
     * @throws ApiError          On a generic API error
     * @throws MalformedResponse If the API response was not parsable
     */
    public function update(string $id, UpdateClientToken $operation): ClientToken
    {
        $parser = new ResponseParser($this->client->patchRaw("/client-tokens/{$id}", $operation));
        return ClientToken::from($parser->getData());
    }
    /**
     * @throws ApiError          On a generic API error
     * @throws MalformedResponse If the API response was not parsable
     */
    public function revoke(string $id): ClientToken
    {
        return $this->update($id, new UpdateClientToken(status: ClientTokenStatus::Revoked()));
    }
}
