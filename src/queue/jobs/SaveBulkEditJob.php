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
use craft\queue\BaseJob;
use venveo\bulkedit\BulkEdit;
use venveo\bulkedit\records\EditContext;

/**
 * SaveItemsTask job
 *
 * Jobs are run in separate process via a Queue of pending jobs. This allows
 * you to spin lengthy processing off into a separate PHP process that does not
 * block the main process.
 *
 * You can use it like this:
 *
 * use venveo\bulkedit\jobs\SaveItemsTask as SaveItemsTaskJob;
 *
 * $queue = Craft::$app->getQueue();
 * $jobId = $queue->push(new SaveItemsTaskJob([
 *     'description' => Craft::t('bulk-edit', 'This overrides the default description'),
 *     'someAttribute' => 'someValue',
 * ]));
 *
 * The key/value pairs that you pass in to the job will set the public properties
 * for that object. Thus whatever you set 'someAttribute' to will cause the
 * public property $someAttribute to be set in the job.
 *
 * Passing in 'description' is optional, and only if you want to override the default
 * description.
 *
 * More info: https://github.com/yiisoft/yii2-queue
 *
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
    public function execute($queue = null)
    {
        $elementIds = BulkEdit::$plugin->bulkEdit->getPendingElementIdsFromContext($this->context);
        $totalSteps = count($elementIds);
        foreach ($elementIds as $key => $elementId) {
            $element = Craft::$app->getElements()->getElementById($elementId);
            $history = BulkEdit::$plugin->bulkEdit->getPendingHistoryForElement($this->context, $element->id)->all();
            BulkEdit::$plugin->bulkEdit->processHistoryItemsForElement($history, $element);

            $this->setProgress($queue, $key++ / $totalSteps);
        }
    }

    // Protected Methods
    // =========================================================================

    protected function defaultDescription(): string
    {
        return Craft::t('bulkedit', 'Bulk Edit in progress by {name}', ['name' => $this->context->owner->firstName]);
    }
}
