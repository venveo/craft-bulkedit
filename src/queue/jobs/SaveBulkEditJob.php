<?php
/**
 * Bulk Edit plugin for Craft CMS 3.x
 *
 * Bulk edit entries
 *
 * @link      https://venveo.com
 * @copyright Copyright (c) 2018 Venveo
 */

namespace venveo\bulkedit\queue\jobs;

use Craft;
use craft\base\ElementInterface;
use craft\queue\BaseJob;
use Throwable;
use venveo\bulkedit\Plugin;
use venveo\bulkedit\records\EditContext;
use venveo\bulkedit\records\History;
use yii\base\Exception;

/**
 * @author    Venveo
 * @package   BulkEdit
 * @since     1.0.0
 */
class SaveBulkEditJob extends BaseJob
{
    // Public Properties
    // =========================================================================


    /** @var EditContext */
    public $context;

    // Public Methods
    // =========================================================================

    /**
     * Saves bulk edited elements
     * @param null $queue
     * @throws Throwable
     */
    public function execute($queue = null)
    {
        $elementHistories = Plugin::$plugin->bulkEdit->getPendingElementsHistoriesFromContext($this->context);
        $totalSteps = $elementHistories->count();
        try {
            $currentRow = 0;
            /**
             * @var History $elementHistory
             */
            foreach ($elementHistories->each() as $elementHistory) {
                $elementId = $elementHistory->elementId;
                /** @var ElementInterface $element */
                $element = Craft::$app->getElements()->getElementById($elementId, null, $this->context->siteId);
                if (!$element) {
                    Craft::warning('Could not locate an element in a bulk save job: ' . $elementId, __METHOD__);
                    continue;
                }

                if (get_class($element) !== $this->context->elementType) {
                    throw new \Exception('Unexpected element type encountered!');
                }

                $history = Plugin::$plugin->bulkEdit->getPendingHistoryFromContext($this->context, $element->id)->all();
                try {
                    Craft::info('Starting processing bulk edit job', __METHOD__);
                    Plugin::$plugin->bulkEdit->processHistoryItemsForElement($history, $element);
                } catch (\Exception $e) {
                    Craft::error('Could not save element in bulk edit job... ' . $e->getMessage(), __METHOD__);
                    throw new Exception('Couldn’t save element ' . $element->id . ' (' . get_class($element) . ')');
                } catch (Throwable $e) {
                    throw $e;
                }
                if (($currentRow + 1) === (int)$totalSteps) {
                    try {
                        $this->context->delete();
                    } catch (\Exception $e) {
                        throw new Exception('Couldn’t delete context: ' . $e->getMessage());
                    }
                }
                $this->setProgress($queue, ($currentRow + 1) / $totalSteps, 'Element ' . ($currentRow + 1) . ' of ' . $totalSteps);
                $currentRow++;
            }
        } catch (\Exception $e) {
            Craft::error('Failed to save... ' . $e->getMessage(), __METHOD__);
            throw $e;
        }
    }

    // Protected Methods
    // =========================================================================

    protected function defaultDescription(): string
    {
        $name = $this->context->owner->firstName ?? $this->context->owner->email ?? '';
        return Craft::t('venveo-bulk-edit', 'Bulk Edit in progress by {name}', ['name' => $name]);
    }
}
