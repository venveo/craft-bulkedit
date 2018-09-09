<?php
/**
 * Bulk Edit plugin for Craft CMS 3.x
 *
 * Bulk edit entries
 *
 * @link      https://venveo.com
 * @copyright Copyright (c) 2018 Venveo
 */

namespace venveo\bulkedit\services;

use craft\elements\actions\Edit;
use craft\records\Element;
use craft\records\FieldLayout;
use venveo\bulkedit\BulkEdit as Plugin;

use craft\base\Component;
use venveo\bulkedit\records\EditContext;
use venveo\bulkedit\records\History;

/**
 * ElementEditor Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Venveo
 * @package   BulkEdit
 * @since     1.0.0
 */
class BulkEdit extends Component
{
    // Public Methods
    // =========================================================================


    /**
     * Get all distinct field layouts from a set of elements
     * @param $elementIds
     * @return FieldLayout
     */
    public function getFieldLayoutsForElementIds($elementIds)
    {
        $layouts = FieldLayout::find()
            ->select('fieldlayouts.*')
            ->distinct(true)
            ->limit(null)
            ->from('{{%fieldlayouts}} fieldlayouts')
            ->leftJoin('{{%elements}} elements', 'elements.fieldLayoutId = fieldlayouts.id')
            ->where(['IN', 'elements.id', $elementIds])
            ->with(['fields'])
            ->all();

        return $layouts;
    }

    public function getBulkEditContextFromId($id) {
        return EditContext::findOne($id);
    }

    /**
     * @param EditContext $context
     * @return array
     */
    public function getPendingElementIdsFromContext(EditContext $context) {
        $items = array_keys(History::find()
            ->limit(null)
            ->where(['=', 'contextId', $context->id])
            ->andWhere(['=', 'status', 'pending'])->indexBy('elementId')->all());

        return $items;
    }

    /**
     * @param EditContext $context
     * @return \yii\db\ActiveQueryInterface
     */
    public function getPendingHistoryFromContext(EditContext $context)
    {
        return $context->getHistoryItems()->where(['=', 'status', 'pending']);
    }

    /**
     * @param EditContext $context
     * @param $elementId
     * @return \yii\db\ActiveQueryInterface
     */
    public function getPendingHistoryForElement(EditContext $context, $elementId) {
        $items = $this->getPendingHistoryFromContext($context);
        $items->where(['=', 'elementId', $elementId]);
        return $items;
    }

    public function processHistoryItemsForElement($historyItems, \craft\base\Element $element) {
        /** @var History $historyItem */
        foreach($historyItems as $historyItem) {
            try {
                $historyItem->originalValue = \GuzzleHttp\json_encode($element->getFieldValue($historyItem->field->handle));
                $historyItem->status = 'completed';
                $element->setFieldValue($historyItem->field->handle, \GuzzleHttp\json_decode($historyItem->newValue));
                $historyItem->save(false);
            } catch (\Exception $e) {
                die($e->getMessage());
            }
        }
        \Craft::$app->elements->saveElement($element);
        return $element;
    }
}
