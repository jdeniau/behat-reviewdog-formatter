<?php

declare(strict_types=1);

namespace JDeniau\BehatReviewdogFormatter;

use Behat\Testwork\Output\ServiceContainer\OutputExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ReviewdogFormatterExtension implements Extension
{
    public function getConfigKey()
    {
        return 'reviewdog_formatter';
    }

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(ExtensionManager $extensionManager): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config): void
    {
        $outputPrinterDefinition = $container->register(
            ReviewdogOutputPrinter::class
        );
        $outputPrinterDefinition->addArgument('%paths.base%');

        $formatterDefinition = $container->register(ReviewdogFormatter::class);
        $formatterDefinition->addArgument('%paths.base%');
        $formatterDefinition->addArgument($outputPrinterDefinition);
        $formatterDefinition->addTag(OutputExtension::FORMATTER_TAG, [
            'priority' => 100,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
    }
}
