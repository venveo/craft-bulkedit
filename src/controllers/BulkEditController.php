<?php
/**
 * Bulk Edit plugin for Craft CMS 3.x
 *
 * Bulk edit entries
 *
 * @link      https://venveo.com
 * @copyright Copyright (c) 2018-2019 Venveo
 */

namespace venveo\bulkedit\controllers;

use Craft;
use craft\errors\SiteNotFoundException;
use craft\helpers\ElementHelper;
use craft\records\Element;
use craft\records\Field;
use craft\web\Controller;
use craft\web\Response;
use Exception;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use venveo\bulkedit\base\ElementTypeProcessorInterface;
use venveo\bulkedit\Plugin;
use venveo\bulkedit\services\BulkEdit as BulkEditService;
use yii\web\BadRequestHttpException;

/**
 * @author    Venveo
 * @package   BulkEdit
 * @since     1.0.0
 */
class BulkEditController extends Controller
{
    /**
     * Return the file preview for an Asset.
     *
     * @return Response
     * @throws BadRequestHttpException if not a valid request
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws SiteNotFoundException
     */
    public function actionGetFields(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $elementIds = Craft::$app->getRequest()->getRequiredParam('elementIds');
        $requestId = Craft::$app->getRequest()->getRequiredParam('requestId');
        $viewParams = Craft::$app->getRequest()->getRequiredParam('viewParams');

        $elementType = $viewParams['elementType'];
        $siteId = $viewParams['criteria']['siteId'];
        $site = Craft::$app->sites->getSiteById($siteId);

//        $sourceKey = $viewParams['source'];
//        $criteria = $viewParams['criteria'];
//
//        $query = $elementType::find();
//        $source = ElementHelper::findSource($elementType, $sourceKey, 'index');
//
//
//        if ($source === null) {
//            throw new BadRequestHttpException('Invalid source key: ' . $sourceKey);
//        }
//
//        // Does the source specify any criteria attributes?
//        if (isset($source['criteria'])) {
//            Craft::configure($query, $source['criteria']);
//        }
//
//        // Override with the request's params
//        if ($criteria !== null) {
//            if (isset($criteria['trashed'])) {
//                $criteria['trashed'] = (bool)$criteria['trashed'];
//            }
//            Craft::configure($query, $criteria);
//        }

        /** @var BulkEditService $service */
        $service = Plugin::$plugin->bulkEdit;
        $fields = $service->getFieldWrappers($elementIds, $elementType);
        $attributes = $service->getAttributeWrappers($elementType);

        $view = Craft::$app->getView();
        $modalHtml = $view->renderTemplate('venveo-bulk-edit/elementactions/BulkEdit/_fields', [
            'fieldWrappers' => $fields,
            'attributeWrappers' => $attributes,
            'elementType' => $elementType,
            'bulkedit' => $service,
            'elementIds' => $elementIds,
            'site' => $site
        ]);

        $responseData = [
            'success' => true,
            'modalHtml' => $modalHtml,
            'requestId' => $requestId,
            'elementIds' => $elementIds,
            'siteId' => $site->id
        ];
        $responseData['headHtml'] = $view->getHeadHtml();
        $responseData['footHtml'] = $view->getBodyHtml();

        return $this->asJson($responseData);
    }

