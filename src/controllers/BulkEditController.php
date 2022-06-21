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
use craft\fieldlayoutelements\CustomField;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Site;
use craft\records\Field;
use craft\web\Response;
use Exception;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use venveo\bulkedit\base\ElementTypeProcessorInterface;
use venveo\bulkedit\enums\FieldType;
use venveo\bulkedit\models\FieldConfig;
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
        $namespace = StringHelper::randomString(10);

        $service = Plugin::getInstance()->bulkEdit;
        $customFields = $service->getFieldWrappersForElementQuery($this->getElementQuery());

        $view = Craft::$app->getView();
        $view->setNamespace($namespace);
        $modalHtml = $view->renderTemplate('venveo-bulk-edit/elementactions/BulkEdit/_fields', [
            'fieldWrappers' => $customFields,
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
            'namespace' => $namespace,
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

        $fields = $this->request->getRequiredParam('fieldConfig');
        $namespace = $this->request->getRequiredParam('namespace');
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

        $fieldLayoutElements = [];
        $fieldLayout = new FieldLayout();
        $fieldLayoutTab = new FieldLayoutTab();
        $fieldLayoutTab->setLayout($fieldLayout);
        $fieldLayoutTab->name = 'Content';
        $fieldLayoutTab->uid = 'content';

        foreach ($fieldModels as $fieldModel) {
            $fieldLayoutElement = new CustomField();
            $fieldLayoutElement->setField($fieldModel);
            $fieldLayoutElements[] = $fieldLayoutElement;
        }
        $fieldLayoutTab->setElements($fieldLayoutElements);
        $fieldLayout->setTabs([$fieldLayoutTab]);
        $fieldLayoutForm = $fieldLayout->createForm($elementPlaceholder, false, [
            'namespace' => $namespace
        ]);
        $html = $fieldLayoutForm->render();

        $modalHtml = $view->renderTemplate('venveo-bulk-edit/elementactions/BulkEdit/_edit', [
            'totalElements' => $this->elementQuery()->count(),
            'fieldHtml' => $html
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
        $namespace = $this->request->getRequiredParam('namespace');
        // Converts the url encoded form values from the json payload to the expected format.
        $namespacedValues = [];

        parse_str($this->request->getRequiredParam('formValues'), $namespacedValues);
        $fieldValues = $namespacedValues[$namespace]['fields'];

        $fieldConfigData = $this->request->getRequiredParam('fieldConfig');

        $fieldConfigs = [];
        foreach ($fieldConfigData as $fieldConfigDatum) {
            if (!$fieldConfigDatum['enabled']) {
                continue;
            }
            $fieldConfig = new FieldConfig();
            $fieldConfig->strategy = $fieldConfigDatum['strategy'];
            $fieldConfig->type = $fieldConfigDatum['type'];
            if ($fieldConfig->type === FieldType::CustomField) {
                $fieldConfig->fieldId = (int)$fieldConfigDatum['id'];
                $fieldConfig->handle = Craft::$app->fields->getFieldById($fieldConfig->fieldId)->handle;
                $fieldConfig->serializedValue = Json::encode($fieldValues[$fieldConfig->handle]);
            }
            if ($fieldConfig->validate()) {
                $fieldConfigs[] = $fieldConfig;
            } else {
                throw new \Exception('Failed to validate field configuration: ' . Json::encode($fieldConfig));
            }
        }


        $elementIds = $this->getElementQuery()->limit(null)->ids();

        try {
            Plugin::$plugin->bulkEdit->saveContext($this->elementType, $this->site->id, $elementIds, $fieldConfigs);

            return $this->asJson([
                'success' => true,
            ]);
        } catch (Exception $e) {
            Craft::error('Failed to save context', $e->getTraceAsString(), __METHOD__);
            return $this->asFailure('Failed to save context');
        }
    }
}
