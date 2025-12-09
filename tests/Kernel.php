<?php

declare(strict_types=1);

namespace Dbp\Relay\MakerBundle\Tests;

use Dbp\Relay\MakerBundle\DbpRelayMakerBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new MonologBundle();
        yield new DbpRelayMakerBundle();
    }

    protected function configureContainer(ContainerConfigurator $container, LoaderInterface $loader)
    {
        $container->extension('framework', [
            'test' => true,
            'secret' => 'something',
        ]);

        $container->extension('dbp_relay_maker', []);
    }
}
