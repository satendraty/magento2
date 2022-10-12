<?php
namespace ChetuTest\WorldClassSoftware\Api;

use ChetuTest\WorldClassSoftware\Api\Data\ThingInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * Interface ThingRepositoryInterface
 *
 * @api
 */
interface ThingRepositoryInterface
{
    /**
     * Create or update a Thing.
     *
     * @param ThingInterface $page
     * @return ThingInterface
     */
    public function save(ThingInterface $page);

    /**
     * Get a Thing by Id
     *
     * @param int $id
     * @return ThingInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException If Thing with the specified ID does not exist.
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($id);

    /**
     * Retrieve Things which match a specified criteria.
     *
     * @param SearchCriteriaInterface $criteria
     */
    public function getList(SearchCriteriaInterface $criteria);

    /**
     * Delete a Thing
     *
     * @param ThingInterface $page
     * @return ThingInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException If Thing with the specified ID does not exist.
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(ThingInterface $page);

    /**
     * Delete a Thing by Id
     *
     * @param int $id
     * @return ThingInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException If customer with the specified ID does not exist.
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($id);
}
