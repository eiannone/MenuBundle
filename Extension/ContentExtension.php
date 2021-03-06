<?php

namespace Symfony\Cmf\Bundle\MenuBundle\Extension;

use Knp\Menu\Factory\ExtensionInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * An extension of the MenuFactory to generate URLs from the Content object.
 *
 * It has to be registered with a priority higher than the CoreExtension and
 * RoutingExtension provided by the KnpMenuBundle.
 *
 * @author Wouter J <wouter@wouterj.nl>
 */
class ContentExtension implements ExtensionInterface
{
    /**
     * @var UrlGeneratorInterface
     */
    private $contentRouter;

    /**
     * @param UrlGeneratorInterface $contentRouter A router to generate URLs based on the content object
     */
    public function __construct(UrlGeneratorInterface $contentRouter)
    {
        $this->contentRouter = $contentRouter;
    }

    /**
     * Builds the full option array used to configure the item.
     *
     * @param array $options The options processed by the previous extensions
     *
     * @return array
     */
    public function buildOptions(array $options)
    {
        $options = array_merge(array(
            'content' => null,
            'linkType' => null,
            'extras' => array(),
        ), $options);

        if (null === $options['linkType']) {
            $options['linkType'] = $this->determineLinkType($options);
        }

        $this->validateLinkType($options['linkType']);

        if ('content' === $options['linkType']) {
            if (!isset($options['content'])) {
                throw new \InvalidArgumentException(sprintf('Link type content configured, but could not find content option in the provided options: %s', implode(', ', array_keys($options))));
            }

            $options['uri'] = $this->contentRouter->generate(
                $options['content'],
                isset($options['routeParameters']) ? $options['routeParameters'] : array(),
                isset($options['routeAbsolute']) ? $options['routeAbsolute'] : false
            );
        }

        if (isset($options['route']) && 'route' !== $options['linkType']) {
            unset($options['route']);
        }

        $options['extras']['content'] = $options['content'];

        return $options;
    }

    /**
     * Configures the item with the passed options
     *
     * @param ItemInterface $item
     * @param array         $options
     */
    public function buildItem(ItemInterface $item, array $options)
    {
    }

    /**
     * If linkType not specified, we can determine it from
     * existing options
     */
    protected function determineLinkType($options)
    {
        if (!empty($options['uri'])) {
            return 'uri';
        }

        if (!empty($options['route'])) {
            return 'route';
        }

        if (!empty($options['content'])) {
            return 'content';
        }

        return 'uri';
    }

    /**
     * Ensure that we have a valid link type.
     *
     * @param string $linkType
     *
     * @throws \InvalidArgumentException if $linkType is not one of the known
     *                                   link types
     */
    protected function validateLinkType($linkType)
    {
        $linkTypes = array('uri', 'route', 'content');
        if (!in_array($linkType, $linkTypes)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid link type "%s", expected: "%s"',
                $linkType,
                implode(',', $linkTypes)
            ));
        }
    }
}
