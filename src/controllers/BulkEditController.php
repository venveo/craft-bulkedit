<?php
/**
 * Bulk Edit plugin for Craft CMS 3.x
 *
 * Bulk edit entries
 *
 * @link      https://venveo.com
 * @copyright Copyright (c) 2018 Venveo
 */

namespace venveo\bulkedit\controllers;

use craft\elements\Entry;
use craft\models\EntryType;
use craft\records\FieldLayout;
use craft\records\Element;
use craft\records\Field;
use craft\records\FieldLayoutField;
use craft\records\FieldLayoutTab;
use craft\records\Section;
use craft\records\Section_SiteSettings;
use craft\web\Response;
use Ramsey\Uuid\Uuid;
use venveo\bulkedit\assetbundles\bulkeditscreen\BulkEditScreenAsset;
use venveo\bulkedit\BulkEdit as Plugin;

use Craft;
use craft\web\Controller;
use venveo\bulkedit\BulkEdit;
use venveo\bulkedit\queue\jobs\SaveBulkEditJob;
use venveo\bulkedit\records\EditContext;
use venveo\bulkedit\records\History;
use venveo\bulkedit\services\BulkEdit as BulkEditService;
use yii\db\Transaction;
use yii\web\BadRequestHttpException;

/**
 * BulkEdit Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Venveo
 * @package   BulkEdit
 * @since     1.0.0
 */
class BulkEditController extends Controller
{

    // Protected Properties
    // =========================================================================


    // Public Methods
    // =========================================================================

    /**
     * Return the file preview for an Asset.
     *
     * @return Response
     * @throws BadRequestHttpException if not a valid request
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function actionGetFields(): Response
    {
        $this->requireLogin();
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $site = Craft::$app->getSites()->getCurrentSite();
        $elementIds = Craft::$app->getRequest()->getRequiredParam('elementIds');
        $requestId = Craft::$app->getRequest()->getRequiredParam('requestId');

        /** @var BulkEditService $service */
        $service = Plugin::$plugin->bulkEdit;
        $layouts = $service->getFieldLayoutsForElementIds($elementIds);
        $view = \Craft::$app->getView();
        $modalHtml = $view->renderTemplate('bulkedit/elementactions/BulkEdit/_fields', [
            'layouts' => $layouts,
            'elementIds' => $elementIds,
            'site' => $site
        ]);
//
//        if (!$asset->getSupportsPreview()) {
//            $modalHtml = '<p class="nopreview centeralign" style="top: calc(50% - 10px) !important; position: relative;">' . Craft::t('app', 'Preview not available.') . '</p>';
//        } else {
//            if ($asset->kind === 'image') {
//                /** @var Volume $volume */
//                $volume = $asset->getVolume();
//
//                if ($volume->hasUrls) {
//                    $imageUrl = $asset->getUrl();
//                } else {
//                    $source = $asset->getTransformSource();
//                    $imageUrl = Craft::$app->getAssetManager()->getPublishedUrl($source, true);
//                }
//
//                $width = $asset->getWidth();
//                $height = $asset->getHeight();
//                $modalHtml = "<img src=\"$imageUrl\" width=\"{$width}\" height=\"{$height}\" data-maxWidth=\"{$width}\" data-maxHeight=\"{$height}\"/>";
//            } else {
//                $localCopy = $asset->getCopyOfFile();
//                $content = htmlspecialchars(file_get_contents($localCopy));
//                $language = $asset->kind === Asset::KIND_HTML ? 'markup' : $asset->kind;
//                $modalHtml = '<div class="highlight ' . $asset->kind . '"><pre><code class="language-' . $language . '">' . $content . '</code></pre></div>';
//                unlink($localCopy);
//            }
//        }

