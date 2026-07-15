<?php

declare(strict_types=1);

namespace Acme\SchemaIdOverride\UserFunc;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Frontend\Typolink\LinkResult;
use TYPO3\CMS\Frontend\Typolink\LinkResultInterface;

/**
 * Fixture userFunc mirroring abeco's ManipulateTypoLinkResult: it swaps the CMS base URL of a
 * resolved typolink for a decoupled storefront base, keeping the page path. Registered on
 * UnityHead.10.schema.10.id.typolink.userFunc it proves the WebPage @id is overridable from an
 * external extension without unity-schema knowing the storefront.
 */
final class ExternalUrlEnricher
{
    private const STOREFRONT_BASE_URL = 'https://shop.example';

    /**
     * @param array<string, mixed> $conf
     */
    public function toStorefrontUrl(
        LinkResultInterface $content,
        array $conf,
        ServerRequestInterface $request,
    ): LinkResultInterface {
        $path = parse_url($content->getUrl(), PHP_URL_PATH);
        if (!is_string($path)) {
            return $content;
        }

        return new LinkResult($content->getType(), self::STOREFRONT_BASE_URL . $path);
    }
}
