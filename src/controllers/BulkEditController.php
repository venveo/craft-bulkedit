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

use craft\models\FieldLayout;
use craft\records\Element;
use craft\records\Field;
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
        $fieldIds = array_values(Craft::$app->getRequest()->getRequiredParam('fieldIds'));

        $site = Craft::$app->getSites()->getSiteById($siteId);
        $elements = Element::findAll($elementIds);
        $fields = Field::findAll($fieldIds);

        $fieldModels = [];
        /** @var Field $field */
        foreach($fields as $field) {
            $fieldModels[] = \Craft::$app->fields->getFieldById($field->id);
        }

        $view = Craft::$app->getView();
        $view->registerAssetBundle(BulkEditScreenAsset::class);

        return $this->renderTemplate('bulkedit/cp/BulkEditScreen', [
            'fields' => $fieldModels,
            'elementIds' => $elementIds,
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
