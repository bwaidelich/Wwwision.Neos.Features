<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Controller;

use Neos\Fusion\View\FusionView;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Neos\Utility\Files;
use Psr\Http\Message\UploadedFileInterface;
use Wwwision\Neos\Features\FeatureSystem;
use Wwwision\Neos\Features\Model\Feature\FeatureId;

final class FeaturesModuleController extends AbstractModuleController
{
    protected $defaultViewObjectName = FusionView::class;

    public function __construct(
        private FeatureSystem $featureSystem,
    ) {}

    public function indexAction(): void
    {
        $this->view->assign('features', $this->featureSystem->getFeatures());
    }

    public function showAction(string $featureId, bool $showActivateDialog = false, bool $showDeactivateDialog = false): void
    {
        $feature = $this->featureSystem->getFeature(FeatureId::fromString($featureId));
        $this->view->assign('feature', $feature);
        $this->view->assign('showActivateDialog', $showActivateDialog);
        $this->view->assign('showDeactivateDialog', $showDeactivateDialog);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function activateAction(string $featureId, array $options = []): void
    {
        $featureIdVO = FeatureId::fromString($featureId);
        $feature = $this->featureSystem->getFeature($featureIdVO);
        foreach ($options as $optionName => $optionValue) {
            if ($optionValue instanceof UploadedFileInterface) {
                $targetPath = Files::concatenatePaths([FLOW_PATH_DATA, 'Features', $featureIdVO->value, $optionName]); // @phpstan-ignore constant.notFound
                if (!is_dir(dirname($targetPath))) {
                    mkdir(dirname($targetPath), 0777, true);
                }
                $optionValue->moveTo($targetPath);
                $options[$optionName] = $targetPath;
            }
        }
        $this->featureSystem->activateFeature($featureIdVO, array_filter($options, static fn($value) => $value !== ''));
        $this->addFlashMessage('Feature "%s" aktiviert', 'success', messageArguments: [$feature->name->value]);
        $this->redirect('index');
    }

    public function deactivateAction(string $featureId): void
    {
        $featureIdVO = FeatureId::fromString($featureId);
        $this->featureSystem->deactivateFeature($featureIdVO, removeState: true);
        $this->addFlashMessage('Feature "%s" deaktiviert', 'success', messageArguments: [$featureIdVO->value]);
        $this->redirect('index');
    }
}
