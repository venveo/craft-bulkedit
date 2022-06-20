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
use craft\controllers\ElementIndexesController;
use craft\errors\SiteNotFoundException;
use craft\models\Site;
use craft\records\Field;
use craft\web\Response;
use Exception;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use venveo\bulkedit\base\ElementTypeProcessorInterface;
use venveo\bulkedit\Plugin;
use yii\web\BadRequestHttpException;

/**
 * @author    Venveo
 * @package   BulkEdit
 * @since     1.0.0
 */
class BulkEditController extends ElementIndexesController
{
    public bool $isSelectingAll = false;

    public ?Site $site = null;

    public function beforeAction($action): bool
    {
        parent::beforeAction($action);
        $this->isSelectingAll = $this->isSelectingAll();
        $this->site = $this->site();
        if (!$this->site) {
            throw new SiteNotFoundException('Site does not exist');
        }
        return true;
    }

    public function isSelectingAll(): bool
    {
        return $this->request->getParam('selectAll', false);
    }

    public function site(): Site
    {
        $siteId = $this->request->getParam('siteId', Craft::$app->sites->currentSite->id);
        return Craft::$app->sites->getSiteById($siteId);
    }

    /**
     * Return the file preview for an Asset.
     *
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

        $service = Plugin::getInstance()->bulkEdit;
        $customFields = $service->getFieldWrappersForElementQuery($this->getElementQuery());
//        $attributes = $service->getAttributeWrappersForElementQuery($this->getElementQuery());
        $attributes = [];

        $view = Craft::$app->getView();
        $modalHtml = $view->renderTemplate('venveo-bulk-edit/elementactions/BulkEdit/_fields', [
            'fieldWrappers' => $customFields,
            'attributeWrappers' => [],
            'bulkedit' => $service,
            'selectedTotal' => $this->getElementQuery()->count(),
            'selectAllTotal' => $this->getElementQuery()->id(null)->count(),
            'selectAllChecked' => $this->isSelectingAll,
            'site' => $this->site,
        ]);

        $responseData = [
            'success' => true,
            'modalHtml' => $modalHtml,
            'siteId' => $this->site->id,
        ];
        $responseData['headHtml'] = $view->getHeadHtml();
        $responseData['footHtml'] = $view->getBodyHtml();

        return $this->asJson($responseData);
    }

    /**
     * @throws BadRequestHttpException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function actionGetEditScreen(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $service = Plugin::getInstance()->bulkEdit;
        $fields = Craft::$app->getRequest()->getRequiredParam('fieldConfig');
        $enabledFields = array_filter($fields, fn($field) => $field['enabled']);
        $fields = Field::findAll(array_keys($enabledFields));
        $fieldModels = [];
        /** @var Field $field */
        foreach ($fields as $field) {
            $fieldModel = Craft::$app->fields->getFieldById($field->id);
            if ($fieldModel && Plugin::$plugin->bulkEdit->isFieldSupported($fieldModel)) {
                $fieldModels[] = $fieldModel;
            }
        }

        $view = Craft::$app->getView();

        /** @var ElementTypeProcessorInterface $processor */
        $processor = Plugin::getInstance()->bulkEdit->getElementTypeProcessor($this->elementType);
        $elementIds = [$this->getElementQuery()->one()->id];
        $elementPlaceholder = $processor::getMockElement($elementIds, [
            'siteId' => $this->site->id,
        ]);

        // We've gotta register any asset bundles - this won't actually be rendered
        foreach ($fieldModels as $fieldModel) {
            $view->renderPageTemplate('_includes/field', [
                'field' => $fieldModel,
                'static' => true,
                'element' => $elementPlaceholder,
                'required' => false,
            ]);
        }

        $modalHtml = $view->renderTemplate('venveo-bulk-edit/elementactions/BulkEdit/_edit', [
            'fields' => $fieldModels,
            'elementType' => $this->elementType,
            'elementPlaceholder' => $elementPlaceholder,
            'totalElements' => $this->elementQuery()->count(),
            'fieldData' => $enabledFields,
            'site' => $this->site,
        ]);
        $responseData = [
            'success' => true,
            'modalHtml' => $modalHtml,
            'siteId' => $this->site->id,
        ];
        $responseData['headHtml'] = $view->getHeadHtml();
        $responseData['footHtml'] = $view->getBodyHtml();

        return $this->asJson($responseData);
    }

    /**
     * @throws BadRequestHttpException
     * @throws \yii\db\Exception
     */
    public function actionSaveContext(): \yii\web\Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $values = [];
        parse_str(Craft::$app->getRequest()->getRequiredParam('formValues'), $values);
        $fieldConfig = Craft::$app->getRequest()->getRequiredParam('fieldConfig');

        $fieldStrategies = [];
        foreach ($fieldConfig as $field) {
            $fieldStrategies[$field['id']] = $field['strategy'];
        }

        $fieldIds = array_keys($fieldStrategies);
        $fields = Field::findAll($fieldIds);

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

        $elementIds = $this->getElementQuery()->limit(null)->ids();

        try {
            Plugin::$plugin->bulkEdit->saveContext($this->elementType, $this->site->id, $elementIds, $fieldIds,
                $keyedFieldValues,
                $fieldStrategies);

            return $this->asJson([
                'success' => true,
            ]);
        } catch (Exception $e) {
//            throw $e;
            return $this->asFailure('Failed to save context');
        }
    }
}
