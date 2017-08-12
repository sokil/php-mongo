<?php

/**
 * This file is part of the PHPMongo package.
 *
 * (c) Dmytro Sokil <dmytro.sokil@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sokil\Mongo;

use Psr\SimpleCache\CacheInterface;
use Sokil\Mongo\Cache\Exception\CacheException;
use Sokil\Mongo\Cache\Exception\InvalidArgumentException;

class Cache implements \Countable, CacheInterface
{
    const FIELD_NAME_VALUE = 'v';
    const FIELD_NAME_EXPIRED = 'e';
    const FIELD_NAME_TAGS = 't';
    
    private $collection;

    /**
     * Cache constructor.
     * @param Database $database
     * @param string $collectionName namespace of cache
     */
    public function __construct(Database $database, $collectionName)
    {
        $this->collection = $database
            ->map($collectionName, array(
                'index' => array(
                    // date field
                    array(
                        'keys' => array(self::FIELD_NAME_EXPIRED => 1),
                        'expireAfterSeconds' => 0
                    ),
                )
            ))
            ->getCollection($collectionName)
            ->disableDocumentPool();
    }

    /**
     * @return Cache
     */
    public function init()
    {
        $this->collection->initIndexes();
        return $this;
    }

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param array                     $values A list of key => value pairs for a multiple-set operation.
     * @param null|int|\DateInterval    $ttl    Optional. The TTL value of this item. If no value is sent and
     *                                          the driver supports TTL then the library may set a default value
     *                                          for it or let the driver take care of that.
     * @param array                     $tags   List of tags
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * MUST be thrown if $values is neither an array nor a Traversable,
     * or if any of the $values are not a legal value.
     */
    public function setMultiple($values, $ttl = null, array $tags = array())
    {
        // prepare expiration
        if (!empty($ttl)) {
            if ($ttl instanceof \DateInterval) {
                $ttl = $ttl->s;
            } elseif (!is_int($ttl)) {
                throw new InvalidArgumentException('Invalid TTL specified');
            }

            $expirationTimestamp = time() + $ttl;
        }

        // prepare persistence
        $persistence = $this->collection->getDatabase()->getClient()->createPersistence();

        // prepare documents to store
        foreach ($values as $key => $value) {
            // create document
            $document = array(
                '_id' => $key,
                self::FIELD_NAME_VALUE => $value,
            );

            // add expiration
            if (!empty($expirationTimestamp)) {
                $document[self::FIELD_NAME_EXPIRED] = new \MongoDate($expirationTimestamp);
            }

            // prepare tags
            if (!empty($tags)) {
                $document[self::FIELD_NAME_TAGS] = $tags;
            }

            // attach document
            $persistence->persist($this->collection->createDocument($document));
        }

        try {
            $persistence->flush();
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Set with expiration on concrete date
     *
     * @deprecated Use self::set() with calculated ttl
     *
     * @param int|string $key
     * @param mixed $value
     * @param int $expirationTime
     * @param array $tags
     *
     * @throws Exception
     *
     * @return bool
     */
    public function setDueDate($key, $value, $expirationTime, array $tags = null)
    {
        return $this->set($key, $value, $expirationTime - time(), $tags);
    }
    
    /**
     * Set key that never expired
     *
     * @deprecated Use self::set() with null in ttl
     *
     * @param int|string $key
     * @param mixed $value
     * @param array $tags
     *
     * @return bool
     */
    public function setNeverExpired($key, $value, array $tags = null)
    {
        return $this->set($key, $value, null, $tags);
    }
    
    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string                    $key    The key of the item to store.
     * @param mixed                     $value  The value of the item to store, must be serializable.
     * @param null|int|\DateInterval    $ttl    Optional. The TTL value of this item. If no value is sent and
     *                                          the driver supports TTL then the library may set a default value
     *                                          for it or let the driver take care of that.
     * @param array                     $tags   List of tags
     *
     * @return bool True on success and false on failure.
     *
     * @throws InvalidArgumentException
     * @throws CacheException
     */
    public function set($key, $value, $ttl = null, array $tags = null)
    {
        // create document
        $document = array(
            '_id' => $key,
            self::FIELD_NAME_VALUE => $value,
        );

        // prepare expiration
        if (!empty($ttl)) {
            if ($ttl instanceof \DateInterval) {
                $ttl = $ttl->s;
            } elseif (!is_int($ttl)) {
                throw new InvalidArgumentException('Invalid TTL specified');
            }

            $expirationTimestamp = time() + $ttl;
            $document[self::FIELD_NAME_EXPIRED] = new \MongoDate((int) $expirationTimestamp);
        }

        // prepare tags
        if (!empty($tags)) {
            $document[self::FIELD_NAME_TAGS] = $tags;
        }

        // create document
        $result = $this
            ->collection
            ->getMongoCollection()
            ->update(
                array(
                    '_id' => $key,
                ),
                $document,
                array(
                    'upsert' => true,
                )
            );

        // check result
        return (double) 1 === $result['ok'];
    }

        /**
     * @param array $keys
     * @param mixed|null $default
     *
     * @return array
     */
    public function getMultiple($keys, $default = null)
    {
        // Prepare defaults
        $values = array_fill_keys($keys, $default);

        // Get document
        $documents = $this->collection->getDocuments($keys);
        if (empty($documents)) {
            return $values;
        }

        // Mongo deletes document not exactly when set in field
        // Required date checking
        // Expiration may be empty for keys which never expired
        foreach ($documents as $document) {
            /** @var \MongoDate $expiredAt */
            $expiredAt = $document->get(self::FIELD_NAME_EXPIRED);
            if (empty($expiredAt) || $expiredAt->sec >= time()) {
                $values[$document->getId()] = $document->get(self::FIELD_NAME_VALUE);
            } else {
                $values[$document->getId()] = $default;
            }
        }

        return $values;
    }

    /**
     * Get value by key
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        // Get document
        $document = $this->collection->getDocument($key);
        if (!$document) {
            return $default;
        }

        // Mongo deletes document not exactly when set in field
        // Required date checking
        // Expiration may be empty for keys which never expired
        $expiredAt = $document->get(self::FIELD_NAME_EXPIRED);
        if (!empty($expiredAt) && $expiredAt->sec < time()) {
            return $default;
        }

        // Return value
        return $document->get(self::FIELD_NAME_VALUE);
    }


    /**
     * Clear all cache
     *
     * @return bool
     */
    public function clear()
    {
        try {
            $this->collection->delete();
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return bool True if the item was successfully removed. False if there was an error.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * MUST be thrown if the $key string is not a legal value.
     */
    public function delete($key)
    {
        if (empty($key) || !is_string($key)) {
            throw new InvalidArgumentException('Key must be string');
        }

        try {
            $this->collection->batchDelete(array(
                '_id' => $key,
            ));
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param array $keys A list of string-based keys to be deleted.
     *
     * @return bool True if the items were successfully removed. False if there was an error.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * MUST be thrown if $keys is neither an array nor a Traversable,
     * or if any of the $keys are not a legal value.
     */
    public function deleteMultiple($keys)
    {
        try {
            $this->collection->batchDelete(
                function(Expression $e) use($keys) {
                    $e->whereIn('_id', $keys);
                }
            );
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
    
    /**
     * Delete documents by tag
     */
    public function deleteMatchingTag($tag)
    {
        $this->collection->batchDelete(function (\Sokil\Mongo\Expression $e) use ($tag) {
            return $e->where(Cache::FIELD_NAME_TAGS, $tag);
        });
        
        return $this;
    }
    
    /**
     * Delete documents by tag
     */
    public function deleteNotMatchingTag($tag)
    {
        $this->collection->batchDelete(function (\Sokil\Mongo\Expression $e) use ($tag) {
            return $e->whereNotEqual(Cache::FIELD_NAME_TAGS, $tag);
        });
        
        return $this;
    }
    
    /**
     * Delete documents by tag
     * Document deletes if it contains all passed tags
     */
    public function deleteMatchingAllTags(array $tags)
    {
        $this->collection->batchDelete(function (\Sokil\Mongo\Expression $e) use ($tags) {
            return $e->whereAll(Cache::FIELD_NAME_TAGS, $tags);
        });
        
        return $this;
    }
    
    /**
     * Delete documents by tag
     * Document deletes if it not contains all passed tags
     */
    public function deleteMatchingNoneOfTags(array $tags)
    {
        $this->collection->batchDelete(function (\Sokil\Mongo\Expression $e) use ($tags) {
            return $e->whereNoneOf(Cache::FIELD_NAME_TAGS, $tags);
        });
        
        return $this;
    }
    
    /**
     * Delete documents by tag
     * Document deletes if it contains any of passed tags
     */
    public function deleteMatchingAnyTag(array $tags)
    {
        $this->collection->batchDelete(function (\Sokil\Mongo\Expression $e) use ($tags) {
            return $e->whereIn(Cache::FIELD_NAME_TAGS, $tags);
        });
        
        return $this;
    }
    
    /**
     * Delete documents by tag
     * Document deletes if it contains any of passed tags
     */
    public function deleteNotMatchingAnyTag(array $tags)
    {
        $this->collection->batchDelete(function (\Sokil\Mongo\Expression $e) use ($tags) {
            return $e->whereNotIn(Cache::FIELD_NAME_TAGS, $tags);
        });
        
        return $this;
    }

    /**
     * Get total count of documents in cache
     *
     * @return int
     */
    public function count()
    {
        return $this->collection->count();
    }

    /**
     * Check if cache has key
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return (bool)$this->get($key);
    }
}
