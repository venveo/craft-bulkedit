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

        $fieldsService = Craft::$app->getFields();
        $fieldModels = [];

        foreach ($fields as $field) {
            if(!$field['enabled']) {
                continue;
            }

            $fieldId = (int)$field['id'];
            $layoutIds = $field['layoutIds'];
            foreach ($layoutIds as $layoutId) {
                $layout = $fieldsService->getLayoutById($layoutId);
                if(!$layout){
                    continue;
                }

                $layoutField = $layout->getFieldById($fieldId);
                if(!$layoutField || !Plugin::$plugin->bulkEdit->isFieldSupported($layoutField)){
                    continue;
                }
                // due to current restrictions of field namespaces we cannot add the very same field with the same handle twice
                // otherwise we need to render 2 separate field layouts and pass two different namespace params.
                //
                // I tried to include another layout but failed unfortunately (in a reasonable amount of time)
                // so as of now this only works for one field for all field layouts
                //
                // This will currently break in case users select two elements with a different layout where one
                // field is instance of A and another field with the very same handle is instance of B while the two
                // field types are incompatible to each other
                // the only way to solve this is to render two separate fields and separate their handles
                // (eg include the layout ID into the handle and regex it)
                /** @see \venveo\bulkedit\services\BulkEdit::processElementWithContext */
                if(isset($fieldModels[$layoutField->handle])){
                    continue;
                }
                $fieldModels[$layoutField->handle] = $layoutField;
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

        $fieldService = Craft::$app->getFields();
        $fieldConfigs = [];
        foreach ($fieldConfigData as $fieldConfigDatum) {
            if (!$fieldConfigDatum['enabled']) {
                continue;
            }

            $fieldConfig = new FieldConfig();
            $fieldConfig->strategy = $fieldConfigDatum['strategy'];
            $fieldConfig->type = $fieldConfigDatum['type'];
            if ($fieldConfig->type !== FieldType::CustomField) {
            if ($fieldConfig->validate()) {
                $fieldConfigs[] = $fieldConfig;
                    continue;
                }
                throw new \Exception('Failed to validate field configuration: ' . Json::encode($fieldConfig));
            }

            $layoutIds = $fieldConfigDatum['layoutIds'];
            foreach ($layoutIds as $layoutId) {
                $fieldConfigForLayout = clone $fieldConfig;

                $fieldConfigForLayout->fieldId = (int)$fieldConfigDatum['id'];

                $layout = $fieldService->getLayoutById($layoutId);
                if(!$layout){
                    continue;
                }

                $field = $layout->getFieldById($fieldConfigDatum['id']);
                if(!$field){
                    continue;
                }

                $fieldConfigForLayout->handle = $field->handle;
                $fieldConfigForLayout->layoutId = $layoutId;
                $fieldConfigForLayout->serializedValue = Json::encode($fieldValues[$fieldConfigForLayout->handle]);
                $fieldConfigs[] = $fieldConfigForLayout;
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
