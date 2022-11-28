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
use venveo\bulkedit\models\EditContext;
use venveo\bulkedit\Plugin;

/**
 * @author    Venveo
 * @package   BulkEdit
 * @since     1.0.0
 */
class SaveBulkEditJob extends BaseJob
{
    public EditContext $context;

    /**
     * Saves bulk edited elements
     * @param null $queue
     * @throws Throwable
     */
    public function execute($queue = null): void
    {
        $totalSteps = $this->context->total;
        try {
            $currentRow = 0;
            foreach ($this->context->elementIds as $elementId) {
                /** @var ElementInterface $element */
                $element = Craft::$app->getElements()->getElementById($elementId, null, $this->context->siteId);
                if (!$element) {
                    Craft::warning('Could not locate an element in a bulk save job: ' . $elementId, __METHOD__);
                    continue;
                }

                try {
                    Plugin::$plugin->bulkEdit->processElementWithContext($element, $this->context);
                } catch (\Exception $exception) {
                    Craft::error('Could not save element in bulk edit job... ' . $exception->getMessage(), __METHOD__);
                    throw $exception;
                } catch (Throwable $throwable) {
                    throw $throwable;
                }

                $this->setProgress($queue, ($currentRow + 1) / $totalSteps,
                    'Element ' . ($currentRow + 1) . ' of ' . $totalSteps);
                ++$currentRow;
            }
        } catch (\Exception $exception) {
            Craft::error('Failed to save... ' . $exception->getMessage(), __METHOD__);
            throw $exception;
        }
    }

    // Protected Methods
    // =========================================================================

    protected function defaultDescription(): ?string
    {
        if ($this->context->getOwner()) {
            $name = $this->context->getOwner()->firstName ?? $this->context->getOwner()->email ?? '';
            return Craft::t('venveo-bulk-edit', 'Bulk Edit in progress by {name}', ['name' => $name]);
        } else {
            return Craft::t('venveo-bulk-edit', 'Bulk Edit in progress');
        }
    }
}
