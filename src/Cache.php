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

class Cache implements \Countable
{
    const FIELD_NAME_VALUE = 'v';
    const FIELD_NAME_EXPIRED = 'e';
    const FIELD_NAME_TAGS = 't';
    
    private $collection;
    
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
    
    public function init()
    {
        $this->collection->initIndexes();
        return $this;
    }
    
    public function clear()
    {
        $this->collection->delete();
        return $this;
    }
    
    /**
     * Set with expiration on concrete date
     *
     * @param int|string $key
     * @param mixed $value
     * @param int $timestamp
     */
    public function setDueDate($key, $value, $timestamp, array $tags = null)
    {
        $document = array(
            '_id' => $key,
            self::FIELD_NAME_VALUE => $value,
        );

        if ($timestamp) {
            $document[self::FIELD_NAME_EXPIRED] = new \MongoDate((int) $timestamp);
        }

        if ($tags) {
            $document[self::FIELD_NAME_TAGS] = $tags;
        }

        $result = $this->collection->getMongoCollection()->update(
            array(
                '_id' => $key,
            ),
            $document,
            array(
                'upsert' => true,
            )
        );

        if ((double) 1 !== $result['ok']) {
            throw new Exception('Error setting value');
        }
        
        return $this;
    }
    
    /**
     * Set key that never expired
     *
     * @param int|string $key
     * @param mixed $value
     * @return \Sokil\Mongo\Cache
     */
    public function setNeverExpired($key, $value, array $tags = null)
    {
        $this->setDueDate($key, $value, null, $tags);
        
        return $this;
    }
    
    /**
     * Set with expiration in seconds
     *
     * @param int|string $key
     * @param mixed $value
     * @param int $ttl
     */
    public function set($key, $value, $ttl, array $tags = null)
    {
        $this->setDueDate($key, $value, time() + $ttl, $tags);
        
        return $this;
    }

    /**
     * Get value by key
     *
     * @param $key
     * @return array|null
     */
    public function get($key)
    {
        // Get document
        $document = $this->collection->getDocument($key);
        if (!$document) {
            return null;
        }

        // Mongo deletes document not exactly when set in field
        // Required date checking
        // Expiration may be empty for keys whicj never expired
        $expiredAt = $document->get(self::FIELD_NAME_EXPIRED);
        if ($expiredAt && $expiredAt->sec < time()) {
            return null;
        }

        // Return value
        return $document->get(self::FIELD_NAME_VALUE);
    }
    
    public function delete($key)
    {
        $this->collection->batchDelete(array(
            '_id' => $key,
        ));
        
        return $this;
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
    
    public function count()
    {
        return $this->collection->count();
    }
    
    public function has($key)
    {
        return (bool) $this->get($key);
    }
}
