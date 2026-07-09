<?php

declare(strict_types=1);

namespace WebVision\UnitySchema\Domain\Repository;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use WebVision\UnitySchema\Exception\MissingFrontendContextException;

/**
 * Fetches the visible tt_content rows of a page, independent of any HTML rendering, so that
 * structured data can be assembled for a page even in a request that never renders that page's
 * content elements (see Unity's head request).
 */
final class PageContentRepository
{
    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findByPageId(int $pageId, ServerRequestInterface $request): array
    {
        $cObj = $this->getContentObjectRenderer($request);
        $topLevelRows = $cObj->getRecords('tt_content', [
            'pidInList' => (string)$pageId,
            'orderBy' => 'sorting',
        ]);

        return $this->withContainerChildren($topLevelRows, $cObj);
    }

    private function getContentObjectRenderer(ServerRequestInterface $request): ContentObjectRenderer
    {
        $frontendController = $request->getAttribute('frontend.controller');
        if (!$frontendController instanceof TypoScriptFrontendController) {
            throw new MissingFrontendContextException(
                'No frontend.controller request attribute available to fetch page content.',
                1783585358,
            );
        }

        return $frontendController->cObj;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function withContainerChildren(array $rows, ContentObjectRenderer $cObj): array
    {
        $parentField = (string)($this->extensionConfiguration->get('unity_schema', 'containerParentField') ?? '');
        if ($parentField === '') {
            return $rows;
        }

        $resolved = $rows;
        foreach ($rows as $row) {
            $children = $cObj->getRecords('tt_content', [
                'pidInList' => (string)$row['pid'],
                'where' => $parentField . ' = ' . (int)$row['uid'],
                'orderBy' => 'sorting',
            ]);
            $resolved = [...$resolved, ...$this->withContainerChildren($children, $cObj)];
        }

        return $resolved;
    }
}