        return $this->asJson([
            'success' => true,
            'modalHtml' => $modalHtml,
            'requestId' => $requestId
        ]);
    }

    public function actionEdit(): Response {
        $this->requireLogin();
        $this->requirePostRequest();
        $elementIds = array_values(Craft::$app->getRequest()->getRequiredParam('elementIds'));
        $siteId = Craft::$app->getRequest()->getRequiredParam('siteId');
        $fieldIds = array_values(array_filter(Craft::$app->getRequest()->getRequiredParam('fieldIds')));

        $site = Craft::$app->getSites()->getSiteById($siteId);
        if (!$site) {
            throw new \Exception('Site does not exist');
        }

        $fields = Field::findAll($fieldIds);
        if (count($fields) !== count($fieldIds)) {
            throw new \Exception('Could not find all fields requested');
        }

        $elements = Element::findAll($elementIds);
        if (count($elements) !== count($elementIds)) {
            throw new \Exception('Could not find all elements requested');
        }




        $view = Craft::$app->getView();
        $view->registerAssetBundle(BulkEditScreenAsset::class);

        /*
        TODO: Finish field layout emulation for certain custom field types
        // We need to create a temporary field layout and tab with all of our
        // fields on it.
        $fieldLayout = new FieldLayout();
        $fieldLayout->type = Entry::class;
        $fieldLayout->save();
        $fieldTab = new FieldLayoutTab();
        $fieldTab->layoutId = $fieldLayout->id;
        $fieldTab->name = 'Temp - Bulk Edit';
        $fieldTab->save();

        */
        $transaction = \Craft::$app->db->beginTransaction(Transaction::SERIALIZABLE);

        $transaction->begin();
        try {
            $fieldModels = [];
            /** @var Field $field */
            foreach ($fields as $field) {
                $fieldModel = \Craft::$app->fields->getFieldById($field->id);
                $fieldModels[] = $fieldModel;
                /* TODO: Finish field layout emulation...
                $fieldLayoutField = new FieldLayoutField();
                $fieldLayoutField->layoutId = $fieldLayout->id;
                $fieldLayoutField->tabId = $fieldTab->id;
                $fieldLayoutField->required = 1;
                $fieldLayoutField->fieldId = $field->id;
                $fieldLayoutField->save();
                */
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
        $transaction->commit();

        /*
         * TODO: Finish field layout emulation

        $section = new \craft\models\Section();
        $section->type = 'single';
        $section->enableVersioning = 0;
        $section->propagateEntries = 0;
        $section->handle = 'BULK_EDIT_DELETE_ME';
        $section->name = 'BULK_EDIT_DELETE_ME';

        $siteSettings = new \craft\models\Section_SiteSettings();
        $siteSettings->siteId = $site->id;
        $siteSettings->hasUrls = false;
        $siteSettings->uriFormat = null;
        $siteSettings->template = null;
        $section->setSiteSettings([$siteSettings]);
        \Craft::$app->sections->saveSection($section, false);

        // Some custom field types require a sacrificial element.
        // We'll feed it an Entry.
        $entryType = $section->getEntryTypes()[0];

        $baseEntry = new Entry();
        $baseEntry->authorId = Craft::$app->getUser()->getIdentity()->id;
        $baseEntry->typeId = $entryType->id;
        $baseEntry->sectionId = $section->id;
        $baseEntry->enabled = true;
        $baseEntry->siteId = $siteId;
        */
        // This is going to break some field types.
        $baseEntry = null;

        return $this->renderTemplate('bulkedit/cp/BulkEditScreen', [
            'fields' => $fieldModels,
            'elementIds' => $elementIds,
            'baseElement' => $baseEntry,
            'siteId' => $siteId
        ]);
    }

    public function actionSaveContext(): Response
    {
        $this->requireLogin();
        $this->requirePostRequest();
        $elementIds = Craft::$app->getRequest()->getRequiredParam('elementIds');
        $siteId = Craft::$app->getRequest()->getRequiredParam('siteId');
        $fieldIds = array_values(Craft::$app->getRequest()->getRequiredParam('fieldIds'));

        $fields = Field::findAll($fieldIds);

        $values = Craft::$app->getRequest()->getBodyParam('fields', []);

        $keyedFieldValues = [];
        foreach($values as $handle => $value) {
            foreach($fields as $field) {
                if ($field->handle === $handle) {
                    $fieldId = $field->id;
                }
            }
            if (!$fieldId) {
                throw new \Exception('Failed to locate field');
            }
            $keyedFieldValues[$fieldId] = $value;
        }

        $context = new EditContext();
        $context->ownerId = \Craft::$app->getUser()->getIdentity()->id;
        $context->siteId = $siteId;
        $context->elementIds = \GuzzleHttp\json_encode($elementIds);
        $context->fieldIds = \GuzzleHttp\json_encode($fieldIds);
        $context->save();

        $rows = [];
        foreach($elementIds as $elementId) {
            foreach($fieldIds as $fieldId) {
                $rows[] = [
                    'pending',
                    $context->id,
                    (int)$elementId,
                    (int)$fieldId,
                    (int)$siteId,
                    '[]',
                    \GuzzleHttp\json_encode($keyedFieldValues[$fieldId]),
                ];
            }
        }

        $cols = ['status', 'contextId', 'elementId', 'fieldId', 'siteId', 'originalValue', 'newValue'];
        \Craft::$app->db->createCommand()->batchInsert(History::tableName(), $cols, $rows)->execute();


        $job = new SaveBulkEditJob([
            'context' => $context
        ]);
        \Craft::$app->getQueue()->push($job);

        \Craft::$app->session->setFlash('notice', "Bulk Edit job started");
        return $this->redirectToPostedUrl();
    }
}
