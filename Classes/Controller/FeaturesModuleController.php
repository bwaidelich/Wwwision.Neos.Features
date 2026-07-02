<?php

declare(strict_types=1);

namespace Wwwision\Neos\Features\Controller;

use Neos\Error\Messages\Message;
use Neos\Fusion\View\FusionView;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Neos\Utility\Files;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Throwable;
use Wwwision\Neos\Features\FeatureSystem;
use Wwwision\Neos\Features\Model\Feature\Feature;
use Wwwision\Neos\Features\Model\Feature\FeatureDependencyViolation;
use Wwwision\Neos\Features\Model\Feature\FeatureId;
use Wwwision\Neos\Features\Model\Feature\FeatureIds;
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
        if (!$feature->hasOptions()) {
            // optionless features are activated directly (one-click) and have no activation form
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
            $this->featureSystem->activateFeature($featureIdVO, $options);
        } catch (FeatureDependencyViolation $exception) {
            $this->addFlashMessage($exception->getMessage(), 'error', Message::SEVERITY_ERROR);
            $this->redirect('index');
            return;
        }
        $this->addFlashMessage('Feature "%s" aktiviert', 'success', messageArguments: [$feature->name->value]);
        $this->redirect('index');
    }

    /**
     * Renders the combined activation form for the given selection of features, expanded (server-side) with all
     * inactive features they transitively depend on.
     *
     * @param array $featureIds
     */
    public function batchActivateFormAction(array $featureIds = []): void
    {
        if ($featureIds === []) {
            $this->addFlashMessage('Es wurden keine Features ausgewählt', 'Hinweis', Message::SEVERITY_NOTICE);
            $this->redirect('index');
        }
        $requestedFeatureIds = FeatureIds::fromArray($featureIds);
        $features = $this->featureSystem->getFeaturesForActivation($requestedFeatureIds);
        if (iterator_to_array($features) === []) {
            $this->addFlashMessage('Alle ausgewählten Features sind bereits aktiv', 'Hinweis', Message::SEVERITY_NOTICE);
            $this->redirect('index');
        }
        $optionsByFeatureId = [];
        foreach ($features as $feature) {
            if ($feature->options !== null) {
                $optionsByFeatureId[$feature->id->value] = $feature->getNormalizedOptions();
            }
        }
        $this->view->assign('features', $features);
        $this->view->assign('requestedFeatureIds', $requestedFeatureIds->toStringArray());
        $this->view->assign('optionsByFeatureId', $optionsByFeatureId);
    }

    /**
     * @param array $featureIds
     * @param array<string, array<string, mixed>> $options options per feature, indexed by feature id
     */
    public function batchActivateAction(array $featureIds = [], array $options = []): void
    {
        if ($featureIds === []) {
            $this->addFlashMessage('Es wurden keine Features ausgewählt', 'Hinweis', Message::SEVERITY_NOTICE);
            $this->redirect('index');
        }
        $processedOptions = [];
        foreach ($options as $featureIdValue => $featureOptions) {
            $processedOptions[$featureIdValue] = self::preProcessOptions(FeatureId::fromString($featureIdValue), $featureOptions);
        }
        try {
            $result = $this->featureSystem->activateFeatures(FeatureIds::fromArray($featureIds), $processedOptions);
        } catch (Throwable $exception) {
            $this->addFlashMessage($exception->getMessage(), 'Fehler', Message::SEVERITY_ERROR);
            $this->redirect('index');
            return;
        }
        if (!$result->activated->isEmpty()) {
            $this->addFlashMessage('Aktivierte Features: %s', 'success', messageArguments: [(string) $result->activated]);
        } elseif (!$result->hasFailure()) {
            $this->addFlashMessage('Keine Features aktiviert – alle ausgewählten Features waren bereits aktiv', 'Hinweis', Message::SEVERITY_NOTICE);
        }
        if ($result->failedFeatureId !== null) {
            $this->addFlashMessage('Feature "%s" konnte nicht aktiviert werden: %s', 'Fehler', Message::SEVERITY_ERROR, messageArguments: [$result->failedFeatureId->value, $result->failureMessage]);
        }
        $this->redirect('index');
    }

    public function updateOptionsFormAction(string $featureId): void
    {
        $featureIdVO = FeatureId::fromString($featureId);
        $feature = $this->featureSystem->getFeature($featureIdVO);
        if (!$feature->active) {
            $this->addFlashMessage('Feature "%s" kann nicht aktualsiert werden, da es nicht aktiv ist', 'Fehler', Message::SEVERITY_WARNING, messageArguments: [$featureIdVO->value]);
            $this->redirect('index');
        }
        if (!$feature->hasOptions()) {
            $this->addFlashMessage('Feature "%s" hat keine Optionen', 'Fehler', Message::SEVERITY_WARNING, messageArguments: [$featureIdVO->value]);
            $this->redirect('index');
        }
        $this->view->assign('feature', $feature);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function updateOptionsAction(string $featureId, array $options = []): void
    {
        $featureIdVO = FeatureId::fromString($featureId);
        $feature = $this->featureSystem->getFeature($featureIdVO);
        $options = self::preProcessOptions($featureIdVO, $options);
        try {
            $this->featureSystem->updateFeatureOptions($featureIdVO, $options);
        } catch (FeatureDependencyViolation $exception) {
            $this->addFlashMessage($exception->getMessage(), 'error', Message::SEVERITY_ERROR);
            $this->redirect('index');
            return;
        }
        $this->addFlashMessage('Feature-Optionen für "%s" aktualisiert', 'success', messageArguments: [$feature->name->value]);
        $this->redirect('index');
    }

    /**
     * @param array<mixed> $options
     * @return array<mixed>
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
                $result[$optionName] = $targetPath;
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
