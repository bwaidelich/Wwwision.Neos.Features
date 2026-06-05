<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Controller;

use Neos\Error\Messages\Message;
use Neos\Fusion\View\FusionView;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Neos\Utility\Files;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Wwwision\Neos\Features\FeatureSystem;
use Wwwision\Neos\Features\Model\Feature\Feature;
use Wwwision\Neos\Features\Model\Feature\FeatureDependencyViolation;
use Wwwision\Neos\Features\Model\Feature\FeatureId;
use Wwwision\Neos\Features\Model\Feature\Features;
use Wwwision\Neos\Features\Model\FeatureGroup\FeatureGroups;

final class FeaturesModuleController extends AbstractModuleController
{
    protected $defaultViewObjectName = FusionView::class;

    public function __construct(
        private FeatureSystem $featureSystem,
    ) {}

    public function indexAction(): void
    {
        $this->view->assign('sections', $this->buildSections($this->featureSystem->getFeatures(), $this->featureSystem->getFeatureGroups()));
    }

    /**
     * Groups the given features for presentation: one section per non-empty group (in group order), followed by a
     * trailing catch-all section for ungrouped features.
     *
     * @param Features $features
     * @param FeatureGroups $groups
     * @return list<array{group: ?\Wwwision\Neos\Features\Model\FeatureGroup\FeatureGroup, features: list<Feature<\Wwwision\Neos\Features\Model\Feature\FeatureOptions>>}>
     */
    private function buildSections(Features $features, FeatureGroups $groups): array
    {
        $byGroup = [];
        $ungrouped = [];
        foreach ($features as $feature) {
            if ($feature->group === null) {
                $ungrouped[] = $feature;
            } else {
                $byGroup[$feature->group->value][] = $feature;
            }
        }
        $sections = [];
        foreach ($groups as $group) {
            $groupedFeatures = $byGroup[$group->id->value] ?? [];
            if ($groupedFeatures !== []) {
                $sections[] = ['group' => $group, 'features' => $groupedFeatures];
            }
        }
        if ($ungrouped !== []) {
            $sections[] = ['group' => null, 'features' => $ungrouped];
        }
        return $sections;
    }

    public function showAction(string $featureId): void
    {
        $feature = $this->featureSystem->getFeature(FeatureId::fromString($featureId));
        $this->view->assign('feature', $feature);
    }

    public function activateFormAction(string $featureId): void
    {
        $featureIdVO = FeatureId::fromString($featureId);
        $feature = $this->featureSystem->getFeature($featureIdVO);
        if (!$feature->isActivatable()) {
            $this->addFlashMessage('Feature "%s" kann nicht aktiviert werden', 'Fehler', Message::SEVERITY_WARNING, messageArguments: [$featureIdVO->value]);
            $this->redirect('index');
        }
        $this->view->assign('feature', $feature);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function activateAction(string $featureId, array $options = []): void
    {
        $featureIdVO = FeatureId::fromString($featureId);
        $feature = $this->featureSystem->getFeature($featureIdVO);
        $options = self::preProcessOptions($featureIdVO, $options);
        try {
            $this->featureSystem->activateFeature($featureIdVO, array_filter($options, static fn($value) => $value !== ''));
        } catch (FeatureDependencyViolation $exception) {
            $this->addFlashMessage($exception->getMessage(), 'error', Message::SEVERITY_ERROR);
            $this->redirect('index');
            return;
        }
        $this->addFlashMessage('Feature "%s" aktiviert', 'success', messageArguments: [$feature->name->value]);
        $this->redirect('index');
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private static function preProcessOptions(FeatureId $featureId, array $options): array
    {
        $result = [];
        foreach ($options as $optionName => $optionValue) {
            if ($optionValue instanceof UploadedFileInterface) {
                $targetPath = Files::concatenatePaths([FLOW_PATH_DATA, 'Features', $featureId->value, $optionName]); // @phpstan-ignore constant.notFound
                if (!is_dir(dirname($targetPath)) && !mkdir($concurrentDirectory = dirname($targetPath), 0777, true) && !is_dir($concurrentDirectory)) {
                    throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
                }
                $optionValue->moveTo($targetPath);
                $options[$optionName] = $targetPath;
                continue;
            }
            if ($optionValue === '') {
                continue;
            }
            if (is_array($optionValue)) {
                $result[$optionName] = self::preProcessOptions($featureId, $optionValue);
            } else {
                $result[$optionName] = $optionValue;
            }
        }
        return $result;
    }

    public function deactivateFormAction(string $featureId): void
    {
        $featureIdVO = FeatureId::fromString($featureId);
        $feature = $this->featureSystem->getFeature($featureIdVO);
        if (!$feature->isDeactivatable()) {
            $this->addFlashMessage('Feature "%s" kann nicht deaktiviert werden', 'Fehler', Message::SEVERITY_WARNING, messageArguments: [$featureIdVO->value]);
            $this->redirect('index');
        }
        $this->view->assign('feature', $feature);
    }

    public function deactivateAction(string $featureId): void
    {
        $featureIdVO = FeatureId::fromString($featureId);
        try {
            $this->featureSystem->deactivateFeature($featureIdVO, removeState: true);
        } catch (FeatureDependencyViolation $exception) {
            $this->addFlashMessage($exception->getMessage(), 'error', Message::SEVERITY_ERROR);
            $this->redirect('index');
            return;
        }
        $this->addFlashMessage('Feature "%s" deaktiviert', 'success', messageArguments: [$featureIdVO->value]);
        $this->redirect('index');
    }
}
