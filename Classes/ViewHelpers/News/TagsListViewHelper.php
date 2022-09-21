<?php

declare(strict_types = 1);

namespace Remind\Typo3Headless\ViewHelpers\News;

use GeorgRinger\News\Domain\Model\Tag;
use GeorgRinger\News\ViewHelpers\Tag\CountViewHelper;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class TagsListViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    const ARGUMENT_TAGS = 'tags';
    const ARGUMENT_SETTINGS = 'settings';
    const ARGUMENT_OVERWRITE_DEMAND = 'overwriteDemand';

    public function initializeArguments()
    {
        $this->registerArgument(self::ARGUMENT_TAGS, 'array', 'tags', true);
        $this->registerArgument(self::ARGUMENT_SETTINGS, 'array', 'settings', true);
        $this->registerArgument(self::ARGUMENT_OVERWRITE_DEMAND, 'array', 'overwriteDemand', true);
    }

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
        )
    {
        if (!$renderingContext instanceof RenderingContext) {
            throw new \RuntimeException(
                sprintf(
                    'RenderingContext must be instance of "%s", but is instance of "%s"',
                    RenderingContext::class,
                    get_class($renderingContext)
                ),
                1663759243
            );
        }
        
        $tags = $arguments[self::ARGUMENT_TAGS];
        $settings = $arguments[self::ARGUMENT_SETTINGS];
        $overwriteDemand = $arguments[self::ARGUMENT_OVERWRITE_DEMAND];

        $overwriteDemandTags = $overwriteDemand ? (int)($overwriteDemand['tags'] ?? false) : false;

        $viewHelperInvoker = $renderingContext->getViewHelperInvoker();
        $uriBuilder = $renderingContext->getUriBuilder();

        $result = [
            'settings' => [
                'orderBy' => $settings['orderBy'],
                'orderDirection' => $settings['orderDirection'],
                'templateLayout' => $settings['templateLayout'],
                'action' => 'tagsList'
            ]
        ];

        $uri = $uriBuilder
            ->reset()
            ->setTargetPageUid((int)$settings['listPid'])
            ->build();

        $allTags = [
            'title' => LocalizationUtility::translate('news.tagsList.all', 'rmnd_headless'),
            'slug' => $uri,
            'active' => !$overwriteDemandTags,
            'count' => 0,
        ];

        $result['tags'][] = &$allTags;

        foreach ($tags as $tag) {
            /** @var Tag $tag */

            $uri = $uriBuilder
                ->reset()
                ->setTargetPageUid((int)$settings['listPid'])
                ->setArguments([
                    'tx_news_pi1' => [
                        'overwriteDemand' => [
                            'tags' => $tag->getUid()
                        ]
                    ]
                ])
                ->build();

            $count = $viewHelperInvoker->invoke(
                CountViewHelper::class,
                ['tagUid' => $tag->getUid()],
                $renderingContext
            );

            $allTags['count'] += $count;
            
            $result['tags'][] = [
                'uid' => $tag->getUid(),
                'pid' => $tag->getPid(),
                'title' => $tag->getTitle(),
                'slug' => $uri,
                'active' => $overwriteDemandTags === $tag->getUid(),
                'count' => $count,
                'seo' => [
                    'title' => $tag->getSeoTitle(),
                    'description' => $tag->getSeoDescription(),
                    'headline' => $tag->getSeoHeadline(),
                    'text' => $tag->getSeoText()
                ]
            ];
        }

        return $result;
    }
}