    /**
     * @return \yii\web\Response
     * @throws BadRequestHttpException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function actionGetEditScreen()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();


        $elementIds = Craft::$app->getRequest()->getRequiredParam('elementIds');
        $requestId = Craft::$app->getRequest()->getRequiredParam('requestId');
        $elementType = Craft::$app->getRequest()->getRequiredParam('elementType');
        $siteId = Craft::$app->getRequest()->getRequiredParam('siteId');
        $fields = Craft::$app->getRequest()->getRequiredParam('fields');

        $viewParams = Craft::$app->getRequest()->getParam('viewParams');

        // Pull out the enabled fields
        $enabledFields = [];
        foreach ($fields as $fieldId => $field) {
            if ($field['enabled']) {
                $enabledFields[$fieldId] = $field;
            }
        }


        $site = Craft::$app->getSites()->getSiteById($siteId);
        if (!$site) {
            throw new Exception('Site does not exist');
        }


        $fields = Field::findAll(array_keys($enabledFields));
        if (count($fields) !== count($enabledFields)) {
            throw new Exception('Could not find all fields requested');
        }

        $elementIds = explode(',', $elementIds);
        $elements = Element::findAll($elementIds);
        if (count($elements) !== count($elementIds)) {
            throw new Exception('Could not find all elements requested');
        }

        try {
            $fieldModels = [];
            /** @var Field $field */
            foreach ($fields as $field) {
                $fieldModel = Craft::$app->fields->getFieldById($field->id);
                if ($fieldModel && Plugin::$plugin->bulkEdit->isFieldSupported($fieldModel)) {
                    $fieldModels[] = $fieldModel;
                }
            }
        } catch (Exception $e) {
            throw $e;
        }

        $view = Craft::$app->getView();

        /** @var ElementTypeProcessorInterface $processor */
        $processor = Plugin::getInstance()->bulkEdit->getElementTypeProcessor($elementType);
        $elementPlaceholder = $processor::getMockElement($elementIds, [
            'siteId' => $siteId
        ]);

        // We've gotta register any asset bundles - this won't actually be rendered
        foreach ($fieldModels as $fieldModel) {
            $view->renderPageTemplate('_includes/field', [
                'field' => $fieldModel,
                'static' => true,
                'element' => $elementPlaceholder,
                'required' => false
            ]);
        }

        $modalHtml = $view->renderTemplate('venveo-bulk-edit/elementactions/BulkEdit/_edit', [
            'fields' => $fieldModels,
            'elementType' => $elementType,
            'elementPlaceholder' => $elementPlaceholder,
            'elementIds' => $elementIds,
            'fieldData' => $enabledFields,
            'site' => $site
        ]);
        $responseData = [
            'success' => true,
            'modalHtml' => $modalHtml,
            'requestId' => $requestId,
            'siteId' => $site->id
        ];
        $responseData['headHtml'] = $view->getHeadHtml();
        $responseData['footHtml'] = $view->getBodyHtml();

        return $this->asJson($responseData);
    }

    /**
     * @return \yii\web\Response
     * @throws BadRequestHttpException
     * @throws \yii\db\Exception
     */
    public function actionSaveContext()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $elementIds = Craft::$app->getRequest()->getRequiredParam('elementIds');
        $elementType = Craft::$app->getRequest()->getRequiredParam('elementType');
        $siteId = Craft::$app->getRequest()->getRequiredParam('siteId');
        $fieldMeta = array_values(Craft::$app->getRequest()->getRequiredParam('fieldMeta'));

        $fieldStrategies = [];
        foreach ($fieldMeta as $field) {
            $fieldStrategies[$field['id']] = $field['strategy'];
        }
        $fieldIds = array_keys($fieldStrategies);
        $fields = Field::findAll($fieldIds);

        $values = Craft::$app->getRequest()->getBodyParam('fields', []);

        $keyedFieldValues = [];
        foreach ($values as $handle => $value) {
            foreach ($fields as $field) {
                if ($field->handle === $handle) {
                    $fieldId = $field->id;
                }
            }
            if (!isset($fieldId)) {
                throw new Exception('Failed to locate field');
            }
            $keyedFieldValues[$fieldId] = $value;
        }

        $elementIds = explode(',', $elementIds);

        try {
            Plugin::$plugin->bulkEdit->saveContext($elementType, $siteId, $elementIds, $fieldIds, $keyedFieldValues, $fieldStrategies);

            return $this->asJson([
                'success' => true
            ]);
        } catch (Exception $e) {
            return $this->asErrorJson('Failed to save context');
        }
    }
}
